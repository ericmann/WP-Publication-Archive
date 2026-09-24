<?php
/**
 * Implements SPEC.md §6.4: one static method per exposed hook (legacy names
 * included). This is the only file that calls apply_filters() or
 * do_action() (P4). Add a Keys constant, a method here, a line in
 * docs/HOOKS.md and a test, in that order.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Hooks {

	private function __construct() {
	}

	public static function filter_enabled( bool $enabled, string $context ): bool {
		return (bool) apply_filters( Keys::FILTER_ENABLED, $enabled, $context );
	}

	public static function booted( Plugin $plugin ): void {
		do_action( Keys::ACTION_BOOTED, $plugin );
	}

	public static function content_save_pre( string $content ): string {
		return (string) apply_filters( Keys::CORE_FILTER_CONTENT_SAVE_PRE, $content );
	}

	public static function open_url( string $url ): string {
		return (string) apply_filters( Keys::FILTER_OPEN_URL, $url );
	}

	public static function download_url( string $url ): string {
		return (string) apply_filters( Keys::FILTER_DOWNLOAD_URL, $url );
	}

	public static function mask_url( bool $mask ): bool {
		return (bool) apply_filters( Keys::FILTER_MASK_URL, $mask );
	}

	public static function publication_icon( string $url, string $doctype ): string {
		return (string) apply_filters( Keys::FILTER_PUBLICATION_ICON, $url, $doctype );
	}
}
