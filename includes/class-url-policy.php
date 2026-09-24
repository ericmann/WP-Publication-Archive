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
	 * @param callable(string):bool $is_safe_external wraps wp_http_validate_url().
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
	 * @return string|\WP_Error
	 */
	public function validate( string $url ) {
		throw new NotImplementedException( __METHOD__ );
	}
}
