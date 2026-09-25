<?php
/**
 * Implements SPEC.md §6.2 Streamer::send()'s D6 test: a bare-PHP child
 * process with zero output buffers, so a PHP 8 "failed to delete buffer"
 * notice (if ob_end_clean() ran with nothing to end) lands on this
 * process' own stderr, observed in isolation by the parent test.
 *
 * Usage: php streamer-child.php <path>
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$streamer_child_file_path = $argv[1];

while ( ob_get_level() > 0 ) {
	ob_end_clean();
}

$streamer = new \WPPA\Streamer(
	dirname( $streamer_child_file_path ),
	static function () {
		// No-op: let the script end naturally.
	},
	static function ( $line ) {
		// No-op: headers are irrelevant to the D6 buffer-notice check.
		unset( $line );
	}
);

$streamer->send( $streamer_child_file_path, 'text/plain', null );
