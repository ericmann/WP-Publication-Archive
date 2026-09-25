<?php
/**
 * Implements SPEC.md §6: the plugin's REST namespace. Ships one static,
 * public, read-only route that reports lineage; feature routes are added
 * beside it.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Rest {

	private Flags $flags;

	public function __construct( Flags $flags ) {
		$this->flags = $flags;
	}

	public function flags(): Flags {
		return $this->flags;
	}

	/**
	 * Hooked to rest_api_init. Registration only; nothing is served until
	 * the route is requested.
	 */
	public function register_routes(): void {
		register_rest_route(
			Keys::REST_NAMESPACE,
			Keys::REST_ROUTE_LINEAGE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'lineage' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @return array<string, string|int>
	 */
	public function lineage(): array {
		return array(
			'lineage' => Keys::LINEAGE,
			'epoch'   => Keys::EPOCH,
			'date'    => Keys::EPOCH_DATE,
			'version' => Keys::VERSION,
		);
	}
}
