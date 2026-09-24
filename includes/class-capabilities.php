<?php
/**
 * Implements SPEC.md §6.1: publication capabilities, granted once to the
 * three §6.1 roles. Behaviour arrives in P2-02; both methods are stubs
 * until then.
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
	 * then marks it done via Flags::mark_caps_granted().
	 */
	public function grant(): void {
		throw new NotImplementedException( __METHOD__ );
	}

	/**
	 * Runs grant() once, the first time it is needed, via Flags::caps_granted().
	 */
	public function maybe_grant(): void {
		throw new NotImplementedException( __METHOD__ );
	}
}
