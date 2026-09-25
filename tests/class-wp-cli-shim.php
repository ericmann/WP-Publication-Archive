<?php
/**
 * Minimal WP_CLI shim for PHPUnit, where the real WP-CLI is not loaded.
 *
 * Guarded with class_exists( ..., false ) rather than the default
 * $autoload=true: this file is part of the tests/ classmap, so an
 * autoloading check would recursively autoload this very class.
 */

if ( ! class_exists( 'WP_CLI', false ) ) {
	class WP_CLI {

		/** @var list<string> */
		public static array $lines = array();

		/** @var list<string> */
		public static array $warnings = array();

		/** @var list<string> */
		public static array $successes = array();

		/** @var list<string> */
		public static array $errors = array();

		/** @var array<string, object> */
		public static array $commands = array();

		public static function line( string $message = '' ): void {
			self::$lines[] = $message;
		}

		public static function log( string $message ): void {
			self::$lines[] = $message;
		}

		public static function warning( string $message ): void {
			self::$warnings[] = $message;
		}

		public static function success( string $message ): void {
			self::$successes[] = $message;
		}

		public static function error( string $message ): void {
			self::$errors[] = $message;

			throw new \WP_CLI\ExitException( $message, 1 );
		}

		public static function halt( int $code ): void {
			throw new \WP_CLI\ExitException( 'halt', $code );
		}

		public static function add_command( string $name, object $callable ): void {
			self::$commands[ $name ] = $callable;
		}

		public static function reset(): void {
			self::$lines     = array();
			self::$warnings  = array();
			self::$successes = array();
			self::$errors    = array();
			self::$commands  = array();
		}
	}
}
