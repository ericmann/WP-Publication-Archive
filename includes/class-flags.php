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

	public function permalink_structure(): string {
		return (string) get_option( 'permalink_structure' );
	}

	/**
	 * @return int|null
	 */
	public function schema_version() {
		$version = get_option( Keys::OPT_SCHEMA );

		return false === $version ? null : (int) $version;
	}

	public function set_schema_version( int $version ): void {
		update_option( Keys::OPT_SCHEMA, $version );
	}

	public function add_schema_version( int $version ): void {
		add_option( Keys::OPT_SCHEMA, $version, '', false );
	}

	public function show_on_front(): string {
		return (string) get_option( 'show_on_front' );
	}

	public function page_for_posts(): int {
		return (int) get_option( 'page_for_posts' );
	}

	public function caps_granted(): bool {
		return (bool) get_option( Keys::OPT_CAPS, false );
	}

	public function mark_caps_granted(): void {
		update_option( Keys::OPT_CAPS, 1, false );
	}
}
