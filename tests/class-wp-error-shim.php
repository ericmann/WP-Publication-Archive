<?php
/**
 * Minimal global WP_Error shim for unit tests that run on the bare host,
 * with no WordPress. Guarded with class_exists( 'WP_Error', false ); see
 * SPEC.md §6.2.
 */

if ( ! class_exists( 'WP_Error', false ) ) {
	class WP_Error {

		/** @var string */
		private $code;

		/** @var string */
		private $message;

		/** @var mixed */
		private $data;

		/**
		 * @param string $code
		 * @param string $message
		 * @param mixed  $data
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}
