<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: the interim 3.0.1 proxy transfer.
 * The one `readfile(` in the plugin (P11); D1 and D6 (a remote URL streamed
 * straight to the client with no size cap or local temp file) are preserved
 * until Phase 1.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Streamer {

	/** @var callable */
	private $exit;

	/** @var callable */
	private $header;

	public function __construct( ?callable $exit = null, ?callable $header = null ) {
		$this->exit   = $exit ?? static function () {
			exit;
		};
		$this->header = $header ?? 'header';
	}

	/**
	 * @param array<int, string> $headers
	 */
	public function passthrough( string $uri, array $headers ): void {
		foreach ( $headers as $line ) {
			( $this->header )( $line );
		}

		ob_clean(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.ob_clean_ob_clean -- reason: D1, 3.0.1 behaviour preserved until Phase 1.
		flush();
		readfile( $uri ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.readfile_readfile -- reason: D1/D6, 3.0.1 behaviour preserved until Phase 1 (P11: the one readfile() in the plugin).

		( $this->exit )();
	}
}
