# WP Publication Archive 3.1.0 build progress
Branch: build/2026-09-24
Started: 2026-09-24T15:54:27.940Z

## Tasks
- [x] P0-01 Toolchain, minimal Keys, and the 3.0.1 runtime behind a transitional loader
- [x] P0-02 Keys inventory, Clock, and docs/HOOKS.md
- [x] P0-03 Flags, Hooks, Plugin and Assets; front-end stylesheet moves to assets/css/base.css
- [x] P0-04 Cli doctor, REST lineage route, humans.txt, docs, the v3 fixture and smoke tests
- [ ] P0-05 The DAM in wp-env
- [ ] P0-06 Characterisation — URLs, link generators and template location
- [ ] P0-07 Characterisation — shortcode and widget output
- [ ] P0-08 Restructure (1/8) — Post_Type, Rewrites, Upgrade and i18n
- [ ] P0-09 Restructure (2/8) — Delivery, interim Streamer and Icons
- [ ] P0-10 Restructure (3/8) — Meta_Boxes and admin assets
- [ ] P0-11 Restructure (4/8) — Publication_Item and the alias layer
- [ ] P0-12 Restructure (5/8) — Shortcode, Templates and templates/classic
- [ ] P0-13 Restructure (6/8) — Categories, Legacy\Utilities and the category-count widget
- [ ] P0-14 Restructure (7/8) — Archive and Related widgets
- [ ] P0-15 Restructure (8/8) — Legacy\Publication_Archive and removal of lib/
- [ ] P0-16 Gate 0 — foundation verified, constraints locked, branch pushed
- [ ] P1-01 Contracts (1/2) — Keys, Hooks, Plugin wiring and signatures for Url_Policy, Dam_Bridge, Delivery and Streamer
- [ ] P1-02 Contracts (2/2) — DAM usage filter (D19) and the dam doctor row
- [ ] P1-03 Url_Policy::validate() and the §6.2 table test
- [ ] P1-04 Meta box save and render through Url_Policy (D1 save, D2 save, D3 admin, D10)
- [ ] P1-05 Dam_Bridge withholding and display URL (D17, D18 at the bridge)
- [ ] P1-06 Streamer::send() — the one temp-file readfile (P11, D6)
- [ ] P1-07 Delivery per §6.2 — validate, withhold, redirect or proxy (D1 delivery, D17 end to end)
- [ ] P1-08 Escaped Publication_Item and list/dropdown templates; thumbnail through the bridge (D2 output, D3 front, D18)
- [ ] P1-09 Gate 1 — security verified, constraints locked, branch pushed
- [ ] P2-01 Contracts (1/2) — Keys, Flags and Plugin for §6.1, §6.6 and §6.7; close D11 and D8's filter; Capabilities signatures; upgrade on init
- [ ] P2-02 Contracts (2/2) — Capabilities granted on activation and init
- [ ] P2-03 Post type and taxonomy in REST and the block editor (D12)
- [ ] P2-04 Registered publication meta in REST (D12 meta, D1 via REST)
- [ ] P2-05 Rewrite rules — slug collisions (D5)
- [ ] P2-06 Upgrade on init, once, with no per-request option writes (D9)
- [ ] P2-07 admin-media.js replaces Thickbox and inline scripts (D13)
- [ ] P2-08 Site-timezone dates and dead-code delegates (D7, D8 delegates)
- [ ] P2-09 Widgets in the Legacy Widget block, id_base preservation, shortcode check (D14)
- [ ] P2-10 Gate 2 — compatibility verified, constraints locked, branch pushed
- [ ] P3-01 uninstall.php, .distignore and composer build
- [ ] P3-02 readme.txt, CHANGELOG.md, version 3.1.0 and the HOOKS.md final pass
- [ ] P3-03 Final gate — verify, build, push

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
