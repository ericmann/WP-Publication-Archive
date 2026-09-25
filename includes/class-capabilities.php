<?php
/**
 * Implements SPEC.md §6.1: publication capabilities, granted once to the
 * four §6.1 roles.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Capabilities {

	private Flags $flags;

	public function __construct( Flags $flags ) {
		$this->flags = $flags;
	}

	public function flags(): Flags {
		return $this->flags;
	}

	/**
	 * Grants Keys::CAP_MAP's publication capabilities to Keys::CAP_ROLES,
	 * one at a time: a role gets a publication cap iff it already has the
	 * matching post cap, so e.g. an Author gets the same subset of
	 * publication caps it has for post. Then marks it done via
	 * Flags::mark_caps_granted(). Idempotent: running it twice changes
	 * nothing, since add_cap() on a role that already has a cap is a no-op.
	 */
	public function grant(): void {
		foreach ( Keys::CAP_ROLES as $role_name ) {
			$role = get_role( $role_name );

			if ( null === $role ) {
				continue;
			}

			foreach ( Keys::CAP_MAP as $post_cap => $publication_cap ) {
				if ( $role->has_cap( $post_cap ) ) {
					$role->add_cap( $publication_cap );
				}
			}
		}

		$this->flags->mark_caps_granted();
	}

	/**
	 * Runs grant() once, the first time it is needed, via Flags::caps_granted().
	 */
	public function maybe_grant(): void {
		if ( $this->flags->caps_granted() ) {
			return;
		}

		$this->grant();
	}
}
