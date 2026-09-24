<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 and §6.1: the 3.0.1 schema upgrade.
 * D9 (P2-06): Plugin hooks maybe_upgrade() to init at
 * Keys::UPGRADE_PRIORITY (after Post_Type/Rewrites register the current
 * request's rules); it runs once, sets the schema option with autoload
 * off, and does no option or rewrite work on any later request.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Upgrade {

	private Flags $flags;

	public function __construct( Flags $flags ) {
		$this->flags = $flags;
	}

	public function flags(): Flags {
		return $this->flags;
	}

	/**
	 * Hooked to init at Keys::UPGRADE_PRIORITY, after Post_Type and Rewrites
	 * have registered the rules for this request (D9). Writes no option and
	 * flushes nothing when the schema is already current.
	 */
	public function maybe_upgrade(): void {
		$from = $this->flags->schema_version();

		if ( null === $from || $from < Keys::SCHEMA_VERSION ) {
			$this->run( $from );

			$this->flags->set_schema_version( Keys::SCHEMA_VERSION );

			flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: 3.0.1 behaviour (D9), runs once per schema upgrade, soft flush per §6.1.
		}
	}

	/**
	 * 3.0.1 WP_Publication_Archive::upgrade().
	 *
	 * @param int|null $from
	 */
	public function run( $from ): void {
		switch ( (int) $from ) {
			case 2:
				$publications = get_posts(
					array(
						'numberposts' => -1, // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_numberposts -- reason: 3.0.1 behaviour (D9), a one-off migration pass over every publication.
						'post_type'   => Keys::POST_TYPE,
					)
				);

				foreach ( $publications as $publication ) {
					$content = get_post_meta( $publication->ID, Keys::META_LEGACY_DESC, true );

					if ( ! empty( $content ) && empty( $publication->post_content ) ) {
						wp_update_post(
							array(
								'ID'           => (int) $publication->ID,
								'post_content' => Hooks::content_save_pre( $content ),
							)
						);
					}
				}

				break;
		}
	}
}
