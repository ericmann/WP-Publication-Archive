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
	const VERSION     = '3.1.0';
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
	const TAX_AUTHOR = 'publication-author';
	const MENU_ICON  = 'dashicons-media-document';

	// Options.
	const OPT_SCHEMA      = 'wp-publication-archive-core';
	const OPT_ENABLED     = 'wp-publication-archive-enabled';
	const SCHEMA_VERSION  = 3;

	// Meta keys. Frozen: only these three are written (P16).
	const META_DOC         = 'wpa_upload_doc';
	const META_IMAGE       = 'wpa-upload_image';
	const META_ALTERNATES  = 'wpa-upload_alternates';
	const META_LEGACY_DESC = 'wpa_doc_desc';

	// Form fields.
	const FIELD_DOC          = 'wpa_upload_doc';
	const FIELD_IMAGE        = 'wpa-upload_image';
	const FIELD_ALTERNATES   = 'wpa-alternates';
	const FIELD_NONCE        = 'wpa_nonce';
	const NONCE_ACTION       = 'wp-publication-archive-save-meta';
	const FIELD_CAT_DROPDOWN = 'wp_pubarch_cat';

	// Meta box ids.
	const META_BOX_DOC        = 'publication_uri';
	const META_BOX_ALTERNATES = 'publication_alternates';
	const META_BOX_THUMB      = 'publication_thumb';

	// Query vars and rewrite tags.
	const QV_OPEN     = 'wppa_open';
	const QV_DOWNLOAD = 'wppa_download';
	const QV_ALT      = 'wppa_alt';
	const QV_PAGED    = 'wpa-paged';
	const TAG_OPEN     = '%wppa_open%';
	const TAG_DOWNLOAD = '%wppa_download%';
	const TAG_ALT       = '%wppa_alt%';

	// Endpoints.
	const ENDPOINT_VIEW     = 'view';
	const ENDPOINT_DOWNLOAD = 'download';
	const ENDPOINT_ALTVIEW  = 'altview';
	const ENDPOINT_ALTDOWN  = 'altdown';
	const QUERY_ALT_KEY     = 'alt';
	const REWRITE_BASE      = 'publication';
	const REWRITE_CATEGORY  = 'category';

	// Assets and paths.
	const STYLE_HANDLE  = 'wp-publication-archive-frontend';
	const STYLE_PATH    = 'assets/css/base.css';
	const ICON_DIR      = 'assets/icons/';
	const LANGUAGES_DIR = 'languages';
	const TEMPLATE_DIR  = 'templates/classic/';

	// REST.
	const REST_NAMESPACE     = 'wp-publication-archive/v1';
	const REST_ROUTE_LINEAGE = '/eam';
	const REST_BASE          = 'publications';

	// Author taxonomy REST/rewrite (§6.1, P2-04).
	const TAX_AUTHOR_QUERY_VAR     = 'publication-author';
	const TAX_AUTHOR_REWRITE_SLUG  = 'publication/author';

	// Capabilities (§6.1, P2-02).
	const CAPABILITY_TYPE    = array( 'publication', 'publications' );
	const CAP_ROLES          = array( 'administrator', 'editor', 'author', 'contributor' );
	const ROLE_ADMINISTRATOR = 'administrator';
	const CAP_MAP            = array(
		'edit_posts'             => 'edit_publications',
		'edit_others_posts'      => 'edit_others_publications',
		'edit_private_posts'     => 'edit_private_publications',
		'edit_published_posts'   => 'edit_published_publications',
		'publish_posts'          => 'publish_publications',
		'read_private_posts'     => 'read_private_publications',
		'delete_posts'           => 'delete_publications',
		'delete_private_posts'   => 'delete_private_publications',
		'delete_published_posts' => 'delete_published_publications',
		'delete_others_posts'    => 'delete_others_publications',
	);
	const OPT_CAPS = 'wp-publication-archive-caps';

	// Admin media enqueue (§6.6/§6.7, P2-07).
	const ADMIN_SCRIPT_HANDLE = 'wp-publication-archive-admin-media';
	const ADMIN_SCRIPT_PATH   = 'assets/js/admin-media.js';
	const ADMIN_SCREENS       = array( 'post.php', 'post-new.php' );
	const ADMIN_SCRIPT_OBJECT = 'wppaAdminMedia';

	// Upgrade timing (D9, P2-06).
	const UPGRADE_PRIORITY = 20;

	// Template files.
	const TEMPLATE_LIST     = 'template.wppa_publication_list.php';
	const TEMPLATE_DROPDOWN = 'template.wppa_publication_dropdown.php';
	const TEMPLATE_WIDGET   = 'template.wppa_widget.php';
	const TEMPLATE_SINGLE   = 'single-publication.php';
	const TEMPLATE_ARCHIVE  = 'archive-publication.php';

	// Legacy class names.
	const LEGACY_CLASS_ARCHIVE           = 'WP_Publication_Archive';
	const LEGACY_CLASS_ITEM              = 'WP_Publication_Archive_Item';
	const LEGACY_CLASS_UTILITIES         = 'WP_Publication_Archive_Utilities';
	const LEGACY_CLASS_ARCHIVE_WIDGET    = 'WP_Publication_Archive_Widget';
	const LEGACY_CLASS_CAT_COUNT_WIDGET  = 'WP_Publication_Archive_Cat_Count_Widget';
	const LEGACY_CLASS_RELATED_WIDGET    = 'WP_Publication_Archive_Category_Widget';

	// Widget id_bases (§6.6): each is
	// preg_replace( '/(wp_)?widget_/', '', strtolower( <LEGACY_CLASS_*> ) ).
	const WIDGET_ARCHIVE_ID_BASE    = 'wp_publication_archive_widget';
	const WIDGET_CAT_COUNT_ID_BASE  = 'wp_publication_archive_cat_count_widget';
	const WIDGET_RELATED_ID_BASE    = 'wp_publication_archive_category_widget';

	// Defaults.
	const DEFAULT_LIST_LIMIT             = 10;
	const DEFAULT_WIDGET_SUMMARY_LENGTH  = 20;
	const DEFAULT_ARCHIVE_WIDGET_NUMBER  = 5;
	const DEFAULT_RELATED_COUNT          = 5;

	// Exposed filters.
	const FILTER_OPEN_URL              = 'wppa_open_url';
	const FILTER_DOWNLOAD_URL          = 'wppa_download_url';
	const FILTER_MASK_URL              = 'wppa_mask_url';
	const FILTER_PUBLICATION_ICON      = 'wppa_publication_icon';
	const FILTER_LIST_LIMIT            = 'wppa_list_limit';
	const FILTER_PUBS_PER_PAGE         = 'wpa-pubs_per_page';
	const FILTER_LIST_TEMPLATE         = 'wppa_list_template';
	const FILTER_DROPDOWN_TEMPLATE     = 'wppa_dropdown_template';
	const FILTER_WIDGET_TEMPLATE       = 'wppa_widget_template';
	const FILTER_SINGLE_TEMPLATE       = 'wppa_single_template';
	const FILTER_ARCHIVE_TEMPLATE      = 'wppa_archive_template';
	const FILTER_LIST_CONTAINER        = 'wppa_publication_list_container';
	const FILTER_TITLE                 = 'wpa-title';
	const FILTER_UPLOAD_IMAGE          = 'wpa-upload_image';
	const FILTER_AUTHORS               = 'wpa-authors';
	const FILTER_SUMMARY               = 'wpa-summary';
	const FILTER_KEYWORDS              = 'wpa-keywords';
	const FILTER_CATEGORIES            = 'wpa-categories';
	const FILTER_SUMMARY_LENGTH        = 'wpa-summary-length';
	const FILTER_WIDGET_SUMMARY_LENGTH = 'wpa-widget-summary-length';
	const FILTER_OPEN_IN_BLANK         = 'wp_pubarch_open_in_blank';
	const FILTER_ENABLED               = 'wp-publication-archive-enabled';

	// Exposed action.
	const ACTION_BOOTED = 'wppa_booted';

	// Core filters the plugin applies.
	const CORE_FILTER_WIDGET_TITLE                       = 'widget_title';
	const CORE_FILTER_LIST_CATS                          = 'list_cats';
	const CORE_FILTER_DROPDOWN_CATS                       = 'wp_dropdown_cats';
	const CORE_FILTER_WIDGET_CATEGORIES_ARGS              = 'widget_categories_args';
	const CORE_FILTER_WIDGET_CATEGORIES_DROPDOWN_ARGS     = 'widget_categories_dropdown_args';
	const CORE_FILTER_LIST_CATEGORIES                     = 'wp_list_categories';
	const CORE_FILTER_CONTENT_SAVE_PRE                    = 'content_save_pre';

	// Consumed hooks.
	const HOOK_INIT                       = 'init';
	const HOOK_CLI_INIT                   = 'cli_init';
	const HOOK_WP_ENQUEUE_SCRIPTS         = 'wp_enqueue_scripts';
	const HOOK_ADMIN_ENQUEUE_SCRIPTS      = 'admin_enqueue_scripts';
	const HOOK_REST_API_INIT              = 'rest_api_init';
	const HOOK_SAVE_POST                  = 'save_post';
	const HOOK_TEMPLATE_REDIRECT          = 'template_redirect';
	const HOOK_QUERY_VARS                 = 'query_vars';
	const HOOK_POSTS_WHERE                = 'posts_where_request';
	const HOOK_POSTS_JOIN                 = 'posts_join_request';
	const HOOK_POSTS_DISTINCT             = 'posts_distinct_request';
	const HOOK_EXCERPT_LENGTH             = 'excerpt_length';
	const HOOK_WIDGETS_INIT               = 'widgets_init';
	const HOOK_TEMPLATE_INCLUDE           = 'template_include';
	const HOOK_ADMIN_NOTICES              = 'admin_notices';
	const HOOK_POST_TYPE_LINK             = 'post_type_link';
	const HOOK_TERM_LINK                  = 'term_link';
	const HOOK_TERMS_CLAUSES              = 'terms_clauses';
	const HOOK_ADD_META_BOXES_PUBLICATION = 'add_meta_boxes_publication';

	// The DAM (§6.9, §7.4).
	const HOOK_DAM_INDEXED_IDS = 'vip_dam_indexed_attachment_ids';
	const DAM_PLUGIN_SLUG      = 'vip-digital-asset-manager';
	const DAM_PLUGIN_FILE      = 'vip-digital-asset-manager/index.php';

	// URL policy and delivery (§5.3, §6.2).
	const DEFAULT_MASK_URL        = false;
	const DEFAULT_PROXY_TIMEOUT   = 30; // ⚠️ ASSUMPTION (§5.3).
	const DEFAULT_PROXY_MAX_BYTES = 52428800; // ⚠️ ASSUMPTION (§5.3).
	const FILTER_PROXY_TIMEOUT    = 'wppa_proxy_timeout';
	const FILTER_PROXY_MAX_BYTES  = 'wppa_proxy_max_bytes';
	const ERR_INVALID_URL              = 'wppa_invalid_url';
	const HOOK_ALLOWED_REDIRECT_HOSTS  = 'allowed_redirect_hosts';
	const CONTENT_TYPE_FALLBACK        = 'application/octet-stream';
	const ACTIVE_CONTENT_TYPES         = array(
		'text/html',
		'application/xhtml+xml',
		'image/svg+xml',
		'text/xml',
		'application/xml',
		'text/javascript',
		'application/javascript',
	);
}
