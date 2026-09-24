<?php
/**
 * Implements SPEC.md §6.7: registers the 3.0.1 front-end stylesheet under
 * its original handle on every request (registration emits nothing) and
 * enqueues it only when the plugin is enabled (P20, the only flag-gated
 * callback).
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
}
