<?php
/**
 * Implements SPEC.md §5: Keys is the single declaration of every name.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests;

use WPPA\Keys;

class Test_Keys extends \PHPUnit\Framework\TestCase {

	public function test_identity() {
		$this->assertSame( 'wp-publication-archive', Keys::SLUG );
		$this->assertSame( 'wp-publication-archive', Keys::TEXT_DOMAIN );
		$this->assertSame( 'wppa', Keys::PREFIX );
		$this->assertSame( 'publication-archive', Keys::CLI_COMMAND );
		$this->assertSame( 'wp-publication-archive', Keys::CACHE_GROUP );
		$this->assertSame( 'publication', Keys::POST_TYPE );
		$this->assertSame( 'wp-publication-archive', Keys::SHORTCODE );
	}

	public function test_versions() {
		$this->assertSame( '7.4', Keys::MIN_PHP );
		$this->assertSame( '6.7', Keys::MIN_WP );
		$this->assertSame( '3.1.0-dev', Keys::VERSION );
	}

	public function test_lineage_epoch_is_the_ninth_of_november_1983() {
		$this->assertSame( 437184000, Keys::EPOCH );
		$this->assertSame( '1983-11-09', Keys::EPOCH_DATE );
		$this->assertSame( '19831109', Keys::ASSET_VERSION );
		$this->assertSame( 'eamann/plugin-template by Eric A. Mann (EAM)', Keys::LINEAGE );
	}

	public function test_plugin_header_matches_keys() {
		$path = dirname( __DIR__, 2 ) . '/wp-publication-archive.php';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertMatchesRegularExpression( '/^ \* Version: 3\.1\.0-dev$/m', $contents );
		$this->assertMatchesRegularExpression( '/^ \* Requires PHP: 7\.4$/m', $contents );
		$this->assertMatchesRegularExpression( '/^ \* Requires at least: 6\.7$/m', $contents );
		$this->assertMatchesRegularExpression( '/^ \* Text Domain: wp-publication-archive$/m', $contents );
		$this->assertMatchesRegularExpression( '/^ \* Domain Path: \/languages$/m', $contents );
	}

	public function test_wp_env_conf_uses_the_slug() {
		$path = dirname( __DIR__, 2 ) . '/bin/wp-env.conf';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringContainsString( 'PLUGIN_SLUG="wp-publication-archive"', $contents );
	}

	public function test_keys_is_final_and_has_no_methods() {
		$reflection = new \ReflectionClass( Keys::class );

		$this->assertTrue( $reflection->isFinal() );
		$this->assertSame( array(), $reflection->getMethods() );
	}

	public function test_model_and_options() {
		$this->assertSame( 'publication-author', Keys::TAX_AUTHOR );
		$this->assertSame( 'dashicons-media-document', Keys::MENU_ICON );
		$this->assertSame( 'wp-publication-archive-core', Keys::OPT_SCHEMA );
		$this->assertSame( 'wp-publication-archive-enabled', Keys::OPT_ENABLED );
		$this->assertSame( 3, Keys::SCHEMA_VERSION );
	}

	public function test_meta_keys_are_the_frozen_301_names() {
		$this->assertSame( 'wpa_upload_doc', Keys::META_DOC );
		$this->assertSame( 'wpa-upload_image', Keys::META_IMAGE );
		$this->assertSame( 'wpa-upload_alternates', Keys::META_ALTERNATES );
		$this->assertSame( 'wpa_doc_desc', Keys::META_LEGACY_DESC );
	}

	public function test_form_fields_nonce_and_meta_boxes() {
		$this->assertSame( 'wpa_upload_doc', Keys::FIELD_DOC );
		$this->assertSame( 'wpa-upload_image', Keys::FIELD_IMAGE );
		$this->assertSame( 'wpa-alternates', Keys::FIELD_ALTERNATES );
		$this->assertSame( 'wpa_nonce', Keys::FIELD_NONCE );
		$this->assertSame( 'wp-publication-archive-save-meta', Keys::NONCE_ACTION );
		$this->assertSame( 'wp_pubarch_cat', Keys::FIELD_CAT_DROPDOWN );
		$this->assertSame( 'publication_uri', Keys::META_BOX_DOC );
		$this->assertSame( 'publication_alternates', Keys::META_BOX_ALTERNATES );
		$this->assertSame( 'publication_thumb', Keys::META_BOX_THUMB );
	}

	public function test_query_vars_rewrite_tags_and_endpoints() {
		$this->assertSame( 'wppa_open', Keys::QV_OPEN );
		$this->assertSame( 'wppa_download', Keys::QV_DOWNLOAD );
		$this->assertSame( 'wppa_alt', Keys::QV_ALT );
		$this->assertSame( 'wpa-paged', Keys::QV_PAGED );
		$this->assertSame( '%wppa_open%', Keys::TAG_OPEN );
		$this->assertSame( '%wppa_download%', Keys::TAG_DOWNLOAD );
		$this->assertSame( '%wppa_alt%', Keys::TAG_ALT );
		$this->assertSame( 'view', Keys::ENDPOINT_VIEW );
		$this->assertSame( 'download', Keys::ENDPOINT_DOWNLOAD );
		$this->assertSame( 'altview', Keys::ENDPOINT_ALTVIEW );
		$this->assertSame( 'altdown', Keys::ENDPOINT_ALTDOWN );
		$this->assertSame( 'alt', Keys::QUERY_ALT_KEY );
		$this->assertSame( 'publication', Keys::REWRITE_BASE );
		$this->assertSame( 'category', Keys::REWRITE_CATEGORY );
	}

	public function test_handles_paths_and_rest() {
		$this->assertSame( 'wp-publication-archive-frontend', Keys::STYLE_HANDLE );
		$this->assertSame( 'assets/css/base.css', Keys::STYLE_PATH );
		$this->assertSame( 'assets/icons/', Keys::ICON_DIR );
		$this->assertSame( 'languages', Keys::LANGUAGES_DIR );
		$this->assertSame( 'templates/classic/', Keys::TEMPLATE_DIR );
		$this->assertSame( 'wp-publication-archive/v1', Keys::REST_NAMESPACE );
		$this->assertSame( '/eam', Keys::REST_ROUTE_LINEAGE );
	}

	public function test_template_file_names() {
		$this->assertSame( 'template.wppa_publication_list.php', Keys::TEMPLATE_LIST );
		$this->assertSame( 'template.wppa_publication_dropdown.php', Keys::TEMPLATE_DROPDOWN );
		$this->assertSame( 'template.wppa_widget.php', Keys::TEMPLATE_WIDGET );
		$this->assertSame( 'single-publication.php', Keys::TEMPLATE_SINGLE );
		$this->assertSame( 'archive-publication.php', Keys::TEMPLATE_ARCHIVE );
	}

	public function test_legacy_class_names() {
		$this->assertSame( 'WP_Publication_Archive', Keys::LEGACY_CLASS_ARCHIVE );
		$this->assertSame( 'WP_Publication_Archive_Item', Keys::LEGACY_CLASS_ITEM );
		$this->assertSame( 'WP_Publication_Archive_Utilities', Keys::LEGACY_CLASS_UTILITIES );
		$this->assertSame( 'WP_Publication_Archive_Widget', Keys::LEGACY_CLASS_ARCHIVE_WIDGET );
		$this->assertSame( 'WP_Publication_Archive_Cat_Count_Widget', Keys::LEGACY_CLASS_CAT_COUNT_WIDGET );
		$this->assertSame( 'WP_Publication_Archive_Category_Widget', Keys::LEGACY_CLASS_RELATED_WIDGET );
	}

	public function test_widget_id_bases_are_the_301_derived_values() {
		$legacy = array(
			Keys::WIDGET_ARCHIVE_ID_BASE   => Keys::LEGACY_CLASS_ARCHIVE_WIDGET,
			Keys::WIDGET_CAT_COUNT_ID_BASE => Keys::LEGACY_CLASS_CAT_COUNT_WIDGET,
			Keys::WIDGET_RELATED_ID_BASE   => Keys::LEGACY_CLASS_RELATED_WIDGET,
		);

		foreach ( $legacy as $id_base => $class_name ) {
			$this->assertSame( $id_base, preg_replace( '/(wp_)?widget_/', '', strtolower( $class_name ) ) );
		}
	}

	public function test_exposed_filters_and_actions() {
		$this->assertSame( 'wppa_open_url', Keys::FILTER_OPEN_URL );
		$this->assertSame( 'wppa_download_url', Keys::FILTER_DOWNLOAD_URL );
		$this->assertSame( 'wppa_mask_url', Keys::FILTER_MASK_URL );
		$this->assertSame( 'wppa_publication_icon', Keys::FILTER_PUBLICATION_ICON );
		$this->assertSame( 'wppa_list_limit', Keys::FILTER_LIST_LIMIT );
		$this->assertSame( 'wpa-pubs_per_page', Keys::FILTER_PUBS_PER_PAGE );
		$this->assertSame( 'wppa_list_template', Keys::FILTER_LIST_TEMPLATE );
		$this->assertSame( 'wppa_dropdown_template', Keys::FILTER_DROPDOWN_TEMPLATE );
		$this->assertSame( 'wppa_widget_template', Keys::FILTER_WIDGET_TEMPLATE );
		$this->assertSame( 'wppa_single_template', Keys::FILTER_SINGLE_TEMPLATE );
		$this->assertSame( 'wppa_archive_template', Keys::FILTER_ARCHIVE_TEMPLATE );
		$this->assertSame( 'wppa_publication_list_container', Keys::FILTER_LIST_CONTAINER );
		$this->assertSame( 'wpa-title', Keys::FILTER_TITLE );
		$this->assertSame( 'wpa-upload_image', Keys::FILTER_UPLOAD_IMAGE );
		$this->assertSame( 'wpa-authors', Keys::FILTER_AUTHORS );
		$this->assertSame( 'wpa-summary', Keys::FILTER_SUMMARY );
		$this->assertSame( 'wpa-keywords', Keys::FILTER_KEYWORDS );
		$this->assertSame( 'wpa-categories', Keys::FILTER_CATEGORIES );
		$this->assertSame( 'wpa-summary-length', Keys::FILTER_SUMMARY_LENGTH );
		$this->assertSame( 'wpa-widget-summary-length', Keys::FILTER_WIDGET_SUMMARY_LENGTH );
		$this->assertSame( 'wp_pubarch_open_in_blank', Keys::FILTER_OPEN_IN_BLANK );
		$this->assertSame( 'wp-publication-archive-enabled', Keys::FILTER_ENABLED );
		$this->assertSame( 'wppa_booted', Keys::ACTION_BOOTED );
	}

	public function test_core_and_consumed_hooks() {
		$this->assertSame( 'widget_title', Keys::CORE_FILTER_WIDGET_TITLE );
		$this->assertSame( 'list_cats', Keys::CORE_FILTER_LIST_CATS );
		$this->assertSame( 'wp_dropdown_cats', Keys::CORE_FILTER_DROPDOWN_CATS );
		$this->assertSame( 'widget_categories_args', Keys::CORE_FILTER_WIDGET_CATEGORIES_ARGS );
		$this->assertSame( 'widget_categories_dropdown_args', Keys::CORE_FILTER_WIDGET_CATEGORIES_DROPDOWN_ARGS );
		$this->assertSame( 'wp_list_categories', Keys::CORE_FILTER_LIST_CATEGORIES );
		$this->assertSame( 'content_save_pre', Keys::CORE_FILTER_CONTENT_SAVE_PRE );

		$this->assertSame( 'init', Keys::HOOK_INIT );
		$this->assertSame( 'cli_init', Keys::HOOK_CLI_INIT );
		$this->assertSame( 'wp_enqueue_scripts', Keys::HOOK_WP_ENQUEUE_SCRIPTS );
		$this->assertSame( 'admin_enqueue_scripts', Keys::HOOK_ADMIN_ENQUEUE_SCRIPTS );
		$this->assertSame( 'rest_api_init', Keys::HOOK_REST_API_INIT );
		$this->assertSame( 'save_post', Keys::HOOK_SAVE_POST );
		$this->assertSame( 'template_redirect', Keys::HOOK_TEMPLATE_REDIRECT );
		$this->assertSame( 'query_vars', Keys::HOOK_QUERY_VARS );
		$this->assertSame( 'posts_where_request', Keys::HOOK_POSTS_WHERE );
		$this->assertSame( 'posts_join_request', Keys::HOOK_POSTS_JOIN );
		$this->assertSame( 'posts_distinct_request', Keys::HOOK_POSTS_DISTINCT );
		$this->assertSame( 'excerpt_length', Keys::HOOK_EXCERPT_LENGTH );
		$this->assertSame( 'widgets_init', Keys::HOOK_WIDGETS_INIT );
		$this->assertSame( 'template_include', Keys::HOOK_TEMPLATE_INCLUDE );
		$this->assertSame( 'admin_notices', Keys::HOOK_ADMIN_NOTICES );
		$this->assertSame( 'post_type_link', Keys::HOOK_POST_TYPE_LINK );
		$this->assertSame( 'term_link', Keys::HOOK_TERM_LINK );
		$this->assertSame( 'terms_clauses', Keys::HOOK_TERMS_CLAUSES );
		$this->assertSame( 'add_meta_boxes_publication', Keys::HOOK_ADD_META_BOXES_PUBLICATION );
	}

	public function test_defaults() {
		$this->assertSame( 10, Keys::DEFAULT_LIST_LIMIT );
		$this->assertSame( 20, Keys::DEFAULT_WIDGET_SUMMARY_LENGTH );
		$this->assertSame( 5, Keys::DEFAULT_ARCHIVE_WIDGET_NUMBER );
		$this->assertSame( 5, Keys::DEFAULT_RELATED_COUNT );
	}

	public function test_humans_txt_credits_the_template_author() {
		$path = dirname( __DIR__, 2 ) . '/humans.txt';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringContainsString( 'Eric A. Mann', $contents );
		$this->assertStringContainsString( '1983-11-09', $contents );
	}

	public function test_base_stylesheet_declares_the_eam_epoch_custom_property() {
		$path = dirname( __DIR__, 2 ) . '/assets/css/base.css';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- reason: local source file, not remote data.
		$contents = file_get_contents( $path );

		$this->assertNotFalse( $contents );
		$this->assertStringContainsString( '--eam-epoch: 19831109', $contents );
	}
}
