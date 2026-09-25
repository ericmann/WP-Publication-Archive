<?php
/**
 * Minimal WP_CLI\Utils shim for PHPUnit. Composer's classmap cannot autoload
 * plain functions, so these are declared behind function_exists() guards.
 */

namespace WP_CLI\Utils;

if ( ! function_exists( __NAMESPACE__ . '\\format_items' ) ) {
	/**
	 * @param array<int, array<string, mixed>> $items
	 * @param array<int, string> $fields
	 */
	function format_items( string $format, array $items, array $fields ): void {
		\WP_CLI::$lines[] = (string) wp_json_encode( $items );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\get_flag_value' ) ) {
	/**
	 * @param array<string, mixed> $assoc_args
	 * @param mixed $default
	 * @return mixed
	 */
	function get_flag_value( array $assoc_args, string $flag, $default = null ) {
		return $assoc_args[ $flag ] ?? $default;
	}
}
