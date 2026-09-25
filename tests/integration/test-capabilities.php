<?php
/**
 * Implements SPEC.md §6.1: publication capabilities, granted once to the
 * four §6.1 roles.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Capabilities;
use WPPA\Keys;
use WPPA\Plugin;

class Test_Capabilities extends \WP_UnitTestCase {

	/** @var array<string, mixed> */
	private $saved_roles;

	/** @var array<string, \WP_Role> */
	private $saved_role_objects;

	/**
	 * WP_Roles is a process-wide singleton whose add_cap()/remove_cap()
	 * only write to the database when $wp_user_roles (a global populated
	 * once, early, for performance) is empty; in this test install it is
	 * not, so every capability change here is purely in-memory and outlives
	 * the per-test DB transaction rollback entirely. Snapshot and restore
	 * the singleton's own public state instead of relying on any DB/cache
	 * mechanism to undo it.
	 */
	public function set_up() {
		parent::set_up();

		$roles                    = wp_roles();
		$this->saved_roles        = $roles->roles;
		$this->saved_role_objects = array();

		foreach ( $roles->role_objects as $name => $role_object ) {
			$this->saved_role_objects[ $name ] = clone $role_object;
		}
	}

	public function tear_down() {
		$roles = wp_roles();

		$roles->roles = $this->saved_roles;

		foreach ( $this->saved_role_objects as $name => $saved ) {
			if ( isset( $roles->role_objects[ $name ] ) ) {
				$roles->role_objects[ $name ]->capabilities = $saved->capabilities;
			}
		}

		parent::tear_down();
	}

	public function test_is_final() {
		$reflection = new \ReflectionClass( Capabilities::class );

		$this->assertTrue( $reflection->isFinal() );
	}

	public function test_grant_gives_administrator_every_publication_cap() {
		Plugin::instance()->capabilities()->grant();

		$administrator = get_role( 'administrator' );
		$this->assertNotNull( $administrator );

		foreach ( Keys::CAP_MAP as $publication_cap ) {
			$this->assertTrue( $administrator->has_cap( $publication_cap ), $publication_cap );
		}
	}

	public function test_grant_gives_editor_the_caps_matching_its_post_caps() {
		Plugin::instance()->capabilities()->grant();

		$editor = get_role( 'editor' );
		$this->assertNotNull( $editor );

		foreach ( Keys::CAP_MAP as $post_cap => $publication_cap ) {
			$this->assertSame( $editor->has_cap( $post_cap ), $editor->has_cap( $publication_cap ), $publication_cap );
		}
	}

	public function test_grant_gives_author_the_same_subset_as_for_post() {
		Plugin::instance()->capabilities()->grant();

		$author = get_role( 'author' );
		$this->assertNotNull( $author );

		$granted = false;

		foreach ( Keys::CAP_MAP as $post_cap => $publication_cap ) {
			$this->assertSame( $author->has_cap( $post_cap ), $author->has_cap( $publication_cap ), $publication_cap );

			if ( $author->has_cap( $publication_cap ) ) {
				$granted = true;
			}
		}

		// An Author has some, but not all, post caps: confirms the loop
		// above is actually exercising both branches, not vacuously true.
		$this->assertTrue( $granted );
		$this->assertFalse( $author->has_cap( Keys::CAP_MAP['edit_others_posts'] ) );
	}

	public function test_grant_gives_contributor_the_same_subset_as_for_post() {
		Plugin::instance()->capabilities()->grant();

		$contributor = get_role( 'contributor' );
		$this->assertNotNull( $contributor );

		foreach ( Keys::CAP_MAP as $post_cap => $publication_cap ) {
			$this->assertSame( $contributor->has_cap( $post_cap ), $contributor->has_cap( $publication_cap ), $publication_cap );
		}

		$this->assertTrue( $contributor->has_cap( Keys::CAP_MAP['edit_posts'] ) );
		$this->assertTrue( $contributor->has_cap( Keys::CAP_MAP['delete_posts'] ) );
		$this->assertFalse( $contributor->has_cap( Keys::CAP_MAP['publish_posts'] ) );
		$this->assertFalse( $contributor->has_cap( Keys::CAP_MAP['edit_others_posts'] ) );
		$this->assertFalse( $contributor->has_cap( Keys::CAP_MAP['edit_published_posts'] ) );
	}

	public function test_contributor_can_edit_own_draft_but_not_publish_or_edit_others() {
		Plugin::instance()->capabilities()->grant();

		$contributor_id = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$other_id       = self::factory()->user->create( array( 'role' => 'contributor' ) );

		$own_draft = self::factory()->post->create(
			array(
				'post_type'   => Keys::POST_TYPE,
				'post_status' => 'draft',
				'post_author' => $contributor_id,
			)
		);

		$others_draft = self::factory()->post->create(
			array(
				'post_type'   => Keys::POST_TYPE,
				'post_status' => 'draft',
				'post_author' => $other_id,
			)
		);

		wp_set_current_user( $contributor_id );

		$this->assertTrue( current_user_can( 'edit_post', $own_draft ) );
		$this->assertFalse( current_user_can( 'publish_post', $own_draft ) );
		$this->assertFalse( current_user_can( 'edit_post', $others_draft ) );
		$this->assertTrue( current_user_can( Keys::CAP_MAP['edit_posts'] ) );

		wp_set_current_user( 0 );
	}

	public function test_grant_records_the_option() {
		delete_option( Keys::OPT_CAPS );

		$flags = Plugin::instance()->flags();

		$this->assertFalse( $flags->caps_granted() );

		Plugin::instance()->capabilities()->grant();

		$this->assertTrue( $flags->caps_granted() );
	}

	public function test_grant_twice_changes_nothing() {
		$capabilities = Plugin::instance()->capabilities();

		$capabilities->grant();

		$administrator = get_role( 'administrator' );
		$before        = $administrator->capabilities;

		$capabilities->grant();

		$administrator = get_role( 'administrator' );
		$this->assertSame( $before, $administrator->capabilities );
	}

	public function test_maybe_grant_skips_when_already_granted() {
		$capabilities = Plugin::instance()->capabilities();

		$capabilities->grant();

		$administrator = get_role( 'administrator' );
		$administrator->remove_cap( Keys::CAP_MAP['edit_posts'] );

		$capabilities->maybe_grant();

		$administrator = get_role( 'administrator' );
		$this->assertFalse( $administrator->has_cap( Keys::CAP_MAP['edit_posts'] ) );
	}
}
