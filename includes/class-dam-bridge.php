<?php
/**
 * Implements SPEC.md §6.9: the bridge to the VIP Digital Asset Manager
 * (D17–D19). Behaviour arrives wave by wave; every method here is a stub
 * until then.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Dam_Bridge {

	private Url_Policy $policy;

	public function __construct( Url_Policy $policy ) {
		$this->policy = $policy;
	}

	public function policy(): Url_Policy {
		return $this->policy;
	}

	public function active(): bool {
		throw new NotImplementedException( __METHOD__ );
	}

	public function version(): string {
		throw new NotImplementedException( __METHOD__ );
	}

	public function attachment_id_for( string $url ): int {
		throw new NotImplementedException( __METHOD__ );
	}

	public function is_withheld( string $url ): bool {
		throw new NotImplementedException( __METHOD__ );
	}

	public function display_url( string $url ): string {
		throw new NotImplementedException( __METHOD__ );
	}

	/**
	 * @param int[] $ids
	 *
	 * @return int[]
	 */
	public function indexed_attachment_ids( array $ids, \WP_Post $post ): array {
		throw new NotImplementedException( __METHOD__ );
	}
}
