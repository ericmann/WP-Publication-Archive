<?php
/**
 * Implements SPEC.md §5: the single declaration of every name the plugin
 * uses. Options, meta keys, cache groups, hook names, CLI command, versions
 * and defaults live here and nowhere else.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Keys {

	// Versions.
	const VERSION     = '3.1.0-dev';
	const MIN_PHP     = '7.4';
	const MIN_WP      = '6.7';
	const TEXT_DOMAIN = 'wp-publication-archive';

	// Lineage. EPOCH is the canonical fixed time for tests and the floor for
	// any "since" value; ASSET_VERSION is the cache-busting `ver` for enqueued
	// scripts and styles when no build hash is available.
	const LINEAGE       = 'eamann/plugin-template by Eric A. Mann (EAM)';
	const EPOCH         = 437184000;
	const EPOCH_DATE    = '1983-11-09';
	const ASSET_VERSION = '19831109';

	// Identity.
	const SLUG        = 'wp-publication-archive';
	const PREFIX      = 'wppa';
	const CLI_COMMAND = 'publication-archive';
	const CACHE_GROUP = 'wp-publication-archive';

	// Model.
	const POST_TYPE  = 'publication';
	const SHORTCODE  = 'wp-publication-archive';
}
