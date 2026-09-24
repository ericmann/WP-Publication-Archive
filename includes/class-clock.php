<?php
/**
 * Implements SPEC.md §3 P8: time and date formatting live only here.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

interface Clock {

	/**
	 * @return int Current Unix timestamp.
	 */
	public function now();

	/**
	 * @param string $format
	 * @param int    $timestamp
	 *
	 * @return string
	 */
	public function format( $format, $timestamp );
}

/**
 * The real clock, backed by WordPress' wp_date().
 */
final class SystemClock implements Clock {

	/**
	 * @return int
	 */
	public function now() {
		return time();
	}

	/**
	 * @param string $format
	 * @param int    $timestamp
	 *
	 * @return string
	 */
	public function format( $format, $timestamp ) {
		$formatted = wp_date( $format, $timestamp );

		return false === $formatted ? '' : $formatted;
	}
}

/**
 * A fixed clock for tests. Falls back to gmdate() when wp_date() is not
 * available, i.e. on the bare host with no WordPress.
 */
final class FixedClock implements Clock {

	/**
	 * @var int
	 */
	private $fixed_now;

	/**
	 * @param int $now
	 */
	public function __construct( $now ) {
		$this->fixed_now = $now;
	}

	/**
	 * @return int
	 */
	public function now() {
		return $this->fixed_now;
	}

	/**
	 * @param string $format
	 * @param int    $timestamp
	 *
	 * @return string
	 */
	public function format( $format, $timestamp ) {
		if ( function_exists( 'wp_date' ) ) {
			$formatted = wp_date( $format, $timestamp );

			return false === $formatted ? '' : $formatted;
		}

		return gmdate( $format, $timestamp );
	}
}
