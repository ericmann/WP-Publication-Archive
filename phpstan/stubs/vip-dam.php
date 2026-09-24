<?php
/**
 * PHPStan-only stubs for the VIP DAM's public surface (§6.9, §7.4). Signatures
 * copied from the pinned DAM_REF (9d7f1667608eb0f1cd537351546d2e4d505e8202,
 * DAM 4.0.2): inc/class-embargo-guard.php, inc/class-lifecycle.php,
 * inc/class-usage-index.php. Never included at runtime; the real classes load
 * from wp-content/plugins/vip-digital-asset-manager when the DAM is active.
 */

namespace VIP\DAM;

class Embargo_Guard {

	/**
	 * @param int $attachment_id
	 * @return bool
	 */
	public static function is_hidden( $attachment_id ) {
	}

	/**
	 * @return string
	 */
	public static function placeholder_url() {
	}
}

class Lifecycle {
}

class Usage_Index {

	/**
	 * @param int $attachment_id
	 * @return array{ used_in: array, usage_count: int, indexed: bool }
	 */
	public static function get_usage( $attachment_id ) {
	}
}
