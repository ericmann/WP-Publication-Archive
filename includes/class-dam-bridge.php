<?php
/**
 * Implements SPEC.md §6.9: the bridge to the VIP Digital Asset Manager
 * (D17–D19). The only file that may reference the DAM's own classes or
 * constants (dam-symbols-confined). Behaviour arrives wave by wave;
 * is_withheld() and display_url() are still stubs until P1-05.
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
		return class_exists( '\VIP\DAM\Embargo_Guard' ) && class_exists( '\VIP\DAM\Lifecycle' );
	}

	public function version(): string {
		if ( ! $this->active() ) {
			return '';
		}

		return defined( 'VIP_DAM_VERSION' ) ? (string) constant( 'VIP_DAM_VERSION' ) : '';
	}

	/**
	 * Same-site only: attachment_url_to_postid() on the normalised URL,
	 * with any `-<w>x<h>` intermediate-size suffix stripped before the
	 * extension. 0 for an external URL or one that resolves to nothing.
	 */
	public function attachment_id_for( string $url ): int {
		$normalised = $this->policy->normalise( $url );

		if ( ! $this->policy->is_same_site( $normalised ) ) {
			return 0;
		}

		$normalised = (string) preg_replace( '/-\d+x\d+(\.[a-zA-Z0-9]+)$/', '$1', $normalised );

		return (int) attachment_url_to_postid( $normalised ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- reason: SPEC §6.9 names this function explicitly; called at most once per stored publication URL, not in a loop over content.
	}

	public function is_withheld( string $url ): bool {
		throw new NotImplementedException( __METHOD__ );
	}

	public function display_url( string $url ): string {
		throw new NotImplementedException( __METHOD__ );
	}

	/**
	 * D19: adds attachment_id_for() of wpa_upload_doc, wpa-upload_image and
	 * every alternate's url, so the DAM's own detector (which only matches
	 * upload URLs literally present in post content/meta) also counts the
	 * legacy http|/https| pipe form. Registered on vip_dam_indexed_attachment_ids
	 * (10, 2) unconditionally by Plugin; a filter the DAM never fires when
	 * it is absent.
	 *
	 * @param int[] $ids
	 *
	 * @return int[]
	 */
	public function indexed_attachment_ids( array $ids, \WP_Post $post ): array {
		if ( ! $this->active() || Keys::POST_TYPE !== $post->post_type ) {
			return $ids;
		}

		$candidates = array();

		$doc = get_post_meta( $post->ID, Keys::META_DOC, true );
		if ( is_string( $doc ) && '' !== $doc ) {
			$candidates[] = $doc;
		}

		$image = get_post_meta( $post->ID, Keys::META_IMAGE, true );
		if ( is_string( $image ) && '' !== $image ) {
			$candidates[] = $image;
		}

		foreach ( get_post_meta( $post->ID, Keys::META_ALTERNATES ) as $alternate ) {
			if ( is_array( $alternate ) && isset( $alternate['url'] ) && is_string( $alternate['url'] ) && '' !== $alternate['url'] ) {
				$candidates[] = $alternate['url'];
			}
		}

		foreach ( $candidates as $candidate ) {
			$id = $this->attachment_id_for( $candidate );

			if ( 0 !== $id ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}
}
