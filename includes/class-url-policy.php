<?php
/**
 * Implements SPEC.md §6.2: URL policy for delivery (D1, D6, D17). A pure
 * leaf (P17) — every WordPress dependency arrives through the constructor,
 * so this file calls no WordPress function and imports nothing.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Url_Policy {

	private string $site_host;

	/** @var callable(string):bool */
	private $is_safe_external;

	/**
	 * @param callable(string):bool $is_safe_external wraps the caller's external-URL check.
	 */
	public function __construct( string $site_host, callable $is_safe_external ) {
		$this->site_host        = $site_host;
		$this->is_safe_external = $is_safe_external;
	}

	public function site_host(): string {
		return $this->site_host;
	}

	/**
	 * @return callable(string):bool
	 */
	public function is_safe_external(): callable {
		return $this->is_safe_external;
	}

	/**
	 * A leading `http|` becomes `http://`, a leading `https|` becomes
	 * `https://`, and the result is trimmed.
	 */
	public function normalise( string $stored ): string {
		$stored = trim( $stored );

		if ( 0 === strpos( $stored, 'http|' ) ) {
			return 'http://' . substr( $stored, strlen( 'http|' ) );
		}

		if ( 0 === strpos( $stored, 'https|' ) ) {
			return 'https://' . substr( $stored, strlen( 'https|' ) );
		}

		return $stored;
	}

	/**
	 * Case-insensitive host comparison with the constructor's $site_host.
	 */
	public function is_same_site( string $url ): bool {
		$host = parse_url( $url, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- reason: leaf may not call WordPress (SPEC §3 P17)

		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		return 0 === strcasecmp( $host, $this->site_host );
	}

	/**
	 * SPEC §6.2: 1. normalise(); 2. reject unless the scheme is http/https
	 * and the host is non-empty; 3. accept if is_same_site(), otherwise
	 * accept only if the constructor's $is_safe_external accepts it. On
	 * success, returns the normalised URL.
	 *
	 * @return string|\WP_Error
	 */
	public function validate( string $url ) {
		$normalised = $this->normalise( $url );

		$scheme = parse_url( $normalised, PHP_URL_SCHEME ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- reason: leaf may not call WordPress (SPEC §3 P17)
		$host   = parse_url( $normalised, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- reason: leaf may not call WordPress (SPEC §3 P17)

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || ! is_string( $host ) || '' === $host ) {
			return new \WP_Error( Keys::ERR_INVALID_URL );
		}

		if ( $this->is_same_site( $normalised ) ) {
			return $normalised;
		}

		if ( ( $this->is_safe_external )( $normalised ) ) {
			return $normalised;
		}

		return new \WP_Error( Keys::ERR_INVALID_URL );
	}
}
