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

	private Dam_Bridge $dam;

	public function __construct( Flags $flags, Dam_Bridge $dam ) {
		$this->flags = $flags;
		$this->dam   = $dam;
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

		$rows[] = $this->rest_enabled_row();

		$rows[] = $this->row(
			'rewrite_rules_present',
			$this->rewrite_rules_present() ? 'pass' : 'fail',
			Keys::REWRITE_BASE . '/{' . implode(
				',',
				array( Keys::ENDPOINT_VIEW, Keys::ENDPOINT_DOWNLOAD, Keys::ENDPOINT_ALTVIEW, Keys::ENDPOINT_ALTDOWN )
			) . '}/'
		);

		$rows[] = $this->caps_granted_row();

		$rows[] = $this->dam_row();

		return $rows;
	}

	/**
	 * @return array{check: string, status: string, message: string}
	 */
	private function rest_enabled_row(): array {
		$post_type = get_post_type_object( Keys::POST_TYPE );
		$taxonomy  = get_taxonomy( Keys::TAX_AUTHOR );

		$enabled = null !== $post_type && $post_type->show_in_rest
			&& false !== $taxonomy && $taxonomy->show_in_rest;

		return $this->row(
			'rest_enabled',
			$enabled ? 'pass' : 'fail',
			Keys::REST_NAMESPACE . '/' . Keys::REST_BASE
		);
	}

	/**
	 * @return array{check: string, status: string, message: string}
	 */
	private function caps_granted_row(): array {
		$administrator = get_role( 'administrator' );
		$admin_has_cap = null !== $administrator && $administrator->has_cap( 'edit_publications' );
		$caps_granted  = $this->flags->caps_granted();

		return $this->row(
			'caps_granted',
			( $caps_granted && $admin_has_cap ) ? 'pass' : 'fail',
			$caps_granted ? 'granted' : 'not granted'
		);
	}

	/**
	 * @return array{check: string, status: string, message: string}
	 */
	private function dam_row(): array {
		if ( ! $this->dam->active() ) {
			return $this->row( 'dam', 'pass', 'absent' );
		}

		$attached = false !== has_filter( Keys::HOOK_DAM_INDEXED_IDS, array( $this->dam, 'indexed_attachment_ids' ) );

		return $this->row(
			'dam',
			$attached ? 'pass' : 'fail',
			$this->dam->version() . ', usage filter ' . ( $attached ? 'attached' : 'not attached' )
		);
	}

	private function rewrite_rules_present(): bool {
		$rules = $this->flags->rewrite_rules();

		$prefixes = array(
			Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_VIEW . '/',
			Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_DOWNLOAD . '/',
			Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_ALTVIEW . '/',
			Keys::REWRITE_BASE . '/' . Keys::ENDPOINT_ALTDOWN . '/',
			Keys::TAX_AUTHOR_REWRITE_SLUG . '/',
		);

		foreach ( $prefixes as $prefix ) {
			$found = false;

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
