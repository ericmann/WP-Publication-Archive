<?php
/**
 * Implements SPEC.md §6.7: registers the 3.0.1 front-end stylesheet under
 * its original handle on every request (registration emits nothing) and
 * enqueues it only when the plugin is enabled (P20, the only flag-gated
 * callback). D13 (P2-07): enqueue_admin() replaces Thickbox with
 * assets/js/admin-media.js, a wp.media frame, limited to publication edit
 * screens.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Assets {

	private Flags $flags;

	public function __construct( Flags $flags ) {
		$this->flags = $flags;
	}

	public function flags(): Flags {
		return $this->flags;
	}

	/**
	 * Hooked to wp_enqueue_scripts and admin_enqueue_scripts.
	 */
	public function register(): void {
		wp_register_style(
			Keys::STYLE_HANDLE,
			plugins_url( Keys::STYLE_PATH, WP_PUB_ARCH_DIR . Keys::SLUG . '.php' ),
			array(),
			Keys::ASSET_VERSION,
			'all'
		);
	}

	/**
	 * Hooked to wp_enqueue_scripts. The only flag-gated callback (P20): it
	 * touches no collaborator while Flags::enabled( 'assets' ) is false.
	 */
	public function enqueue_front(): void {
		if ( ! $this->flags->enabled( 'assets' ) ) {
			return;
		}

		wp_enqueue_style( Keys::STYLE_HANDLE );
	}

	/**
	 * D13: replaces 3.0.1's unconditional Thickbox enqueue. Hooked to
	 * admin_enqueue_scripts; acts only on the publication edit screens
	 * (Keys::ADMIN_SCREENS), and only when that screen's post type is this
	 * plugin's.
	 *
	 * @param string $hook_suffix
	 */
	public function enqueue_admin( $hook_suffix = '' ): void {
		if ( ! in_array( $hook_suffix, Keys::ADMIN_SCREENS, true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( null === $screen || Keys::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			Keys::ADMIN_SCRIPT_HANDLE,
			plugins_url( Keys::ADMIN_SCRIPT_PATH, WP_PUB_ARCH_DIR . Keys::SLUG . '.php' ),
			array( 'media-editor' ),
			Keys::ASSET_VERSION,
			true
		);

		wp_localize_script(
			Keys::ADMIN_SCRIPT_HANDLE,
			Keys::ADMIN_SCRIPT_OBJECT,
			array(
				'docTitle'       => __( 'Upload Publication', 'wp-publication-archive' ),
				'imageTitle'     => __( 'Upload Thumbnail', 'wp-publication-archive' ),
				'alternateTitle' => __( 'Upload Alternate', 'wp-publication-archive' ),
			)
		);
	}
}
