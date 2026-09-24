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
}
