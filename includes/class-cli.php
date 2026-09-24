<?php
/**
 * Implements SPEC.md §6: the `wp publication-archive` command. Every public
 * method on this object becomes a subcommand (P13a), so dependencies are
 * private and helpers are private. `doctor` is the smoke test every
 * environment runs.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Cli {

	private Flags $flags;

	public function __construct( Flags $flags ) {
		$this->flags = $flags;
	}

	/**
	 * Verifies the environment: PHP and WordPress versions, lineage, the
	 * publication post type and the delivery rewrite rules.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp publication-archive doctor
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Named arguments.
	 */
	public function doctor( array $args, array $assoc_args ): void {
		$rows = $this->doctor_rows();

		$format = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		\WP_CLI\Utils\format_items( $format, $rows, array( 'check', 'status', 'message' ) );

		$failed = array_filter(
			$rows,
			static function ( array $row ): bool {
				return 'fail' === $row['status'];
			}
		);

		if ( $failed ) {
			\WP_CLI::halt( 1 );
		}
	}

	/**
	 * @return list<array{check: string, status: string, message: string}>
	 */
	private function doctor_rows(): array {
		global $wp_version;

		$rows = array();

		$rows[] = $this->row(
			'lineage',
			'pass',
			Keys::LINEAGE . ', epoch ' . Keys::EPOCH_DATE
		);

		$rows[] = $this->row(
			'version',
			'pass',
			Keys::VERSION
		);

		$rows[] = $this->row(
			'php',
			version_compare( phpversion(), Keys::MIN_PHP, '>=' ) ? 'pass' : 'fail',
			phpversion() . ' (min ' . Keys::MIN_PHP . ')'
		);

		$rows[] = $this->row(
			'wp',
			version_compare( (string) $wp_version, Keys::MIN_WP, '>=' ) ? 'pass' : 'fail',
			(string) $wp_version . ' (min ' . Keys::MIN_WP . ')'
		);

		$rows[] = $this->row(
			'post_type_registered',
			post_type_exists( Keys::POST_TYPE ) ? 'pass' : 'fail',
			Keys::POST_TYPE
		);

		$rows[] = $this->row(
			'rewrite_rules_present',
			$this->rewrite_rules_present() ? 'pass' : 'fail',
			Keys::REWRITE_BASE . '/{' . implode(
				',',
				array( Keys::ENDPOINT_VIEW, Keys::ENDPOINT_DOWNLOAD, Keys::ENDPOINT_ALTVIEW, Keys::ENDPOINT_ALTDOWN )
			) . '}/'
		);

		return $rows;
	}

	private function rewrite_rules_present(): bool {
		$rules = $this->flags->rewrite_rules();

		foreach ( array( Keys::ENDPOINT_VIEW, Keys::ENDPOINT_DOWNLOAD, Keys::ENDPOINT_ALTVIEW, Keys::ENDPOINT_ALTDOWN ) as $endpoint ) {
			$prefix = Keys::REWRITE_BASE . '/' . $endpoint . '/';
			$found  = false;

			foreach ( array_keys( $rules ) as $rule ) {
				$rule = ltrim( (string) $rule, '^' );

				if ( 0 === strpos( $rule, $prefix ) ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return array{check: string, status: string, message: string}
	 */
	private function row( string $check, string $status, string $message ): array {
		return array(
			'check'   => $check,
			'status'  => $status,
			'message' => $message,
		);
	}
}
