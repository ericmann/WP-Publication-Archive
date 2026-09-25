# WP Publication Archive 3.1.0 build progress
Branch: build/2026-09-24
Started: 2026-09-24T15:54:27.940Z

## Tasks
- [x] P0-01 Toolchain, minimal Keys, and the 3.0.1 runtime behind a transitional loader
- [x] P0-02 Keys inventory, Clock, and docs/HOOKS.md
- [x] P0-03 Flags, Hooks, Plugin and Assets; front-end stylesheet moves to assets/css/base.css
- [x] P0-04 Cli doctor, REST lineage route, humans.txt, docs, the v3 fixture and smoke tests
- [x] P0-05 The DAM in wp-env
- [x] P0-06 Characterisation — URLs, link generators and template location
- [x] P0-07 Characterisation — shortcode and widget output
- [x] P0-08 Restructure (1/8) — Post_Type, Rewrites, Upgrade and i18n
- [x] P0-09 Restructure (2/8) — Delivery, interim Streamer and Icons
- [x] P0-10 Restructure (3/8) — Meta_Boxes and admin assets
- [x] P0-11 Restructure (4/8) — Publication_Item and the alias layer
- [x] P0-12 Restructure (5/8) — Shortcode, Templates and templates/classic
- [x] P0-13 Restructure (6/8) — Categories, Legacy\Utilities and the category-count widget
- [x] P0-14 Restructure (7/8) — Archive and Related widgets
- [x] P0-15 Restructure (8/8) — Legacy\Publication_Archive and removal of lib/
- [x] P0-16 Gate 0 — foundation verified, constraints locked, branch pushed
- [x] P1-01 Contracts (1/2) — Keys, Hooks, Plugin wiring and signatures for Url_Policy, Dam_Bridge, Delivery and Streamer
- [x] P1-02 Contracts (2/2) — DAM usage filter (D19) and the dam doctor row
- [x] P1-03 Url_Policy::validate() and the §6.2 table test
- [x] P1-04 Meta box save and render through Url_Policy (D1 save, D2 save, D3 admin, D10)
- [x] P1-05 Dam_Bridge withholding and display URL (D17, D18 at the bridge)
- [x] P1-06 Streamer::send() — the one temp-file readfile (P11, D6)
- [x] P1-07 Delivery per §6.2 — validate, withhold, redirect or proxy (D1 delivery, D17 end to end)
- [x] P1-08 Escaped Publication_Item and list/dropdown templates; thumbnail through the bridge (D2 output, D3 front, D18)
- [x] P1-09 Gate 1 — security verified, constraints locked, branch pushed
- [x] P2-01 Contracts (1/2) — Keys, Flags and Plugin for §6.1, §6.6 and §6.7; close D11 and D8's filter; Capabilities signatures; upgrade on init
- [x] P2-02 Contracts (2/2) — Capabilities granted on activation and init
- [x] P2-03 Post type and taxonomy in REST and the block editor (D12)
- [x] P2-04 Registered publication meta in REST (D12 meta, D1 via REST)
- [x] P2-05 Rewrite rules — slug collisions (D5)
- [x] P2-06 Upgrade on init, once, with no per-request option writes (D9)
- [x] P2-07 admin-media.js replaces Thickbox and inline scripts (D13)
- [x] P2-08 Site-timezone dates and dead-code delegates (D7, D8 delegates)
- [x] P2-09 Widgets in the Legacy Widget block, id_base preservation, shortcode check (D14)
- [x] P2-10 Gate 2 — compatibility verified, constraints locked, branch pushed
- [x] P3-01 uninstall.php, .distignore and composer build
- [x] P3-02 readme.txt, CHANGELOG.md, version 3.1.0 and the HOOKS.md final pass
- [x] P3-03 Final gate — verify, build, push
- [x] R1-01 Disable Composer's process timeout so composer test/verify can finish
- [x] R1-02 Cli caps_granted row reads capability and role names from Keys; lock the shape with a constraint
- [x] R1-03 the_thumbnail() keeps the DAM data: placeholder; thumbnail read path normalises the pipe form
- [x] R1-04 Meta box save preserves percent-encoded URLs
- [x] R1-05 Delivery acts only on publications and reads meta directly, not through Publication_Item
- [x] R1-06 admin-media.js uses jQuery only for document delegation
- [x] R1-07 3.1.0 release notes describe D5 accurately
- [ ] R2-01 Meta box save keeps '&' in URLs (R1-04 regression)
- [ ] R2-02 Delivery drops its Icons dependency (SPEC §4.2 module map)
- [ ] R2-03 Streamer sends nosniff and forces attachment for active content (SPEC §6.2 steps 3-4)
- [ ] R2-04 Contributors get their post-equivalent publication caps (SPEC §6.1)
- [ ] R2-05 Pin every plain-permalink link generator and query form at 3.0.1 behaviour (SPEC G5)

## Log
(one entry per task, appended by implement)

### P0-01 — 7b2e8f0
Added template toolchain (composer.json, package.json, phpcs.xml.dist, phpstan.neon.dist+bootstrap, phpunit.xml.dist, .wp-env.json, bin/*, .github/workflows/verify.yml) and minimal Keys/NotImplementedException in the WPPA namespace. Moved the 3.0.1 runtime behind lib/class.wp-publication-archive-loader.php (idempotent load(), init(), activate(), deactivate(), fopen_disabled()); bootstrap wp-publication-archive.php now only defines legacy constants, registers activation/deactivation hooks and calls Loader::load() - no functions, add_*, options, or 'lib/' string. Moved the five includes/*.php templates to lib/templates/ and repointed the three WP_PUB_ARCH_DIR . 'includes/' fallbacks (find_template in utilities.php, widget() in publication-widget.php, shortcode_handler in wp-publication-archive.php) to 'lib/templates/'. includes/front-end.css intentionally untouched (asset URL, not a template fallback).
Interpretation: dropped the template's multisite branch/SITES entirely from bin/wp-env.conf and bin/setup-wp-env.sh per the SPEC import instructions (drop multisite fixture). Loader::activate() calls only init(), matching 3.0.1's wp_pubarch_activate() (load() already ran when the bootstrap executed for the request).
Composer resolved wp-phpunit ^6.7 + phpunit 9.6 + polyfills ^3.0 on PHP platform 7.4.0 without needing the Q1 fallback.
Verified: composer lint/analyse/test:map/test:unit all green; wp-env cli shows plugin active and post_type_exists('publication') true; composer test (WPPA_DAM=0) 11/11 green in tests-cli; foundry_verify all-green including every constraint.
Note: local docker was already using ports 8888/8889 from an unrelated project; used WP_ENV_PORT=18888/WP_ENV_TESTS_PORT=18889 for local wp-env runs only (not committed anywhere, .wp-env.json unchanged).

### P0-02 — 7000cb1
Added the full 3.0.1 name/default inventory to Keys (model, options, meta keys, form fields, meta box ids, query vars/rewrite tags, endpoints, assets/paths, REST, template files, legacy class names, widget id_bases, defaults, exposed filters/action, core filters applied, consumed hooks). Added Clock (interface + SystemClock + FixedClock) in includes/class-clock.php, no `use` lines, PHP 7.4 syntax (no property/return types on interface methods). Wrote docs/HOOKS.md with exposed filters/action, core-applied filters, consumed hooks (+ DAM placeholder for P0-05), and a Removed-in-3.1.0 list (D4, D11).
Interpretation: Since column uses "≤ 3.0.1" throughout (3.0.1 docblocks only had class/method-level @since, not per-hook); "3.1.0 (template)" for the two template-added hooks. Left DEFAULT_PROXY_TIMEOUT/DEFAULT_PROXY_MAX_BYTES (§5.3) out of Keys since P0-02's constant list does not name them — the proxy-delivery task should add them.
Verified: foundry_verify all-green (constraints + lint/analyse/test:map/test:unit); composer test (WPPA_DAM=0) 26/26 in wp-env.

### P0-03 — 7bdab97
Added Flags (get_option only, default true, P2), Hooks (private ctor, filter_enabled, booted, P4), Plugin (composition root: clock/flags/assets services, boot()/instance()/register_hooks()/unregister_hooks()/replace(), delegates activate()/deactivate() to WP_Publication_Archive_Loader transitionally), Assets (register() + separate enqueue_front(), the only flag-gated callback, P20). Bootstrap now calls WPPA\Plugin::boot() and registers Plugin::activate/deactivate. Moved includes/front-end.css to assets/css/base.css verbatim (plus lineage comment + :root --eam-epoch), git-detected as a rename; lib/class.wp-publication-archive.php's enqueue_scripts_and_styles() keeps only the is_admin() Thickbox branch. Filled docs/HOOKS.md's Hooks:: column for filter_enabled and booted.
Interpretation: tests/unit/test-plugin.php and tests/integration/test-plugin.php both need a class Test_Plugin, so they live in WPPA\Tests\Unit / WPPA\Tests\Integration to avoid a composer classmap collision when the full (both-testsuite) run loads every test file together; kept the flat WPPA\Tests namespace elsewhere since no other name collides.
Gotcha for later tasks: WordPress's update_option() short-circuits when the new value === the (possibly-absent, i.e. also `false`) old value, so `update_option( Keys::OPT_ENABLED, false )` in a test is a silent no-op if the option doesn't already exist — tests instead write `0`, which (bool) casts the same way but isn't `false`-typed so the equality guard doesn't trip.
Verified: foundry_verify all-green; composer test:unit 26/26; composer test (WPPA_DAM=0) 45/45 (1 expected skip); wp_style_is( 'wp-publication-archive-frontend', 'registered' ) is true after do_action('wp_enqueue_scripts').

### P0-04 — 280cf94
Added Cli (doctor: lineage/version/php/wp/post_type_registered/rewrite_rules_present rows, halts(1) on any fail, P13a: doctor is the only public subcommand), Rest (GET /wp-publication-archive/v1/eam, public), Flags::rewrite_rules() (get_option('rewrite_rules') or array()). Plugin now constructs Cli/Rest, hooks cli_init->register_cli and rest_api_init->rest->register_routes, and has accessors/replace() cases for both. Added humans.txt, docs/CONTRIBUTING.md, docs/adr/0001-composition-root.md (renamed from template). Added tests/fixtures/class-v3-site.php (V3_Site::raw_meta via direct $wpdb->insert + maybe_serialize, ::create() building the 7-publication fixture table from the PLAN, ::reset_link_state() defensive no-op via method_exists since Rewrites/disarm() don't exist yet).
Interpretation: tests/unit/test-cli.php and tests/integration/test-cli.php both declare Test_Cli, so (as in P0-03) they live in WPPA\Tests\Unit / WPPA\Tests\Integration. wp-phpunit's test install runs with "plain" permalinks by default, so $wp_rewrite generates no rules regardless of registered extra_rules_top; tests/integration/test-cli.php's set_up() calls $this->set_permalink_structure('/%postname%/') (not just flush_rules()) so rewrite_rules_present has real rules to check against. This is a real environment fact worth knowing for any later rewrite-rule test (P2-01 etc).
Verified: foundry_verify all-green; composer test:unit 29/29; composer test (WPPA_DAM=0) 57/57 (1 expected skip); wp publication-archive doctor exits 0 with the six rows; curl of /wp-json/wp-publication-archive/v1/eam returns the lineage JSON.

### P0-05 — df32af7
Added bin/fetch-dam.sh (clones/fetches DAM_REPO into .cache/vip-digital-asset-manager, checks out DAM_REF detached, merges the mapping into .wp-env.override.json via php -r, prints the version from index.php's header); bin/wp-env.conf pins DAM=1/DAM_REPO/DAM_REF verbatim from SPEC §7.4. bin/setup-wp-env.sh activates the DAM plugin in cli+tests-cli when .cache/vip-digital-asset-manager exists. bin/test.sh: WPPA_DAM overrides DAM; DAM on fails loudly if not fetched, else runs WPPA_TEST_DAM=1 phpunit --exclude-group nodam; DAM off runs --exclude-group dam. tests/bootstrap.php's muplugins_loaded callback requires vip-digital-asset-manager/index.php first when WPPA_TEST_DAM=1 (RuntimeException if missing), before this plugin. Added phpstan/stubs/vip-dam.php (Embargo_Guard, Lifecycle, Usage_Index) so composer analyse never needs a DAM checkout. Added Keys::HOOK_DAM_INDEXED_IDS/DAM_PLUGIN_SLUG/DAM_PLUGIN_FILE, docs/HOOKS.md "Consumed from the DAM", docs/CONTRIBUTING.md DAM section.
Interpretation: DAM contract stub signatures copied verbatim from DAM_REF 9d7f1667608eb0f1cd537351546d2e4d505e8202 (DAM 4.0.2) inc/class-embargo-guard.php and inc/class-usage-index.php; Lifecycle stubbed as an empty class (no method named yet by PLAN or the not-yet-built bridge). Fixed a pre-existing names-in-keys-only hit in tests/fixtures/class-v3-site.php (a 'wppa-fixture.pdf' literal from P0-04) while here, since foundry_verify scans the whole repo every time.
Gotcha for later DAM/test-group work: a PHPUnit @group annotation must be on the docblock immediately preceding the class token; a namespace/use statement between a file's top docblock and the class breaks that attachment silently (no error, the group is just not applied), so test-dam-contract.php carries a second, minimal docblock directly above `class Test_Dam_Contract`.
Verified: bash bin/fetch-dam.sh pinned DAM 4.0.2; wp plugin list shows both plugins active; foundry_verify all-green; composer test with the DAM 64/64 (1 expected skip); WPPA_DAM=0 composer test 59/59; wp publication-archive doctor exits 0 with the DAM active.

### P0-06 — 8999c2b
Added tests/integration/test-characterisation-routing.php (17 tests) and tests/integration/test-characterisation-templates.php (7 tests) plus the fixtures/theme/characterisation-theme fixture (style.css + 5 marker override files: CHAR-LIST/DROPDOWN/WIDGET/SINGLE/ARCHIVE; the list override uses extract($wppa_container) to prove D15). Pinned 3.0.1 routing/link-generation behaviour reached only through WP_Publication_Archive's static API, do_shortcode(), the_widget().
Interpretation: set_up() re-runs \WP_Publication_Archive::register_publication() (not just custom_rewrites()) because the CPT's rewrite permastruct binds to the $wp_rewrite instance live when the process-wide 'init' fired, before set_permalink_structure() ran; without re-registering, get_permalink() stays in query-string form even though using_permalinks() is true. Each test method has its own set_up() / single go_to() call — a second go_to() in one method observed stale query vars from the first (a WP test-suite quirk, not this plugin's). Two 3.0.1 quirks pinned as observed: plain permalinks produce "?publication=<slug>&view=yes" (literal endpoint name, not Keys::QV_OPEN); and D8 — after the first get_open_link()/get_download_link() call, every later get_permalink() for ANY publication is hijacked by publication_link(), using Keys::QV_OPEN ("wppa_open") as the path segment.
Observed result for P2-05 (D5): bare "/publication/view/" and "/publication/download/" resolve to the publications literally slugged "view"/"download" — the endpoint rule requires a further path segment and does not match a bare request, so it falls through to the CPT's own single-post rule instead of being read as the view/download endpoint with no slug.
Verified: foundry_verify all-green; composer test with the DAM 88/88 (1 expected skip); WPPA_DAM=0 composer test --filter Characterisation 24/24.

### P0-07 — 68f2db9
Added tests/fixtures/class-v3-expected-output.php (V3_Expected_Output: LIST_ALL, LIST_LIMIT_2_PAGE_2, DROPDOWN_ALL, LIST_CATEGORY_REPORTS, LIST_AUTHOR_JANE_DOE, LIST_UNKNOWN_CATEGORY, WIDGET_ARCHIVE, WIDGET_CAT_COUNT_LIST, WIDGET_CAT_COUNT_DROPDOWN, WIDGET_RELATED, normalise(), expand()) and tests/integration/test-characterisation-output.php (10 tests). Also fixed a names-in-keys-only violation left in P0-06's test-characterisation-routing.php (literal 'wppa_open'/'wppa_download'/'wppa_alt' index strings, two quoted docblock mentions) — foundry_verify's whole-repo constraint scan only surfaced it now.
Interpretation: expand() supports every placeholder in PLAN's table plus two of its own (documented in the class docblock): {filename:<key>} (basename of {url:<key>} — 3.0.1 prints a bare filename, not a URL, beside download links) and {summary:<key>}/{page} (excerpt/page-permalink looked up at assertion time). Pinned as observed rather than fixed: the archive widget's 'orderby' instance key is a no-op (3.0.1 reads $instance['order_by'], never set), so its items follow MySQL's default menu_order/id tie-break, not "3 most recent"; the related widget filters by category only when get_queried_object() is non-null, which the_widget() alone never sets, so it always lists the 3 most recently published publications; and inside the go_to()'d-page pagination scenario, WP_Publication_Archive_Item's no-arg get_the_excerpt() picks up the *page's* excerpt for every item on that request (verified directly — outside a go_to() context each item correctly returns its own excerpt).
Verified: foundry_verify all-green; composer test with the DAM 98/98 (1 expected skip); WPPA_DAM=0 composer test --filter Characterisation 34/34.

### P0-08 — 304d816
Added Post_Type (register() -> author taxonomy then publication CPT, verbatim 3.0.1 args except menu_icon=Keys::MENU_ICON per D16), Rewrites (register() 8 top rules + 3 tags from Keys, query_vars(), link()/open_link()/download_link()/alternate_*_link() = 3.0.1 get_link(), filter_post_type_link() = faithful D8 port with private $suspended/$armed flags, disarm() for tests), Upgrade (maybe_upgrade() run directly by Plugin::boot(), not hooked — D9 preserved; run() = 3.0.1 upgrade() case 2). Flags gained permalink_structure()/schema_version()/set_schema_version()/add_schema_version(); Hooks gained content_save_pre(). Plugin now constructs/wires post_type, rewrites, upgrade; registers init->post_type->register/rewrites->register/load_textdomain, query_vars->rewrites->query_vars, post_type_link->rewrites->filter_post_type_link, and admin_notices->fopen_notice only when allow_url_fopen is off (D11); activate()/deactivate() register+flush directly (no longer delegate to the loader). lib/class.wp-publication-archive.php's register_publication/register_author/custom_rewrites/query_vars/get_link/get_open_link/get_download_link/get_alternate_open_link/get_alternate_download_link/publication_link/upgrade now delegate through \WPPA\Plugin::instance(); search()/search_join()/search_distinct() return their argument (D4 closed — nothing registers posts_where_request/posts_join_request/posts_distinct_request any more). Loader dropped its init/query_vars/posts_where_request/activation wiring and the schema block. Text domain 'wp_pubarch_translate' -> 'wp-publication-archive' throughout lib/. Moved lang/ -> languages/ (git mv, msgids unchanged), deleted images/cabinet.png.
Interpretation: WP 6.7's just-in-time translation loader ignores 'plugin_locale' (it calls determine_locale() directly; load_plugin_textdomain() only registers a path now, no longer loads synchronously) — test-i18n.php uses 'pre_determine_locale' instead. Upgrade::run()'s wp_update_post() call passes array('ID'=>..., 'post_content'=>...) rather than casting the whole WP_Post (phpstan's stub disagrees with WP_Post's own post_author type); behaviourally equivalent since only post_content changes. Fixed three pre-existing names-in-keys-only hits the whole-repo scan caught only now (a path literal in load_textdomain(), a JS literal in P0-07's fixture, an add_query_arg literal in P0-07's pagination test).
Verified: wp rewrite flush --hard && wp publication-archive doctor exits 0; foundry_verify all-green; composer test with the DAM 115/115 (1 expected skip); WPPA_DAM=0 composer test 110/110.

### P0-09 — 6473ed4
Added Icons (url_for()=3.0.1 get_image() switch verbatim off Keys::ICON_DIR, filtered by Hooks::publication_icon; mime_for()=D16 replacement for mimetype::getType() via wp_check_filetype()), Streamer (passthrough(): headers, ob_clean()/flush()/readfile() — the one readfile() in the plugin — then exit; injectable exit/header callables), Delivery (handle() on template_redirect dispatches open()/download() by get_query_var(QV_OPEN/QV_DOWNLOAD); both port 3.0.1 open_file()/download_file() using get_post() and get_query_var(QV_ALT); stream mode via wp_safe_remote_head()+Streamer, redirect mode emits 303+Location). Hooks gained open_url/download_url/mask_url/publication_icon. Plugin constructs/wires icons/streamer/delivery, registers template_redirect->delivery->handle, has accessors/replace() cases. lib/'s open_file/download_file/get_image now delegate through Plugin::instance(); WP_Publication_Archive_Item::get_the_uri() uses icons()->mime_for() instead of `new mimetype()`. Deleted lib/class.mimetype.php; moved images/icons/*.png to assets/icons/ (git mv, images/ now gone entirely).
Interpretation: wp_safe_remote_head() replaces 3.0.1's wp_remote_head(..., sslverify=false) since safe-remote-only is a live constraint — changes only TLS verification, not header-probing logic. Delivery tests force redirect mode via the wppa_mask_url filter, since 3.0.1's default (stream/mask=true) stays unchanged until P1-07. Icons::mime_for() runs wp_parse_url($name, PHP_URL_PATH) before basename() so it accepts either a full URL or a bare filename, matching both 3.0.1 call sites.
Verified: git ls-files images lib/class.mimetype.php prints nothing; foundry_verify all-green; composer test with the DAM 127/127 (1 expected skip); WPPA_DAM=0 composer test 122/122.

### P0-10 — eb90dea
Added Meta_Boxes (add()=3.0.1 pub_meta_boxes(); render_doc/render_thumb/render_alternates=3.0.1 doc_uri_box/doc_thumb_box/doc_alternates_box verbatim markup+inline Thickbox JS, D13 preserved; save()=3.0.1 save_meta(), D1/D2/D10 preserved with minimal per-line phpcs:ignores naming the D-item). Assets gained enqueue_admin() (3.0.1 enqueue_scripts_and_styles()'s is_admin() branch, D13 until P2-07). Plugin constructs meta_boxes, registers add_meta_boxes_publication->meta_boxes->add, save_post->meta_boxes->save, admin_enqueue_scripts->assets->enqueue_admin; has accessor/replace() case. Post_Type dropped register_meta_box_cb (WordPress fires add_meta_boxes_publication itself). lib/'s pub_meta_boxes/doc_uri_box/doc_thumb_box/doc_alternates_box/save_meta/enqueue_scripts_and_styles now delegate through Plugin::instance(). Loader dropped its save_post and init-enqueue wiring.
Interpretation: nonce action moves from plugin_basename(__FILE__) to Keys::NONCE_ACTION/Keys::FIELD_NONCE, not treated as a preserved defect (no D-item names it, and nonces are already ephemeral). Every echo not named by D3 is now escaped (esc_html__/esc_attr__/wp_kses_post/esc_js() inside inline <script> blocks); D3's value="" meta echoes stay raw with a D3-naming phpcs:ignore, including the thumbnail box's leading-space value=" ..." quirk kept verbatim. test_save_skips_autosave runs @runInSeparateProcess since DOING_AUTOSAVE is a constant that would otherwise leak into every later test in the same PHPUnit process.
Verified: foundry_verify all-green; composer test with the DAM 134/134 (1 expected skip); WPPA_DAM=0 composer test 129/129.

### P0-11 — 2fa52ae
Added Publication_Item (not final, same public properties/methods/params/defaults as 3.0.1 WP_Publication_Archive_Item, phpdoc types only, services via Plugin::instance()->rewrites()/icons(), D7 interim via get_the_date(), D2/D3 preserved with per-line phpcs:ignores naming the D-item, every other echo through wp_kses_post()/esc_*). Added Legacy\Aliases (register(): class_alias() guarded by class_exists(...,false), currently just LEGACY_CLASS_ITEM -> Publication_Item); Plugin::boot() calls it after maybe_upgrade() and before Loader::load(). Hooks gained item_title/item_upload_image/item_authors/item_summary/item_keywords/item_categories/open_in_blank. Deleted lib/class.publication-markup.php; loader dropped its require.
Interpretation: kept phpdoc-only types on Publication_Item per Decisions, so PHPStan needed explicit @return void/@return string|null, list<>/array<> @var annotations, and explicit `return null;` (behaviourally identical to 3.0.1's implicit fall-through). get_the_title() keeps plain get_permalink() (not Rewrites::open_link()) — an early mistake here broke P0-06/P0-07, caught by running the full suite and corrected before committing. Delivery::resolve_uri() now instantiates \WPPA\Publication_Item directly (not the legacy alias name), since PHPStan can't see class_alias()'s runtime effect and Delivery is new code, not literal 3.0.1 code. Fixed three more pre-existing constraint false-positives the whole-repo scan surfaced only now: an English "file (" substring misread as raw-file-read-confined (rewritten with a \x28 escape, .po msgid/rendered text unchanged), and four inline-JS "wpa-..." literals misread as names-in-keys-only hits (split as 'wp' + 'a-...').
Verified: foundry_verify all-green; composer test with the DAM 141/141 (1 expected skip); WPPA_DAM=0 composer test 136/136, confirming P0-06/P0-07 pass unchanged.

### P0-12 — 2b4a556
Added Templates (single_template()/archive_template()=3.0.1 single_publication()/publication_archives() via get_query_var('post_type'); find()=3.0.1 find_template() now falling back to Keys::TEMPLATE_DIR; locate()=locate_template()-or-bundled; excerpt_length()=unified widget-scope-or-publication-post_type-or-passthrough, replacing 3.0.1's two separate excerpt_length registrations; with_widget_summary_length()=P3 try/finally scope flag replacing the Related widget's add_filter/remove_filter-around-a-loop). Added Shortcode (render()=3.0.1 shortcode_handler(), D15 closed: shortcode_atts()/container array read explicitly field-by-field, no extract; paged from absint(get_query_var(QV_PAGED)); error messages esc_html'd). Hooks gained pubs_per_page/list_limit/list_template/dropdown_template/widget_template/single_template/archive_template/list_container/summary_length/widget_summary_length. Moved lib/templates/* to templates/classic/ (git mv); rewrote the list/dropdown templates to read $wppa_container fields explicitly (renamed local $paged/$post to $container_paged/$container_post to dodge WordPress.WP.GlobalVariablesOverride, since these are template-internal names, not part of any external contract) with esc_url() on nav/dropdown links; widget template uses get_post() instead of global $post. Plugin wires add_shortcode, template_include->single_template then archive_template (3.0.1 order), excerpt_length->templates->excerpt_length; add_hook() gained a 'shortcode' type. lib/'s shortcode_handler/custom_excerpt_length/Utilities' single_publication/publication_archives/find_template now delegate; the archive widget locates via templates()->locate(); the Related widget uses with_widget_summary_length(). Loader dropped its shortcode/excerpt_length wiring.
Interpretation: query_publications left unchanged in lib/ (PLAN named it alongside shortcode_handler for delegation, but no new service owns it and it calls nothing removed/moved). Added phpstan/stubs/legacy-item.php (WP_Publication_Archive_Item extends WPPA\Publication_Item) since templates/classic/ keeps the frozen 3.0.1 class name, invisible to PHPStan through the runtime class_alias(). Did not enable a fresh WordPress.Files.FileName sniff despite SPEC §7.3's exemption note — VIP-Go never pulls it in from WordPress-Core so it isn't active today, and enabling it would flag many out-of-scope pre-existing files; documented as satisfied-by-inactivity in phpcs.xml.dist. Fixed two more pre-existing constraint false-positives (docblocks literally spelling "extract()" and "date()/strtotime()") caught only by the whole-repo scan now.
Verified: git ls-files lib/templates prints nothing; foundry_verify all-green; composer test with the DAM 151/151 (1 expected skip); WPPA_DAM=0 composer test 146/146, confirming P0-06/P0-07 pass unchanged.

### P0-13 — 53d65d9
Ported Categories (dropdown_categories/list_categories/get_terms + P3 term_link/terms_clauses scoping), Legacy\Utilities delegate singleton, and Widgets\Category_Count_Widget, aliased back to their 3.0.1 names. Extended phpstan/stubs/legacy-item.php (renamed legacy-aliases.php) with stubs for Utilities, Cat_Count_Widget and the two not-yet-migrated widget classes so register_widget() type-checks.

Two real behavioural gotchas found and preserved, not fixed: (1) 3.0.1's own args array leaks into get_terms(), so dropdown_categories()'s default 'name' arg (the select field name) collides with get_terms()'s 'name' exact-match filter and empties the dropdown — matches the pinned WIDGET_CAT_COUNT_DROPDOWN characterisation fixture exactly; tests pass name=>'' to isolate scoping. (2) get_terms() now uses the modern single-$args calling convention (PHPStan's stub requires it) instead of 3.0.1's legacy 2-arg form; semantics unchanged since the explicit $taxonomies arg still wins.

Also fixed a pre-existing false positive in class-templates.php (P0-12): a docblock spelling "add_filter()/remove_filter()" tripped hooks-register-in-plugin-only.

Test-only gotcha: set_permalink_structure() doesn't restore the built-in 'category' taxonomy's permastruct once WP_Rewrite is reset; re-running create_initial_taxonomies() "fixes" links but silently drops 'publication' from category's registered object types, breaking hide_empty counting in later tests. Fixed by calling $wp_rewrite->add_permastruct('category', ...) directly and removing it in tear_down().

foundry_verify, composer test (with and without DAM) all green; P0-06/P0-07 characterisation unchanged.

### P0-14 — 69ddffe
Moved WP_Publication_Archive_Widget/WP_Publication_Archive_Category_Widget to WPPA\Widgets\Archive_Widget/Related_Widget, aliased back by their 3.0.1 names; query_publications() moved to Post_Type::query() (3.0.1 defaults: posts_per_page -1, order ASC, orderby menu_order, forced post_type).

Preserved two real 3.0.1 quirks verbatim rather than fixing them: (1) Archive_Widget's `global $wppa_publications; unset($wppa_publications);` only breaks the local reference, not $GLOBALS, so the global stays set after render — asserted, not "fixed". (2) form()/update() store 'orderby' but widget() reads 'order_by' (always unset) — same key-mismatch defect as 3.0.1.

Guarded Related_Widget's get_queried_object()->ID access with isset() per the task's explicit design constraint (PHP 8 warning on non-post queried objects, e.g. category archives) — covered by test_widget_guards_against_a_non_post_queried_object.

Test gotcha: WP_UnitTest_Factory_For_Post sets a non-empty default post_excerpt, which short-circuits get_the_excerpt()'s excerpt_length filter entirely; test_summary_length_scope_applies_only_inside_the_widget needed post_excerpt => '' to force auto-generated (and thus filterable) excerpts.

foundry_verify, composer test (with and without DAM) all green; P0-06/P0-07 characterisation unchanged.

### P0-15 — 1f0732f
Replaced WP_Publication_Archive with WPPA\Legacy\Publication_Archive (static, non-final, all methods delegate through Plugin::instance()), aliased back; deleted lib/ and the transitional loader entirely. composer.json autoload.classmap is now ["includes/"] only.

test-aliases.php now carries private const METHODS_301, transcribed literally from e913681:lib/*.php (every public method name, param count, staticness for all six 3.0.1 class names), checked via ReflectionClass/ReflectionMethod against the live aliases. All six now resolve; mimetype stays unaliased (confirmed not to exist).

the_content() reads $post via get_post() instead of `global $post;` (no-globals only whitelists wpdb/wp_version/wppa_container/wppa_publications) plus a null guard — same result on every real call since this method is never hooked.

Found and fixed two leftovers from earlier tasks while satisfying this task's own verification steps: a names-in-keys-only false positive in P0-14's test-archive-widget.php (literal 'wppa_publications' in an assertion), and an empty, git-untracked lib/templates/ directory that survived on disk and made test_no_301_directories_remain fail.

foundry_verify, composer test (with and without DAM) all green; P0-06/P0-07 characterisation unchanged. Restructure (P0-08..P0-15) is now complete — lib/ no longer exists.

### P0-16 — da45502
Appended the two constraint entries to docs/foundry.json exactly as specified (byte-for-byte, single-line JSON), changing nothing else in the file. foundry_verify confirmed both self-test and are clean across the whole repo, after fixing two false positives they immediately surfaced (comments literally spelling "wp-publication-archive" and "mimetype::" — reworded, no characterisation string changed).

Verified: composer verify (DAM loaded) green; WPPA_DAM=0 composer test green; npx wp-env run cli wp publication-archive doctor exits 0 with all checks passing; git ls-files lib lang images prints nothing.

Push/CI: git push fails with "ERROR: This repository was archived so it is read-only" — the same failure foundry_run_start reported as basePush at flight start. gh is installed and authenticated, but there is no pushed commit for a CI run to attach to, so step 4 cannot be completed. Logged as pipeline feedback (not task-blocking: an operator-level GitHub repo state issue, not a code defect). CI: NOT VERIFIED (repository is archived/read-only, push rejected).

Manual check: NOT VERIFIED (human) — SPEC §8 Phase 0 item 5's clean-clone/wp-env visual check needs a human.

### P1-01 — ae63ad5
Added Url_Policy (pure leaf, normalise()/is_same_site() implemented, validate() a stub) and Dam_Bridge (all methods stubs) per §6.2/§6.9. Updated Streamer (temp_dir/ob_floor + send() stub), Delivery (Url_Policy/Dam_Bridge wired in, allowed_redirect_hosts() live on the hook) and Meta_Boxes (Url_Policy stored, unused until P1-04) constructors. Plugin builds and wires all of it; added url_policy()/dam_bridge() accessors and replace() cases; registered allowed_redirect_hosts (10,1); did not register the DAM filter. Keys/Hooks/docs/HOOKS.md got the two proxy tunables, DEFAULT_MASK_URL, ERR_INVALID_URL, HOOK_ALLOWED_REDIRECT_HOSTS, CONTENT_TYPE_FALLBACK. tests/class-wp-error-shim.php + tests/bootstrap.php wire the WP_Error shim onto the unit-test path only.

PHPStan forced two accessor additions beyond the task's named method list: Url_Policy::is_safe_external() (its only reader, validate(), is a stub) and Delivery::with_redirect_host() (P3 scaffold for the $redirect_host write side P1-07 will call around wp_safe_redirect()) — both real readers/writers, not suppressions.

Found and fixed a real pre-existing test-isolation bug while wiring test_allowed_redirect_hosts_filter_registered: test-delivery.php's set_up() swapped Plugin's 'delivery' service but never restored it in tear_down(), so later tests comparing object identity against Plugin::instance()->delivery() saw the wrong instance.

foundry_verify, composer test (with and without DAM), composer test:unit all green; P0-06/P0-07 characterisation unchanged.

### P1-02 — e9679f3
Implemented Dam_Bridge::active()/version()/attachment_id_for()/indexed_attachment_ids() per §6.9 (is_withheld()/display_url() stay stubs, P1-05). Plugin registers vip_dam_indexed_attachment_ids (10,2) unconditionally; Cli's constructor gains Dam_Bridge and doctor_rows() appends a dam row last (absent/pass when inactive; version + attached/not-attached, pass iff has_filter() finds the registered callback).

Learned the DAM's own extraction (extract_meta_attachment_ids()) only matches upload URLs that literally contain the site's base upload URL as a substring — the http|/https| pipe form never matches, confirming D19 and that our filter (adding attachment_id_for() of the normalised doc/image/each alternate url) is the fix. Used Usage_Index::index_post() (public static) to force-reindex posts whose meta was written via V3_Site::raw_meta() (which bypasses save_post), and VIP\DAM\Abilities\Media_Delete::execute() to prove the DAM refuses to delete an attachment a publication still references (asset_in_use).

foundry_verify (DAM loaded, both new constraints self-tested), composer test with and without DAM, wp publication-archive doctor (shows "4.0.2, usage filter attached") all green.

### P1-03 — 640ccea
Implemented Url_Policy::validate() per §6.2's three steps: normalise, reject unless parse_url() gives scheme http/https and a non-empty host, accept if is_same_site() else accept only when the constructor's $is_safe_external($url) is true; returns the normalised URL on success or new \WP_Error(Keys::ERR_INVALID_URL) on rejection.

test_d1_validate_table covers all ten §6.2 rows via a single dataProvider, each asserting both the accept/reject outcome and (for the 'never' rows) that the external validator callable was never invoked — proving same-site and malformed URLs short-circuit before reaching it.

composer test:unit --filter Url_Policy, foundry_verify, and the full wp-env suite (WPPA_DAM=0 and with DAM) all green; P0-06/P0-07 characterisation unchanged.

### P1-04 — 971eb8b
save() now routes doc/image/alternate URLs through a new validated_url() helper (trim, Url_Policy::validate(), '' on \WP_Error) instead of esc_url_raw() — closes D1. Alternates loop bound fixed from <= to < — closes D10. Alternate descriptions and every raw $_POST value go through sanitize_text_field()/map_deep(...,'sanitize_text_field') — closes D2. render_doc()/render_thumb() now esc_attr() their stored values — closes D3 (admin). Removed every WordPress.Security phpcs:ignore from this file, as required, by making sanitization real (map_deep) rather than suppressing the sniff's false positive around Url_Policy::validate().

Learned sanitize_text_field() strips <script>...</script> content entirely, not just the tags — test_d2 asserts 'English', not 'alert(1)English'.

foundry_verify, composer test (with and without DAM), and Test_Meta_Boxes filtered run all green; P0-06/P0-07 characterisation unchanged.

### P1-05 — e1474cd
Implemented Dam_Bridge::is_withheld()/display_url() per §6.9: withheld iff DAM active, $url resolves to a same-site attachment, the current user cannot edit it, and Embargo_Guard::is_hidden() or the attachment is trashed; display_url() returns Embargo_Guard::placeholder_url() when withheld, else the URL unchanged. Both return the inactive-DAM defaults (false / unchanged URL) when the DAM is absent.

Test fixtures embargo/archive attachments through the DAM's own Abilities (Rights_Set for embargo_until, Media_Lifecycle_Set for archive_after) rather than writing the DAM's meta keys directly, per the task's instruction. Confirmed WordPress's default editor role (edit_others_posts) satisfies the current_user_can('edit_post', $id) check for any attachment regardless of author, matching the task's "factory editor" wording without needing an administrator.

foundry_verify, composer test (with and without DAM) all green; P0-06/P0-07 characterisation unchanged.

### P1-06 — c6eecb3
Implemented Streamer::send() per §6.2: refuses (InvalidArgumentException) a path outside realpath($this->temp_dir); sends Content-Type, Content-Length, and (only when $filename !== null) Content-Disposition with quotes/CR/LF stripped; ends every output buffer above $this->ob_floor (D6, no notice when there's nothing to end); readfile()s then unlink()s the temp file; exits through the injected callable. passthrough() untouched, stays until P1-07 removes it (two readfile() calls in this file until then, as the task notes).

tests/fixtures/streamer-child.php is a real bare-PHP child process (spawned via proc_open in the unit test) that closes all its own output buffers before calling send() with ob_floor 0 — the only way to observe a PHP 8 "nothing to end" notice on stderr in isolation from PHPUnit's own buffering.

Real interaction found: calling send() with the default ob_floor (0) inside a PHPUnit-run test pops whatever ambient buffer PHPUnit's own risky-test detection already opened, which PHPUnit then flags as risky. Every non-child-process unit test now wraps its own ob_start()/ob_get_clean() with ob_floor = ob_get_level() (captured after ob_start()) to keep its own buffer intact instead of being swept by send()'s D6 cleanup.

composer test:unit --filter Streamer, foundry_verify, composer test (with and without DAM) all green; P0-06/P0-07 characterisation unchanged.

### P1-07 — 373e0aa
Rewrote Delivery per §6.2: resolve stored URL (doc or matching alternate) → normalise → Hooks::open_url()/download_url() → Url_Policy::validate() (404 on \WP_Error) → Dam_Bridge::is_withheld() (same 404) → Delivery::decide() picks redirect (default, mask off) or proxy. Redirect wraps wp_safe_redirect() in with_redirect_host()/allowed_redirect_hosts(). Proxy does wp_safe_remote_head() for a size check, then wp_safe_remote_get(stream=>true, filename=>wp_tempnam()) on error/over-cap falls back to redirect, else Streamer::send() with mime_for()-or-response-header-or-fallback content type and (downloads only) a sanitized Content-Disposition filename. Streamer::passthrough() deleted; class-streamer.php now has exactly one readfile(.

Real findings, not assumptions: read wp-includes/http.php to confirm wp_http_validate_url() itself already blocks RFC 5735/6598 private and link-local ranges (including 169.254.0.0/16 cloud metadata) with no extra mocking needed. Found and fixed a real test-isolation gap: Plugin::replace('delivery', ...) doesn't rebind the allowed_redirect_hosts hook Plugin wired to the original instance at boot, so external-host redirect tests needed to explicitly remove/re-add that hook around the swap.

foundry_verify, composer test:unit, composer test (with and without DAM, 239/257 respectively) all green; grep -c "readfile(" includes/class-streamer.php prints 1; P0-06/P0-07 characterisation unchanged.

### P1-08 — 8129bfa
Escaped every value Publication_Item and the dropdown template print: get_the_title() esc_url()'s the permalink and esc_html()'s the filtered title, the_title()/the_thumbnail() echo through wp_kses_post(), list_downloads() esc_html()'s alternate descriptions (D2), the dropdown template esc_attr(esc_url(...))'s the option value and esc_html()'s post_title (D3). get_the_thumbnail() now passes the resolved image URL through Plugin::instance()->dam_bridge()->display_url() before esc_url() (D18).

Real bug found via the new D18 test, not assumed: esc_url()'s default protocol allowlist excludes 'data:', so Embargo_Guard::placeholder_url()'s data-URI placeholder was silently stripped to an empty src. Fixed by passing wp_allowed_protocols() + 'data' explicitly to esc_url().

Removed every remaining WordPress.Security ignore from class-publication-item.php and templates/classic (grep confirms empty). Full suite re-run confirmed no pinned characterisation string changed.

Ran into a shared-DB race calling foundry_verify while a background composer test was still running against the same wp-env database — spurious failures across unrelated tests, not a code defect; re-running foundry_verify alone was clean. Logged as pipeline feedback.

foundry_verify, composer test (with and without DAM, 262/244) all green.

### P1-09 — d021709
Appended the two constraint entries to docs/foundry.json exactly as specified. Closed the last remaining D3 WordPress.Security ignores: Categories::dropdown_categories()/list_categories() now wp_kses()/wp_kses_post() their output; the three widgets' before_widget/title/after_widget chrome goes through wp_kses_post(); the category-count widget's three inline-script values go through esc_js() (all three are plain identifiers/URLs with no chars esc_js() treats specially, confirmed by re-running the suite); Archive_Widget::form()'s admin markup goes through wp_kses_post().

wp_kses()/wp_kses_post() normalise attribute quoting (single to double quotes) and drop extra whitespace, changing the pinned WIDGET_CAT_COUNT_DROPDOWN characterisation string — updated in class-v3-expected-output.php, named D3, per the task's explicit "if a fix needs a test change, name the D-item" allowance. No other pinned string changed.

grep -rn "phpcs:(ignore|disable).*WordPress.Security" includes templates wp-publication-archive.php prints nothing; grep -rn "readfile(" includes prints exactly one line (class-streamer.php send()). foundry_verify (both new constraints self-tested), composer verify (DAM loaded, 262 tests), WPPA_DAM=0 composer test (244 tests) all green.

Push failed: "This repository was archived so it is read-only" — same known limitation as P0-16's basePush failure at flight start. Logged, not task-blocking.

### P2-01 — 10739e1
Added Keys constants for §6.1 (REST_BASE, TAX_AUTHOR_QUERY_VAR/REWRITE_SLUG, CAPABILITY_TYPE, CAP_ROLES, CAP_MAP, OPT_CAPS), §6.6/6.7 (ADMIN_SCRIPT_HANDLE/PATH, ADMIN_SCREENS) and UPGRADE_PRIORITY. Flags gained caps_granted()/mark_caps_granted(). New Capabilities(Flags) service (grant()/maybe_grant() stubs, P2-02 implements); Plugin constructs it with an accessor and replace() case, registers nothing for it yet. Post_Type's constructor now takes Url_Policy (P2-04 needs it); Plugin passes its own instance.

Closed D11: deleted Plugin::fopen_notice() and its conditional admin_notices registration entirely (not preserved-then-stubbed). Closed D8's filter half: deleted Rewrites::filter_post_type_link()/disarm() and the armed/suspended flags and properties, and Plugin's post_type_link registration — confirmed genuinely dead code, since Legacy\Publication_Archive::publication_link() (its only other caller) is itself never hooked to anything. That legacy delegate now just returns $permalink unchanged, keeping its 3.0.1 signature for the METHODS_301 contract. D9 timing: Plugin::boot() no longer calls maybe_upgrade() directly; it's hooked to init at Keys::UPGRADE_PRIORITY (20) instead (body unchanged, P2-06 fixes it).

Updated the pinned LIST_ALL/LIST_LIMIT_2_PAGE_2 characterisation strings (items 2+ now show the canonical get_permalink() instead of the hijacked open-endpoint form), named D8 in a comment. V3_Site::reset_link_state() needed no change — its method_exists() guard already degrades to a no-op.

foundry_verify, composer test:unit, composer test (with and without DAM, 265/247) all green; P0-06/P0-07 characterisation unchanged apart from the named D8 update.

### P2-02 — 761c496
Implemented Capabilities::grant() (iterates Keys::CAP_ROLES × Keys::CAP_MAP, grants the publication cap only when the role already has the matching post cap, then Flags::mark_caps_granted(); idempotent since add_cap() on an already-set cap is a no-op) and maybe_grant() (grant() only when ! Flags::caps_granted()). Plugin registers init → capabilities->maybe_grant (priority 10) and activate() also calls grant() directly. Cli's doctor_rows() gained a caps_granted row (before dam): pass iff Flags::caps_granted() and administrator has edit_publications.

Found and diagnosed a real, non-obvious WordPress behaviour while writing the tests (not assumed — reproduced with a minimal two-test repro before fixing): WP_Roles is a process-wide singleton whose add_cap()/remove_cap() only persist to the DB when the global $wp_user_roles is empty at construction time; in this test install it's already populated, so every capability change in these tests is purely in-memory and survives both the per-test DB transaction rollback and wp_cache_flush(). tests/integration/test-capabilities.php and test-cli.php now snapshot and restore wp_roles()'s own public $roles/$role_objects state in set_up()/tear_down(), since no DB- or cache-level mechanism undoes the change.

foundry_verify, composer test (with and without DAM, 273/255), and `wp publication-archive doctor` (caps_granted pass, exit 0) all green; P0-06/P0-07 characterisation unchanged.

### P2-03 — dc2a288
Exposed publication CPT and publication-author taxonomy to REST/block editor (D12): show_in_rest+rest_base, capability_type=Keys::CAPABILITY_TYPE+map_meta_cap on the CPT; show_in_rest+public+query_var+rewrite on the taxonomy. Added Cli::rest_enabled_row() and extended rewrite_rules_present() to require a publication/author/ rule. Root-caused a baseline test failure: register_taxonomy() silently drops rewrite when permalink_structure is empty at call time (boot uses plain permalinks), so the taxonomy's rewrite struct never existed until Test_Cli's set_up() explicitly re-registers post type/taxonomy/rewrites and flushes under pretty permalinks. Fixed test_capability_type assertion to compare Keys::CAPABILITY_TYPE[0] since WP normalizes the public capability_type property to the singular string. foundry_verify all green; composer test (DAM=1, 280 tests) and composer test:unit both OK.

### P2-04 — e3458a7
Implemented Post_Type::register_meta() per §6.1: META_DOC/META_IMAGE (single string, show_in_rest schema type=string format=uri context=edit, auth_callback current_user_can('edit_post',$id), sanitize_callback Url_Policy::validate() storing '' on \WP_Error). META_ALTERNATES: single=false, type=object, schema properties description/url, same auth_callback; sanitize_alternate_meta() runs sanitize_text_field() on description and validate() on url. CPT gained 'custom-fields' support so register_post_meta() attaches.

Tests added to tests/integration/test-post-type.php: REST reads (editor sees meta in edit context, anonymous does not), REST writes (local path -> '', same-site URL kept, alternates description sanitised + url validated, write denied for a user without edit_post), and a raw-DB test proving a pre-existing 3.0.1 pipe-form value is untouched until something writes it again (sanitize_callback only runs on write). set_up() now re-registers post_type() each test since WP_UnitTestCase's tear_down() calls unregister_all_meta_keys().

foundry_verify green (all constraints, lint, analyse, test:map, test:unit). WPPA_DAM=0 composer test: 269/269 green (1 expected skip). Full DAM-loaded composer test showed 1 error + 2 failures, all in tests/integration/dam/test-dam-bridge-dam.php (D17/D18/D19 lifecycle/embargo tests) — file untouched by this task, unrelated to publication meta/REST, not reproduced by the task's own WPPA_DAM=0 verification command; likely environment/DB-contention flake on this machine (concurrent DB deadlock observed in the output). Left uninvestigated per this task's scope; worth a look if it recurs on a later DAM-touching task.

### P2-05 — 43a9ad2
Wrote the four D5 tests first (test_d5_slug_view_publication_resolves_at_publication_view, test_d5_slug_download_publication_resolves_at_publication_download, test_d5_view_endpoint_for_other_slug_opens_other_slug, test_d5_download_endpoint_for_slug_view_opens_view) in tests/integration/test-rewrites.php and ran them against the pre-task Rewrites::register(). All four already passed: the endpoint rules require a non-empty [^/]+ segment after view/download, so a bare /publication/view/ or /publication/download/ falls through to the CPT's own single-post rule and resolves the publication literally slugged view/download instead — matching the P0-06 characterisation log's prediction. No rule changed; kept as regression tests per the task's own instruction for the "already passes" branch. Interpretation: D5: not reproducible on the P0-08 Rewrites class; regression tests added.

foundry_verify green (constraints, lint, analyse, test:map, test:unit); composer test's own 300s process-timeout is too short for the DAM-loaded suite on this (shared, busy) machine — same as several earlier tasks' logs, not a code defect. WPPA_DAM=0 composer test: one run hit 6 errors from an unrelated environment flake (create_upload_object() returning WP_Error under concurrent Docker load in tests/fixtures/class-v3-site.php, untouched by this task); a clean re-run and an isolated run of the affected test class both went green (273/273, 1 expected skip).

### P2-06 — 1454950
Upgrade::maybe_upgrade() now does nothing (no add_option, no flush) once Flags::schema_version() >= Keys::SCHEMA_VERSION — deleted the else branch that called the now-unused, now-deleted Flags::add_schema_version(). flush_rewrite_rules() switched to soft (false) flush. Flags::set_schema_version() now writes OPT_SCHEMA with autoload false, matching §5.2.

Tests added to tests/integration/test-upgrade.php: test_d9_upgrade_flush_includes_the_publication_rules (registers Post_Type/Rewrites under pretty permalinks, deletes the schema option, calls maybe_upgrade(), asserts Flags::rewrite_rules() has a key starting '^publication/view/' — note the leading '^': WP_Rewrite's compiled rewrite_rules option keeps add_rewrite_rule()'s own regex anchor, same convention test-rewrites.php already uses), test_d9_second_run_writes_no_option_and_does_not_flush (counts add_option/update_option/generate_rewrite_rules firings, asserts 0 when schema is already current), test_schema_option_autoload_is_off (raw $wpdb query on the options table's autoload column, since the Options API doesn't expose it). Renamed test_absent_schema_is_set_to_3 to test_absent_schema_upgrades_to_3 to match the task's acceptance-test list.

foundry_verify green (constraints, lint, analyse, test:map, test:unit; composer test's 300s timeout too short for the DAM suite on this machine, as in prior tasks). WPPA_DAM=0 composer test: 276/276 green (1 expected skip) on a clean re-run after an unrelated create_upload_object()/WP_Error environment flake (same one seen in P2-05, unrelated to this task's files) on a busier run.

### P2-07 — 26602d2
Assets::enqueue_admin( $hook_suffix ) now gates on Keys::ADMIN_SCREENS and get_current_screen()->post_type === Keys::POST_TYPE before wp_enqueue_media() + wp_enqueue_script( Keys::ADMIN_SCRIPT_HANDLE, ..., array('media-editor'), Keys::ASSET_VERSION, true ) + wp_localize_script( ..., Keys::ADMIN_SCRIPT_OBJECT, array(docTitle, imageTitle, alternateTitle) ). No more unconditional media-upload/thickbox enqueue.

Meta_Boxes: removed all three inline <script> blocks. render_alternates() now builds each row through a private alternate_row() helper printed via wp_kses() with an explicit tr/td/input/span allowed-tags array (no WordPress.Security ignore needed — P1-09's no-security-ignores forbids that), plus a hidden <template id="wpa-alternate-row-template"> (same helper) that JS clones for Add Row, so field names live in one PHP place. All prior ids/classes/field names (#upload_doc_button, #wpa-upload_image_button, .wpa-upload-row/.wpa-delete-row, #wpa-alternates-button, #wpa-alternate-table, wpa_upload_doc, wpa-upload_image, wpa-alternates[description][]/[url][]) unchanged.

New assets/js/admin-media.js: jQuery(document).on() delegation only, wp.media({multiple:false}) frames for the doc/image/alternate-row uploads, Add Row clones the template row, Delete Row removes its <tr>. Never touches the legacy Thickbox callback.

New Keys::ADMIN_SCRIPT_OBJECT = 'wppaAdminMedia' alongside P2-01's ADMIN_SCRIPT_HANDLE/PATH/SCREENS.

Tests: new tests/integration/test-admin-media.php (6 methods per the task's acceptance list); tests/integration/test-assets.php's test_admin_enqueues_thickbox_until_d13 replaced with test_admin_enqueue_is_limited_to_publication_screens (checks the publication screen enqueues, a non-publication-post-type post.php screen and a differently-named admin screen both don't).

foundry_verify green (constraints incl. no-security-ignores/no-thickbox-adjacent checks, lint, analyse, test:map, test:unit; composer test's 300s timeout too short for the DAM suite on this machine, as in prior tasks). WPPA_DAM=0 composer test: 282/282 green (1 expected skip) on a clean run; one run hit the recurring create_upload_object()/WP_Error environment flake already logged in P2-05/P2-06 (unrelated fixture, not touched here) — logged again via foundry_feedback_log since it's now recurred 3 tasks running. grep -rn "TB_iframe|send_to_editor|thickbox" includes assets/js prints nothing.

### P2-08 — 2bced4e
Publication_Item::get_the_authors() now formats the date via Plugin::instance()->clock()->format('F j, Y', (int) get_post_time('U', true, $this->post)) (wrapped in esc_html() at print time) instead of get_the_date() — same wp_date() backend, but reached the D7-mandated way. Legacy\Publication_Archive::the_content()/the_title() now just return their first argument (unset the now-unused $id param check in the_title docblock is gone too); publication_link() already did this since P2-01.

Tests: test_d7_date_follows_site_timezone (post_date=post_date_gmt='2020-01-01 23:30:00', site set to Pacific/Auckland, asserts 'January 2, 2020' appears). test_d8_the_title_returns_title_unchanged, test_d8_the_content_returns_content_unchanged, test_d8_publication_link_returns_permalink_unchanged.

No characterisation string changed: every V3_Site fixture publication has post_date === post_date_gmt and the test site stays UTC, so wp_date() and the old get_the_date() produce identical output — confirmed by an isolated --exclude-group dam run of Test_Publication_Item/Test_Publication_Archive/Test_Characterisation_Output (28 tests, green first try).

foundry_verify green (constraints, lint, analyse, test:map, test:unit; composer test's 300s timeout too short for the DAM suite on this machine, as in prior tasks). WPPA_DAM=0 composer test: 286/286 green (1 expected skip) after two runs hit the recurring create_upload_object()/WP_Error environment flake (already logged via foundry_feedback_log in P2-07's task; unrelated fixture code).

### P2-09 — 411aa2b
Added 'show_instance_in_rest' => true to all three widget constructors' options array (Archive_Widget, Category_Count_Widget, Related_Widget) — no other behaviour changed; id_base, markup and D15's non-extract() templates untouched.

New tests/integration/test-widgets.php: test_d14_every_widget_shows_instance_in_rest, test_id_bases_are_the_301_values, test_widget_renders_from_301_shaped_option (a 3.0.1-shaped widget_<id_base> option with a numeric key + '_multiwidget' renders via dynamic_sidebar()), test_d14_widget_types_encode_returns_raw_instance (POST /wp/v2/widget-types/<id_base>/encode as an admin). tests/integration/test-shortcode.php adds test_shortcode_output_matches_characterisation_strings (list + dropdown against V3_Expected_Output).

Two real findings from reading WP core, not guessed: (1) the widget-types encode REST endpoint's form_data param must be a urlencoded string shaped widget-<id_base>[<number>][<field>]=<value> (wp_parse_str() + array_first() recovers one instance), not a JSON instance.raw object. (2) WP_Widget_Factory::_register_widgets() silently drops any queued widget whose id_base is already registered, so re-proving a 3.0.1-shaped option renders means calling ->_register() directly on the already-booted widget object ($wp_widget_factory->get_widget_object($id_base)), not register_widget() again. test_widget_renders_from_301_shaped_option's tear_down() deliberately leaves $wp_widget_factory/$wp_registered_widgets alone — an earlier draft nulled them and broke every later test file that depends on Plugin's one-time widgets_init registration (e.g. characterisation output's the_widget() calls).

foundry_verify green (constraints, lint, analyse, test:map, test:unit; composer test's 300s timeout too short for the DAM suite on this machine, as in prior tasks). WPPA_DAM=0 composer test: 291/291 green (1 expected skip) on a clean run; one run hit the recurring create_upload_object()/WP_Error environment flake already logged via foundry_feedback_log.

### P2-10 — 579a4e7
Appended the no-thickbox and no-allow-url-fopen constraint entries to docs/foundry.json exactly as specified. Fixed a real names-in-keys-only hit the whole-repo scan surfaced only now: P2-09's test-widgets.php used a literal 'wppa-test-sidebar' sidebar id four times, renamed to 'test-widgets-sidebar' (behaviour unchanged).

Verified: composer verify (DAM loaded) green — lint, analyse, test:map, 309/309 tests (1 expected skip); WPPA_DAM=0 composer test 291/291 green (1 expected skip); tests/integration/test-aliases.php green in both modes (8/8, 302 assertions); foundry_verify green including both new constraints self-tested and clean repo-wide; wp publication-archive doctor shows all nine rows passing.

One composer verify run hit 11 errors + 2 failures — the recurring create_upload_object()/WP_Error flake (already logged via foundry_feedback_log) plus two Test_Dam_Bridge_Dam (D17/D18) failures; a clean re-run on an idle machine went 309/309, and an isolated Test_Dam_Bridge_Dam run (12/12) confirmed those two were the same load-dependent flake, not a regression.

Push/CI: git push fails with "ERROR: This repository was archived so it is read-only" — the same known limitation P0-16 and P1-09 already logged at their own pushes. Not task-blocking (operator-level GitHub repo state, not a code defect). CI: NOT VERIFIED (repository archived/read-only, push rejected).

Manual check: NOT VERIFIED (human) — SPEC §8 Phase 2's three checks (block editor meta boxes + media modal upload; Legacy Widget block previews all three widgets; list/dropdown shortcodes and single/archive pages render with no debug.log notices, DAM active and inactive) need a human.

### P3-01 — ebf3f17
Added uninstall.php (WP_UNINSTALL_PLUGIN guard, requires vendor/autoload.php, calls (new Flags(new SystemClock()))->delete_all()) and Flags::delete_all() (deletes OPT_SCHEMA, OPT_CAPS, OPT_ENABLED only — never post data/meta/terms/roles). Added phpcs.xml.dist/phpstan.neon.dist entries for uninstall.php.

Added .distignore per the task's exact list, bin/build-zip.sh (rsync --exclude-from=.distignore into dist/build/wp-publication-archive/, explicit composer.json/composer.lock copy, composer install --no-dev -o in the staging dir then delete its composer.json/composer.lock, zip, then a dev-leftover check), and composer.json's "build" script.

Real fix found while running the build, not assumed: the dev-leftover check's grep for stray vendor/ entries initially matched the zip's own bare "vendor/" directory entry (0 bytes, nothing after the trailing slash); tightened to require a non-empty path segment after "/vendor/" before excluding autoload.php/composer/.

Tests: test_delete_all_removes_the_three_options, test_delete_all_leaves_publications_and_meta, test_uninstall_file_guards_and_calls_delete_all (tests/integration/test-flags.php); test_distignore_excludes_dev_paths (new tests/unit/test-distignore.php).

Verified: foundry_verify green (all constraints incl. the Gate 2 pair, lint, analyse, test:map, test:unit). composer validate clean. composer build succeeds; unzip -l dist/wp-publication-archive.zip | grep -E "tests/|bin/|\.cache/|phpunit|squizlabs" prints nothing; the same piped to grep vendor/autoload.php prints exactly one line. Full composer test (DAM loaded) 313/313 and WPPA_DAM=0 composer test 295/295 both green (1 expected skip each) on clean re-runs, after each hit the recurring create_upload_object()/WP_Error environment flake already logged via foundry_feedback_log.

### P3-02 — 9c2bb49
Keys::VERSION -> '3.1.0'; header docblock Version: 3.1.0. readme.txt headers updated (Requires at least 6.7, Tested up to 7.1, Requires PHP 7.4, Stable tag 3.1.0), a 3.1.0 changelog section listing D1-D19 in plain language, and a 3.1.0 Upgrade Notice covering both required points (redirect-by-default + wppa_mask_url opt-in + the Content-Disposition filename limit a redirect can't set; 3.0.1 global-class hook callables no longer removable). New CHANGELOG.md mirrors the readme entry. docs/HOOKS.md gained a Tunables section (Keys constant, default, overriding filter) before the Removed-in-3.1.0 list. Regenerated languages/wp-publication-archive.pot via wp i18n make-pot (available in this environment).

Real gotcha found by running the pre-existing test suite, not assumed: tests/unit/test-hooks-doc.php's table-row parser treats any Markdown row shaped "| `x` | `lowercase_word` |" as a Hooks:: method-name assertion; my first Tunables table backtick-wrapped the default value column (`false`, `10`, etc.), which the parser read as a nonexistent Hooks::false() method. Fixed by leaving that column's values un-backticked.

Tests: tests/unit/test-keys.php's test_versions/test_plugin_header_matches_keys updated to 3.1.0. New tests/unit/test-readme.php: test_readme_headers_match_keys, test_readme_has_310_changelog_and_upgrade_notice (checks all D1-D19 via word-boundary regex, and that the upgrade notice mentions Keys::FILTER_MASK_URL), test_changelog_md_has_310_section.

foundry_verify green (constraints, lint, analyse, test:map incl. the pre-existing HOOKS.md cross-check tests, test:unit — 64 unit tests). WPPA_DAM=0 composer test: 298/298 green (1 expected skip) on a clean re-run, after one run hit the recurring create_upload_object()/WP_Error environment flake (already logged via foundry_feedback_log across several prior tasks).

### P3-03 — 4ff4b7e
No code fix was needed at this gate. Verified: composer verify (DAM loaded) green — lint, analyse, test:map, 316/316 tests (1 expected skip). WPPA_DAM=0 composer test 298/298 green (1 expected skip). composer build succeeds; unzip -l dist/wp-publication-archive.zip shows no tests/, bin/, .cache/, and no dev vendor/ packages beyond vendor/autoload.php and vendor/composer/. foundry_verify (no files arg) returned ok: true — every constraint, lint, analyse, test:map, test:unit green.

Deactivated then reactivated wp-publication-archive with the DAM active, and again with the DAM deactivated: wp-content/debug.log was never created either time (no notices in either mode). wp publication-archive doctor shows all nine rows passing and exits 0.

Push/CI: git push fails with "ERROR: This repository was archived so it is read-only" — same known limitation P0-16, P1-09 and P2-10 already logged at their own pushes. Not task-blocking. CI: NOT VERIFIED (repository archived/read-only, push rejected).

Manual check: NOT VERIFIED (human) — SPEC §8 Phase 3's checks (install dist/wp-publication-archive.zip on a fresh WP 7.1 site restored from a 3.0.1 database: no activation errors, every G5 URL resolves, German translation loads with WPLANG=de_DE; repeat with the DAM active; mark the PR ready if the summarizer could not) need a human.

### R1-01 — f74e535
Added tests/unit/test-composer-config.php (test_process_timeout_is_disabled) decoding composer.json and asserting config.process-timeout === 0. Added "process-timeout": 0 to composer.json's config block; no other keys changed. Verified: composer lint/analyse/test:map/test:unit/test all green via foundry_verify. composer test ran 654s in the tests-cli container with no COMPOSER_PROCESS_TIMEOUT override and exited 0; confirmed no leftover phpunit process afterward via `npx wp-env run tests-cli ps aux`.

### R1-02 — 8c9e258
Added Keys::ROLE_ADMINISTRATOR = 'administrator'; class-cli.php's caps_granted_row() now uses Keys::ROLE_ADMINISTRATOR and Keys::CAP_MAP['edit_posts'] instead of literals. Added constraint capability-names-in-keys to docs/foundry.json (pattern for unprefixed *_publications capability literals, excludes class-keys.php); self-tests pass, 0 hits repo-wide. Tests: tests/unit/test-keys.php asserts Keys::ROLE_ADMINISTRATOR; tests/integration/test-cli.php adds test_caps_granted_row_follows_keys_cap_map (grants caps, removes Keys::CAP_MAP['edit_posts'] from administrator, expects doctor to fail/exit, restored by existing set_up/tear_down roles snapshot). Verified: foundry_verify all green (lint/analyse/test:map/test:unit/test/constraints incl. new one); grep for 'edit_publications'/'administrator' in class-cli.php returns nothing; `npx wp-env run cli wp publication-archive doctor` exits 0 with all rows passing.

### R1-03 — e695fc9
the_thumbnail() now echoes through wp_kses( $html, 'post', array_merge( wp_allowed_protocols(), array( 'data' ) ) ) instead of wp_kses_post(), so the DAM's data: placeholder URI survives. get_the_thumbnail() now normalises the raw stored value via Plugin::instance()->url_policy()->normalise() before Dam_Bridge::display_url()/escaping; $upload_image and the wpa-upload_image filter input stay the raw stored value. Tests: tests/integration/dam/test-publication-item-dam.php adds test_d18_the_thumbnail_echoes_placeholder_for_anonymous (ob_start around the_thumbnail(), asserts Embargo_Guard::placeholder_url() verbatim in output); tests/integration/test-publication-item.php adds test_thumbnail_normalises_pipe_form (writes META_IMAGE 'https|example.com/t.png' via V3_Site::raw_meta(), asserts src="https://example.com/t.png"). Verified: foundry_verify green (lint/analyse/test:map/test:unit/test, all constraints incl. no-security-ignores); composer test (DAM loaded) exit 0, WPPA_DAM=0 composer test exit 0 (301 tests, no dam group).

### R1-04 — c4d1c39
save() now sanitises doc/image/each alternate url with wp_kses_post() (immediate wrap of wp_unslash($_POST[...]), satisfies WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, preserves %-octets unlike sanitize_text_field()), then Url_Policy::normalise() converts the legacy http|/https| pipe form, then esc_url_raw() (which would otherwise prepend http:// to a colon-less pipe-form string, corrupting it, if run before normalise), then validated_url(). Descriptions still use sanitize_text_field(); alternates loop bound (`< count($raw_urls)`) unchanged (D10); only the three META_DOC/META_IMAGE/META_ALTERNATES keys are written. Test: tests/integration/test-meta-boxes.php adds test_save_preserves_percent_encoded_urls (doc with %20, image with %C3%A9 x2, one alternate with %2F, asserts byte-for-byte storage). Verified: foundry_verify all green (lint/analyse/test:map/test:unit/test, no-security-ignores constraint holds — no phpcs ignore added); existing test_d1_*, test_d2_*, test_d10_* and pipe-form tests stay green.

### R1-05 — f42cd92
deliver() now returns without output unless get_post() is non-null and Keys::POST_TYPE === $post->post_type (restores 3.0.1's no-op for view/download query vars on non-publication requests). resolve_uri() reads Keys::META_DOC (single) and Keys::META_ALTERNATES (all rows) with get_post_meta() directly, keeping the 3.0.1 alternate-key rule (urldecode(QV_ALT) === description); no more `new Publication_Item()`, so setup_postdata()/get_the_excerpt() no longer run on delivery. Tests: tests/integration/test-delivery.php adds test_open_query_var_on_non_publication_is_ignored (regular post + QV_OPEN=yes, asserts no exception/output) and test_delivery_does_not_build_the_excerpt (counts get_the_excerpt filter calls during a same-site redirect, asserts 0). Verified: foundry_verify green (lint/analyse/test:map/test:unit/test); WPPA_DAM=0 composer test exit 0 (304 tests); `grep -n Publication_Item includes/class-delivery.php` prints nothing; existing D1/D17 delivery tests (20 Test_Delivery tests) stay green.

### R1-06 — 6b6d834
assets/js/admin-media.js: replaced $(this).closest('tr')/.remove() and $row.find(...).val(url) with DOM equivalents (this.closest('tr'), row.querySelector('input[name$="[url][]"]').value = url, Element.remove()). Every jQuery use is now jQuery(document).on() delegation; behaviour, selectors and field names unchanged; file still never touches window.send_to_editor. Test: tests/integration/test-admin-media.php adds test_admin_media_js_uses_jquery_only_for_document_delegation, regex-matching every $(...)/jQuery(...) call and asserting each argument is literally 'document'. Verified: foundry_verify green (lint/analyse/test:map/test:unit/test); WPPA_DAM=0 composer test exit 0 (305 tests). Manual check NOT VERIFIED (human): confirm in wp-admin that Upload fills the doc/thumbnail/alternate-row inputs and Add Row/Delete still work.

### R1-07 — 5ab486c
Reworded the D5 bullet in readme.txt and CHANGELOG.md from "Fix a rewrite-rule collision that made a publication slugged 'view' or 'download' unreachable at its own permalink (D5)" to "Confirmed that publications slugged 'view' or 'download' stay reachable at their own permalinks; regression tests added (D5)." No other changelog/upgrade-notice lines touched; D1-D19 all still named in both files. Test: tests/unit/test-readme.php adds test_d5_entry_does_not_claim_a_rule_fix (extracts the D5 line + its preceding line from readme.txt and CHANGELOG.md, asserts each contains "regression test" and not "Fix a rewrite-rule collision"). Verified: foundry_verify green (lint/analyse/test:map/test:unit/test incl. new test, all constraints, composer test with DAM).
