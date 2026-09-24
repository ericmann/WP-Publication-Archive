<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: class_alias() for every 3.0.1 class
 * name this plugin now implements as a WPPA class, so external code that
 * references the old class names keeps working.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Legacy;

use WPPA\Keys;

final class Aliases {

	/**
	 * Called by Plugin::boot() after hooks are registered and before the
	 * transitional loader runs.
	 */
	public static function register(): void {
		$aliases = array(
			Keys::LEGACY_CLASS_ITEM => \WPPA\Publication_Item::class,
		);

		foreach ( $aliases as $alias => $target ) {
			if ( ! class_exists( $alias, false ) ) {
				class_alias( $target, $alias );
			}
		}
	}
}
