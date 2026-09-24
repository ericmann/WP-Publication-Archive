<?php
/**
 * Implements SPEC.md §3 P5: the failing-by-design body of every stub.
 * Usage: `throw new NotImplementedException( __METHOD__ );`
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class NotImplementedException extends \LogicException {
}
