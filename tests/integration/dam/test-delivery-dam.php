<?php
/**
 * Implements SPEC.md §6.2: Delivery::handle() withholds a DAM-embargoed or
 * trashed same-site file end to end (D17), with the DAM loaded.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Delivery;
use WPPA\Keys;
use WPPA\Plugin;
use WPPA\Streamer;
use WPPA\Tests\Fixtures\V3_Site;

/**
 * @group dam
 */
class Test_Delivery_Dam extends \WP_UnitTestCase {

	/** @var \WPPA\Delivery */
	private $original_delivery;

	private $exited = false;

	/** @var string|null */
	private $redirect_location;

	public function set_up() {
		parent::set_up();

		$this->original_delivery = Plugin::instance()->delivery();
		$this->exited            = false;
		$this->redirect_location = null;

		add_filter( 'wp_redirect', array( $this, 'capture_redirect' ), 10, 2 );
	}

	public function tear_down() {
		remove_filter( 'wp_redirect', array( $this, 'capture_redirect' ) );

		$replaced = Plugin::instance()->delivery();
		remove_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $replaced, 'allowed_redirect_hosts' ), 10 );

		Plugin::instance()->replace( 'delivery', $this->original_delivery );
		add_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $this->original_delivery, 'allowed_redirect_hosts' ), 10, 1 );

		parent::tear_down();
	}

	/**
	 * @param string $location
	 * @param int    $status
	 *
	 * @return string
	 */
	public function capture_redirect( $location, $status ) { // phpcs:ignore WordPressVIPMinimum.Hooks.AlwaysReturnInFilter.TerminatingInsteadOfReturn -- reason: test-only, deliberately interrupts wp_safe_redirect() to capture its arguments without a real exit.
		unset( $status );

		$this->redirect_location = $location;

		throw new \RuntimeException( 'redirected' );
	}

	private function install_delivery(): Delivery {
		$exit = function () {
			$this->exited = true;

			throw new \RuntimeException( 'exit' );
		};

		$streamer = new Streamer( get_temp_dir(), $exit, static function () {} );

		$delivery = new Delivery(
			Plugin::instance()->url_policy(),
			$streamer,
			Plugin::instance()->dam_bridge(),
			$exit,
			static function () {}
		);

		$original = Plugin::instance()->delivery();
		remove_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $original, 'allowed_redirect_hosts' ), 10 );

		Plugin::instance()->replace( 'delivery', $delivery );
		add_filter( Keys::HOOK_ALLOWED_REDIRECT_HOSTS, array( $delivery, 'allowed_redirect_hosts' ), 10, 1 );

		return $delivery;
	}

	/**
	 * @param int                  $id
	 * @param array<string, mixed> $extra_query
	 */
	private function go_to_publication( $id, $extra_query = array() ): void {
		$this->set_permalink_structure( '/%postname%/' );
		Plugin::instance()->post_type()->register();
		Plugin::instance()->rewrites()->register();
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: test-only, needs this site's own rewrite rules.

		$this->go_to( add_query_arg( $extra_query, get_permalink( $id ) ) );
	}

	private function create_publication_with_embargoed_doc(): array {
		$attachment_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$url           = (string) wp_get_attachment_url( $attachment_id );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		\VIP\DAM\Abilities\Rights_Set::execute(
			array(
				'attachment_ids' => array( $attachment_id ),
				'rights'         => array( 'embargo_until' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ),
			)
		);

		$post_id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		V3_Site::raw_meta( $post_id, Keys::META_DOC, $url );

		return array( $post_id, $url );
	}

	public function test_d17_embargoed_attachment_404_for_anonymous() {
		$this->install_delivery();

		list( $post_id, $url ) = $this->create_publication_with_embargoed_doc();
		unset( $url );

		wp_set_current_user( 0 );

		$this->go_to_publication( $post_id, array( Keys::QV_OPEN => 'yes' ) );

		$this->expectException( \WPDieException::class );
		$this->expectExceptionCode( 404 );

		Plugin::instance()->delivery()->handle();
	}

	public function test_d17_embargoed_attachment_302_for_editor() {
		$this->install_delivery();

		list( $post_id, $url ) = $this->create_publication_with_embargoed_doc();

		$this->go_to_publication( $post_id, array( Keys::QV_OPEN => 'yes' ) );

		try {
			Plugin::instance()->delivery()->handle();
			$this->fail( 'Expected a redirect.' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$this->assertSame( $url, $this->redirect_location );
		$this->assertFalse( $this->exited );
	}

	public function test_d17_trashed_attachment_404() {
		$this->install_delivery();

		$attachment_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$url           = (string) wp_get_attachment_url( $attachment_id );

		wp_update_post(
			array(
				'ID'          => $attachment_id,
				'post_status' => 'trash',
			)
		);

		$post_id = self::factory()->post->create( array( 'post_type' => Keys::POST_TYPE ) );
		V3_Site::raw_meta( $post_id, Keys::META_DOC, $url );

		wp_set_current_user( 0 );

		$this->go_to_publication( $post_id, array( Keys::QV_OPEN => 'yes' ) );

		$this->expectException( \WPDieException::class );
		$this->expectExceptionCode( 404 );

		Plugin::instance()->delivery()->handle();
	}
}
