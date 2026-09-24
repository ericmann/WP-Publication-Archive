<?php
/**
 * Minimal WP_CLI\ExitException shim for PHPUnit. Guarded with
 * class_exists( ..., false ); see class-wp-cli-shim.php.
 */

namespace WP_CLI;

if ( ! class_exists( __NAMESPACE__ . '\\ExitException', false ) ) {
	class ExitException extends \Exception {
	}
}
