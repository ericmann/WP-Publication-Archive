<?php
/**
 * Implements SPEC.md §5.2: feature flags. This is the only class that reads
 * or writes options (P2).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Flags {

	private Clock $clock;

	public function __construct( Clock $clock ) {
		$this->clock = $clock;
	}

	public function clock(): Clock {
		return $this->clock;
	}

	public function enabled( string $context = 'default' ): bool {
		return Hooks::filter_enabled( (bool) get_option( Keys::OPT_ENABLED, true ), $context );
	}

	/**
	 * @return array<string, string>
	 */
	public function rewrite_rules(): array {
		$rules = get_option( 'rewrite_rules' );

		return is_array( $rules ) ? $rules : array();
	}
}
