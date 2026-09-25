# WP Publication Archive 3.1.0 build plan
Derived from docs/SPEC.md v2.0 on 2026-09-24. SPEC.md wins over this file.

## Decisions
- Q1 (PHPUnit/polyfills pair on PHP 7.4) → keep SPEC's pair: `phpunit/phpunit ^9.6`, `yoast/phpunit-polyfills ^3.0`, `wp-phpunit/wp-phpunit ^6.7` under `config.platform.php = 7.4.0`. If `composer install` cannot resolve it, P0-01 picks the nearest resolving pair and records it under Interpretation and in the progress log. SPEC says no spike.
- Q2 (proxy mode) → keep it behind `wppa_mask_url` (default now `false`). P11 is satisfiable: the proxied body is streamed by `wp_safe_remote_get()` into a `wp_tempnam()` file and the single `readfile()` in `Streamer::send()` reads only a path inside the temp directory.
- Q3 (DAM floor WP 6.9/PHP 8.2 vs ours WP 6.7/PHP 7.4) → recorded reading: `Dam_Bridge::active()` is `class_exists`-based, so the bridge is inert wherever the DAM cannot load. Nothing to build; P1-02's inert tests (`@group nodam`) prove it.
- Live constraints vs "preserve 3.0.1 until the restructure" → `foundry_verify` scans the whole repo on every task, and the 3.0.1 bootstrap and `includes/*.php` templates already violate seven constraints. P0-01 therefore moves the 3.0.1 bootstrap body into `lib/class.wp-publication-archive-loader.php` and the five 3.0.1 templates into `lib/templates/`. `lib/` is in no constraint, lint or analysis path, sits in the Composer classmap until P0-15, and is deleted by P0-15. This is also how "outside the lint and analysis paths for this task only" (SPEC §8 Phase 0 item 1) is read: the 3.0.1 code stays outside those paths until the restructure removes it, without any excludes.
- Defects the restructure cannot keep verbatim because a constraint is live from P0-01 → handled as follows, each recorded in the task that does it:
  - D1 delivery: the 3.0.1 `readfile( $uri )` moves verbatim into an interim `Streamer::passthrough()` (the only file allowed `readfile`), and the HEAD request becomes `wp_safe_remote_head()` without `sslverify` (safe-remote-only). The SSRF/LFI read stays open until P1-07.
  - D4: 3.0.1 `search()` needs `global $wp` and run-time `add_filter` (both forbidden). P0-08 closes D4: the three search callbacks are not registered and the legacy methods return their argument. P2-01 therefore closes only D11 (plus the D8 filter below).
  - D15: `extract()` is forbidden from P0-01. P0-12 closes D15 when it moves the shortcode and templates. P2-09 does not reopen it.
  - D7: `date()`/`strtotime()` are forbidden outside `Clock`. The restructure uses `get_the_date( 'F j, Y', $post )` (same output as 3.0.1 on a UTC, English site); P2-08 closes D7 with `Clock::format()` on the GMT timestamp.
  - Escaping: P7 allows Security ignores only for D1, D2, D3 and D10, and Gate 1 requires none. So every output that is not one of those four open defects is escaped during the restructure. That includes the widget escaping that SPEC files under D14; P2-09 still closes D14 (`show_instance_in_rest`).
  - `global $post`, `$wp_query` and `$wp` become `get_post()`, `get_query_var()` and `get_queried_object()`. Option reads (`permalink_structure`, `show_on_front`, `page_for_posts`, `rewrite_rules`) go through `Flags` methods.
  - Icons move to `assets/icons/` in P0-09 (SPEC §6.3). The characterisation strings express icon URLs through `\WP_Publication_Archive::get_image()`, so they pass unchanged.
  - `images/cabinet.png` is deleted in Phase 0 (SPEC §4.1), so P0-08 sets `menu_icon` to `dashicons-media-document` (the D16 part of §6.1) at the same time.
- D8 is not a no-op → `get_link()` removes `publication_link` from `post_type_link`, reads the permalink, then adds it back. After the first link is generated, every later `get_permalink()` of a publication in that request returns `site_url()/publication/wppa_open/<slug>`. P0-06 and P0-07 pin this. P0-08 ports it faithfully as a Plugin-registered `post_type_link` callback on `Rewrites` that acts only while a private "armed" flag is set, per P3. P2-01 removes it, closing D8's filter half and updating the pinned strings. P2-08 closes the other half: the `the_content`, `the_title` and `publication_link` delegates return their first argument.
- A contracts task never wires a live hook to a throwing stub. The DAM fires `vip_dam_indexed_attachment_ids` whenever it indexes a post, and `init` fires on every request. So:
  - P1-01 implements `Url_Policy::normalise()`, `Url_Policy::is_same_site()` and `Delivery::allowed_redirect_hosts()` outright.
  - P1-02 is a second serial contracts task. It registers the DAM filter and implements `active()`, `version()`, `attachment_id_for()` and `indexed_attachment_ids()`, which closes D19.
  - P2-02 is a second serial contracts task that implements and registers `Capabilities`.
  - The `dam` workstream keeps D17 and D18, and the `model` workstream keeps `Post_Type`.
- Amendments to the §4.2 module map → the SPEC's own requirements force three:
  - `Publication_Item` and the widgets are constructed by theme code and WordPress with their 3.0.1 constructors, so they reach services through `Plugin::instance()`.
  - `Categories` needs `Flags` for `show_on_front` and `page_for_posts` (P2).
  - `Url_Policy` references `Keys::ERR_INVALID_URL`. `Keys` is constant-only and needs no `use` line, so the leaf rule holds.
- Classes aliased to 3.0.1 names stay non-final, keep 3.0.1 method names, parameters, defaults and public properties, and add no native types to those methods. A theme subclass of `WP_Publication_Archive_Item` must not fatal.
- Widgets are registered under their 3.0.1 class names (`Keys::LEGACY_CLASS_*`, resolved through the aliases), so `the_widget( 'WP_Publication_Archive_Widget' )` keeps working. Each constructor passes its 3.0.1-derived `id_base` from `Keys` (§6.6).
- Link generation (`get_link` family) lives in `Rewrites`. The two `excerpt_length` behaviours (publication summary length, and the Related widget's scoped length) live in `Templates`. `query_publications()` becomes `Post_Type::query()`. `Plugin::fopen_notice()` holds the D11 notice from P0-08 until P2-01 deletes it.
- Doctor rows are exactly SPEC §6.8's list; the template's `autoloader`, `cache_group` and `enabled` rows are dropped. Each row lands with its feature:
  - P0-04: `lineage`, `version`, `php`, `wp`, `post_type_registered`, `rewrite_rules_present` (endpoint rules);
  - P1-02: `dam` (it needs `Dam_Bridge`, and P14 confines DAM symbols to that file);
  - P2-02: `caps_granted`;
  - P2-03: `rest_enabled`, plus the `publication/author` slug in `rewrite_rules_present`.
- Inert-bridge tests cannot run while the DAM is loaded, because `active()` is class-based. They carry `@group nodam`. `bin/test.sh` runs `--exclude-group nodam` when the DAM is loaded and `--exclude-group dam` when it is not. Gates run both modes.
- `Keys::VERSION` is `'3.1.0-dev'` (header `Version: 3.1.0-dev`) until P3-02 sets `'3.1.0'`. A pushed mid-flight branch is never mistakable for the release.
- `Flags` uses per-site `get_option()`/`update_option()` (MULTISITE=0, and 3.0.1 used per-site options), not the template's `get_site_option()`.
- P11 says `readfile`'s argument is "a path returned by `wp_tempnam()` in the same method", but §6.2 has `Delivery` call `wp_tempnam()` and hand the path to `Streamer::send()`. The reading used here: `Delivery` obtains the path from `wp_tempnam()`, and `Streamer::send()` refuses any path whose `realpath()` is not inside the temp directory it was constructed with (`get_temp_dir()`).
- `wp_safe_redirect()` sends any host not in `allowed_redirect_hosts` to `wp-admin`, which would break external documents that pass `Url_Policy::validate()`. So `Delivery` allows exactly the validated URL's host through a Plugin-registered `allowed_redirect_hosts` callback that acts only while `Delivery` holds that host in a private field (set and cleared in `try`/`finally`).
- The shortcode reads the page number with `get_query_var( Keys::QV_PAGED )`, a query var `Rewrites` registers, instead of `$_GET`. Reading `$_GET` would need a `WordPress.Security.NonceVerification` ignore that P7 forbids.
- The registered meta's REST schema uses `context => array( 'edit' )`. That is the only way SPEC's "with it for an Editor, without it for anonymous" test holds: `auth_callback` governs writes, not reads.
- Once P2-04 registers sanitize callbacks, `update_post_meta()` rewrites hostile or pipe-form values. So from P0-04 on, the fixture and every test that needs a 3.0.1-shaped or hostile stored value writes it with `V3_Site::raw_meta()`, a direct `$wpdb` insert plus a meta cache flush.
- `uninstall.php` deletes all three §5.2 options (`OPT_SCHEMA`, `OPT_CAPS`, `OPT_ENABLED`) and nothing else. §4.1 says "the two options"; §5.2 lists three, and uninstall must leave no plugin option behind. See Spec issues.
- ⚠️ ASSUMPTION tunables (`wppa_proxy_timeout` → `Keys::DEFAULT_PROXY_TIMEOUT` = 30, `wppa_proxy_max_bytes` → `Keys::DEFAULT_PROXY_MAX_BYTES` = 52428800) get no tuning task: SPEC §5.3 says so explicitly, and the Phase 1 manual check exercises them. They are introduced in P1-01 and used only through `Keys`/`Hooks` (tunables-in-keys-only).
- Phases and waves follow SPEC §8 exactly. Wave tasks carry `**Workstream:** <slug>`. Contracts, gate, Phase 0 and Phase 3 tasks carry `**Workstream:** serial`. No `**Stream:**` fields and no `parallel` block (Foundry 0.3.2).
- Gate tasks add the constraints that can only hold once their phase lands:
  - P0-16: `no-mimetype-class`, `no-legacy-string-callables`;
  - P1-09: `no-security-ignores`, `no-ob-clean`;
  - P2-10: `no-thickbox`, `no-allow-url-fopen`.

  Their JSON is in the gate task text and was self-tested at plan time. The planner itself added `tunables-in-keys-only`, `url-policy-is-pure` and `stub-form-exact`, which hold from P0-01.
- Plain-permalink link generation in 3.0.1 produces `?view=yes` / `?download=yes` (and `&alt=<key>`), which the delivery endpoint ignores. No D-item covers it and public behaviour is frozen, so it is preserved and pinned (see Spec issues).
- Template source → P0-01 clones `https://github.com/ericmann/eamann-plugin-template` at `main` HEAD into `.cache/eamann-plugin-template` (gitignored). If the clone fails and `/Users/ericmann/Projects/eamann-plugin-template` exists, it uses that and records which.

## Conventions
- One task, one commit, titled `<ID>: <title>`, with the CLAUDE.md commit template. The `Closes:` line names every D-item the commit closes; a commit that edits a pinned characterisation string names the D-item that changed it.
- The 3.0.1 source at commit `e913681` (`git show e913681:lib/<file>`) is the behavioural reference for every restructure and fix task.
- Test classes: `WPPA\Tests\Unit\Test_<Slug>` (extend `\Yoast\PHPUnitPolyfills\TestCases\TestCase`), `WPPA\Tests\Integration\Test_<Slug>` (extend `\WP_UnitTestCase`), `WPPA\Tests\Integration\Dam\Test_<Slug>` with `@group dam` on the class. Fixtures live in `WPPA\Tests\Fixtures`. A test that closes a D-item is named `test_d<n>_<behaviour>`.
- Tests never contain a prefixed name literal. They use `Keys` constants, except the text domain as the last argument of an i18n call.
- Every includes file starts with a docblock that names its `SPEC.md §` section and carries `@author Eric A. Mann (EAM) <eric@eamann.com>`.
- Before verifying a task that touches `includes/`, `templates/`, `tests/`, `bin/`, `.wp-env.json`, the bootstrap, `uninstall.php` or `composer.json`, make sure wp-env is running (`npx wp-env start`). After P0-05, make sure the DAM is fetched (`bash bin/fetch-dam.sh`).
- If `bash bin/fetch-dam.sh` or `composer test` fails because this machine has no github.a8c.com access, block the task with that reason (SPEC §7.4). Never skip the `dam` group to get green.
- Characterisation strings (`tests/fixtures/class-v3-expected-output.php`) change only in a task that names the D-item responsible.
- "Verification: foundry_verify" means calling `foundry_verify` with the task's Files touched. It runs the constraints plus `composer lint`, `composer analyse`, `composer test:map` and `composer test:unit`, and `composer test` in wp-env with the DAM loaded when the extraVerify paths are touched.

## Phase 0 — Foundation
Serial. Everything here is a hotspot (SPEC §8 Phase 0).

### P0-01: Toolchain, minimal Keys, and the 3.0.1 runtime behind a transitional loader
**Goal:** Add the template toolchain and move every 3.0.1 file that a live constraint would flag into `lib/`, so that `composer verify:static` and `composer test` pass while the 3.0.1 plugin behaves exactly as before.
**Files touched:**
- toolchain: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `.gitignore`, `phpcs.xml.dist`, `phpstan.neon.dist`, `phpstan/bootstrap.php`, `phpstan/stubs/README.md`, `phpunit.xml.dist`, `.wp-env.json`, `.github/workflows/verify.yml`;
- scripts: `bin/wp-env.conf`, `bin/setup-wp-env.sh`, `bin/test.sh`, `bin/test-map.php`;
- runtime: `wp-publication-archive.php`, `lib/class.wp-publication-archive-loader.php` (new), `lib/class.wp-publication-archive.php`, `lib/class.wp-publication-archive-utilities.php`, `lib/class.publication-widget.php`, `lib/templates/` (`git mv` of the five `includes/*.php` templates);
- classes: `includes/class-keys.php`, `includes/class-not-implemented-exception.php`;
- tests: `tests/bootstrap.php`, `tests/class-wp-cli-shim.php`, `tests/class-wp-cli-exit-exception-shim.php`, `tests/wp-cli-utils-shim.php`, `tests/class-spy-container.php`, `tests/unit/test-keys.php`, `tests/unit/test-not-implemented-exception.php`, `tests/integration/test-bootstrap.php`.

**Design constraints:**
- Global: every rule in CLAUDE.md § Constraints applies to every file touched; `foundry_verify` runs them on the whole repo.
- Template import (SPEC Appendix, §8 Phase 0 item 1): clone per Decisions, then rename `Eamann\Plugin` → `WPPA`, slug `eamann-plugin` → `wp-publication-archive`, CLI command → `publication-archive`, and test namespaces → `WPPA\Tests\…`. Drop the template's multisite fixture, `bin/init.sh` and `bin/mu-loader.php.example`.
- `composer.json` (§5.4, §7):
  - name `ericmann/wp-publication-archive`; `require.php` `>=7.4`; `config.platform.php` `7.4.0`;
  - require-dev: the template's packages plus `phpcompatibility/phpcompatibility-wp:^2.1`, with `phpunit/phpunit:^9.6`, `yoast/phpunit-polyfills:^3.0`, `wp-phpunit/wp-phpunit:^6.7` (Q1 fallback per Decisions);
  - `autoload.classmap` `["includes/", "lib/"]` (`lib/` is transitional and removed in P0-15); `autoload-dev.classmap` `["tests/"]`;
  - scripts: `lint`, `lint:fix`, `analyse`, `test:map`, `test:unit`, `test` exactly as §7; `verify:static` = lint, analyse, test:map, test:unit; `verify` = `@verify:static` then `@test`. No `build` script yet (P3-01).
- `package.json`: private, devDependencies `@wordpress/env: ^11`. Commit `package-lock.json` (CI runs `npm ci`).
- `phpcs.xml.dist` (§7.3):
  - list only paths that exist now: `wp-publication-archive.php`, `includes`, `tests`, `bin`. PHPCS aborts on a missing `<file>`, so P0-12 adds `templates` and P3-01 adds `uninstall.php`;
  - exclude-patterns `vendor/*`, `node_modules/*`, `.cache/*`, `dist/*`;
  - `WordPress-VIP-Go` plus, at error severity, `WordPress.Security`, `WordPress.WP.I18n` (`text_domain` `wp-publication-archive`), `WordPress.DateTime.RestrictedFunctions`, `WordPress.PHP.DontExtract`, `WordPress.PHP.DevelopmentFunctions`, and `PHPCompatibilityWP` with `testVersion` `7.4-`;
  - `WordPress.Security.EscapeOutput` excluded for `tests/*` and `bin/*`.
- `phpstan.neon.dist` (§7.3):
  - level 6; paths `includes`, `wp-publication-archive.php`, `bin/test-map.php` (P0-12 adds `templates`, P3-01 adds `uninstall.php`);
  - the `szepeviktor/phpstan-wordpress` extension and the wp-cli stub scanFiles;
  - `bootstrapFiles: [phpstan/bootstrap.php]`, which defines `WP_PUB_ARCH_VERSION`, `WP_PUB_ARCH_URL` and `WP_PUB_ARCH_DIR`;
  - `scanDirectories: [phpstan/stubs]`.
- `.wp-env.json`: `core: null`, `phpVersion: "8.3"`, mapping `wp-content/plugins/wp-publication-archive` → `.`, config `WP_DEBUG`, `WP_DEBUG_LOG` and `SCRIPT_DEBUG` true, `lifecycleScripts.afterStart: "bash bin/setup-wp-env.sh"`.
- `bin/wp-env.conf`: `MULTISITE=0`, `PLUGIN_SLUG="wp-publication-archive"`; DAM keys come in P0-05. `bin/setup-wp-env.sh`: the template's single-site branch plus `wp rewrite structure '/%postname%/' --hard` in both `cli` and `tests-cli`. `bin/test.sh`: the template's. `bin/test-map.php`: the template's, with the namespace regex `^namespace WPPA(\\[A-Za-z]+)?;`.
- CI (§7.4):
  - `static` job: matrix PHP 7.4 and 8.3, `composer install`, `composer verify:static`;
  - `integration` job: PHP 8.3, Node 22, `composer install`, `npm ci`, `npx wp-env start`, `WPPA_DAM=0 composer test`.
- `.gitignore`: keep the existing lines; add `vendor/`, `node_modules/`, `.cache/`, `dist/`, `.wp-env.override.json`, `.phpunit.result.cache`, `*.log`, `.DS_Store`, `.foundry/implement.lock`.
- Bootstrap `wp-publication-archive.php` (§4.1, §5.5):
  - headers exactly §5.5 except `Version: 3.1.0-dev`, then an `ABSPATH` guard and `require_once __DIR__ . '/vendor/autoload.php'`;
  - the template's PHP/WP guard against `Keys::MIN_PHP` and `Keys::MIN_WP`;
  - define `WP_PUB_ARCH_VERSION` = `Keys::VERSION`, `WP_PUB_ARCH_URL` = `plugin_dir_url( __FILE__ )`, `WP_PUB_ARCH_DIR` = `plugin_dir_path( __FILE__ )`;
  - `register_activation_hook`/`register_deactivation_hook` with `array( \WP_Publication_Archive_Loader::class, 'activate' | 'deactivate' )`, then `\WP_Publication_Archive_Loader::load()`;
  - no functions, no `add_*`, no option calls, and no `'lib/` string, because the loader autoloads through the classmap.
- `lib/class.wp-publication-archive-loader.php`: global class `WP_Publication_Archive_Loader`, all static.
  - `load()` is idempotent through a static guard. It `require_once`s the seven 3.0.1 lib files (`__DIR__ . '/class.mimetype.php'` etc., in 3.0.1 order), then runs the 3.0.1 schema block verbatim (D9 preserved), then the 3.0.1 hook wiring verbatim, including the conditional `allow_url_fopen` notice.
  - `init()`, `activate()`, `deactivate()` and `fopen_disabled()` are the bodies of the four `wp_pubarch_*` functions. The `init` hook points at `array( __CLASS__, 'init' )`.
  - The textdomain path must be computed from the plugin root: `dirname( plugin_basename( WP_PUB_ARCH_DIR . 'wp-publication-archive.php' ) ) . '/lang/'`.
- `lib/` path edits: only the three `WP_PUB_ARCH_DIR . 'includes/'` template fallbacks become `'lib/templates/'` (in `shortcode_handler`, the archive widget and `find_template`). Nothing else in `lib/` changes.
- `includes/class-keys.php`: `final`, constants only, with `VERSION` `'3.1.0-dev'`, `MIN_PHP` `'7.4'`, `MIN_WP` `'6.7'`, `SLUG`, `TEXT_DOMAIN` and `CACHE_GROUP` (all `'wp-publication-archive'`), `PREFIX` `'wppa'`, `CLI_COMMAND` `'publication-archive'`, `POST_TYPE` `'publication'`, `SHORTCODE` `'wp-publication-archive'`, and the four lineage constants with SPEC §5.4 values.
- `includes/class-not-implemented-exception.php`: the template's (`final class NotImplementedException extends \LogicException`).
- `tests/bootstrap.php`: the template's shape. On the unit path (no `WP_TESTS_DIR`) it returns after the autoloader. It loads `wp-publication-archive.php` at `muplugins_loaded` and requires the three WP-CLI shims after WordPress loads.
- PHP 7.4 syntax everywhere (§5.4).

**Acceptance tests:**
- `tests/unit/test-keys.php`:
  - `test_identity`;
  - `test_versions` (`'7.4'`, `'6.7'`, `'3.1.0-dev'`);
  - `test_lineage_epoch_is_the_ninth_of_november_1983`;
  - `test_plugin_header_matches_keys` (Version, Requires PHP, Requires at least, Text Domain, `Domain Path: /languages`);
  - `test_wp_env_conf_uses_the_slug`;
  - `test_keys_is_final_and_has_no_methods`.
- `tests/unit/test-not-implemented-exception.php`: `test_message_is_the_method_name`.
- `tests/integration/test-bootstrap.php`:
  - `test_legacy_constants_are_defined` (`WP_PUB_ARCH_VERSION === Keys::VERSION`, `WP_PUB_ARCH_DIR` ends with `/`);
  - `test_301_runtime_registers_the_publication_post_type`;
  - `test_301_shortcode_is_registered` (`shortcode_exists( Keys::SHORTCODE )`);
  - `test_301_templates_are_found_under_lib_templates`.

**Out of scope:** Clock, Flags, Hooks, Plugin or any other service. Any behaviour change to 3.0.1 code. DAM tooling (P0-05). Characterisation (P0-06/07).
**Verification:**
1. `composer install && npm install`, then `npx wp-env start`, then `composer dump-autoload -o`.
2. foundry_verify.
3. `npx wp-env run cli wp plugin list` shows `wp-publication-archive` active.
4. `npx wp-env run cli wp eval 'echo post_type_exists("publication") ? "ok" : "missing";'` prints `ok`.
5. `git grep -n "includes/" -- lib` shows no remaining template fallback into `includes/`.

**Depends on:** none
**Workstream:** serial

### P0-02: Keys inventory, Clock, and docs/HOOKS.md
**Goal:** Declare every 3.0.1 name and default as a `Keys` constant, add `Clock`, and document every exposed hook, so that later code and tests never write a prefixed literal.
**Files touched:** `includes/class-keys.php`, `includes/class-clock.php`, `docs/HOOKS.md`, `tests/unit/test-keys.php`, `tests/unit/test-clock.php`, `tests/unit/test-hooks-doc.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. `Keys` and `Clock` are leaves: no `use` lines (leaf-files-import-nothing).
- Add exactly these constants (values are the 3.0.1 strings; SPEC §3 P1, §5, §6.4, §6.5, §6.6):
  - Model: `TAX_AUTHOR` `'publication-author'`; `MENU_ICON` `'dashicons-media-document'`.
  - Options: `OPT_SCHEMA` `'wp-publication-archive-core'`; `OPT_ENABLED` `'wp-publication-archive-enabled'`; `SCHEMA_VERSION` `3`.
  - Meta keys: `META_DOC` `'wpa_upload_doc'`; `META_IMAGE` `'wpa-upload_image'`; `META_ALTERNATES` `'wpa-upload_alternates'`; `META_LEGACY_DESC` `'wpa_doc_desc'`.
  - Form fields: `FIELD_DOC` `'wpa_upload_doc'`; `FIELD_IMAGE` `'wpa-upload_image'`; `FIELD_ALTERNATES` `'wpa-alternates'`; `FIELD_NONCE` `'wpa_nonce'`; `NONCE_ACTION` `'wp-publication-archive-save-meta'`; `FIELD_CAT_DROPDOWN` `'wp_pubarch_cat'`.
  - Meta box ids: `META_BOX_DOC` `'publication_uri'`; `META_BOX_ALTERNATES` `'publication_alternates'`; `META_BOX_THUMB` `'publication_thumb'`.
  - Query vars and rewrite tags: `QV_OPEN` `'wppa_open'`; `QV_DOWNLOAD` `'wppa_download'`; `QV_ALT` `'wppa_alt'`; `QV_PAGED` `'wpa-paged'`; `TAG_OPEN` `'%wppa_open%'`; `TAG_DOWNLOAD` `'%wppa_download%'`; `TAG_ALT` `'%wppa_alt%'`.
  - Endpoints: `ENDPOINT_VIEW` `'view'`; `ENDPOINT_DOWNLOAD` `'download'`; `ENDPOINT_ALTVIEW` `'altview'`; `ENDPOINT_ALTDOWN` `'altdown'`; `QUERY_ALT_KEY` `'alt'`; `REWRITE_BASE` `'publication'`; `REWRITE_CATEGORY` `'category'`.
  - Assets and paths: `STYLE_HANDLE` `'wp-publication-archive-frontend'`; `STYLE_PATH` `'assets/css/base.css'`; `ICON_DIR` `'assets/icons/'`; `LANGUAGES_DIR` `'languages'`; `TEMPLATE_DIR` `'templates/classic/'`.
  - REST: `REST_NAMESPACE` `'wp-publication-archive/v1'`; `REST_ROUTE_LINEAGE` `'/eam'`.
  - Template files: `TEMPLATE_LIST` `'template.wppa_publication_list.php'`; `TEMPLATE_DROPDOWN` `'template.wppa_publication_dropdown.php'`; `TEMPLATE_WIDGET` `'template.wppa_widget.php'`; `TEMPLATE_SINGLE` `'single-publication.php'`; `TEMPLATE_ARCHIVE` `'archive-publication.php'`.
  - Legacy class names: `LEGACY_CLASS_ARCHIVE` `'WP_Publication_Archive'`; `LEGACY_CLASS_ITEM` `'WP_Publication_Archive_Item'`; `LEGACY_CLASS_UTILITIES` `'WP_Publication_Archive_Utilities'`; `LEGACY_CLASS_ARCHIVE_WIDGET` `'WP_Publication_Archive_Widget'`; `LEGACY_CLASS_CAT_COUNT_WIDGET` `'WP_Publication_Archive_Cat_Count_Widget'`; `LEGACY_CLASS_RELATED_WIDGET` `'WP_Publication_Archive_Category_Widget'`.
  - Widget id_bases (literals, §6.6): `WIDGET_ARCHIVE_ID_BASE` `'wp_publication_archive_widget'`; `WIDGET_CAT_COUNT_ID_BASE` `'wp_publication_archive_cat_count_widget'`; `WIDGET_RELATED_ID_BASE` `'wp_publication_archive_category_widget'`.
  - Defaults: `DEFAULT_LIST_LIMIT` `10`; `DEFAULT_WIDGET_SUMMARY_LENGTH` `20`; `DEFAULT_ARCHIVE_WIDGET_NUMBER` `5`; `DEFAULT_RELATED_COUNT` `5`.
  - Exposed filters:

    | Constant | Value |
    |---|---|
    | `FILTER_OPEN_URL` | `'wppa_open_url'` |
    | `FILTER_DOWNLOAD_URL` | `'wppa_download_url'` |
    | `FILTER_MASK_URL` | `'wppa_mask_url'` |
    | `FILTER_PUBLICATION_ICON` | `'wppa_publication_icon'` |
    | `FILTER_LIST_LIMIT` | `'wppa_list_limit'` |
    | `FILTER_PUBS_PER_PAGE` | `'wpa-pubs_per_page'` |
    | `FILTER_LIST_TEMPLATE` | `'wppa_list_template'` |
    | `FILTER_DROPDOWN_TEMPLATE` | `'wppa_dropdown_template'` |
    | `FILTER_WIDGET_TEMPLATE` | `'wppa_widget_template'` |
    | `FILTER_SINGLE_TEMPLATE` | `'wppa_single_template'` |
    | `FILTER_ARCHIVE_TEMPLATE` | `'wppa_archive_template'` |
    | `FILTER_LIST_CONTAINER` | `'wppa_publication_list_container'` |
    | `FILTER_TITLE` | `'wpa-title'` |
    | `FILTER_UPLOAD_IMAGE` | `'wpa-upload_image'` |
    | `FILTER_AUTHORS` | `'wpa-authors'` |
    | `FILTER_SUMMARY` | `'wpa-summary'` |
    | `FILTER_KEYWORDS` | `'wpa-keywords'` |
    | `FILTER_CATEGORIES` | `'wpa-categories'` |
    | `FILTER_SUMMARY_LENGTH` | `'wpa-summary-length'` |
    | `FILTER_WIDGET_SUMMARY_LENGTH` | `'wpa-widget-summary-length'` |
    | `FILTER_OPEN_IN_BLANK` | `'wp_pubarch_open_in_blank'` |
    | `FILTER_ENABLED` | `'wp-publication-archive-enabled'` |

  - Exposed action: `ACTION_BOOTED` `'wppa_booted'`.
  - Core filters the plugin applies: `CORE_FILTER_WIDGET_TITLE` `'widget_title'`; `CORE_FILTER_LIST_CATS` `'list_cats'`; `CORE_FILTER_DROPDOWN_CATS` `'wp_dropdown_cats'`; `CORE_FILTER_WIDGET_CATEGORIES_ARGS` `'widget_categories_args'`; `CORE_FILTER_WIDGET_CATEGORIES_DROPDOWN_ARGS` `'widget_categories_dropdown_args'`; `CORE_FILTER_LIST_CATEGORIES` `'wp_list_categories'`; `CORE_FILTER_CONTENT_SAVE_PRE` `'content_save_pre'`.
  - Consumed hooks:

    | Constant | Value |
    |---|---|
    | `HOOK_INIT` | `'init'` |
    | `HOOK_CLI_INIT` | `'cli_init'` |
    | `HOOK_WP_ENQUEUE_SCRIPTS` | `'wp_enqueue_scripts'` |
    | `HOOK_ADMIN_ENQUEUE_SCRIPTS` | `'admin_enqueue_scripts'` |
    | `HOOK_REST_API_INIT` | `'rest_api_init'` |
    | `HOOK_SAVE_POST` | `'save_post'` |
    | `HOOK_TEMPLATE_REDIRECT` | `'template_redirect'` |
    | `HOOK_QUERY_VARS` | `'query_vars'` |
    | `HOOK_POSTS_WHERE` | `'posts_where_request'` |
    | `HOOK_POSTS_JOIN` | `'posts_join_request'` |
    | `HOOK_POSTS_DISTINCT` | `'posts_distinct_request'` |
    | `HOOK_EXCERPT_LENGTH` | `'excerpt_length'` |
    | `HOOK_WIDGETS_INIT` | `'widgets_init'` |
    | `HOOK_TEMPLATE_INCLUDE` | `'template_include'` |
    | `HOOK_ADMIN_NOTICES` | `'admin_notices'` |
    | `HOOK_POST_TYPE_LINK` | `'post_type_link'` |
    | `HOOK_TERM_LINK` | `'term_link'` |
    | `HOOK_TERMS_CLAUSES` | `'terms_clauses'` |
    | `HOOK_ADD_META_BOXES_PUBLICATION` | `'add_meta_boxes_publication'` |

- `includes/class-clock.php` (P8):
  - `interface Clock { public function now(): int; public function format( string $format, int $timestamp ): string; }`;
  - `final class SystemClock`: `now()` returns `time()`; `format()` returns `wp_date( $format, $timestamp )`, or `''` if that returns false;
  - `final class FixedClock( int $now )`: `format()` uses `wp_date()` when `function_exists( 'wp_date' )` and `gmdate()` otherwise, for host unit tests.
- `docs/HOOKS.md` (§6.4):
  - tables for exposed filters, exposed actions, core hooks the plugin applies, consumed hooks, and hooks consumed from the DAM (added in P0-05);
  - a "Removed in 3.1.0" list (D4's three request filters and D11's `admin_notices` callback);
  - columns: Hook | `Hooks::` method | Arguments | Since. The method column is `—` until the task that adds the method fills it in. Since is the 3.0.1-era version taken from the `@since`/`@uses` docblocks at `e913681`, or `≤ 3.0.1` when none is given.

**Acceptance tests:**
- `tests/unit/test-keys.php` adds:
  - `test_model_and_options`, `test_meta_keys_are_the_frozen_301_names`, `test_form_fields_nonce_and_meta_boxes`;
  - `test_query_vars_rewrite_tags_and_endpoints`, `test_handles_paths_and_rest`, `test_template_file_names`, `test_legacy_class_names`;
  - `test_widget_id_bases_are_the_301_derived_values`: each equals `preg_replace( '/(wp_)?widget_/', '', strtolower( <LEGACY_CLASS_*> ) )`;
  - `test_exposed_filters_and_actions`, `test_core_and_consumed_hooks`, `test_defaults`.
- `tests/unit/test-clock.php`: `test_system_clock_now_is_current`, `test_fixed_clock_returns_its_time`, `test_fixed_clock_formats_the_epoch` (`'Y-m-d'` gives `'1983-11-09'`).
- `tests/unit/test-hooks-doc.php`: `test_every_exposed_and_core_hook_is_documented`. Every `FILTER_*`, `ACTION_*` and `CORE_FILTER_*` value appears in backticks in `docs/HOOKS.md` (read through `Keys` reflection).

**Out of scope:** Using any of these constants in production code. `Hooks` methods. Changing `lib/`.
**Verification:** foundry_verify.
**Depends on:** P0-01
**Workstream:** serial

### P0-03: Flags, Hooks, Plugin and Assets; front-end stylesheet moves to assets/css/base.css
**Goal:** Stand up the template composition root, with `Plugin::boot()` loading the 3.0.1 runtime and `Assets` owning the 3.0.1 front-end stylesheet handle.
**Files touched:**
- services: `includes/class-flags.php`, `includes/class-hooks.php`, `includes/class-plugin.php`, `includes/class-assets.php`;
- stylesheet: `assets/css/base.css`, `includes/front-end.css` (deleted);
- runtime: `wp-publication-archive.php`, `lib/class.wp-publication-archive.php`, `lib/class.wp-publication-archive-loader.php`;
- docs: `docs/HOOKS.md`;
- tests: `tests/unit/test-plugin.php`, `tests/unit/test-hooks-doc.php`, `tests/integration/test-flags.php`, `tests/integration/test-hooks.php`, `tests/integration/test-plugin.php`, `tests/integration/test-assets.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply (options only in `Flags`, `add_*` only in `Plugin`, hook firing only in `Hooks`).
- `Flags( Clock $clock )`: `enabled( string $context = 'default' ): bool` returns `Hooks::filter_enabled( (bool) get_option( Keys::OPT_ENABLED, true ), $context )`. The default is true (P20, §5.2), and per-site `get_option` is used (Decisions).
- `Hooks`: private constructor; `filter_enabled( bool, string ): bool`; `booted( Plugin ): void` (SPEC §6.4 "Added").
- `Plugin` has the template's shape (ADR 0001): idempotent `boot()`, `instance()`, private constructor, `register_hooks()`/`unregister_hooks()` through `add_hook()`, `replace()` for tests only, and one accessor per service.
  - Services now: `clock` (`SystemClock`), `flags`, `assets`. Keep `wp_cache_add_global_groups( Keys::CACHE_GROUP )`.
  - `boot()` registers hooks, then calls `\WP_Publication_Archive_Loader::load()` (transitional until P0-15), then `Hooks::booted()`.
  - Static `activate()`/`deactivate()` delegate to the loader's for now. The bootstrap now calls `\WPPA\Plugin::boot()` and registers activation and deactivation against `array( Plugin::class, 'activate' | 'deactivate' )`.
- `Assets( Flags $flags )`:
  - `register()` registers `Keys::STYLE_HANDLE` → `plugins_url( Keys::STYLE_PATH, <plugin file> )`, deps `array()`, ver `Keys::ASSET_VERSION`, media `'all'`;
  - `enqueue_front()` returns early when `! $this->flags->enabled( 'assets' )`, then enqueues the handle. This is the only flag-gated callback (P20);
  - hooked: `wp_enqueue_scripts` → `register` (10) and `enqueue_front` (11); `admin_enqueue_scripts` → `register` (10).
- `assets/css/base.css` (§4.1, §6.7):
  - begins with a `/*!` lineage comment, then `:root { --eam-epoch: 19831109; }`;
  - then every rule of 3.0.1 `includes/front-end.css`, verbatim. Delete `includes/front-end.css`.
- `lib/class.wp-publication-archive.php`: `enqueue_scripts_and_styles()` keeps only its `is_admin()` branch. The front-end stylesheet is now `Assets`'s job under the same handle, so theme dequeues keep working.
- `docs/HOOKS.md`: fill in the method column for `filter_enabled` and `booted`.

**Acceptance tests:**
- `tests/integration/test-flags.php`: `test_enabled_defaults_to_true_when_option_absent`, `test_enabled_reads_the_option`, `test_enabled_is_filterable_with_context`.
- `tests/integration/test-hooks.php`: `test_filter_enabled_passes_value_and_context`, `test_booted_passes_the_plugin`.
- `tests/unit/test-plugin.php`: `test_is_final_with_a_private_constructor`, `test_boot_and_instance_are_static`, `test_instance_throws_before_boot`.
- `tests/integration/test-plugin.php`:
  - `test_boot_is_idempotent`, `test_unregister_and_register_round_trip`;
  - `test_replace_swaps_a_service_and_rejects_wrong_types`, `test_replace_rejects_unknown_service`;
  - `test_booted_action_fired`, `test_boot_loads_the_301_runtime`.
- `tests/integration/test-assets.php`:
  - `test_front_style_registered_under_301_handle_with_asset_version`;
  - `test_front_style_enqueued_by_default`, `test_front_style_not_enqueued_when_disabled`;
  - `test_base_css_contains_the_301_rules` (`.publication_title` present).
- `tests/unit/test-hooks-doc.php` adds `test_every_documented_method_exists`: every `Hooks::name` in HOOKS.md is a static method on `WPPA\Hooks`.

**Out of scope:** Cli, Rest (P0-04). Moving any other 3.0.1 behaviour out of `lib/`. The admin (Thickbox) enqueue stays in `lib/` until P0-10.
**Verification:**
1. foundry_verify.
2. `npx wp-env run cli wp eval 'var_dump( wp_style_is( "wp-publication-archive-frontend", "registered" ) );'` after `do_action('wp_enqueue_scripts')` returns true. (This string is a shell argument, not a scanned file.)

**Depends on:** P0-02
**Workstream:** serial

### P0-04: Cli doctor, REST lineage route, humans.txt, docs, the v3 fixture and smoke tests
**Goal:** Finish the template scaffold (doctor, `/eam`, attribution and docs) and add the 3.0.1-shaped fixture that every later test uses.
**Files touched:**
- services: `includes/class-cli.php`, `includes/class-rest.php`, `includes/class-plugin.php`, `includes/class-flags.php`;
- attribution and docs: `humans.txt`, `docs/CONTRIBUTING.md`, `docs/adr/0001-composition-root.md`, `docs/HOOKS.md`;
- fixture: `tests/fixtures/class-v3-site.php`;
- tests: `tests/unit/test-cli.php`, `tests/unit/test-keys.php`, `tests/integration/test-cli.php`, `tests/integration/test-rest.php`, `tests/integration/test-v3-site.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply (wp-cli-confined: `WP_CLI` only in `class-cli.php`/`class-plugin.php`).
- `Cli( Flags $flags )`: `doctor( array $args, array $assoc_args ): void` is the only public method besides the constructor (P13a). It supports `--format=table|json`, prints `check,status,message`, and calls `\WP_CLI::halt( 1 )` if any row is `fail`.
  - Rows, in this order: `lineage` (pass; `Keys::LINEAGE` plus the epoch date), `version` (pass; `Keys::VERSION`), `php` and `wp` (pass iff at or above `Keys::MIN_PHP`/`Keys::MIN_WP`), `post_type_registered` (`post_type_exists( Keys::POST_TYPE )`), and `rewrite_rules_present`.
  - `rewrite_rules_present` passes iff, for each of the four `ENDPOINT_*`, some key of `Flags::rewrite_rules()` begins (after an optional `^`) with `publication/<endpoint>/`. Build the prefix from `Keys::REWRITE_BASE` and use `strpos(...) === 0`, which is PHP 7.4-safe.
  - Later tasks append `dam` (P1-02), `caps_granted` (P2-02) and `rest_enabled` (P2-03). Do not add them now.
- `Flags::rewrite_rules(): array` returns `get_option( 'rewrite_rules' )`, or `array()` when it is not an array.
- `Rest( Flags $flags )`: the template's route. `GET /wp-json/wp-publication-archive/v1/eam` returns `{ lineage, epoch, date, version }`; `permission_callback` is `__return_true`.
- `Plugin`: construct `Cli` and `Rest`; register `cli_init` → `register_cli`, which calls `\WP_CLI::add_command( Keys::CLI_COMMAND, $this->cli )` when WP-CLI exists, and `rest_api_init` → `rest->register_routes`. Add accessors and `replace()` cases.
- `humans.txt`, `docs/CONTRIBUTING.md` and `docs/adr/0001-composition-root.md`: the template's, renamed for this plugin, with this plugin's commands (CLAUDE.md § Commands). `humans.txt` must contain `Eric A. Mann` and `1983-11-09`.
- `tests/fixtures/class-v3-site.php`: `final class V3_Site` in `WPPA\Tests\Fixtures` (P16, SPEC §8 Phase 0 item 1).
  - `public static function raw_meta( int $post_id, string $key, $value ): void`: inserts into `$wpdb->postmeta` directly (serialising arrays with `maybe_serialize`), then `wp_cache_delete( $post_id, 'post_meta' )`. This bypasses registered sanitize callbacks, which arrive in P2-04.
  - `public static function create( \WP_UnitTest_Factory $factory ): array` builds the fixture and returns keyed IDs and values. All publications are `publish`, with ASCII titles and a short `post_content`; `post_date` equals `post_date_gmt`, as listed below. Every meta value is written with `raw_meta()`.

  | Key | Title | Date | Meta and terms |
  |---|---|---|---|
  | `attached` | `Attached Report` | 2013-01-07 12:00:00 | `META_DOC` = URL of a PDF attachment created with `$factory->attachment->create_upload_object( DIR_TESTDATA . '/images/wordpress-gsoc-flyer.pdf' )`; if that file is missing, write a small `.pdf` into `get_temp_dir()` and upload that. Return the attachment as `attachment_id` and its URL as `attachment_url` |
  | `pipe` | `Pipe Report` | 2013-01-06 | `META_DOC` = `'https|'` + a second PDF attachment's URL without its `scheme://`; return that attachment as `pipe_attachment_id` |
  | `alternates` | `Alternates Report` | 2013-01-05 | `META_DOC` = `attachment_url`; two `META_ALTERNATES` rows: `array( 'description' => 'English', 'url' => attachment_url )` and `array( 'description' => '<script>alert(1)</script>', 'url' => attachment_url )` |
  | `thumbnail` | `Thumbnail Report` | 2013-01-04 | `META_DOC` = `attachment_url`; `META_IMAGE` = URL of an image attachment (`DIR_TESTDATA . '/images/canola.jpg'`), returned as `image_attachment_id` |
  | `categorised` | `Categorised Report` | 2013-01-03 | `META_DOC` = `attachment_url`; category `Reports` (slug `reports`, returned as `category_id`) and `TAX_AUTHOR` term `Jane Doe` (slug `jane-doe`, returned as `author_term_id`) |
  | `slug_view` | `View` | 2013-01-02 | `post_name` `view`; `META_DOC` = `attachment_url` |
  | `slug_download` | `Download` | 2013-01-01 | `post_name` `download`; `META_DOC` = `attachment_url` |

  - `public static function reset_link_state(): void` is used by characterisation tests in `set_up()`. It is a no-op unless `Plugin::instance()` has a `rewrites()` accessor that returns an object with a `disarm()` method, in which case it calls it (see P0-08 and P2-01). Use `method_exists` checks so that it works before, during and after the restructure.
- The three docs and `humans.txt` are not code; keep them short.

**Acceptance tests:**
- `tests/unit/test-cli.php`: `test_doctor_is_the_only_public_subcommand`.
- `tests/integration/test-cli.php` (uses the WP-CLI shims):
  - `test_doctor_rows_are_the_phase_0_rows_in_order`;
  - `test_doctor_passes_on_this_site`;
  - `test_rewrite_rules_row_fails_when_endpoint_rules_missing`: `pre_option_rewrite_rules` returns `array()`, and `halt(1)` is observed.
- `tests/integration/test-rest.php`: `test_lineage_route_returns_lineage`, `test_lineage_route_is_public`.
- `tests/integration/test-v3-site.php`:
  - `test_fixture_creates_seven_publications`;
  - `test_pipe_value_is_stored_raw` (starts `https|`);
  - `test_alternates_has_two_rows_including_script`;
  - `test_slug_view_and_slug_download_exist`.
- `tests/unit/test-keys.php` adds `test_humans_txt_credits_the_template_author` and `test_base_stylesheet_declares_the_eam_epoch_custom_property`.

**Out of scope:** The `dam`, `caps_granted` and `rest_enabled` doctor rows. DAM tooling. Changing 3.0.1 behaviour.
**Verification:**
1. foundry_verify.
2. `npx wp-env run cli wp publication-archive doctor` exits 0 and shows the six rows.
3. `curl -s http://localhost:8888/wp-json/wp-publication-archive/v1/eam` returns the lineage JSON (use the port wp-env printed).

**Depends on:** P0-03
**Workstream:** serial

### P0-05: The DAM in wp-env
**Goal:** Place the DAM at the pinned ref next to this plugin in both wp-env environments, load it in the test suite, and prove its contract, with a flag to run without it.
**Files touched:**
- scripts: `bin/fetch-dam.sh`, `bin/wp-env.conf`, `bin/setup-wp-env.sh`, `bin/test.sh`;
- test bootstrap and stubs: `tests/bootstrap.php`, `phpstan/stubs/vip-dam.php`, `phpstan/stubs/README.md`;
- names and docs: `includes/class-keys.php`, `docs/HOOKS.md`, `docs/CONTRIBUTING.md`;
- tests: `tests/unit/test-keys.php`, `tests/integration/dam/test-dam-contract.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. DAM symbols may appear in `tests/` and `phpstan/stubs/` only (dam-symbols-confined covers `includes/`, `templates/` and the bootstrap).
- `bin/wp-env.conf` adds `DAM=1`, `DAM_REPO="git@github.a8c.com:mrchriswdixon/vip-digital-asset-manager.git"` and `DAM_REF="9d7f1667608eb0f1cd537351546d2e4d505e8202"` (§7.4, verbatim).
- `bin/fetch-dam.sh` (`set -euo pipefail`, sources the conf, idempotent):
  1. Clone or fetch `DAM_REPO` into `.cache/vip-digital-asset-manager` and check out `DAM_REF` detached.
  2. Merge the mapping `"wp-content/plugins/vip-digital-asset-manager": "./.cache/vip-digital-asset-manager"` into `.wp-env.override.json` under `mappings`, creating the file if needed and preserving every other key. Use `node -e` or `php -r`.
  3. Print the DAM version taken from the `Version:` header of `.cache/vip-digital-asset-manager/index.php`.
- `bin/setup-wp-env.sh`: when `.cache/vip-digital-asset-manager` exists, `wp plugin activate vip-digital-asset-manager` in both `cli` and `tests-cli`.
- `bin/test.sh` (§7, plus the `nodam` decision): `WPPA_DAM` in the environment overrides `DAM` from the conf.
  - When the DAM is on: fail with exit 1 and the message `DAM not fetched: run bash bin/fetch-dam.sh (needs SSH access to github.a8c.com)` if `.cache/vip-digital-asset-manager/index.php` is missing. Otherwise run `WPPA_TEST_DAM=1 vendor/bin/phpunit --exclude-group nodam "$@"` in `tests-cli`.
  - When it is off: run `vendor/bin/phpunit --exclude-group dam "$@"` without `WPPA_TEST_DAM`.
  - Keep the "wp-env is not running" check.
- `tests/bootstrap.php`:
  - with `getenv( 'WPPA_TEST_DAM' ) === '1'`, the `muplugins_loaded` callback first requires `WP_PLUGIN_DIR . '/vip-digital-asset-manager/index.php'`, throwing `\RuntimeException` if it is missing, then loads this plugin;
  - otherwise the DAM is never loaded. Never skip `dam` tests from inside a test.
- `phpstan/stubs/vip-dam.php` declares, in namespace `VIP\DAM`, the classes `Embargo_Guard` (`is_hidden( int $id ): bool`, `placeholder_url(): string`), `Lifecycle` and `Usage_Index` (`get_usage( int $id )`). Copy the exact signatures, static or instance, from the pinned DAM's `inc/class-embargo-guard.php`, `inc/class-lifecycle.php` and `inc/class-usage-index.php`, and record which in the log.
- `Keys` adds `HOOK_DAM_INDEXED_IDS` `'vip_dam_indexed_attachment_ids'`, `DAM_PLUGIN_SLUG` `'vip-digital-asset-manager'` and `DAM_PLUGIN_FILE` `'vip-digital-asset-manager/index.php'`. `docs/HOOKS.md` gains "Consumed from the DAM".
- `docs/CONTRIBUTING.md`: a DAM section covering fetch, access requirement, `WPPA_DAM=0` and the `dam`/`nodam` groups.
- Do not add the `dam` doctor row (P1-02).

**Acceptance tests:**
- `tests/unit/test-keys.php` adds:
  - `test_wp_env_conf_pins_the_dam` (conf contains `DAM=1` and the 40-hex `DAM_REF`);
  - `test_dam_names`.
- `tests/integration/dam/test-dam-contract.php` (`@group dam`):
  - `test_embargo_guard_is_hidden_exists_with_one_parameter`;
  - `test_embargo_guard_placeholder_url_exists_with_no_parameters`;
  - `test_usage_index_get_usage_exists_with_one_parameter`;
  - `test_lifecycle_class_exists`;
  - `test_dam_applies_the_indexed_attachment_ids_filter`: scan the DAM's `inc/` PHP files under `WP_PLUGIN_DIR` for an `apply_filters(` call naming `Keys::HOOK_DAM_INDEXED_IDS`.

**Out of scope:** `Dam_Bridge` and any bridge behaviour (Phase 1). The `dam` doctor row.
**Verification:**
1. `bash bin/fetch-dam.sh`. If it fails for lack of github.a8c.com access, `foundry_task_block` with that reason.
2. `npx wp-env start` (a restart applies the override).
3. `npx wp-env run cli wp plugin list` shows both plugins active.
4. foundry_verify, which runs `composer test` with the DAM, so the contract tests run.
5. `WPPA_DAM=0 composer test` passes.
6. `npx wp-env run cli wp publication-archive doctor` exits 0 with the DAM active.

**Depends on:** P0-04
**Workstream:** serial

### P0-06: Characterisation — URLs, link generators and template location
**Goal:** Pin 3.0.1 routing, link generation and theme-template location in integration tests before anything moves.
**Files touched:**
- tests: `tests/integration/test-characterisation-routing.php`, `tests/integration/test-characterisation-templates.php`;
- fixture theme: `tests/fixtures/theme/characterisation-theme/style.css`, plus the five override files `template.wppa_publication_list.php`, `template.wppa_publication_dropdown.php`, `template.wppa_widget.php`, `single-publication.php`, `archive-publication.php` in that directory;
- `phpcs.xml.dist`, only if `WordPress.Files.FileName` fires on the fixture theme.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply to `tests/` (names-in-keys-only, no-hardcoded-versions). Build every query-var URL with `Keys` constants and `add_query_arg()`.
- SPEC §8 Phase 0 item 3. These tests must pass unchanged through P0-15. So they touch plugin behaviour only through the 3.0.1 public API (`\WP_Publication_Archive::get_open_link()` and the rest, `\WP_Publication_Archive::custom_rewrites()`, `\WP_Publication_Archive::get_image()`), `do_shortcode()`, `the_widget()` with the 3.0.1 class names, core functions and `Keys`.
- `set_up()`:
  - create the fixture with `V3_Site::create( self::factory() )` and call `V3_Site::reset_link_state()`;
  - `$this->set_permalink_structure( '/%postname%/' )`, then `\WP_Publication_Archive::custom_rewrites()` and `flush_rewrite_rules( false )`, so the plugin's rules are present.
- Pin what 3.0.1 actually does. Where the output looks wrong, pin it anyway and say so in a comment naming the D-item:
  - the `post_type_link` hijack after the first link is generated is D8 (Decisions);
  - plain permalinks produce `?view=yes` (Spec issues).
- For `/publication/view/`, request the publication with slug `view` and pin whether 3.0.1 resolves it to that publication. Record the observed result in the progress-log entry, because P2-05 (D5) depends on it.
- Point the theme at `tests/fixtures/theme/characterisation-theme` with `register_theme_directory()` and `switch_theme()`, and restore the original theme in `tear_down()`. Each override file prints a unique marker (`CHAR-LIST`, `CHAR-DROPDOWN`, `CHAR-WIDGET`, `CHAR-SINGLE`, `CHAR-ARCHIVE`).
- The list override reproduces a 3.0.1-style theme copy: it calls `extract( $wppa_container );` and prints `CHAR-LIST <count($publications)>`. This proves D15 compatibility (§6.5). `WordPress.PHP.DontExtract` fires in `tests/`, so ignore it on that line with `-- reason: 3.0.1 theme copy fixture (SPEC §6.5)`.
- If `WordPress.Files.FileName` fires on the fixture theme, exclude `tests/fixtures/theme/*` from that sniff only, with an XML comment citing SPEC §6.5.

**Acceptance tests:**
- `tests/integration/test-characterisation-routing.php`:
  - `test_single_publication_url_resolves`;
  - `test_view_url_sets_open_query_var`, `test_download_url_sets_download_query_var`;
  - `test_altview_url_sets_open_and_alt`, `test_altdown_url_sets_download_and_alt` (key `English`);
  - `test_category_url_lists_publications_in_category` (`post_type` = `Keys::POST_TYPE`, `category_name` = `reports`);
  - `test_open_query_form_resolves`, `test_download_query_form_resolves`;
  - `test_slug_view_publication_at_publication_view`, `test_slug_download_publication_at_publication_download`, `test_view_endpoint_for_other_slug_opens_other_slug`;
  - `test_open_link_matches_301`, `test_download_link_matches_301` (`site_url()` + `/publication/<endpoint>/<slug>`, no trailing slash);
  - `test_alternate_open_link_matches_301`, `test_alternate_download_link_matches_301` (`/<key>` appended);
  - `test_open_link_with_plain_permalinks_matches_301`;
  - `test_permalink_after_link_generation_matches_301`: generate one publication's open link, then `get_permalink()` of another gives `site_url()/publication/<Keys::QV_OPEN>/<slug>` (D8 pin).
- `tests/integration/test-characterisation-templates.php`:
  - `test_theme_list_template_overrides_bundled`, `test_theme_dropdown_template_overrides_bundled`, `test_theme_widget_template_overrides_bundled`;
  - `test_theme_single_template_is_located`, `test_theme_archive_template_is_located`: run the `template_include` filter after `go_to()`;
  - `test_bundled_list_template_used_without_theme_override`;
  - `test_theme_copy_of_301_template_using_extract_still_renders`.

**Out of scope:** Any change to production code or `lib/`. Shortcode and widget output strings (P0-07).
**Verification:** foundry_verify, which runs `composer test` with the DAM. Also run `WPPA_DAM=0 composer test --filter Characterisation`; both must pass.
**Depends on:** P0-05
**Workstream:** serial

### P0-07: Characterisation — shortcode and widget output
**Goal:** Pin the 3.0.1 shortcode (list, dropdown, filters, pagination, messages) and widget output as whitespace-normalised expected strings.
**Files touched:** `tests/fixtures/class-v3-expected-output.php`, `tests/integration/test-characterisation-output.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply to `tests/`.
- SPEC §8 Phase 0 item 3: expected strings are stored as literals, and later D-tasks edit them only by naming the D-item.
- `final class V3_Expected_Output` (`WPPA\Tests\Fixtures`) provides:
  - public string constants `LIST_ALL`, `LIST_LIMIT_2_PAGE_2`, `DROPDOWN_ALL`, `LIST_CATEGORY_REPORTS`, `LIST_AUTHOR_JANE_DOE`, `LIST_UNKNOWN_CATEGORY`, `WIDGET_ARCHIVE`, `WIDGET_CAT_COUNT_LIST`, `WIDGET_CAT_COUNT_DROPDOWN`, `WIDGET_RELATED`;
  - `normalise( string $html ): string`: collapse whitespace runs to one space, remove whitespace between `>` and `<`, trim;
  - `expand( string $expected, array $fixture ): string`, which replaces these placeholders:

    | Placeholder | Replaced with |
    |---|---|
    | `{home}` | `untrailingslashit( home_url() )` |
    | `{site}` | `site_url()` |
    | `{id:<key>}` | the fixture ID for `<key>` |
    | `{slug:<key>}` | the fixture slug for `<key>` |
    | `{url:<key>}` | the fixture URL for `<key>` |
    | `{icon:<mime>}` | `\WP_Publication_Archive::get_image( '<mime>' )`, so the P0-09 icon move keeps these strings valid |
    | `{nonce}` | never; do not pin nonces |

- Capture procedure: render on the current (3.0.1) code, then write each expected string with placeholders so it is independent of the test site's host and post IDs. Do not fix anything. Title links for the second and later list items show the D8 hijack (`{site}/publication/wppa_open/{slug:…}`); pin them and comment "D8".
- Widget args: `before_widget` `<div class="widget">`, `after_widget` `</div>`, `before_title` `<h2>`, `after_title` `</h2>`, with explicit instances:
  - archive: title `Publications`, number `3`, orderby `date`;
  - category count: title `Categories`, one list instance and one dropdown instance, both with count `1`;
  - related: title `Related`, count `3`.

  Call `the_widget( '<3.0.1 class name>', $instance, $args )` inside `ob_start()`, with the class names written as string literals (they are not prefixed names).
- Pagination: create a page, `go_to()` its permalink with `add_query_arg( Keys::QV_PAGED, 2, … )`, make it the global post (`$GLOBALS['post']` is allowed in tests), and render `[<Keys::SHORTCODE> limit="2"]`.
- Each test calls `V3_Site::create()` and `V3_Site::reset_link_state()` in `set_up()`.
- The strings must be identical with and without the DAM loaded.

**Acceptance tests:** In `tests/integration/test-characterisation-output.php`:
- `test_list_shortcode_output_matches_301`, `test_list_shortcode_page_2_matches_301`, `test_dropdown_shortcode_output_matches_301`;
- `test_category_filter_output_matches_301`, `test_author_filter_output_matches_301`, `test_unknown_category_message_matches_301`;
- `test_archive_widget_output_matches_301`, `test_category_count_widget_list_output_matches_301`, `test_category_count_widget_dropdown_output_matches_301`, `test_related_widget_output_matches_301`.

**Out of scope:** Production code and `lib/` changes. Delivery responses (they exit; Phase 1 tests them).
**Verification:** foundry_verify, plus `WPPA_DAM=0 composer test --filter Characterisation`.
**Depends on:** P0-06
**Workstream:** serial

### P0-08: Restructure (1/8) — Post_Type, Rewrites, Upgrade and i18n
**Goal:** Move CPT and taxonomy registration, rewrite rules, query vars, link generation, the schema upgrade and textdomain loading into `WPPA` services registered by `Plugin`, preserving behaviour and closing D4.
**Files touched:**
- services: `includes/class-post-type.php`, `includes/class-rewrites.php`, `includes/class-upgrade.php`, `includes/class-plugin.php`, `includes/class-flags.php`, `includes/class-hooks.php`;
- docs: `docs/HOOKS.md`;
- runtime: `lib/class.wp-publication-archive.php`, `lib/class.wp-publication-archive-loader.php`, every remaining `lib/*.php` (text domain only);
- translations: `languages/wp-publication-archive.pot` (from `lang/wp_pubarch_translate.po`), `languages/wp-publication-archive-de_DE.po`, `languages/wp-publication-archive-de_DE.mo`; delete `lang/` (including the locale-less `wp_pubarch_translate.mo`) and `images/cabinet.png`;
- tests: `tests/integration/test-post-type.php`, `tests/integration/test-rewrites.php`, `tests/integration/test-upgrade.php`, `tests/integration/test-plugin.php`, `tests/integration/test-i18n.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. This task is where they bite first: no `global $`, no option calls outside `Flags`, no `add_*` outside `Plugin`, no `apply_filters` outside `Hooks`, no prefixed literals outside `Keys`, no `images/` or `lang/` references, no `date(`.
- SPEC §8 Phase 0 item 4: behaviour is preserved, including open defects, except where Decisions say otherwise (D4 closed here; `menu_icon`). P0-06 and P0-07 must pass unchanged.
- `Post_Type()`: `register(): void` on `init` registers the `TAX_AUTHOR` taxonomy, then the `POST_TYPE` CPT, with the 3.0.1 arguments from `e913681:lib/class.wp-publication-archive.php` (`register_publication`, `register_author`) verbatim, except:
  - `menu_icon => Keys::MENU_ICON`, because `images/cabinet.png` is deleted (D16 part, Decisions);
  - `register_meta_box_cb` stays pointed at `array( \WP_Publication_Archive::class, 'pub_meta_boxes' )` until P0-10.
- `Rewrites( Flags $flags )`:
  - `register(): void` adds the three rewrite tags and the eight 3.0.1 rules verbatim, all `'top'` (D5 untouched), building strings from `Keys`;
  - `query_vars( array $vars ): array` appends `Keys::QV_PAGED`;
  - `link( int $publication_id, string $endpoint, ?string $permalink = null, ?string $key = null ): string` is 3.0.1 `get_link()`: plain permalinks → `add_query_arg( $endpoint, 'yes', $permalink )` plus `add_query_arg( Keys::QUERY_ALT_KEY, $key )`; pretty → `site_url() . '/publication/' . $endpoint . '/' . basename( $permalink )` plus `'/' . $key`. The structure comes from `Flags::permalink_structure()`;
  - `open_link( int $id )`, `download_link( int $id )`, `alternate_open_link( int $id, ?string $key )` and `alternate_download_link( int $id, ?string $key )` wrap it.
  - D8 faithful port (Decisions, P3):
    - When `link()` computes the permalink itself, it sets a private `$suspended` flag in `try`/`finally` around `get_permalink( $id )`, and afterwards sets a private `$armed = true`.
    - `filter_post_type_link( string $permalink, \WP_Post $post ): string` returns `$permalink` unless `$armed && ! $suspended && Keys::POST_TYPE === $post->post_type`. In that case it returns `$this->link( $post->ID, Keys::QV_OPEN, $permalink )`.
    - `disarm(): void` sets `$armed = false`; it exists for tests (`V3_Site::reset_link_state()`).
- `Upgrade( Flags $flags )`:
  - `maybe_upgrade(): void` is the 3.0.1 bootstrap block with 3.0.1 semantics, including the swapped branches:
    - schema `null` or `< Keys::SCHEMA_VERSION` → `run( $from )`, `Flags::set_schema_version( 3 )`, `flush_rewrite_rules()`;
    - otherwise → `Flags::add_schema_version( 3 )` (`add_option` with autoload `'no'`).
  - `run( $from ): void` is 3.0.1 `upgrade()` (case 2: move `META_LEGACY_DESC` into empty `post_content` through `Hooks::content_save_pre()`).
  - `Plugin::boot()` calls `maybe_upgrade()` directly at load time: D9 is preserved until P2-06.
- `Flags` adds `schema_version(): ?int`, `set_schema_version( int ): void`, `add_schema_version( int ): void` and `permalink_structure(): string`. `Hooks` adds `content_save_pre( string ): string`.
- `Plugin`:
  - constructs `post_type`, `rewrites` and `upgrade`, with accessors and `replace()` cases;
  - registers on `init` (10, in this order) `post_type->register`, `rewrites->register` and `load_textdomain`; `query_vars` → `rewrites->query_vars`; `post_type_link` → `rewrites->filter_post_type_link` (10, 2);
  - registers `admin_notices` → `fopen_notice` only when `! (bool) ini_get( 'allow_url_fopen' )`. This is D11, preserved; the output uses `wp_kses_post()` and the new text domain;
  - `load_textdomain(): void` calls `load_plugin_textdomain( Keys::TEXT_DOMAIN, false, dirname( plugin_basename( WP_PUB_ARCH_DIR . 'wp-publication-archive.php' ) ) . '/' . Keys::LANGUAGES_DIR )`. No translation function may run before `init` (WordPress 6.7 just-in-time notice);
  - `activate()` registers the post type and rewrites, then flushes rewrite rules; `deactivate()` flushes. The loader's versions are no longer used for these.
  - D4 (closed here, Decisions): the `posts_where_request` callback is not registered, and nothing registers `posts_join_request` or `posts_distinct_request`.
- `lib/class.wp-publication-archive.php`:
  - `register_publication`, `register_author`, `custom_rewrites`, `query_vars`, `get_link` and the four link methods, and `upgrade` delegate through `\WPPA\Plugin::instance()->…()`;
  - `publication_link()` delegates to `rewrites()->link( $pub->ID, Keys::QV_OPEN, $permalink )`;
  - `search()`, `search_join()` and `search_distinct()` return their first argument (D4).
- The loader drops the `init`, `query_vars`, `posts_where_request` and activation wiring and the schema block. In every remaining `lib/` file, replace the text domain `'wp_pubarch_translate'` with `'wp-publication-archive'`.
- Translations: `git mv` so msgids are preserved; update each `.po` header's text domain if present. Recompiling the `.mo` is not needed.
- `docs/HOOKS.md`: fill in methods; mark D4's three request filters as removed.

**Acceptance tests:**
- `tests/integration/test-post-type.php`:
  - `test_publication_post_type_registered_with_301_args`: public, has_archive, `menu_position` 20, supports title and editor, taxonomies category and post_tag, `capability_type` post;
  - `test_d16_menu_icon_is_a_dashicon`;
  - `test_author_taxonomy_registered_with_301_args`: `query_var` false, `rewrite` false.
- `tests/integration/test-rewrites.php`:
  - `test_eight_301_rules_registered_top`, `test_query_vars_include_paged`;
  - `test_link_matches_301_with_pretty_permalinks`, `test_link_matches_301_with_plain_permalinks`;
  - `test_permalink_is_hijacked_after_link_generation_until_d8`, `test_disarm_clears_the_hijack`.
- `tests/integration/test-upgrade.php`:
  - `test_upgrade_from_2_moves_legacy_description_into_content`;
  - `test_absent_schema_is_set_to_3`;
  - `test_current_schema_is_left_as_is`.
- `tests/integration/test-plugin.php` adds:
  - `test_d4_no_plugin_callback_on_search_request_filters`: iterate `$GLOBALS['wp_filter']` for the three `HOOK_POSTS_*` names and assert no callback whose class is `WPPA\*` or a 3.0.1 class. This fails on the pre-task code;
  - `test_d4_legacy_search_methods_return_their_argument`;
  - `test_model_and_routing_hooks_registered`.
- `tests/integration/test-i18n.php`:
  - `test_german_translation_loads_from_languages_dir`: filter `plugin_locale` to `de_DE`, `unload_textdomain()`, call `Plugin::instance()->load_textdomain()`, then assert `is_textdomain_loaded( Keys::TEXT_DOMAIN )` and that one known msgid from the `.po` translates;
  - `test_no_lang_directory_remains`.

**Out of scope:** Delivery, meta boxes, `Publication_Item`, shortcode, templates, widgets and `Categories`: they stay in `lib/`. Changing any rewrite regex (D5 is P2-05). Moving the upgrade to `init` (P2-01/P2-06).
**Verification:**
1. `composer dump-autoload -o`, then foundry_verify.
2. `WPPA_DAM=0 composer test`.
3. `npx wp-env run cli wp rewrite flush --hard && npx wp-env run cli wp publication-archive doctor` exits 0.

**Depends on:** P0-07
**Workstream:** serial

### P0-09: Restructure (2/8) — Delivery, interim Streamer and Icons
**Goal:** Move the view and download endpoints, icon lookup and MIME detection into `WPPA` services, delete `class.mimetype.php`, and move the icons to `assets/icons/`, preserving endpoint behaviour.
**Files touched:**
- services: `includes/class-delivery.php`, `includes/class-streamer.php`, `includes/class-icons.php`, `includes/class-plugin.php`, `includes/class-hooks.php`;
- docs: `docs/HOOKS.md`;
- assets: `assets/icons/` (`git mv images/icons/*.png`; delete `images/`);
- runtime: `lib/class.mimetype.php` (deleted), `lib/class.wp-publication-archive.php`, `lib/class.publication-markup.php`, `lib/class.wp-publication-archive-loader.php`;
- tests: `tests/integration/test-delivery.php`, `tests/integration/test-streamer.php`, `tests/integration/test-icons.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. In particular, `readfile(` only in `class-streamer.php` (raw-file-read-confined), no `wp_remote_head` or `sslverify` (safe-remote-only), and no `ob_clean` restriction yet.
- `Icons()`:
  - `url_for( string $doctype ): string` is 3.0.1 `get_image()`'s switch verbatim, with base `WP_PUB_ARCH_URL . Keys::ICON_DIR`, filtered by `Hooks::publication_icon( $url, $doctype )`;
  - `mime_for( string $name ): string` returns `wp_check_filetype( basename( (string) wp_parse_url( $name, PHP_URL_PATH ) ) )['type']`, or `'application/octet-stream'` when that is false (D16; replaces `mimetype::getType`).
- `Streamer( ?callable $exit = null, ?callable $header = null )`:
  - `passthrough( string $uri, array $headers ): void` is the interim 3.0.1 proxy: emit each header line through the header callable (default `header`), then `ob_clean(); flush(); readfile( $uri );` (D1 and D6 preserved; `phpcs:ignore` lines carry `-- reason: D1 …`), then call the exit callable (default: a closure that `exit`s);
  - this is the only `readfile(` in the plugin.
- `Delivery( Streamer $streamer, Icons $icons, ?callable $exit = null, ?callable $header = null )`:
  - `handle(): void` on `template_redirect` calls `open()` when `get_query_var( Keys::QV_OPEN )` is non-empty, else `download()` when `QV_DOWNLOAD` is. Both port 3.0.1 `open_file()`/`download_file()`:
    - the post comes from `get_post()`;
    - with `QV_ALT` set, the alternate whose `description === urldecode( QV_ALT )`;
    - otherwise `META_DOC` with `http|`/`https|` replaced;
    - `Hooks::open_url`/`download_url`; an empty result returns.
  - When `Hooks::mask_url( true )` is true (3.0.1 default kept until P1-07):
    - `wp_safe_remote_head( $uri )`. This replaces `wp_remote_head(…, sslverify false)` because safe-remote-only is live; note it under Interpretation;
    - build the 3.0.1 header list in 3.0.1 order, with `Content-type` from `$icons->mime_for( basename( $uri ) )`, then `streamer->passthrough()`.
  - Otherwise emit `HTTP/1.1 303 See Other` and `Location: <uri>` through the header callable, then exit.
  - `open()` and `download()` are public so the legacy delegates can call them.
- `Plugin`: construct `icons`, `streamer` and `delivery`, with accessors and `replace()` cases. Register `template_redirect` → `delivery->handle`.
- `lib/`:
  - `open_file()`/`download_file()` delegate to `delivery()->open()`/`download()`; `get_image()` delegates to `icons()->url_for()`;
  - `WP_Publication_Archive_Item` uses `icons()->mime_for()` instead of `new mimetype()`;
  - the loader drops the `template_redirect` wiring and the mimetype require. Delete `lib/class.mimetype.php`.
- `Hooks` adds `open_url`, `download_url`, `mask_url( bool ): bool` and `publication_icon( string, string ): string`; update `docs/HOOKS.md`.

**Acceptance tests:**
- `tests/integration/test-icons.php`:
  - `test_pdf_icon_url_points_at_assets_icons`, `test_unknown_type_uses_unknown_icon`, `test_icon_filter_applies`;
  - `test_d16_mime_for_uses_wp_check_filetype`, `test_every_301_icon_file_exists_under_assets_icons`.
- `tests/integration/test-delivery.php`: inject header and exit callables through `Plugin::instance()->replace( 'delivery', … )`, with the exit callable throwing a test-local exception.
  - `test_no_endpoint_query_var_does_nothing`;
  - `test_view_redirect_mode_emits_303_location`, `test_download_redirect_mode_emits_303_location`;
  - `test_alternate_is_matched_by_description`, `test_pipe_url_is_normalised_before_redirect`.
- `tests/integration/test-streamer.php`: `test_passthrough_emits_headers_then_file_bytes`, using a temp file as `$uri` and capturing output inside the test's own buffer, and `test_passthrough_calls_exit`.

**Out of scope:** D1, D6 and D17 fixes (Phase 1). `Streamer::send()`. `Url_Policy`.
**Verification:**
1. `composer dump-autoload -o`, then foundry_verify.
2. `WPPA_DAM=0 composer test`.
3. `git ls-files images lib/class.mimetype.php` prints nothing.

**Depends on:** P0-08
**Workstream:** serial

### P0-10: Restructure (3/8) — Meta_Boxes and admin assets
**Goal:** Move the three meta boxes, `save_meta` and the admin (Thickbox) enqueue into `WPPA` services, preserving markup and the open D1, D2, D3, D10 and D13 defects.
**Files touched:**
- services: `includes/class-meta-boxes.php`, `includes/class-assets.php`, `includes/class-plugin.php`, `includes/class-post-type.php`;
- docs: `docs/HOOKS.md`;
- runtime: `lib/class.wp-publication-archive.php`, `lib/class.wp-publication-archive-loader.php`;
- tests: `tests/integration/test-meta-boxes.php`, `tests/integration/test-assets.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. Prefixed names inside inline JavaScript or HTML text (not attribute values) must be printed from `Keys` with `esc_js()`/`esc_attr()` (names-in-keys-only). `$_POST` is allowed in `class-meta-boxes.php` (superglobals-confined).
- `Meta_Boxes()`:
  - `add(): void` adds the three boxes with the 3.0.1 ids (`META_BOX_*`), titles, context and priority;
  - `render_doc( \WP_Post $post )`, `render_thumb( \WP_Post $post )` and `render_alternates( \WP_Post $post )` print the 3.0.1 markup and inline Thickbox JavaScript (D13 preserved). The nonce field uses `Keys::FIELD_NONCE` and `Keys::NONCE_ACTION`;
  - `save( int $post_id ): int` has the 3.0.1 `save_meta` semantics: post-type check, nonce check, autosave, `esc_url_raw` (D1 preserved), raw descriptions (D2 preserved), the `<=` loop (D10 preserved).
- Escaping (P7):
  - every output that is not one of the preserved defects is escaped: labels with `esc_html__`, and HTML-bearing help text with `wp_kses_post( __( … ) )`;
  - the D3 `value="…"` echoes of the doc and thumbnail URLs stay raw, with `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- reason: D3 (SPEC §1.1)`. The 3.0.1 leading-space quirk in the thumbnail value is kept;
  - the `$_POST` reads in `save()` carry `phpcs:ignore WordPress.Security.* -- reason: D1`, `D2` or `D10`, as applicable. Keep the ignores minimal and per line.
- `Plugin`: construct `meta_boxes`; register `add_meta_boxes_publication` (`Keys::HOOK_ADD_META_BOXES_PUBLICATION`) → `meta_boxes->add`, `save_post` → `meta_boxes->save` (10, 1), and `admin_enqueue_scripts` → `assets->enqueue_admin` (10, 1). `Post_Type` drops `register_meta_box_cb`: WordPress registers that callback on the same hook, so behaviour is equal.
- `Assets::enqueue_admin( string $hook_suffix = '' ): void` is the 3.0.1 admin branch, enqueuing `media-upload` and `thickbox` scripts and the `thickbox` style. It runs on every admin page, as in 3.0.1 (D13 preserved until P2-07).
- `lib/`: `pub_meta_boxes`, `doc_uri_box`, `doc_thumb_box`, `doc_alternates_box`, `save_meta` and `enqueue_scripts_and_styles` delegate to the services. The loader drops the `save_post` and `init` enqueue wiring.

**Acceptance tests:**
- `tests/integration/test-meta-boxes.php`:
  - `test_three_meta_boxes_added_with_301_ids`;
  - `test_doc_box_prints_field_with_301_id_and_name`;
  - `test_save_requires_valid_nonce`;
  - `test_save_stores_doc_and_image_for_publications`;
  - `test_save_ignores_other_post_types`;
  - `test_save_skips_autosave`.
- `tests/integration/test-assets.php` adds `test_admin_enqueues_thickbox_until_d13`.

**Out of scope:** Any D1, D2, D3, D10 or D13 fix (Phase 1 and Phase 2). `Url_Policy`.
**Verification:** `composer dump-autoload -o`, then foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P0-09
**Workstream:** serial

### P0-11: Restructure (4/8) — Publication_Item and the alias layer
**Goal:** Replace the 3.0.1 `WP_Publication_Archive_Item` with `WPPA\Publication_Item` behind a `class_alias`, preserving output and the open D2, D3 and D7 defects.
**Files touched:**
- classes: `includes/class-publication-item.php`, `includes/legacy/class-aliases.php`, `includes/class-plugin.php`, `includes/class-hooks.php`;
- docs: `docs/HOOKS.md`;
- runtime: `lib/class.publication-markup.php` (deleted), `lib/class.wp-publication-archive-loader.php`;
- tests: `tests/integration/test-publication-item.php`, `tests/integration/test-aliases.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply (no `date(`/`strtotime(`, no `global $`, hooks fired through `Hooks`).
- `Publication_Item`:
  - not final; the same public properties (`$ID`, `$title`, `$date`, `$content`, `$summary`, `$upload_image`, `$uri`, `$filename`, `$alternates`, `$keywords`, `$keyword_array`, `$categories`, `$category_array`, `$authors`, `$author_array`) and `protected $post`;
  - the same public methods, parameters and defaults as `e913681:lib/class.publication-markup.php`, with phpdoc types only and no native types (Decisions);
  - services come through `\WPPA\Plugin::instance()` (Decisions): `rewrites()` for links, `icons()` for the image and mime type;
  - the date uses `get_the_date( 'F j, Y', $this->post )` as the interim D7 behaviour (Decisions);
  - filters fire through `Hooks`: `item_title( $title, $id )`, `item_upload_image( $url, $id )`, `item_authors( $authors, $id )`, `item_summary( $summary, $id )`, `item_keywords( $keywords, $id )`, `item_categories( $categories, $id )`, `open_in_blank( bool )`;
  - escaping (P7): `the_title()`/`the_thumbnail()` echo raw with `-- reason: D3`; `list_downloads()` echoes the description raw with `-- reason: D2`. Every other echo goes through `wp_kses_post()` or `esc_*`. Returned strings keep 3.0.1 markup exactly.
- `Legacy\Aliases` (`final`, `WPPA\Legacy`):
  - `register(): void` calls `class_alias()` for each implemented class, guarded by `! class_exists( $alias, false )`;
  - for now the only entry is `Keys::LEGACY_CLASS_ITEM` → `\WPPA\Publication_Item`; P0-13, P0-14 and P0-15 add the others;
  - `Plugin::boot()` calls it after registering hooks and before `Loader::load()`.
- Delete `lib/class.publication-markup.php` and remove its require from the loader.
- `docs/HOOKS.md`: fill in the item filter methods.

**Acceptance tests:**
- `tests/integration/test-publication-item.php`:
  - `test_item_reads_301_meta_and_normalises_pipe_uri`;
  - `test_get_the_uri_markup_matches_301` (icon via `get_image()`);
  - `test_get_the_authors_includes_the_date`;
  - `test_title_filter_receives_title_and_id`;
  - `test_list_downloads_prints_view_and_download_links`.
- `tests/integration/test-aliases.php`: `test_item_alias_resolves_to_publication_item`, `test_publication_item_is_not_final`.
- P0-06 and P0-07 pass unchanged.

**Out of scope:** D2, D3, D7 and D18 fixes. Templates and shortcode (P0-12).
**Verification:** `composer dump-autoload -o`, then foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P0-10
**Workstream:** serial

### P0-12: Restructure (5/8) — Shortcode, Templates and templates/classic
**Goal:** Move the shortcode handler, template location and the five bundled templates into `WPPA` (`Shortcode`, `Templates`, `templates/classic/`), closing D15 because `extract()` is forbidden.
**Files touched:**
- services: `includes/class-shortcode.php`, `includes/class-templates.php`, `includes/class-plugin.php`, `includes/class-hooks.php`;
- docs: `docs/HOOKS.md`;
- templates: `templates/classic/` (`git mv lib/templates/*`);
- config: `phpcs.xml.dist`, `phpstan.neon.dist`;
- runtime: `lib/class.wp-publication-archive.php`, `lib/class.wp-publication-archive-utilities.php`, `lib/class.publication-widget.php`, `lib/class.wp-publication-archive-category-widget.php`, `lib/class.wp-publication-archive-loader.php`;
- tests: `tests/integration/test-shortcode.php`, `tests/integration/test-templates.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. `templates/` is now covered by hooks-fire-in-hooks-only, no-extract, no-globals (only `$wppa_container` and `$wppa_publications`) and no-wall-clock.
- `Templates()`:
  - `single_template( string $template ): string` and `archive_template( string $template ): string` are 3.0.1 `single_publication`/`publication_archives`, using `get_query_var( 'post_type' )` instead of `global $wp_query`;
  - `find( string $name )` is 3.0.1 `find_template`: stylesheet dir, then template dir, then `WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR`;
  - `locate( string $name ): string` returns `locate_template( $name )`, or the bundled path;
  - `excerpt_length( int $length ): int`: inside a widget scope, returns `Hooks::widget_summary_length( Keys::DEFAULT_WIDGET_SUMMARY_LENGTH )`; otherwise, for publication posts (`get_post()`), returns `Hooks::summary_length( $length )`; otherwise `$length`;
  - `with_widget_summary_length( callable $render ): void` sets the private scope flag in `try`/`finally` around `$render()` (P3; replaces the Related widget's add/remove of `excerpt_length`).
- `Shortcode( Templates $templates )`: `render( $atts ): string` is 3.0.1 `shortcode_handler` with these changes:
  - `shortcode_atts` read explicitly, with no `extract` (D15, Decisions); limit default `Keys::DEFAULT_LIST_LIMIT`, passed through `Hooks::pubs_per_page()` then `Hooks::list_limit()` in 3.0.1 order;
  - page from `absint( get_query_var( Keys::QV_PAGED ) )` (Decisions); the container's `post` from `get_post()`; `global $wppa_container` set, filtered through `Hooks::list_container()`, included through `$this->templates->locate()`, then unset;
  - error messages escape user-supplied values with `esc_html`.
- `templates/classic/` (the five 3.0.1 file names, frozen; §6.5):
  - list and dropdown read `$wppa_container['publications']`, `['total_pubs']`, `['limit']`, `['offset']`, `['paged']` and `['post']` explicitly (D15);
  - the widget template uses `get_post()` rather than `global $post`, and escapes;
  - single and archive use the new text domain;
  - the dropdown's `post_title` echo stays raw with `-- reason: D3`; the list's `$prev`/`$next` use `esc_url()`.
- `phpcs.xml.dist` adds `<file>templates</file>` and excludes `WordPress.Files.FileName` for `templates/classic/*` only. `phpstan.neon.dist` adds `templates`.
- `Plugin`:
  - `add_shortcode( Keys::SHORTCODE, … )`;
  - `template_include` → `templates->single_template` then `templates->archive_template`, at priority 10, in 3.0.1 order;
  - `excerpt_length` → `templates->excerpt_length`.
- `lib/`:
  - `shortcode_handler`, `query_publications` and `custom_excerpt_length` delegate;
  - Utilities' `single_publication`, `publication_archives` and `find_template` delegate, and its constructor no longer adds `template_include` filters;
  - the archive widget locates its template through `templates()->locate()`;
  - the Related widget wraps its loop in `templates()->with_widget_summary_length()` instead of add/remove `excerpt_length`;
  - the loader drops the shortcode and `excerpt_length` wiring.
- `Hooks` adds `pubs_per_page`, `list_limit`, `list_template`, `dropdown_template`, `widget_template`, `single_template`, `archive_template`, `list_container`, `summary_length` and `widget_summary_length`; update `docs/HOOKS.md`.

**Acceptance tests:**
- `tests/integration/test-shortcode.php`:
  - `test_shortcode_registered_with_301_tag`;
  - `test_list_uses_bundled_template`;
  - `test_container_filter_applies`;
  - `test_limit_filters_apply_in_301_order`;
  - `test_page_number_comes_from_the_query_var`;
  - `test_d15_no_extract_in_shortcode_or_bundled_templates`: reads `includes/class-shortcode.php` and `templates/classic/*.php`. It fails on the pre-task `lib/templates/` files.
- `tests/integration/test-templates.php`:
  - `test_single_template_prefers_theme_then_bundled`;
  - `test_archive_template_for_publication_archive`;
  - `test_excerpt_length_applies_summary_filter_for_publications`;
  - `test_widget_summary_scope_sets_and_clears_even_on_exception`.
- P0-06 (including the theme `extract()` copy) and P0-07 pass unchanged.

**Out of scope:** `Categories` and the widgets' own move (P0-13/P0-14). D2/D3 escaping of the dropdown title (P1-08).
**Verification:**
1. `composer dump-autoload -o`, then foundry_verify, then `WPPA_DAM=0 composer test`.
2. `git ls-files lib/templates` prints nothing.

**Depends on:** P0-11
**Workstream:** serial

### P0-13: Restructure (6/8) — Categories, Legacy\Utilities and the category-count widget
**Goal:** Move the Utilities helpers into `Categories` with scoped `term_link`/`terms_clauses` callbacks, add the `WP_Publication_Archive_Utilities` delegate singleton, and move the category-count widget.
**Files touched:**
- services: `includes/class-categories.php`, `includes/class-flags.php`, `includes/class-hooks.php`, `includes/class-plugin.php`;
- legacy and widget: `includes/legacy/class-utilities.php`, `includes/legacy/class-aliases.php`, `includes/widgets/class-category-count-widget.php`;
- docs: `docs/HOOKS.md`;
- runtime: `lib/class.wp-publication-archive-utilities.php` (deleted), `lib/class.wp-publication-archive-cat-count-widget.php` (deleted), `lib/class.wp-publication-archive-loader.php`;
- tests: `tests/integration/test-categories.php`, `tests/integration/test-utilities.php`, `tests/integration/test-category-count-widget.php`, `tests/integration/test-aliases.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply (options only via `Flags`; `global $wpdb` is allowed).
- `Categories( Flags $flags )`: `dropdown_categories( $args = '' )`, `list_categories( $args = '' )`, `get_terms( $taxonomies, $args = array() )`, `filter_terms_by_cpt( $pieces, $tax, $args )` and `filter_category_link( $termlink, $term, $taxonomy )`, with 3.0.1 logic and return shapes.
  - P3: `Plugin` registers `term_link` → `filter_category_link` (10, 3) and `terms_clauses` → `filter_terms_by_cpt` (10, 3) once. Each acts only while its private flag is set in `try`/`finally` around `walk_category_tree()` or `get_terms()`; otherwise it returns its first argument. The scoped post types live in the flag state.
  - `show_on_front` and `page_for_posts` come from new `Flags::show_on_front(): string` and `Flags::page_for_posts(): int`.
  - Core filters fire through `Hooks::list_cats`, `dropdown_cats`, `list_categories( $output, $args )`.
  - Echoed HTML goes through `wp_kses()` with an allow-list for the select and option markup, or `wp_kses_post()` for lists. That is not a D-item, so it is escaped now (Decisions).
- `Legacy\Utilities` (`WPPA\Legacy`, not final):
  - 3.0.1 members: `protected static $instance`, `get_instance()`, `create_instance()` (throws if already created), and public `register_widgets`, `dropdown_categories`, `list_categories`, `get_terms`, `filter_terms_by_cpt`, `filter_category_link`, `single_publication`, `publication_archives`, plus protected `find_template`. All delegate through `\WPPA\Plugin::instance()`.
  - Its constructor adds no hooks.
  - `Plugin::boot()` calls `Legacy\Utilities::create_instance()` once, after `Aliases::register()`.
- `Widgets\Category_Count_Widget` (not final, extends `\WP_Widget`):
  - the parent constructor gets `Keys::WIDGET_CAT_COUNT_ID_BASE`, 3.0.1's name, and widget options (§6.6);
  - `protected $utilities` = `\WP_Publication_Archive_Utilities::get_instance()`;
  - `form`, `update` and `widget` keep 3.0.1 signatures and markup;
  - `widget_title` fires through `Hooks::widget_title()` with 3.0.1's three arguments; `widget_categories_args`/`widget_categories_dropdown_args` fire through `Hooks`;
  - output is escaped (`wp_kses_post` for `before_*`/`after_*`); the inline dropdown script prints the element id with `esc_js( Keys::FIELD_CAT_DROPDOWN )` and the URL with `esc_url()`.
- `Hooks::widget_title( string $title, ...$extra )` passes exactly the arguments each caller gives.
- `Plugin`:
  - `widgets_init` → `register_widgets()`, which calls `register_widget()` with `Keys::LEGACY_CLASS_ARCHIVE_WIDGET`, `LEGACY_CLASS_CAT_COUNT_WIDGET` and `LEGACY_CLASS_RELATED_WIDGET` (Decisions), so the legacy names stay the factory keys;
  - `term_link` and `terms_clauses` as above;
  - construct `categories`, with an accessor and a `replace()` case.
- `Aliases` adds `LEGACY_CLASS_UTILITIES` → `Legacy\Utilities` and `LEGACY_CLASS_CAT_COUNT_WIDGET` → `Widgets\Category_Count_Widget`.

**Acceptance tests:**
- `tests/integration/test-categories.php`:
  - `test_get_terms_limits_counts_to_publications`;
  - `test_list_categories_links_to_publication_category_urls`;
  - `test_dropdown_categories_markup_uses_301_field_name`;
  - `test_scoped_callbacks_do_nothing_outside_their_calls`.
- `tests/integration/test-utilities.php`:
  - `test_singleton_instance_exists_after_boot`;
  - `test_create_instance_twice_throws`;
  - `test_utilities_delegates_match_categories_results`.
- `tests/integration/test-category-count-widget.php`: `test_id_base_is_the_301_value`, `test_list_mode_renders_publication_categories`.
- `tests/integration/test-aliases.php` adds `test_utilities_and_cat_count_aliases_resolve`.
- P0-06 and P0-07 pass unchanged.

**Out of scope:** `show_instance_in_rest` (D14, P2-09). The archive and related widgets (P0-14).
**Verification:** `composer dump-autoload -o`, then foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P0-12
**Workstream:** serial

### P0-14: Restructure (7/8) — Archive and Related widgets
**Goal:** Move the archive and related-publications widgets into `WPPA\Widgets`, with their 3.0.1 `id_base`s and the scoped summary length, preserving output.
**Files touched:**
- widgets: `includes/widgets/class-archive-widget.php`, `includes/widgets/class-related-widget.php`;
- services and legacy: `includes/legacy/class-aliases.php`, `includes/class-post-type.php`, `includes/class-plugin.php`;
- runtime: `lib/class.publication-widget.php` (deleted), `lib/class.wp-publication-archive-category-widget.php` (deleted), `lib/class.wp-publication-archive.php`, `lib/class.wp-publication-archive-loader.php`;
- tests: `tests/integration/test-archive-widget.php`, `tests/integration/test-related-widget.php`, `tests/integration/test-aliases.php`, `tests/integration/test-post-type.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply (no `extract`, `global $wppa_publications` allowed, tunables-in-keys-only).
- `Post_Type::query( array $args ): \WP_Query` is 3.0.1 `query_publications` (defaults `posts_per_page -1`, `order ASC`, `orderby menu_order`, forced `post_type`). `lib` `query_publications()` delegates to it.
- `Widgets\Archive_Widget` (not final):
  - `id_base` `Keys::WIDGET_ARCHIVE_ID_BASE`; 3.0.1 name and options; form default number `Keys::DEFAULT_ARCHIVE_WIDGET_NUMBER`;
  - `widget()` reads `$args['before_widget']` and the rest explicitly (no `extract`); `widget_title` fires through `Hooks::widget_title()` with 3.0.1's one argument; keep the 3.0.1 `order_by`/`orderby` key mismatch;
  - sets `global $wppa_publications` from `Plugin::instance()->post_type()->query()`, includes `templates()->locate( Hooks::widget_template( Keys::TEMPLATE_WIDGET ) )`, then unsets it;
  - output is escaped.
- `Widgets\Related_Widget` (not final):
  - `id_base` `Keys::WIDGET_RELATED_ID_BASE`; form default count `Keys::DEFAULT_RELATED_COUNT`;
  - `widget()` renders its loop inside `Plugin::instance()->templates()->with_widget_summary_length( … )` (P3). It uses `new \WPPA\Publication_Item( $post )`, `$publication->the_title()` and `wp_kses_post( $publication->summary )`;
  - keeps `limit_summary_length( $length )` public (3.0.1 method) returning `Hooks::widget_summary_length( Keys::DEFAULT_WIDGET_SUMMARY_LENGTH )`; it is never hooked;
  - if a non-post queried object triggers a PHP 8 warning, guard with `isset( $queried->ID )` and record that under Interpretation.
- `Aliases` adds `LEGACY_CLASS_ARCHIVE_WIDGET` and `LEGACY_CLASS_RELATED_WIDGET`.

**Acceptance tests:**
- `tests/integration/test-archive-widget.php`: `test_id_base_is_the_301_value`, `test_renders_publications_through_the_widget_template`.
- `tests/integration/test-related-widget.php`: `test_id_base_is_the_301_value`, `test_summary_length_scope_applies_only_inside_the_widget`.
- `tests/integration/test-post-type.php` adds `test_query_matches_301_query_publications_defaults`.
- `tests/integration/test-aliases.php` adds `test_widget_aliases_resolve_and_are_the_factory_keys`.
- P0-06 and P0-07 pass unchanged.

**Out of scope:** `show_instance_in_rest` (P2-09). The `Legacy\Publication_Archive` class (P0-15).
**Verification:** `composer dump-autoload -o`, then foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P0-13
**Workstream:** serial

### P0-15: Restructure (8/8) — Legacy\Publication_Archive and removal of lib/
**Goal:** Replace the last 3.0.1 class with `WPPA\Legacy\Publication_Archive` delegates behind an alias, delete `lib/` and the transitional loader, and prove the whole 3.0.1 public surface still exists.
**Files touched:**
- legacy: `includes/legacy/class-publication-archive.php`, `includes/legacy/class-aliases.php`;
- plugin: `includes/class-plugin.php`, `wp-publication-archive.php`, `composer.json`, `composer.lock` (only if `composer validate` requires it);
- runtime: `lib/` (deleted);
- docs: `docs/HOOKS.md`;
- tests: `tests/integration/test-aliases.php`, `tests/integration/test-publication-archive.php`, `tests/integration/test-bootstrap.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. After this task nothing references `lib/`, `lang/`, `images/` or `includes/front-end.css` (P19).
- `Legacy\Publication_Archive` (`WPPA\Legacy`, not final; every method static as in 3.0.1, same names, parameters and defaults, phpdoc types only) delegates through `\WPPA\Plugin::instance()`. The methods:

  | Group | Methods |
  |---|---|
  | Upgrade | `upgrade( $from )` → `upgrade()->run()` |
  | Links | `get_open_link( $publication_id = 0 )`, `get_download_link`, `get_alternate_open_link( $publication_id = 0, $key = false )`, `get_alternate_download_link`, and protected `get_link( $publication_id = 0, $endpoint = 'view', $permalink = false, $key = false )` |
  | Delivery and icons | `open_file`, `download_file`, `get_image( $doctype )` |
  | Assets and model | `enqueue_scripts_and_styles`, `register_publication`, `register_author` |
  | Meta boxes | `pub_meta_boxes`, `doc_uri_box( $post )`, `doc_thumb_box`, `doc_alternates_box`, `save_meta( $post_id )` |
  | Shortcode and routing | `shortcode_handler( $atts )`, `query_vars( $public_vars )`, `custom_rewrites` |
  | Filter helpers | `publication_link( $permalink, $post )`, `the_content( $content )`, `the_title( $title, $id = 0 )`, `search( $where )`, `search_join( $join )`, `search_distinct( $distinct )`, `custom_excerpt_length( $length )` |
  | Queries | `query_publications( $args )` |

  Behaviour notes:
  - `the_content`, `the_title` and `publication_link` keep their 3.0.1 results until P2-08: the summary for publications (via `get_post()`), `sprintf( __( '%s (Publication)', 'wp-publication-archive' ), $title )`, and `rewrites()->link( …, Keys::QV_OPEN, $permalink )`;
  - `search*` return their argument (D4);
  - no hook is ever registered with these callables (§2).
- `Aliases` adds `LEGACY_CLASS_ARCHIVE` → `Legacy\Publication_Archive`; all six 3.0.1 names now resolve (§6.3). `mimetype` is not aliased.
- `Plugin` no longer calls the loader; `activate()` and `deactivate()` are its own. The bootstrap is unchanged except that nothing names the loader. `composer.json` `autoload.classmap` becomes `["includes/"]`; run `composer dump-autoload -o`.
- `tests/integration/test-aliases.php` holds `private const METHODS_301`, which maps each of the six 3.0.1 class names to every public method name and its parameter count.
  - Write it out literally from `e913681:lib/*.php`; do not compute it. Constructors, widget methods (`__construct`, `form`, `update`, `widget`), `limit_summary_length` and the Utilities singleton methods are included.
  - It also holds the static-ness of each method.

**Acceptance tests:**
- `tests/integration/test-aliases.php`:
  - `test_every_301_class_name_resolves`;
  - `test_every_301_public_method_exists_with_the_same_parameter_count_and_staticness`;
  - `test_aliased_classes_are_not_final`;
  - `test_mimetype_is_not_aliased`.
- `tests/integration/test-publication-archive.php`:
  - `test_link_delegates_match_rewrites`;
  - `test_search_methods_return_their_argument`;
  - `test_no_hook_is_registered_with_a_legacy_callable`: scan `$GLOBALS['wp_filter']` for callbacks whose class is `WPPA\Legacy\*` or a 3.0.1 name.
- `tests/integration/test-bootstrap.php`: rename the `lib/templates` test to `test_bundled_templates_are_found_under_templates_classic` and add `test_no_301_directories_remain` (`lib`, `lang`, `images`).

**Out of scope:** Changing any 3.0.1 result (D8 is P2-08). New features.
**Verification:**
1. `composer dump-autoload -o`, then foundry_verify, then `WPPA_DAM=0 composer test`.
2. `git ls-files lib lang images includes/front-end.css` prints nothing.
3. `grep -rn "lib/\|lang/\|images/" includes templates wp-publication-archive.php` prints nothing.

**Depends on:** P0-14
**Workstream:** serial

### P0-16: Gate 0 — foundation verified, constraints locked, branch pushed
**Goal:** Prove the foundation green in both DAM modes and in CI, lock in two Phase 0 invariants as constraints, push, and list the human check.
**Files touched:** `docs/foundry.json` (append two constraints), plus any file a fix requires, each listed in the commit.
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. SPEC §8.0 gate shape and §8 Phase 0 item 5.
- Append exactly these two entries to `constraints` in `docs/foundry.json`. Change nothing else in the file (SPEC §7.1):
```json
{"id":"no-mimetype-class","description":"SPEC D16: the 2002 mimetype class is gone; MIME lookups go through wp_check_filetype(). Added by P0-16.","paths":["includes/","templates/","wp-publication-archive.php"],"pattern":"\\bnew\\s+\\\\?mimetype\\b|class\\.mimetype|\\bmimetype::","shouldMatch":["$mime = new mimetype();","require_once __DIR__ . '/class.mimetype.php';","$t = \\mimetype::getType( $f );"],"shouldNotMatch":["$type = wp_check_filetype( $name );","$mimetype = 'application/pdf';"]}
```
```json
{"id":"no-legacy-string-callables","description":"SPEC §2 non-goals: no hook is registered under a 3.0.1 global class callable. Added by P0-16.","paths":["includes/","templates/","wp-publication-archive.php"],"exclude":["includes/class-keys.php"],"pattern":"array\\(\\s*['\"]WP_Publication_Archive|['\"]WP_Publication_Archive\\w*::","shouldMatch":["add_action( 'save_post', array( 'WP_Publication_Archive', 'save_meta' ) );","add_filter( 'template_include', array( \"WP_Publication_Archive_Utilities\", 'single_publication' ) );","$cb = 'WP_Publication_Archive::open_file';"],"shouldNotMatch":["$item = new \\WP_Publication_Archive_Item( $post );","register_widget( Keys::LEGACY_CLASS_ARCHIVE_WIDGET );"]}
```
- A fix made here must not change a pinned characterisation string.

**Acceptance tests:** No new test files. The following must pass:
- `composer verify` with the DAM loaded;
- `WPPA_DAM=0 composer test`;
- `npx wp-env run cli wp publication-archive doctor` exits 0;
- `foundry_verify` reports both new constraints self-tested and clean.

**Out of scope:** Any D-fix. Any Phase 1 work.
**Verification:**
1. `composer verify`, then `WPPA_DAM=0 composer test`, then foundry_verify.
2. `git ls-files lib lang images` prints nothing.
3. Push the branch per policy.
4. If `gh` is available: `gh run list --branch "$(git branch --show-current)" --limit 1` and `gh run watch <id> --exit-status`; record the CI result. Otherwise log `CI: NOT VERIFIED (gh unavailable)`.
5. Log `Manual check: NOT VERIFIED (human)` with SPEC §8 Phase 0 item 5's check: on a clean clone, `bash bin/fetch-dam.sh && npx wp-env start`; the front-end list and single pages and wp-admin → Publications look the same as on 3.0.1, with both plugins active.

**Depends on:** P0-15
**Workstream:** serial

## Phase 1 — Security and DAM bridge
Contracts (P1-01, P1-02, serial) → Wave 1 (`url-policy`, `dam`, `delivery`, `markup`) → Gate 1 (P1-09). SPEC §8 Phase 1.

### P1-01: Contracts (1/2) — Keys, Hooks, Plugin wiring and signatures for Url_Policy, Dam_Bridge, Delivery and Streamer
**Goal:** Do every shared-file edit Wave 1 needs, so no wave task touches `Keys`, `Hooks`, `Flags`, `Plugin`, `docs/HOOKS.md` or `composer.json`.
**Files touched:**
- shared: `includes/class-keys.php`, `includes/class-hooks.php`, `includes/class-plugin.php`, `docs/HOOKS.md`;
- signatures: `includes/class-url-policy.php` (new), `includes/class-dam-bridge.php` (new), `includes/class-delivery.php`, `includes/class-streamer.php`, `includes/class-meta-boxes.php`;
- test support: `tests/class-wp-error-shim.php`, `tests/bootstrap.php`;
- tests: `tests/unit/test-keys.php`, `tests/unit/test-url-policy.php`, `tests/integration/test-dam-bridge.php`, `tests/integration/test-delivery.php`, `tests/integration/test-plugin.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. `Url_Policy` is a leaf and pure (leaf-files-import-nothing, url-policy-is-pure). Stubs are exactly `throw new NotImplementedException( __METHOD__ );` (stub-form-exact). No live hook may point at a stub (Decisions).
- `Keys` adds:
  - `DEFAULT_PROXY_TIMEOUT` = `30` (⚠️ ASSUMPTION, §5.3) and `DEFAULT_PROXY_MAX_BYTES` = `52428800` (⚠️ ASSUMPTION, §5.3). These two literals appear nowhere else (tunables-in-keys-only);
  - `DEFAULT_MASK_URL` `false`, `FILTER_PROXY_TIMEOUT` `'wppa_proxy_timeout'`, `FILTER_PROXY_MAX_BYTES` `'wppa_proxy_max_bytes'`;
  - `ERR_INVALID_URL` `'wppa_invalid_url'`, `HOOK_ALLOWED_REDIRECT_HOSTS` `'allowed_redirect_hosts'`, `CONTENT_TYPE_FALLBACK` `'application/octet-stream'`.
- `Hooks` adds `proxy_timeout( int ): int` and `proxy_max_bytes( int ): int`. `docs/HOOKS.md` gains both tunables and notes that the `wppa_mask_url` default becomes `false` in P1-07.
- `Url_Policy` (final, §6.2): `__construct( string $site_host, callable $is_safe_external )`.
  - `normalise( string $stored ): string` is implemented now because P1-02 needs it: trim, then turn a leading `http|` into `http://` and a leading `https|` into `https://`.
  - `is_same_site( string $url ): bool` is implemented now: a case-insensitive host comparison with `$site_host`, using `parse_url()` with `// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- reason: leaf may not call WordPress (SPEC §3 P17)`.
  - `validate( string $url )` is a stub; phpdoc `@return string|\WP_Error`.
- `Dam_Bridge` (final, §6.9): `__construct( Url_Policy $policy )`; stubs `active(): bool`, `version(): string`, `attachment_id_for( string $url ): int`, `is_withheld( string $url ): bool`, `display_url( string $url ): string` and `indexed_attachment_ids( array $ids, \WP_Post $post ): array`.
- `Streamer` final constructor: `__construct( string $temp_dir, ?callable $exit = null, ?callable $header = null, int $ob_floor = 0 )`. Add the stub `send( string $path, string $content_type, ?string $filename ): void`. The interim `passthrough()` keeps working.
- `Delivery`:
  - final constructor: `__construct( Url_Policy $policy, Streamer $streamer, Dam_Bridge $dam, Icons $icons, ?callable $exit = null, ?callable $header = null )`. The interim `handle()`, `open()` and `download()` keep working;
  - `allowed_redirect_hosts( array $hosts ): array` is implemented now because it is on a live hook. It returns `$hosts` plus a private `?string $redirect_host` when that is set; P1-07 sets it in `try`/`finally` around `wp_safe_redirect()`.
- `Meta_Boxes` constructor becomes `__construct( Url_Policy $policy )` (stored, unused until P1-04).
- `Plugin`:
  - construct `url_policy = new Url_Policy( (string) wp_parse_url( home_url(), PHP_URL_HOST ), static function ( string $url ): bool { return false !== wp_http_validate_url( $url ); } )`, then `dam_bridge`, `streamer = new Streamer( get_temp_dir() )`, `delivery` and `meta_boxes` with the new signatures;
  - add accessors `url_policy()` and `dam_bridge()` and `replace()` cases;
  - register `allowed_redirect_hosts` → `delivery->allowed_redirect_hosts` (10, 1);
  - do not register the DAM filter (P1-02).
- `tests/class-wp-error-shim.php`: a minimal global `WP_Error` (`__construct( $code = '', $message = '', $data = '' )`, `get_error_code()`, `get_error_message()`, `get_error_data()`) inside `if ( ! class_exists( 'WP_Error', false ) )`. `tests/bootstrap.php` requires it only on the unit path, before its early return (§6.2).

**Acceptance tests:**
- `tests/unit/test-keys.php` adds:
  - `test_proxy_tunable_defaults` (30 and 52428800);
  - `test_mask_default_is_false`;
  - `test_error_code_and_redirect_hook`.
- `tests/unit/test-url-policy.php`:
  - `test_is_final`;
  - `test_normalise_turns_pipe_forms_into_schemes`;
  - `test_normalise_trims`;
  - `test_is_same_site_compares_host_case_insensitively`.
- `tests/integration/test-dam-bridge.php`: `test_is_final`.
- `tests/integration/test-delivery.php` adds `test_allowed_redirect_hosts_unchanged_when_no_redirect_in_progress`.
- `tests/integration/test-plugin.php` adds:
  - `test_phase_1_services_are_constructed_and_replaceable`;
  - `test_allowed_redirect_hosts_filter_registered`;
  - `test_dam_filter_not_yet_registered`.

**Out of scope:** `validate()`, `send()` and every other bridge method (the wave and P1-02). Any D-fix.
**Verification:** `composer dump-autoload -o`, then foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P0-16
**Workstream:** serial

### P1-02: Contracts (2/2) — DAM usage filter (D19) and the dam doctor row
**Goal:** Register `vip_dam_indexed_attachment_ids` and implement the bridge methods it needs, so the DAM counts publication files including the pipe form (D19); add the `dam` doctor row.
**Files touched:**
- `includes/class-dam-bridge.php`, `includes/class-plugin.php`, `includes/class-cli.php`, `docs/HOOKS.md`;
- tests: `tests/integration/test-dam-bridge.php`, `tests/integration/dam/test-dam-bridge-dam.php`, `tests/integration/test-cli.php`, `tests/integration/dam/test-cli-dam.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. DAM symbols only in `class-dam-bridge.php` (dam-symbols-confined).
- §6.9:
  - `active()`: `class_exists( '\VIP\DAM\Embargo_Guard' ) && class_exists( '\VIP\DAM\Lifecycle' )`.
  - `version(): string`: the DAM's version as the DAM itself exposes it, a constant or its plugin header; read `.cache/vip-digital-asset-manager/index.php`. Return `''` when inactive.
  - `attachment_id_for( string $url ): int`:
    1. `normalise()` the URL;
    2. return 0 unless `$this->policy->is_same_site()`;
    3. strip a `-<w>x<h>` size suffix before the extension;
    4. return `attachment_url_to_postid()`.
  - `indexed_attachment_ids( array $ids, \WP_Post $post ): array`:
    - return `$ids` unchanged when `! $this->active()` or the post type is not `Keys::POST_TYPE`;
    - otherwise add the non-zero `attachment_id_for()` of `META_DOC`, `META_IMAGE` and every `META_ALTERNATES` row's `url`, and return `array_values( array_unique( array_map( 'intval', … ) ) )`.
  - `is_withheld()` and `display_url()` stay stubs (P1-05). Nothing live calls them yet.
- `Plugin`: register `Keys::HOOK_DAM_INDEXED_IDS` → `dam_bridge->indexed_attachment_ids` (10, 2) unconditionally. `Cli`'s constructor gains `Dam_Bridge`.
- `Cli` `dam` row, appended last (§6.8):
  - DAM absent: status `pass`, message `absent`;
  - DAM present: message `<version>, usage filter attached|not attached`; `pass` iff `has_filter( Keys::HOOK_DAM_INDEXED_IDS, array( $bridge, 'indexed_attachment_ids' ) ) !== false`, else `fail`.
- In tests, read the DAM's own source (`inc/class-usage-index.php`, its abilities under `inc/abilities/`) to learn how `Usage_Index::get_usage()` is populated and how `media-delete` is invoked. Use the DAM's API, not its private meta. DAM-specific literals are allowed in `tests/`.
- Tests that need the pipe form write it with `V3_Site::raw_meta()`.

**Acceptance tests:**
- `tests/integration/test-dam-bridge.php` (class ungrouped):
  - `test_attachment_id_for_resolves_same_site_url`;
  - `test_attachment_id_for_strips_size_suffix`;
  - `test_attachment_id_for_resolves_pipe_form`;
  - `test_attachment_id_for_external_url_is_zero`;
  - `test_d19_indexed_ids_unchanged_when_dam_inactive`, tagged `@group nodam` on the method.
- `tests/integration/dam/test-dam-bridge-dam.php` (`@group dam`):
  - `test_active_with_dam_loaded`;
  - `test_d19_indexed_ids_include_doc_image_and_alternates_once_each`;
  - `test_d19_other_post_types_untouched`;
  - `test_d19_usage_index_counts_pipe_form_publication`;
  - `test_d19_dam_refuses_media_delete_of_attachment_a_publication_uses` (expects `asset_in_use`).
- `tests/integration/test-cli.php`: `test_dam_row_reports_absent_and_passes`, tagged `@group nodam`.
- `tests/integration/dam/test-cli-dam.php` (`@group dam`): `test_dam_row_reports_version_and_attached_filter`, `test_dam_row_fails_when_filter_detached`.

**Out of scope:** `is_withheld()`, `display_url()` and anything in Delivery or `Publication_Item`.
**Verification:**
1. foundry_verify (DAM loaded: the `dam` tests run).
2. `WPPA_DAM=0 composer test` (the `nodam` tests run).
3. `npx wp-env run cli wp publication-archive doctor` exits 0 and shows `dam` with the DAM version and the filter attached.

**Depends on:** P1-01
**Workstream:** serial

### P1-03: Url_Policy::validate() and the §6.2 table test
**Goal:** Implement `Url_Policy::validate()` as a pure normaliser and validator that rejects local paths, non-HTTP schemes and unsafe hosts (D1 at its root).
**Files touched:** `includes/class-url-policy.php`, `tests/unit/test-url-policy.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. The leaf and purity rules apply to this file (leaf-files-import-nothing, url-policy-is-pure); `\WP_Error` is referenced by its fully qualified name, never imported.
- §6.2 steps:
  1. `normalise()`.
  2. Reject with `new \WP_Error( Keys::ERR_INVALID_URL )` unless `parse_url()` gives scheme `http` or `https` and a non-empty host. Carry the P17 `phpcs:ignore` reason, as in P1-01.
  3. Accept if `is_same_site()`. Otherwise accept only if `( $this->is_safe_external )( $url )` is true.

  On success, return the normalised URL (that is "the validated value" §5.1 stores).
- Unit tests run on the host with the `WP_Error` shim and a stub callable (§6.2).

**Acceptance tests:** `tests/unit/test-url-policy.php`:
- `test_d1_validate_table` with `@dataProvider provide_validate_table`, one named row per SPEC §6.2 input:

  | Row | Stub | Expected |
  |---|---|---|
  | `/etc/passwd` | — | reject |
  | `../wp-config.php` | — | reject |
  | `file:///etc/passwd` | — | reject |
  | `javascript:alert(1)` | — | reject |
  | `http://127.0.0.1/` | rejects | reject |
  | `http://169.254.169.254/latest/meta-data/` | rejects | reject |
  | same-site `http://<site_host>/wp-content/uploads/a.pdf` | never called | accept |
  | `https://example.com/a.pdf` | accepts | accept |
  | `https\|example.com/a.pdf` | accepts | accept as `https://example.com/a.pdf` |
  | empty string | — | reject |

  Every rejection is a `\WP_Error` with code `Keys::ERR_INVALID_URL`.
- `test_same_site_url_never_calls_the_external_validator`.

**Out of scope:** Using `validate()` anywhere else (the other wave tasks).
**Verification:** foundry_verify. `composer test:unit --filter Url_Policy` must pass on the host.
**Depends on:** P1-02
**Workstream:** url-policy

### P1-04: Meta box save and render through Url_Policy (D1 save, D2 save, D3 admin, D10)
**Goal:** Make the meta box save path store only validated URLs and sanitised descriptions with a correct loop, and escape every meta box value.
**Files touched:** `includes/class-meta-boxes.php`, `tests/integration/test-meta-boxes.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. Remove every `WordPress.Security` ignore in this file: D1, D2, D3 and D10 are closed here (P7).
- `save()`:
  - nonce: `sanitize_text_field( wp_unslash( $_POST[ Keys::FIELD_NONCE ] ) )` checked with `wp_verify_nonce( …, Keys::NONCE_ACTION )`;
  - doc and image: `$this->policy->validate( trim( wp_unslash( … ) ) )`; store the returned string, or `''` on `\WP_Error` (§5.1, D1);
  - alternates: loop `$i < count( urls )` (D10); description `sanitize_text_field( wp_unslash( … ) )` (D2); URL validated, and rows whose URL is empty or invalid are skipped (3.0.1 skipped empty rows);
  - the three §5.1 keys only.
- Render: every `value=""` goes through `esc_attr()` (D3). Inline Thickbox JavaScript stays (D13 is P2-07).

**Acceptance tests:** `tests/integration/test-meta-boxes.php` adds:
- D1: `test_d1_save_stores_empty_string_for_local_path_doc`, `test_d1_save_stores_empty_string_for_javascript_image`, `test_d1_save_keeps_same_site_url`, `test_d1_save_normalises_pipe_form`;
- D2: `test_d2_save_strips_markup_from_alternate_description`;
- D10: `test_d10_saving_two_alternates_stores_two_rows_without_warning`;
- D3: `test_d3_doc_box_escapes_stored_value`, `test_d3_thumb_box_escapes_stored_value`. These store `"><script>x</script>` with `V3_Site::raw_meta()`; the render contains `&quot;&gt;&lt;script` and not `"><script`.

**Out of scope:** REST writes (P2-04). The Thickbox and media modal (P2-07).
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test --filter Meta_Boxes`.
**Depends on:** P1-03
**Workstream:** url-policy

### P1-05: Dam_Bridge withholding and display URL (D17, D18 at the bridge)
**Goal:** Implement `is_withheld()` and `display_url()` so an embargoed, lifecycle-archived or trashed same-site attachment is withheld from users who cannot edit it.
**Files touched:** `includes/class-dam-bridge.php`, `tests/integration/test-dam-bridge.php`, `tests/integration/dam/test-dam-bridge-dam.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply (dam-symbols-confined).
- §6.9:
  - `is_withheld( $url )` returns true iff all of these hold:
    - the DAM is active;
    - `$id = attachment_id_for( $url )` is non-zero;
    - `! current_user_can( 'edit_post', $id )`;
    - `\VIP\DAM\Embargo_Guard::is_hidden( $id )` or `'trash' === get_post_status( $id )`.
  - `display_url( $url )` returns `\VIP\DAM\Embargo_Guard::placeholder_url()` when `is_withheld( $url )`, else `$url`.
  - Inactive DAM: `false` and the unchanged URL.
  - Call `is_hidden`/`placeholder_url` as the pinned DAM declares them (static or instance; see the P0-05 log).
- In tests, embargo or lifecycle-archive an attachment through the DAM's own API (read `inc/class-embargo-guard.php` and `inc/class-lifecycle.php`). Trash with `wp_update_post( array( 'ID' => $id, 'post_status' => 'trash' ) )`, because `MEDIA_TRASH` may be off. Switch users with `wp_set_current_user()` (0 for anonymous, and a factory `editor`).

**Acceptance tests:**
- `tests/integration/dam/test-dam-bridge-dam.php` (`@group dam`) adds:
  - D17: `test_d17_embargoed_attachment_is_withheld_for_anonymous`, `test_d17_embargoed_attachment_is_not_withheld_for_editor`, `test_d17_lifecycle_archived_attachment_is_withheld_for_anonymous`, `test_d17_trashed_attachment_is_withheld_for_anonymous`, `test_d17_external_url_is_never_withheld`;
  - D18: `test_d18_display_url_returns_placeholder_for_anonymous_on_embargoed`, `test_d18_display_url_unchanged_for_editor`.
- `tests/integration/test-dam-bridge.php` adds `test_is_withheld_false_when_dam_inactive` and `test_display_url_unchanged_when_dam_inactive`, both with `@group nodam` on the method.

**Out of scope:** Calling the bridge from Delivery (P1-07) or `Publication_Item` (P1-08).
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P1-02
**Workstream:** dam

### P1-06: Streamer::send() — the one temp-file readfile (P11, D6)
**Goal:** Implement `Streamer::send()` to stream a temp file with correct headers, end output buffers without notices, delete the file and exit through the injected callable.
**Files touched:** `includes/class-streamer.php`, `tests/unit/test-streamer.php`, `tests/integration/test-streamer.php`, `tests/fixtures/streamer-child.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply (raw-file-read-confined). The class stays WordPress-free, so the unit tests run on the host.
- §6.2 `Streamer::send( $path, $content_type, $filename )`, in order:
  1. Refuse (throw `\InvalidArgumentException`) unless `realpath( $path )` is inside `realpath( $this->temp_dir )` (Decisions, the P11 reading).
  2. `Content-Type: <content_type>`, as passed by `Delivery`.
  3. `Content-Length: <filesize>`.
  4. Only when `$filename !== null`: `Content-Disposition: attachment; filename="<filename>"`, with `"`, CR and LF stripped. `Delivery` passes the name already run through `sanitize_file_name()`.
  5. D6: `while ( ob_get_level() > $this->ob_floor ) { ob_end_clean(); }`.
  6. `readfile( $path )`, then `unlink( $path )` with `// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- reason: Streamer owns and deletes its temp file; wp_delete_file() is unavailable in unit tests`.
  7. Call the exit callable.

  Headers go through the injected header callable (default `header`).
- `passthrough()` stays until P1-07 removes it. Until then there are two `readfile(` calls in this file; Gate 1 checks for exactly one.

**Acceptance tests:**
- `tests/unit/test-streamer.php`:
  - `test_send_emits_type_and_length_headers_in_order`;
  - `test_send_adds_disposition_only_when_a_filename_is_given`;
  - `test_send_strips_quotes_and_newlines_from_the_filename`;
  - `test_send_outputs_the_file_bytes_and_deletes_the_file`;
  - `test_send_calls_the_exit_callable`;
  - `test_send_refuses_a_path_outside_the_temp_dir`;
  - `test_d6_no_notice_with_zero_output_buffers`. This runs `tests/fixtures/streamer-child.php` with `proc_open( PHP_BINARY … )`. The child requires `vendor/autoload.php`, ends all buffers, and sends a temp file with `ob_floor` 0 and a no-op exit. The test asserts the child's stderr is empty and its stdout equals the file bytes.
- `tests/integration/test-streamer.php` adds `test_send_accepts_a_wp_tempnam_file`, which uses `get_temp_dir()` and `ob_floor = ob_get_level()`.

**Out of scope:** Delivery changes (P1-07). Deleting `passthrough()` (P1-07).
**Verification:** foundry_verify. `composer test:unit --filter Streamer` must pass on the host.
**Depends on:** P1-02
**Workstream:** delivery

### P1-07: Delivery per §6.2 — validate, withhold, redirect or proxy (D1 delivery, D17 end to end)
**Goal:** Rewrite `Delivery` so every endpoint validates the URL, 404s on invalid or DAM-withheld files, redirects by default, and proxies only on opt-in through `wp_safe_remote_get()` into a temp file.
**Files touched:** `includes/class-delivery.php`, `includes/class-streamer.php` (delete `passthrough()`), `tests/unit/test-delivery.php`, `tests/integration/test-delivery.php`, `tests/integration/test-streamer.php`, `tests/integration/dam/test-delivery-dam.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply (safe-remote-only, raw-file-read-confined, tunables-in-keys-only). After this task `class-streamer.php` contains exactly one `readfile(`.
- §6.2 `handle()` on `template_redirect`:
  - The endpoint comes from `get_query_var( Keys::QV_OPEN | QV_DOWNLOAD )`, and the publication from `get_post()`. If there is no publication post, return and let WordPress serve its 404.
  - Steps, per §6.2:
    1. Read the stored URL: `META_DOC`, or the alternate whose description equals `urldecode( QV_ALT )` (3.0.1 key semantics). Normalise it through `$this->policy->normalise()`.
    2. `Hooks::open_url()` or `download_url()`.
    3. `validate()`. On `\WP_Error`, `wp_die( esc_html__( 'File not found.', 'wp-publication-archive' ), '', array( 'response' => 404 ) )`.
    4. `$this->dam->is_withheld( $url )` → the same 404.
    5. Default mode (`Hooks::mask_url( Keys::DEFAULT_MASK_URL )` false): set `redirect_host` to the URL's host in `try`/`finally` around `wp_safe_redirect( $url, 302 )`, then call the exit callable. Downloads do the same; a redirect cannot force `Content-Disposition` (P3-02's readme says so).
    6. Proxy mode:
       1. `wp_safe_remote_head( $url, array( 'timeout' => Hooks::proxy_timeout( Keys::DEFAULT_PROXY_TIMEOUT ) ) )`.
       2. If `content-length` > `Hooks::proxy_max_bytes( Keys::DEFAULT_PROXY_MAX_BYTES )`, redirect as in step 5.
       3. Otherwise `wp_safe_remote_get( $url, array( 'stream' => true, 'filename' => wp_tempnam( … ), 'timeout' => … ) )`. On `\WP_Error` or a non-2xx code, delete the temp file and redirect.
       4. Content type: `$this->icons->mime_for( $url )` unless that is the fallback, else the response `content-type` header, else `Keys::CONTENT_TYPE_FALLBACK`. Filename: for downloads only, `sanitize_file_name( basename( <URL path> ) )`. Then `$this->streamer->send( $tmp, $type, $filename_or_null )`.
- `open()` and `download()` remain as entry points for the legacy delegates.
- Remove the interim 303 and header code; the `$header` constructor parameter may be left unused.
- `Url_Policy` and `Dam_Bridge` are final and cannot be doubled. Unit tests cover the pure decision helper `decide( bool $mask, ?int $content_length, int $max_bytes ): string` (`'redirect'` or `'proxy'`) with a real `Url_Policy` and stub callable.
- Integration tests:
  - invoke `Plugin::instance()->delivery()->handle()` after `$this->go_to( <endpoint URL> )`;
  - inject exit, header and `ob_floor` through `replace()`;
  - capture redirects with a `wp_redirect` filter that records the location and status and throws a test-local exception;
  - mock HTTP with `pre_http_request`, where the mock writes the body to `$args['filename']` when `stream` is set;
  - write hostile stored values with `V3_Site::raw_meta()`;
  - for an external host, use a public IP literal (`https://93.184.216.34/doc.pdf`) so no DNS is needed.
- If `Url_Policy::validate()` or `Dam_Bridge::is_withheld()` still throws `NotImplementedException` (its workstream was blocked), `foundry_task_block` with that reason.

**Acceptance tests:**
- `tests/unit/test-delivery.php`: `test_decide_redirects_when_mask_is_off`, `test_decide_redirects_when_length_exceeds_cap`, `test_decide_proxies_when_under_cap_or_length_unknown`.
- `tests/integration/test-delivery.php`:
  - D1 redirect path: `test_d1_same_site_file_redirects_302`, `test_d1_stored_local_path_gets_404_with_no_file_bytes`, `test_d1_internal_host_gets_404`, `test_external_public_url_redirects_to_its_host`;
  - defaults and alternates: `test_mask_url_defaults_to_false`, `test_alternate_endpoint_uses_the_alternate_url`;
  - proxy mode: `test_proxy_mode_streams_mocked_body_and_headers`, `test_proxy_mode_over_size_cap_redirects`, `test_proxy_mode_request_error_redirects`, `test_proxy_mode_download_adds_disposition`.
- `tests/integration/test-streamer.php`: remove the `passthrough` tests.
- `tests/integration/dam/test-delivery-dam.php` (`@group dam`): `test_d17_embargoed_attachment_404_for_anonymous`, `test_d17_embargoed_attachment_302_for_editor`, `test_d17_trashed_attachment_404`.

**Out of scope:** Rewrite rules (D5, P2-05). Thumbnail output (P1-08).
**Verification:**
1. foundry_verify, then `WPPA_DAM=0 composer test`.
2. `grep -c "readfile(" includes/class-streamer.php` prints 1.

**Depends on:** P1-06
**Workstream:** delivery

### P1-08: Escaped Publication_Item and list/dropdown templates; thumbnail through the bridge (D2 output, D3 front, D18)
**Goal:** Escape every value `Publication_Item` and the list and dropdown templates print, and pass the thumbnail through `Dam_Bridge::display_url()`.
**Files touched:** `includes/class-publication-item.php`, `templates/classic/template.wppa_publication_dropdown.php`, `templates/classic/template.wppa_publication_list.php`, `tests/integration/test-publication-item.php`, `tests/integration/dam/test-publication-item-dam.php`, `tests/fixtures/class-v3-expected-output.php` (only if a pinned string changes; name the D-item)
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. Remove every remaining `WordPress.Security` ignore in these three files (P7).
- D3:
  - `get_the_thumbnail()`: `$thumb = Hooks::item_upload_image( … )`, then `Plugin::instance()->dam_bridge()->display_url( $thumb )` (§6.9, D18), then `esc_url()` into `src`;
  - `get_the_title()`: `esc_url()` for the permalink and `esc_html()` on the filtered title;
  - `the_title()`/`the_thumbnail()`/`the_*()` echo through `wp_kses_post()`.
- D2: `list_downloads()` uses `esc_html()` on descriptions and `esc_url()` on links.
- Dropdown template: `esc_attr()` on the option value and `esc_html()` on `post_title` (D3). The list template must contain no unescaped echo.
- For ordinary fixture data the output must equal the pinned strings. If a string changes, update it naming D2 or D3.

**Acceptance tests:**
- `tests/integration/test-publication-item.php` adds:
  - D2: `test_d2_script_alternate_description_renders_escaped` (fixture `alternates`: contains `&lt;script&gt;alert(1)&lt;/script&gt;`, not `<script>alert(1)`);
  - D3: `test_d3_thumbnail_url_is_escaped` (raw meta `x" onerror="alert(1)`), `test_d3_filtered_title_is_escaped`, `test_d3_dropdown_escapes_post_title` (set a raw `<b>` title with `$wpdb->update` and clean the post cache);
  - bridge: `test_thumbnail_passes_through_display_url_unchanged_without_dam`, with `@group nodam` on the method.
- `tests/integration/dam/test-publication-item-dam.php` (`@group dam`): `test_d18_embargoed_thumbnail_renders_placeholder_for_anonymous`.

**Out of scope:** Dates (D7, P2-08). Widgets (P2-09).
**Verification:**
1. foundry_verify, then `WPPA_DAM=0 composer test`.
2. `grep -rn "WordPress\.Security" includes/class-publication-item.php templates/classic` prints nothing.

**Depends on:** P1-02
**Workstream:** markup

### P1-09: Gate 1 — security verified, constraints locked, branch pushed
**Goal:** Prove Phase 1 green in both DAM modes, with zero Security ignores and exactly one `readfile`; lock both invariants in as constraints; push; and list the human checks.
**Files touched:** `docs/foundry.json` (append two constraints), plus any file a fix requires, each listed in the commit.
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. SPEC §8.0 gate shape and §8 Phase 1 Gate 1.
- Append exactly these two entries to `constraints` in `docs/foundry.json`. Change nothing else in the file:
```json
{"id":"no-security-ignores","description":"SPEC §3 P7 at the Phase 1 gate: no phpcs ignore or disable silences a WordPress.Security sniff. Added by P1-09.","paths":["includes/","templates/","wp-publication-archive.php","uninstall.php"],"pattern":"phpcs:(ignore|disable).*WordPress\\.Security","shouldMatch":["// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- reason: D3","/* phpcs:disable WordPress.Security.NonceVerification */"],"shouldNotMatch":["unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- reason: Streamer owns the temp file","echo esc_html( $title );"]}
```
```json
{"id":"no-ob-clean","description":"SPEC D6: ob_clean() with no buffer raised notices; Streamer ends buffers only while ob_get_level() is above its floor. Added by P1-09.","paths":["includes/","templates/"],"pattern":"\\bob_clean\\s*\\(","shouldMatch":["ob_clean();","\t\tob_clean( );"],"shouldNotMatch":["ob_end_clean();","$html = ob_get_clean();"]}
```
- A fix here keeps each workstream's tests intact. If a fix needs a test change, name the D-item.

**Acceptance tests:** No new test files. The following must pass:
- `composer verify` with the DAM loaded;
- `WPPA_DAM=0 composer test`;
- `foundry_verify`, including both new constraints;
- `grep -rn "phpcs:\(ignore\|disable\).*WordPress\.Security" includes templates wp-publication-archive.php uninstall.php` prints nothing (P7);
- `grep -rn "readfile(" includes` prints exactly one line, in `includes/class-streamer.php`, inside `send()`.

**Out of scope:** Any Phase 2 work.
**Verification:**
1. The commands above, then push per policy.
2. Record the CI result as in P0-16.
3. Log `Manual check: NOT VERIFIED (human)` with SPEC §8 Phase 1's three checks:
   1. As an Author, save `/etc/passwd` as the document URL: the field comes back empty and the view URL returns 404.
   2. Embargo the linked attachment in the DAM: the view URL 404s logged out and redirects logged in as an Editor.
   3. With `add_filter( 'wppa_mask_url', '__return_true' )` in an mu-plugin, a 60 MB file redirects rather than proxies.

**Depends on:** P1-04, P1-05, P1-07, P1-08
**Workstream:** serial

## Phase 2 — Correctness and compatibility
Contracts (P2-01, P2-02, serial) → Wave 2 (`model`, `routing`, `admin`, `front`) → Gate 2 (P2-10). SPEC §8 Phase 2.

### P2-01: Contracts (1/2) — Keys, Flags and Plugin for §6.1, §6.6 and §6.7; close D11 and D8's filter; Capabilities signatures; upgrade on init
**Goal:** Make every shared-file edit Wave 2 needs, remove the D11 notice and the D8 `post_type_link` hijack, and move the schema upgrade to `init`.
**Files touched:**
- shared: `includes/class-keys.php`, `includes/class-flags.php`, `includes/class-plugin.php`, `docs/HOOKS.md`;
- services: `includes/class-capabilities.php` (new), `includes/class-rewrites.php`, `includes/class-post-type.php` (constructor only);
- test support: `tests/fixtures/class-v3-site.php`, `tests/fixtures/class-v3-expected-output.php`;
- tests: `tests/unit/test-keys.php`, `tests/integration/test-plugin.php`, `tests/integration/test-capabilities.php`, `tests/integration/test-rewrites.php`, `tests/integration/test-characterisation-routing.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. No live hook may point at a stub (Decisions).
- `Keys` adds:
  - REST and taxonomy: `REST_BASE` `'publications'`, `TAX_AUTHOR_QUERY_VAR` `'publication-author'`, `TAX_AUTHOR_REWRITE_SLUG` `'publication/author'`;
  - capabilities: `CAPABILITY_TYPE` `array( 'publication', 'publications' )`, `CAP_ROLES` `array( 'administrator', 'editor', 'author' )`, and `CAP_MAP`. `CAP_MAP` maps each primitive post cap to its publication equivalent: `edit_posts` → `edit_publications`, `edit_others_posts`, `edit_private_posts`, `edit_published_posts`, `publish_posts`, `read_private_posts`, `delete_posts`, `delete_private_posts`, `delete_published_posts`, `delete_others_posts`, each to the `_publications` form (§6.1);
  - `OPT_CAPS` `'wp-publication-archive-caps'`;
  - admin script: `ADMIN_SCRIPT_HANDLE` `'wp-publication-archive-admin-media'`, `ADMIN_SCRIPT_PATH` `'assets/js/admin-media.js'`, `ADMIN_SCREENS` `array( 'post.php', 'post-new.php' )`;
  - `UPGRADE_PRIORITY` `20`.
- `Flags` adds `caps_granted(): bool` and `mark_caps_granted(): void` (`update_option( Keys::OPT_CAPS, 1, false )`, §5.2).
- `Capabilities( Flags $flags )` (final): stubs `grant(): void` and `maybe_grant(): void`. `Plugin` constructs it and adds an accessor and `replace()` case, but registers nothing for it (P2-02 does).
- `Post_Type`'s constructor becomes `__construct( Url_Policy $policy )`, which P2-04 needs; `Plugin` passes it.
- D11: delete `Plugin::fopen_notice()` and its conditional `admin_notices` registration; mark it removed in `docs/HOOKS.md`.
- D8, filter half (Decisions): delete `Plugin`'s `post_type_link` registration, `Rewrites::filter_post_type_link()`, `disarm()` and the armed and suspended flags. `link()` keeps its output. `V3_Site::reset_link_state()` stays tolerant, because its `method_exists` checks make it a no-op.
- D9 timing: `Plugin::boot()` no longer calls `maybe_upgrade()`. Register `init` → `upgrade->maybe_upgrade` at `Keys::UPGRADE_PRIORITY`. The body is unchanged here; P2-06 fixes it.
- Pinned strings: in `V3_Expected_Output`, the list title links for items 2+ become the canonical `get_permalink()` (`{site}/publication/{slug:…}/`). `test_permalink_after_link_generation_matches_301` becomes `test_d8_permalink_unchanged_after_link_generation`. Remove the P0-08 hijack tests from `test-rewrites.php`. Name D8 in the commit.

**Acceptance tests:**
- `tests/unit/test-keys.php` adds `test_phase_2_names`.
- `tests/integration/test-plugin.php` adds:
  - `test_d11_no_allow_url_fopen_notice_is_defined_or_registered`: `! method_exists( Plugin::class, 'fopen_notice' )` and no plugin callback on `Keys::HOOK_ADMIN_NOTICES`. This fails on the pre-task code;
  - `test_d8_post_type_link_not_hooked_by_plugin`;
  - `test_upgrade_runs_on_init_not_at_boot`: `has_action( Keys::HOOK_INIT, array( $upgrade, 'maybe_upgrade' ) ) === Keys::UPGRADE_PRIORITY`.
- `tests/integration/test-characterisation-routing.php`: `test_d8_permalink_unchanged_after_link_generation`.
- `tests/integration/test-capabilities.php`: `test_is_final`.

**Out of scope:** Capability grants (P2-02). The CPT, REST, rewrite, upgrade-body, admin and widget changes (the wave).
**Verification:** `composer dump-autoload -o`, then foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P1-09
**Workstream:** serial

### P2-02: Contracts (2/2) — Capabilities granted on activation and init
**Goal:** Implement and register `Capabilities` so each role gets the `publication` equivalents of its `post` caps, once, before Wave 2 switches the CPT to `capability_type` `publication`.
**Files touched:** `includes/class-capabilities.php`, `includes/class-plugin.php`, `includes/class-cli.php`, `tests/integration/test-capabilities.php`, `tests/integration/test-cli.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply (options only via `Flags`).
- §6.1: `grant()` gives each role in `Keys::CAP_ROLES`, for each `CAP_MAP` pair, the publication cap if and only if the role has the post cap. The author therefore gets the same subset it has for `post`. Then it calls `Flags::mark_caps_granted()`. Running it twice changes nothing. `maybe_grant()` calls `grant()` only when `! Flags::caps_granted()`.
- `Plugin`: register `init` → `capabilities->maybe_grant` (priority 10); `activate()` also calls `grant()`.
- `Cli` adds the `caps_granted` row (§6.8): pass iff `Flags::caps_granted()` and the `administrator` role has `edit_publications`.

**Acceptance tests:**
- `tests/integration/test-capabilities.php`:
  - `test_grant_gives_administrator_every_publication_cap`;
  - `test_grant_gives_editor_the_caps_matching_its_post_caps`;
  - `test_grant_gives_author_the_same_subset_as_for_post`;
  - `test_grant_records_the_option`;
  - `test_grant_twice_changes_nothing`;
  - `test_maybe_grant_skips_when_already_granted`.
- `tests/integration/test-cli.php`: `test_caps_granted_row_passes_after_grant`, `test_caps_granted_row_fails_before_grant`.

**Out of scope:** Changing `capability_type` on the CPT (P2-03).
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test`, then `npx wp-env run cli wp publication-archive doctor` exits 0.
**Depends on:** P2-01
**Workstream:** serial

### P2-03: Post type and taxonomy in REST and the block editor (D12)
**Goal:** Expose the `publication` CPT and the `publication-author` taxonomy to REST and the block editor with publication capabilities, per §6.1.
**Files touched:** `includes/class-post-type.php`, `includes/class-cli.php`, `tests/integration/test-post-type.php`, `tests/integration/test-cli.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply.
- §6.1: keep every 3.0.1 CPT argument and add:
  - `show_in_rest => true`, `rest_base => Keys::REST_BASE`;
  - `capability_type => Keys::CAPABILITY_TYPE`, `map_meta_cap => true`.

  `menu_icon` is already a dashicon.
- The taxonomy gains `show_in_rest => true`, `public => true`, `query_var => Keys::TAX_AUTHOR_QUERY_VAR` and `rewrite => array( 'slug' => Keys::TAX_AUTHOR_REWRITE_SLUG )`.
- `Cli`:
  - add the `rest_enabled` row: pass iff the post type object and the taxonomy object both have `show_in_rest` true;
  - `rewrite_rules_present` now also requires a rule starting with `publication/author/`.
- REST tests create an Editor with `wp_set_current_user()`. Caps come from P2-02.

**Acceptance tests:** `tests/integration/test-post-type.php` adds:
- `test_d12_post_type_shows_in_rest_with_rest_base`;
- `test_d12_author_taxonomy_shows_in_rest`;
- `test_capability_type_is_publication_with_map_meta_cap`;
- `test_author_taxonomy_rewrite_slug_and_query_var`;
- `test_d12_rest_lists_fixture_publications`.

`tests/integration/test-cli.php` adds `test_rest_enabled_row_passes` and `test_rewrite_row_requires_the_author_slug`.

**Out of scope:** Registered meta (P2-04).
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P2-02
**Workstream:** model

### P2-04: Registered publication meta in REST (D12 meta, D1 via REST)
**Goal:** Register the three §5.1 meta keys for REST, with schemas, edit-only visibility, `edit_post` auth and `Url_Policy` sanitising.
**Files touched:** `includes/class-post-type.php`, `tests/integration/test-post-type.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. Stored formats are frozen (P16).
- §6.1: `register_post_meta( Keys::POST_TYPE, … )` in `Post_Type::register()`:
  - `META_DOC` and `META_IMAGE`: `single` true, `type` string, `show_in_rest => array( 'schema' => array( 'type' => 'string', 'format' => 'uri', 'context' => array( 'edit' ) ) )`;
  - `META_ALTERNATES`: `single` false, `type` object, schema properties `description` (string) and `url` (string), `context` edit;
  - `auth_callback`: `current_user_can( 'edit_post', $object_id )`;
  - `sanitize_callback` for the URL keys: `$this->policy->validate()`, storing `''` on `\WP_Error`;
  - for alternates: `sanitize_text_field()` on the description and `validate()` on the URL, with `''` when invalid.
- The `edit` context is the Decisions reading of "without it for anonymous".

**Acceptance tests:** `tests/integration/test-post-type.php` adds:
- REST reads: `test_d12_rest_exposes_meta_to_editor_in_edit_context`, `test_d12_rest_hides_meta_from_anonymous`;
- REST writes: `test_d1_rest_write_of_local_path_stores_empty_string`, `test_rest_write_of_same_site_url_is_kept`, `test_alternates_rest_write_sanitises_description_and_validates_url`, `test_rest_meta_write_denied_without_edit_post`;
- raw data: `test_raw_301_pipe_value_is_untouched_until_written`.

**Out of scope:** Block editor UI or any block (§2 non-goals).
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P2-03
**Workstream:** model

### P2-05: Rewrite rules — slug collisions (D5)
**Goal:** Make sure a publication whose slug is `view` or `download` is reachable at its permalink while every endpoint keeps working, per §6.1 and D5.
**Files touched:** `includes/class-rewrites.php`, `tests/integration/test-rewrites.php`, `tests/integration/test-characterisation-routing.php` (only if a pinned D5 behaviour changes; name D5)
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. G5 URLs are frozen.
- Write the D5 tests first and run them against the pre-task code. The P0-06 progress-log entry records whether 3.0.1 reached slug `view`.
  - If they fail: fix `Rewrites::register()` without changing any G5 URL. The endpoint rules must stay ahead of the CPT's attachment-child rule, so keep them `'top'`; require a non-empty slug segment; add rules for the `view`/`download` slugs themselves only if needed. Update the pinned routing test naming D5.
  - If they already pass: a reading of the 3.0.1 regexes says they may, because the endpoint rules need a non-empty segment after `view/`. Then change no rule, keep the tests as regression tests, write `D5: not reproducible on 3.0.1; regression tests added` under Interpretation, and continue. Do not invent a different defect.

**Acceptance tests:** `tests/integration/test-rewrites.php` adds:
- `test_d5_slug_view_publication_resolves_at_publication_view`;
- `test_d5_slug_download_publication_resolves_at_publication_download`;
- `test_d5_view_endpoint_for_other_slug_opens_other_slug`;
- `test_d5_download_endpoint_for_slug_view_opens_view`.

**Out of scope:** Link generation changes. The upgrade (P2-06).
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P2-02
**Workstream:** routing

### P2-06: Upgrade on init, once, with no per-request option writes (D9)
**Goal:** Make the schema upgrade run once on `init`, after the rules are registered, flush rewrites once, and do no option work on later requests.
**Files touched:** `includes/class-upgrade.php`, `tests/integration/test-upgrade.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply (options only via `Flags`).
- D9: `maybe_upgrade()`:
  - `$from = Flags::schema_version()`;
  - if `null` or `< Keys::SCHEMA_VERSION`: `run( $from )`, then `Flags::set_schema_version( Keys::SCHEMA_VERSION )` (autoload off, §5.2), then `flush_rewrite_rules( false )`;
  - otherwise do nothing (no `add_option`).
- `Plugin` already runs it at `init` `Keys::UPGRADE_PRIORITY` (P2-01).
- The schema option keeps its name, value `3` and integer format (G4).

**Acceptance tests:** `tests/integration/test-upgrade.php` adds:
- `test_d9_upgrade_flush_includes_the_publication_rules`: delete the option, call `maybe_upgrade()`, and assert `Flags::rewrite_rules()` has a key starting `publication/view/`. This fails on the pre-task body run at load;
- `test_d9_second_run_writes_no_option_and_does_not_flush`: count `add_option`, `update_option` and `generate_rewrite_rules` firings; the result is 0;
- `test_schema_option_autoload_is_off`;
- `test_absent_schema_upgrades_to_3`.

**Out of scope:** Changing `run()`'s case-2 migration.
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P2-05
**Workstream:** routing

### P2-07: admin-media.js replaces Thickbox and inline scripts (D13)
**Goal:** Replace the Thickbox uploader and the meta boxes' inline scripts with `assets/js/admin-media.js`, using a `wp.media` frame and enqueued only on publication edit screens.
**Files touched:** `assets/js/admin-media.js`, `includes/class-meta-boxes.php`, `includes/class-assets.php`, `tests/integration/test-admin-media.php`, `tests/integration/test-assets.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. No `<script` in meta box markup.
- `Assets::enqueue_admin( string $hook_suffix = '' )` (§6.7):
  - acts only when `$hook_suffix` is in `Keys::ADMIN_SCREENS` and `get_current_screen()->post_type === Keys::POST_TYPE`;
  - calls `wp_enqueue_media()`, then `wp_enqueue_script( Keys::ADMIN_SCRIPT_HANDLE, plugins_url( Keys::ADMIN_SCRIPT_PATH, <plugin file> ), array( 'media-editor' ), Keys::ASSET_VERSION, true )`;
  - frame titles and row labels go through `wp_localize_script( …, 'wppaAdminMedia', … )` with the new text domain;
  - no `thickbox` or `media-upload` enqueue.
- `Meta_Boxes`: remove the three inline `<script>` blocks. Keep the box ids, `#upload_doc_button`, `#wpa-upload_image_button`, the `.wpa-upload-row` and `.wpa-delete-row` spans, `#wpa-alternates-button`, `#wpa-alternate-table` and the field names `wpa_upload_doc`, `wpa-upload_image`, `wpa-alternates[description][]` and `wpa-alternates[url][]`. The escaping from P1-04 stays.
- `assets/js/admin-media.js`:
  - on click of `#upload_doc_button`, `#wpa-upload_image_button` or `.wpa-upload-row`, open `wp.media( { multiple: false } )` and write the selected attachment's `url` into that row's input;
  - Add Row appends a new description/url row with the same field names; Delete removes its row;
  - the only jQuery is `jQuery( document ).on()` delegation; it never touches `window.send_to_editor`.

**Acceptance tests:** `tests/integration/test-admin-media.php`:
- `test_d13_script_enqueued_on_publication_edit_screen`: `set_current_screen( Keys::POST_TYPE )`, fire `admin_enqueue_scripts` with `post.php`, and check the handle is enqueued with dep `media-editor`;
- `test_d13_script_not_enqueued_on_post_edit_screen`;
- `test_d13_thickbox_not_enqueued`;
- `test_d13_meta_box_markup_has_no_script_or_thickbox` (no `<script`, no `TB_iframe`);
- `test_meta_box_ids_and_field_names_unchanged`;
- `test_admin_media_js_never_touches_send_to_editor` (file content).

`tests/integration/test-assets.php`: replace `test_admin_enqueues_thickbox_until_d13` with `test_admin_enqueue_is_limited_to_publication_screens`.

**Out of scope:** Any block editor UI. Changing stored formats.
**Verification:**
1. foundry_verify, then `WPPA_DAM=0 composer test`.
2. `grep -rn "TB_iframe\|send_to_editor\|thickbox" includes assets/js` prints nothing.

**Depends on:** P2-02
**Workstream:** admin

### P2-08: Site-timezone dates and dead-code delegates (D7, D8 delegates)
**Goal:** Format publication dates through `Clock::format()` in the site timezone and locale, and make the never-hooked `the_content`, `the_title` and `publication_link` return their first argument.
**Files touched:** `includes/class-publication-item.php`, `includes/legacy/class-publication-archive.php`, `tests/integration/test-publication-item.php`, `tests/integration/test-publication-archive.php`, `tests/fixtures/class-v3-expected-output.php` (only if a pinned date changes; name D7)
**Design constraints:**
- Global: CLAUDE.md § Constraints apply (no-wall-clock: dates only through `Clock`).
- D7: `get_the_authors()` formats the date as `Plugin::instance()->clock()->format( 'F j, Y', (int) get_post_time( 'U', true, $this->post ) )`.
- D8: `Legacy\Publication_Archive::the_content( $content )`, `the_title( $title, $id = 0 )` and `publication_link( $permalink, $post )` return their first argument unchanged. Keep the signatures (§6.3).

**Acceptance tests:**
- `tests/integration/test-publication-item.php` adds `test_d7_date_follows_site_timezone`. Create a publication with `post_date` = `post_date_gmt` = `2020-01-01 23:30:00` while the site is UTC, then set `timezone_string` to `Pacific/Auckland`. The authors markup contains `January 2, 2020`; the pre-task code prints `January 1, 2020`.
- `tests/integration/test-publication-archive.php` adds `test_d8_the_title_returns_title_unchanged`, `test_d8_the_content_returns_content_unchanged` and `test_d8_publication_link_returns_permalink_unchanged`.

**Out of scope:** Widgets (P2-09).
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P2-02
**Workstream:** front

### P2-09: Widgets in the Legacy Widget block, id_base preservation, shortcode check (D14)
**Goal:** Give all three widgets `show_instance_in_rest`, prove that 3.0.1-shaped widget options still render, and confirm the shortcode matches the characterisation strings.
**Files touched:**
- widgets: `includes/widgets/class-archive-widget.php`, `includes/widgets/class-category-count-widget.php`, `includes/widgets/class-related-widget.php`;
- shortcode and templates, only if an escaping gap remains: `includes/class-shortcode.php`, `templates/classic/template.wppa_widget.php`, `templates/classic/single-publication.php`, `templates/classic/archive-publication.php`;
- tests: `tests/integration/test-widgets.php`, `tests/integration/test-shortcode.php`, `tests/integration/test-archive-widget.php`, `tests/integration/test-category-count-widget.php`, `tests/integration/test-related-widget.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply.
- §6.6: each constructor adds `'show_instance_in_rest' => true` to its widget options. `id_base` values stay the `Keys` literals, and markup is unchanged. D15 is already closed (P0-12); `extract` stays forbidden.

**Acceptance tests:**
- `tests/integration/test-widgets.php`:
  - `test_d14_every_widget_shows_instance_in_rest`;
  - `test_id_bases_are_the_301_values`;
  - `test_widget_renders_from_301_shaped_option`: store `widget_<id_base>` = `array( 2 => <3.0.1 instance>, '_multiwidget' => 1 )` and a sidebar containing `<id_base>-2`, then assert `dynamic_sidebar()` output contains the title;
  - `test_d14_widget_types_encode_returns_raw_instance`: `POST /wp/v2/widget-types/<id_base>/encode` as an administrator.
- `tests/integration/test-shortcode.php` adds `test_shortcode_output_matches_characterisation_strings`, using `V3_Expected_Output`.

**Out of scope:** Block versions of the widgets (§2 non-goals).
**Verification:** foundry_verify, then `WPPA_DAM=0 composer test`.
**Depends on:** P2-08
**Workstream:** front

### P2-10: Gate 2 — compatibility verified, constraints locked, branch pushed
**Goal:** Prove Phase 2 green in both DAM modes with the full 3.0.1 surface intact, lock in the D11 and D13 invariants, push, and list the human checks.
**Files touched:** `docs/foundry.json` (append two constraints), plus any file a fix requires, each listed in the commit.
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. SPEC §8.0 gate shape and §8 Phase 2 Gate 2.
- Append exactly these two entries to `constraints` in `docs/foundry.json`. Change nothing else:
```json
{"id":"no-thickbox","description":"SPEC D13: no Thickbox, media-upload.php iframe or send_to_editor override. Added by P2-10.","paths":["includes/","templates/","assets/js/"],"pattern":"TB_iframe|send_to_editor|\\btb_(show|remove)\\s*\\(|['\"](thickbox|media-upload)['\"]","shouldMatch":["window.tb_show( title, 'media-upload.php?TB_iframe=1&width=640' );","wp_enqueue_script( 'thickbox' );","window.send_to_editor = handler;","wp_enqueue_script( 'media-upload' );"],"shouldNotMatch":["wp_enqueue_media();","var frame = wp.media( { multiple: false } );"]}
```
```json
{"id":"no-allow-url-fopen","description":"SPEC D11: nothing checks or reports allow_url_fopen. Added by P2-10.","paths":["includes/","wp-publication-archive.php"],"pattern":"allow_url_fopen","shouldMatch":["if ( ! (bool) ini_get( 'allow_url_fopen' ) ) {"],"shouldNotMatch":["$enabled = Hooks::filter_enabled( true, 'assets' );"]}
```

**Acceptance tests:** No new test files. The following must pass:
- `composer verify` with the DAM loaded;
- `WPPA_DAM=0 composer test`;
- `tests/integration/test-aliases.php` green in both modes;
- `foundry_verify`, including both new constraints;
- `npx wp-env run cli wp publication-archive doctor` shows all nine rows passing.

**Out of scope:** Any Phase 3 work.
**Verification:**
1. The commands above, then push per policy.
2. Record the CI result as in P0-16.
3. Log `Manual check: NOT VERIFIED (human)` with SPEC §8 Phase 2's checks:
   1. In the block editor, Publications → Add New shows the three meta boxes, and Upload opens the media modal and fills the URL.
   2. The Legacy Widget block previews all three widgets.
   3. The list and dropdown shortcodes and the single and archive pages render with no notices in `debug.log`, first with the DAM active and again with it deactivated.

**Depends on:** P2-04, P2-06, P2-07, P2-09
**Workstream:** serial

## Phase 3 — Release
Serial; small, and every file is a hotspot. SPEC §8 Phase 3.

### P3-01: uninstall.php, .distignore and composer build
**Goal:** Add a data-safe uninstall and a `composer build` that writes an installable `dist/wp-publication-archive.zip` containing only runtime files.
**Files touched:**
- runtime: `uninstall.php`, `includes/class-flags.php`;
- build: `.distignore`, `bin/build-zip.sh`, `composer.json`;
- config: `phpcs.xml.dist`, `phpstan.neon.dist`;
- tests: `tests/integration/test-flags.php`, `tests/unit/test-distignore.php`.

**Design constraints:**
- Global: CLAUDE.md § Constraints apply. `uninstall.php` is in the names, security-ignore and raw-read constraint paths.
- `uninstall.php` (§4.1):
  - `if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }`;
  - `require_once __DIR__ . '/vendor/autoload.php';`;
  - `( new \WPPA\Flags( new \WPPA\SystemClock() ) )->delete_all();`.

  Never post data, meta, terms or roles.
- `Flags::delete_all(): void` deletes `OPT_SCHEMA`, `OPT_CAPS` and `OPT_ENABLED` (Decisions; see Spec issues).
- `phpcs.xml.dist` and `phpstan.neon.dist` add `uninstall.php`.
- `.distignore` excludes:
  - repo and dev metadata: `/.git`, `/.github`, `/.cache`, `/.claude`, `/.foundry`, `.distignore`, `.gitignore`, `.editorconfig`, `CLAUDE.md`;
  - dev directories: `/bin`, `/docs`, `/tests`, `/phpstan`, `/node_modules`, `/vendor`, `/dist`;
  - dev config: `.wp-env.json`, `.wp-env.override.json`, `package.json`, `package-lock.json`, `phpcs.xml.dist`, `phpstan.neon.dist`, `phpunit.xml.dist`, `.phpunit.result.cache`;
  - planning docs: `V4_SPEC.md`, `V4_FOUNDRY.json`, `V4-ASSESSMENT.md`.
- `bin/build-zip.sh` (`set -euo pipefail`; §7):
  1. `rm -rf dist`, then `rsync -a --exclude-from=.distignore ./ dist/build/wp-publication-archive/`. Copy `composer.json` and `composer.lock` in explicitly if excluded.
  2. `composer install --no-dev -o --no-interaction` in the staging directory, then delete its `composer.json` and `composer.lock`.
  3. `(cd dist/build && zip -rq ../wp-publication-archive.zip wp-publication-archive)`.
  4. Fail with a message if `unzip -l` shows `tests/`, `bin/`, `.cache/` or anything under `vendor/` other than `vendor/autoload.php` and `vendor/composer/`.
- `composer.json` scripts add `"build": "bash bin/build-zip.sh"`. Run `composer validate`.

**Acceptance tests:**
- `tests/integration/test-flags.php` adds:
  - `test_delete_all_removes_the_three_options`;
  - `test_delete_all_leaves_publications_and_meta`;
  - `test_uninstall_file_guards_and_calls_delete_all`, which reads `uninstall.php` for the `WP_UNINSTALL_PLUGIN` guard and the `delete_all()` call.
- `tests/unit/test-distignore.php`: `test_distignore_excludes_dev_paths`.

**Out of scope:** Readme and changelog (P3-02). The version bump (P3-02).
**Verification:**
1. foundry_verify.
2. `composer build`.
3. `unzip -l dist/wp-publication-archive.zip | grep -E "tests/|bin/|\.cache/|phpunit|squizlabs"` prints nothing.
4. `unzip -l dist/wp-publication-archive.zip | grep vendor/autoload.php` prints one line.

**Depends on:** P2-10
**Workstream:** serial

### P3-02: readme.txt, CHANGELOG.md, version 3.1.0 and the HOOKS.md final pass
**Goal:** Set the release version everywhere through `Keys`, and ship the readme, changelog, upgrade notice and final hook documentation that SPEC §5.5 requires.
**Files touched:** `readme.txt`, `CHANGELOG.md`, `wp-publication-archive.php`, `includes/class-keys.php`, `docs/HOOKS.md`, `languages/wp-publication-archive.pot`, `tests/unit/test-keys.php`, `tests/unit/test-readme.php`
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. The version literal lives only in `Keys` and the header docblock (no-hardcoded-versions).
- `Keys::VERSION` = `'3.1.0'`; the header `Version: 3.1.0`. The other §5.5 headers are unchanged.
- `readme.txt` (§5.5):
  - `Requires at least: 6.7`, `Tested up to: 7.1`, `Requires PHP: 7.4`, `Stable tag: 3.1.0`;
  - a 3.1.0 changelog entry listing D1–D19 in plain language;
  - a 3.1.0 Upgrade Notice with two points: downloads now redirect by default and proxying is opt-in through `wppa_mask_url`, which a redirect cannot use to force a download filename; and hook callbacks named by the 3.0.1 global class callables can no longer be removed (§2).
- `CHANGELOG.md`: a 3.1.0 section mirroring the readme entry.
- `docs/HOOKS.md` final pass: every exposed hook with its `Hooks::` method, arguments and the version it dates from; the removed list (D4, D11); the tunables with their `Keys` defaults; and the DAM hook.
- Regenerate the `.pot` with `npx wp-env run cli wp i18n make-pot wp-content/plugins/wp-publication-archive wp-content/plugins/wp-publication-archive/languages/wp-publication-archive.pot --exclude=tests,bin,vendor,node_modules,.cache,dist`. If WP-CLI's i18n command is unavailable, keep the existing `.pot` and note it.

**Acceptance tests:**
- `tests/unit/test-keys.php`: `test_versions` now expects `'3.1.0'`, and `test_plugin_header_matches_keys` passes.
- `tests/unit/test-readme.php`:
  - `test_readme_headers_match_keys` (Tested up to 7.1, `Stable tag: ` . `Keys::VERSION`, `Requires PHP` and `Requires at least` from `Keys`);
  - `test_readme_has_310_changelog_and_upgrade_notice`: the notice mentions `Keys::FILTER_MASK_URL`;
  - `test_changelog_md_has_310_section`.

**Out of scope:** Any behaviour change.
**Verification:** foundry_verify.
**Depends on:** P3-01
**Workstream:** serial

### P3-03: Final gate — verify, build, push
**Goal:** Prove the release candidate green in both DAM modes, build and inspect the zip, push the branch, and list the human release checks.
**Files touched:** Only files a fix requires, each listed in the commit.
**Design constraints:**
- Global: CLAUDE.md § Constraints apply. SPEC §1 "Done means" and §8 Phase 3 final gate.
- The PR is opened by the Foundry controller and marked ready by the summarizer (§7.2), not by this task.

**Acceptance tests:** No new test files. The following must pass:
- `composer verify` with the DAM loaded;
- `WPPA_DAM=0 composer test`;
- `composer build`;
- `foundry_verify`;
- the plugin activates with no notices, both with and without the DAM: after `npx wp-env run cli wp plugin deactivate wp-publication-archive && npx wp-env run cli wp plugin activate wp-publication-archive`, `wp-content/debug.log` gains no line mentioning `wp-publication-archive`;
- `npx wp-env run cli wp publication-archive doctor` exits 0.

**Out of scope:** New features or refactors.
**Verification:**
1. The commands above.
2. `unzip -l dist/wp-publication-archive.zip` shows no `tests/`, `bin/` or `.cache/`, and no dev `vendor/` packages.
3. Push per policy, and record CI as in P0-16.
4. Log `Manual check: NOT VERIFIED (human)` with SPEC §8 Phase 3's checks:
   1. Install `dist/wp-publication-archive.zip` on a fresh WordPress 7.1 site restored from a 3.0.1 database: there are no activation errors, every G5 URL resolves, and the German translation loads with `WPLANG=de_DE`.
   2. Repeat with the DAM active.
   3. Mark the PR ready (§7.2) if the summarizer could not.

**Depends on:** P3-02
**Workstream:** serial

## Spec issues
1. **Constraints are live from the first task, but Phase 0 preserves 3.0.1 until the restructure.**
   - The problem: `foundry_verify` scans the whole repo every task, and the 3.0.1 bootstrap and `includes/*.php` templates already violate seven constraints. No constraint has an "active from" point.
   - Resolution: P0-01 relocates that code to `lib/`, which no constraint, lint or analysis path covers. That also realises "outside the lint and analysis paths for this task only" (read as: until the restructure removes it).
2. **The restructure cannot keep some defects verbatim, because a live constraint forbids the 3.0.1 code.** Covered: D1's `readfile`/`sslverify`, D4's `global $wp` and run-time `add_filter`, D7's `date()`, D15's `extract()`, `global $post`/`$wp_query`, and unescaped non-D output.
   - Resolution: the Decisions entry lists how each is handled.
   - Net effect: D4 and D15 close in Phase 0 (P0-08, P0-12), and D14's escaping half lands in Phase 0 while `show_instance_in_rest` stays in P2-09. SPEC's table puts D4 in P2 contracts and D15 in the P2 `front` workstream.
3. **D8 is not a no-op.** `get_link()` re-adds `publication_link` to `post_type_link` after its own `get_permalink()`. From then on, publication permalinks in that request become `site_url()/publication/wppa_open/<slug>`, which is broken under pretty permalinks.
   - Resolution: pinned in P0-06 and P0-07, ported faithfully in P0-08, and removed in P2-01 under D8. That changes the list shortcode's title links for items 2+ to the canonical permalink. A human should confirm this is the intended D8 outcome.
4. **D5 may not reproduce.** The 3.0.1 endpoint rules need a non-empty segment after `view/`/`download/`, and WordPress trims the request path. So `/publication/view/` should already resolve to a publication with slug `view`.
   - Resolution: P0-06 records what 3.0.1 does. P2-05 fixes the rules only if its tests fail first; otherwise it keeps them as regression tests and says D5 was not reproducible.
5. **P11 vs §6.2.** P11 says `readfile`'s argument comes from `wp_tempnam()` "in the same method", but §6.2 has `Delivery` call `wp_tempnam()` and `Streamer::send()` read the file.
   - Resolution: `Streamer::send()` refuses any path outside its temp directory; the reviewer checks by reading.
6. **`wp_safe_redirect()` sends external hosts to `wp-admin`.** That breaks external document URLs that pass `validate()`.
   - Resolution: a scoped `allowed_redirect_hosts` callback allows exactly the validated host during the redirect.
7. **The module map is incomplete.**
   - `Publication_Item` and the widgets are instantiated by theme code and WordPress with 3.0.1 constructors, so they cannot receive services by injection; they use `Plugin::instance()`.
   - `Categories` reads options, so it needs `Flags`.
   - `Url_Policy` needs `Keys::ERR_INVALID_URL`, because P1 forbids the literal. It needs no `use` line.
8. **Contracts would wire live hooks to throwing stubs.** This applies to the DAM filter (the DAM fires it when it indexes a post) and to `Capabilities` on `init`.
   - Resolution: P1-02 and P2-02 are second serial contracts tasks that implement those callbacks. D19 therefore closes in P1-02, not in the `dam` workstream.
9. **The `dam` doctor row cannot exist in Phase 0.** DAM symbols are confined to `Dam_Bridge` (P14), which Phase 1 creates, and with the DAM present the row would fail until the filter is registered.
   - Resolution: it moves to P1-02. Likewise, `caps_granted` and `rest_enabled` land with their features.
10. **Inert-bridge tests cannot run while the DAM is loaded**, because `active()` is class-based.
    - Resolution: a `nodam` group, which `bin/test.sh` excludes when the DAM is loaded. SPEC says "runs every group".
11. **"Deletes the two options (§5.2)"** — §5.2 lists three.
    - Resolution: `uninstall.php` deletes all three. If only `OPT_SCHEMA` and `OPT_CAPS` were meant, P3-01's `Flags::delete_all()` drops `OPT_ENABLED`.
12. **The shortcode's `$_GET['wpa-paged']` read would need a `NonceVerification` ignore**, which P7 forbids.
    - Resolution: read it through `get_query_var()`; 3.0.1 already registers it as a query var.
13. **REST meta visibility.** `auth_callback` does not hide registered meta from anonymous readers.
    - Resolution: an edit-only schema context.
14. **Sanitize callbacks rewrite hostile or pipe-form test data** once meta is registered (P2-04).
    - Resolution: fixtures write raw rows through `V3_Site::raw_meta()` from P0-04 on.
15. **Plain-permalink link generation in 3.0.1 is broken.** It emits `?view=yes`, `?download=yes` and `&alt=` params that the endpoints ignore; the G5 query forms use `wppa_open`/`wppa_download`.
    - Resolution: no D-item covers it and URLs are frozen, so it is preserved and pinned. A human should decide whether 3.1.0 should emit the `wppa_open=yes` form.
16. **Icon URLs move to `assets/icons/`** (§6.3), yet the characterisation tests must "pass unchanged".
    - Resolution: the expected strings resolve icon URLs through `get_image()`.
17. **`images/cabinet.png` is deleted in Phase 0** (§4.1), but the dashicon `menu_icon` is listed under §6.1 (Phase 2).
    - Resolution: P0-08 sets the dashicon.
18. **Headers and output buffers cannot be observed under PHPUnit's CLI SAPI.**
    - Resolution: `Delivery`/`Streamer` take injectable header and exit callables and an output-buffer floor. The zero-buffer D6 check runs in a child PHP process.
19. **`Url_Policy`/`Dam_Bridge` are `final`**, so the "test doubles" in the `delivery` workstream are the real `Url_Policy` with a stub callable. Delivery's unit tests cover a pure `decide()` helper.
20. **3.0.1 calls `widget_title` with one argument in the archive widget and three in the others.** `Hooks::widget_title()` uses a variadic tail so that callbacks see exactly the 3.0.1 arity.
21. **`the_widget( 'WP_Publication_Archive_Widget' )` in themes needs the factory key to be the 3.0.1 class name.**
    - Resolution: widgets are registered under the legacy names through the aliases.

## Review fixes (round 1)

### R1-01: Disable Composer's process timeout so composer test/verify can finish
**Goal:** `composer test` and `composer verify` run to completion without a caller-side COMPOSER_PROCESS_TIMEOUT override, so no host-side timeout orphans phpunit inside the wp-env tests-cli container (REVIEW finding 2).
**Files touched:** composer.json, tests/unit/test-composer-config.php
**Design constraints:** Add `"process-timeout": 0` to composer.json's `config` block. Change nothing else in composer.json (scripts, require, autoload stay byte-identical). CLAUDE.md § Constraints apply.
**Acceptance tests:** New tests/unit/test-composer-config.php (namespace WPPA\Tests\Unit, extends \Yoast\PHPUnitPolyfills\TestCases\TestCase): `test_process_timeout_is_disabled` decodes composer.json and asserts `config['process-timeout'] === 0`. This fails on the current composer.json, which has no key.
**Out of scope:** Changing bin/test.sh, the phpunit invocation, CI workflow, or making the suite faster.
**Verification:** 1. foundry_verify with the files touched. 2. Run `composer test` with NO COMPOSER_PROCESS_TIMEOUT in the environment and confirm exit 0 even though the run takes longer than 300 s. 3. Afterwards, `npx wp-env run tests-cli ps aux | grep phpunit` shows no leftover phpunit process.
**Depends on:** none

### R1-02: Cli caps_granted row reads capability and role names from Keys; lock the shape with a constraint
**Goal:** Remove the hard-coded 'edit_publications' capability and 'administrator' role literals from Cli (P1), and add a constraint so an unprefixed publication capability literal cannot reappear outside Keys (REVIEW finding 1).
**Files touched:** includes/class-cli.php, includes/class-keys.php, tests/unit/test-keys.php, tests/integration/test-cli.php, docs/foundry.json
**Design constraints:** Cli::caps_granted_row() uses Keys::CAP_MAP['edit_posts'] and a new Keys::ROLE_ADMINISTRATOR = 'administrator' constant; no capability or role string literal remains in class-cli.php. Append ONE constraint to docs/foundry.json, changing nothing else in the file: id `capability-names-in-keys`, description citing SPEC §3 P1 (capability names live in Keys), paths ["includes/", "wp-publication-archive.php", "uninstall.php"], exclude ["includes/class-keys.php"], pattern `['\"][a-z]+(_[a-z]+)*_publications['\"]`, shouldMatch including this diff's own line `$admin_has_cap = null !== $administrator && $administrator->has_cap( 'edit_publications' );` and `current_user_can( 'edit_others_publications' )`, shouldNotMatch including `$administrator->has_cap( Keys::CAP_MAP['edit_posts'] );` and `'publications' => $publications,`. foundry_verify must show the new rule's fixture self-test passing and 0 hits.
**Acceptance tests:** tests/unit/test-keys.php: assert Keys::ROLE_ADMINISTRATOR === 'administrator'. tests/integration/test-cli.php: `test_caps_granted_row_follows_keys_cap_map` removes Keys::CAP_MAP['edit_posts'] from the administrator role (restoring it in tear_down via the existing roles snapshot pattern) and asserts the caps_granted row fails. The new constraint's shouldMatch fixture is the original line, so the constraint self-test itself would have caught the finding.
**Out of scope:** Changing which roles or caps Capabilities::grant() gives out; other doctor rows.
**Verification:** 1. foundry_verify with the files touched: capability-names-in-keys self-tests and reports 0 hits. 2. `grep -n "'edit_publications'\|'administrator'" includes/class-cli.php` prints nothing. 3. `npx wp-env run cli wp publication-archive doctor` exits 0.
**Depends on:** R1-01

### R1-03: the_thumbnail() keeps the DAM data: placeholder; thumbnail read path normalises the pipe form
**Goal:** Make the echoed thumbnail (the path every bundled template uses) render the DAM placeholder for withheld images, and normalise legacy http|/https| thumbnail values on read per SPEC §5.1 (REVIEW finding 3).
**Files touched:** includes/class-publication-item.php, tests/integration/test-publication-item.php, tests/integration/dam/test-publication-item-dam.php
**Design constraints:** Publication_Item stays non-final, with 3.0.1 method names, parameters, defaults and phpdoc-only types. the_thumbnail() echoes through `wp_kses( $html, 'post', array_merge( wp_allowed_protocols(), array( 'data' ) ) )` (or an equivalent that allows only the extra `data` protocol) instead of wp_kses_post(). get_the_thumbnail() applies Plugin::instance()->url_policy()->normalise() to the value returned by Hooks::item_upload_image() before Dam_Bridge::display_url() and escaping. The public $upload_image property and the filter's input stay the raw stored value, as in 3.0.1. No WordPress.Security phpcs ignore.
**Acceptance tests:** tests/integration/dam/test-publication-item-dam.php: `test_d18_the_thumbnail_echoes_placeholder_for_anonymous` embargoes the attachment as the existing D18 test does, captures `$item->the_thumbnail()` with ob_start(), and asserts the output contains Embargo_Guard::placeholder_url() verbatim (including `data:`). This fails today because wp_kses_post strips `data:`. tests/integration/test-publication-item.php: `test_thumbnail_normalises_pipe_form` writes META_IMAGE `https|example.com/t.png` via V3_Site::raw_meta() and asserts get_the_thumbnail() contains `src="https://example.com/t.png"`.
**Out of scope:** Changing the wpa-upload_image filter arguments, other item fields, or template markup.
**Verification:** 1. foundry_verify with the files touched. 2. `composer test` (DAM loaded) and `WPPA_DAM=0 composer test` are green, and no characterisation string changes.
**Depends on:** R1-01

### R1-04: Meta box save preserves percent-encoded URLs
**Goal:** Stop Meta_Boxes::save() from deleting %xx octets out of document, thumbnail and alternate URLs, while keeping D1, D2 and D10 closed (REVIEW finding 4).
**Files touched:** includes/class-meta-boxes.php, tests/integration/test-meta-boxes.php
**Design constraints:** Doc, image and each alternate url are sanitised with a URL-preserving sanitiser, for example `esc_url_raw()` applied to `Url_Policy::normalise( wp_unslash( … ) )` so the pipe form still normalises, then passed through validated_url(). Descriptions keep sanitize_text_field(); the nonce handling is unchanged. The alternates loop keeps the `<` bound (D10). No WordPress.Security phpcs ignore (no-security-ignores). Only the three §5.1 meta keys are written.
**Acceptance tests:** tests/integration/test-meta-boxes.php: `test_save_preserves_percent_encoded_urls` posts doc `https://example.com/My%20Report.pdf`, image `https://example.com/r%C3%A9sum%C3%A9.png` and one alternate url `https://example.com/a.pdf?x=a%2Fb`, and asserts all three are stored byte-for-byte. This fails today (sanitize_text_field yields `MyReport.pdf`). The existing test_d1_*, test_d2_*, test_d10_* and pipe-form save tests stay green.
**Out of scope:** The REST sanitize callbacks in Post_Type (unaffected), meta box markup, and admin-media.js.
**Verification:** 1. foundry_verify with the files touched. 2. `composer test` (DAM loaded) is green.
**Depends on:** R1-01

### R1-05: Delivery acts only on publications and reads meta directly, not through Publication_Item
**Goal:** Keep Delivery inside its module-map imports and restore 3.0.1's no-op for view/download query vars on non-publication requests (REVIEW finding 5).
**Files touched:** includes/class-delivery.php, tests/integration/test-delivery.php
**Design constraints:** Delivery::deliver() returns without output unless get_post() is non-null and its post_type is Keys::POST_TYPE. resolve_uri() reads Keys::META_DOC (single) and Keys::META_ALTERNATES (all rows) with get_post_meta() and keeps the 3.0.1 alternate-key rule (urldecode(QV_ALT) === description). Delivery no longer references Publication_Item, calls setup_postdata(), or builds the excerpt. SPEC §6.2 steps 2-6 are otherwise unchanged (filters, validate→404, withhold→404, redirect/proxy).
**Acceptance tests:** tests/integration/test-delivery.php: `test_open_query_var_on_non_publication_is_ignored` creates a regular post, go_to()s it with Keys::QV_OPEN=yes, and asserts handle() throws no WPDieException and prints nothing. This fails today with a 404 wp_die. `test_delivery_does_not_build_the_excerpt` counts `get_the_excerpt` filter calls during a same-site redirect handle() and asserts 0. The existing D1/D17 delivery tests stay green.
**Out of scope:** Changing Url_Policy, Streamer, the proxy-mode Content-Type rule, or the 404 behaviour for publications whose stored URL is empty or invalid.
**Verification:** 1. foundry_verify with the files touched. 2. `composer test` (DAM loaded) and `WPPA_DAM=0 composer test` are green. 3. `grep -n Publication_Item includes/class-delivery.php` prints nothing.
**Depends on:** R1-01

### R1-06: admin-media.js uses jQuery only for document delegation
**Goal:** Bring assets/js/admin-media.js in line with SPEC §6.7 and the PLAN P2-07 design constraint: the only jQuery use is jQuery( document ).on() delegation (REVIEW finding 6).
**Files touched:** assets/js/admin-media.js, tests/integration/test-admin-media.php
**Design constraints:** Replace `$( this ).closest( 'tr' )`, `$row.find( … ).val( url )` and `$( this ).closest( 'tr' ).remove()` with DOM equivalents (`this.closest( 'tr' )`, `querySelector( 'input[name$="[url][]"]' ).value = url`, `Element.remove()`). Behaviour, selectors and field names are unchanged, and the file never touches window.send_to_editor.
**Acceptance tests:** tests/integration/test-admin-media.php: `test_admin_media_js_uses_jquery_only_for_document_delegation` reads the file and asserts every `$(` / `jQuery(` call's argument is `document`, matching `/(?:\$|jQuery)\(\s*([^)]*?)\s*\)/`. This fails today on `$( this )` and `$row`.
**Out of scope:** Meta box markup, Assets::enqueue_admin(), and any block-editor UI.
**Verification:** 1. foundry_verify with the files touched. 2. `WPPA_DAM=0 composer test` is green. 3. Manual (NOT VERIFIED (human)): in wp-admin, Upload fills the doc, thumbnail and alternate-row inputs, and Add Row and Delete work.
**Depends on:** R1-01

### R1-07: 3.1.0 release notes describe D5 accurately
**Goal:** Stop readme.txt and CHANGELOG.md claiming a D5 rewrite-rule fix that P2-05 found unnecessary (REVIEW finding 7).
**Files touched:** readme.txt, CHANGELOG.md, tests/unit/test-readme.php
**Design constraints:** Reword only the D5 bullet in both files, e.g. "Confirmed that publications slugged view or download stay reachable at their own permalinks; regression tests added (D5)." Every other changelog and upgrade-notice line is unchanged, and D1–D19 are all still named.
**Acceptance tests:** tests/unit/test-readme.php: `test_d5_entry_does_not_claim_a_rule_fix` extracts the line naming D5 from readme.txt's 3.1.0 changelog and from CHANGELOG.md, and asserts each contains `regression test` and does not contain `Fix a rewrite-rule collision`. This fails on today's wording.
**Out of scope:** Rewrite rules, and any other readme or changelog content.
**Verification:** 1. foundry_verify with the files touched (unit suite green, including the existing readme tests).
**Depends on:** R1-01

## Review fixes (round 2)

### R2-01: Meta box save keeps '&' in URLs (R1-04 regression)
**Goal:** Stop Meta_Boxes::save() storing every '&' in a document, thumbnail or alternate URL as '&amp;' (wp_kses_post() runs wp_kses_normalize_entities()), while keeping percent-encoding, the legacy pipe form, D1, D2 and D10 intact.
**Files touched:** includes/class-meta-boxes.php, tests/integration/test-meta-boxes.php
**Design constraints:** Replace wp_kses_post() with wp_strip_all_tags() as the immediate sanitiser wrapping wp_unslash( $_POST[...] ) for Keys::FIELD_DOC and Keys::FIELD_IMAGE, and as the map_deep() callback for the alternates URL array. Keep the rest of the chain: Url_Policy::normalise() -> esc_url_raw() -> validated_url(). wp_strip_all_tags is in WPCS SanitizationHelperTrait's sanitising list, so WordPress.Security.ValidatedSanitizedInput.InputNotSanitized stays satisfied without any phpcs:ignore (P7, no-security-ignores). While on these lines, cast the posted description and url collections to arrays and skip any non-string url element, so a hand-crafted POST cannot reach count() or normalise( string ) with the wrong type. Update the comment above the doc/thumbnail lines to name wp_strip_all_tags and why (keeps %xx, '&' and '|').
**Acceptance tests:** tests/integration/test-meta-boxes.php: new test_save_preserves_ampersands_in_urls posts doc 'https://example.com/a.pdf?x=1&y=2', thumbnail 'https://example.com/t.png?w=1&h=2', and one alternate 'https://example.com/b.pdf?id=3&export=download', then asserts each stored value is byte-identical (no '&amp;'). This test fails on the current wp_kses_post() code. Existing test_save_preserves_percent_encoded_urls, the pipe-form save test and the D1/D2/D10 tests must still pass unchanged.
**Out of scope:** Post_Type's REST sanitize callbacks; Url_Policy; any other file.
**Verification:** foundry_verify with files [includes/class-meta-boxes.php, tests/integration/test-meta-boxes.php]: constraints clean, lint/analyse/test:map/test:unit green, composer test green with the DAM.
**Depends on:** none

### R2-02: Delivery drops its Icons dependency (SPEC §4.2 module map)
**Goal:** Delivery imports only what SPEC §4.2 allows (Keys, Hooks, Flags, Url_Policy, Streamer, Dam_Bridge): resolve the §6.2 step-1 content type with wp_check_filetype() directly instead of through Icons.
**Files touched:** includes/class-delivery.php, includes/class-plugin.php, tests/integration/test-delivery.php, tests/integration/dam/test-delivery-dam.php
**Design constraints:** Remove the Icons constructor parameter, the $icons property and the icons() accessor from Delivery; new signature __construct( Url_Policy $policy, Streamer $streamer, Dam_Bridge $dam, ?callable $exit = null, ?callable $header = null ). In proxy(), compute the type as wp_check_filetype( basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) )['type'], falling back to the response content-type header, then Keys::CONTENT_TYPE_FALLBACK, exactly as today. Update Plugin::__construct() wiring and the two test files that construct Delivery. Icons itself and Publication_Item's use of Icons are unchanged.
**Acceptance tests:** tests/integration/test-delivery.php: new test_delivery_constructor_takes_no_icons uses ReflectionMethod on Delivery::__construct and asserts no parameter is typed WPPA\Icons and that method_exists( Delivery::class, 'icons' ) is false. Existing proxy-mode tests (test_proxy_mode_streams_mocked_body_and_headers, test_proxy_mode_download_adds_disposition) must still pass with the same Content-Type.
**Out of scope:** Streamer changes (next task); Icons class; any header changes.
**Verification:** foundry_verify with the touched files: constraints clean, all verify commands green, composer test green with the DAM.
**Depends on:** none

### R2-03: Streamer sends nosniff and forces attachment for active content (SPEC §6.2 steps 3-4)
**Goal:** Implement the product-owner decision in SPEC 464750b: every proxied response carries X-Content-Type-Options: nosniff, and a view request for active content (Keys::ACTIVE_CONTENT_TYPES) is sent as an attachment, so the plugin never serves HTML/SVG/XML/JS inline from the site's origin. Also make the temp-dir containment check exact.
**Files touched:** includes/class-keys.php, includes/class-streamer.php, includes/class-delivery.php, tests/unit/test-keys.php, tests/unit/test-streamer.php, tests/integration/test-delivery.php
**Design constraints:** Add Keys::ACTIVE_CONTENT_TYPES = array( 'text/html', 'application/xhtml+xml', 'image/svg+xml', 'text/xml', 'application/xml', 'text/javascript', 'application/javascript' ) and assert it in test-keys.php. Add public static Streamer::is_active_content( string $content_type ): bool that strips any ';' parameters, trims, lowercases, and checks membership in Keys::ACTIVE_CONTENT_TYPES. Streamer::send() keeps its three-parameter signature and header order per §6.2: Content-Type, Content-Length, then always 'X-Content-Type-Options: nosniff', then Content-Disposition: 'attachment; filename="…"' when $filename is non-null, else a bare 'Content-Disposition: attachment' when is_active_content( $content_type ) (backstop). Delivery::proxy() passes sanitize_file_name( basename( URL path ) ) as $filename when $is_download OR Streamer::is_active_content( $content_type ); otherwise null. In send(), change the containment check to require $real_path to start with rtrim( $real_temp_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR. Tunables/literals stay in Keys (tunables-in-keys-only, names-in-keys-only).
**Acceptance tests:** tests/unit/test-streamer.php: test_send_always_sends_nosniff (third header is 'X-Content-Type-Options: nosniff' with and without a filename); test_send_forces_attachment_for_active_content_with_no_filename (data provider over 'text/html', 'text/html; charset=UTF-8', 'IMAGE/SVG+XML', 'application/javascript' -> a Content-Disposition attachment header is sent); test_send_sends_no_disposition_for_pdf_view ('application/pdf', null filename -> no Content-Disposition); test_send_refuses_a_sibling_dir_sharing_the_temp_dir_prefix (temp dir X, file in X . '-evil/' -> InvalidArgumentException). Update existing header-index assertions (disposition moves from index 2 to 3). tests/integration/test-delivery.php, with wppa_mask_url true and pre_http_request mocked: test_proxy_view_of_html_is_attachment_with_nosniff (.html URL, remote content-type text/html), test_proxy_view_of_svg_is_attachment_with_nosniff (.svg URL, image/svg+xml), test_proxy_view_of_pdf_is_inline_with_nosniff (.pdf -> nosniff, no Content-Disposition). The html/svg tests fail on the current code.
**Out of scope:** Redirect (default) mode headers; changing Streamer::send()'s required parameters; readme/CHANGELOG wording.
**Verification:** foundry_verify with the touched files: constraints clean (raw-file-read-confined still one readfile), all verify commands green, composer test green with the DAM.
**Depends on:** R2-02

### R2-04: Contributors get their post-equivalent publication caps (SPEC §6.1)
**Goal:** Implement the product-owner decision in SPEC 464750b: Capabilities::grant() also maps the contributor role, so contributors keep creating and editing their own draft publications as in 3.0.1, without being able to publish or edit others'.
**Files touched:** includes/class-keys.php, includes/class-capabilities.php, tests/unit/test-keys.php, tests/integration/test-capabilities.php
**Design constraints:** Add 'contributor' to Keys::CAP_ROLES (after 'author'). grant()'s has_cap()-based mapping is unchanged, so contributor gets edit_publications and delete_publications only. Update Capabilities' file and grant() docblocks, which say 'three §6.1 roles'. Capability names stay in Keys (capability-names-in-keys, names-in-keys-only); tests use Keys::CAP_MAP, not literals.
**Acceptance tests:** tests/unit/test-keys.php: CAP_ROLES assertion includes 'contributor'. tests/integration/test-capabilities.php: test_grant_gives_contributor_the_same_subset_as_for_post (contributor has exactly the CAP_MAP values whose post caps it has, and not publish/edit_others/edit_published); test_contributor_can_edit_own_draft_but_not_publish_or_edit_others (after grant(), as a Contributor: current_user_can( 'edit_post', own draft publication ) true, current_user_can( 'publish_post', same ) false, current_user_can( 'edit_post', another user's draft publication ) false, and current_user_can( Keys::CAP_MAP['edit_posts'] ) true). Restore role state the same way the existing Test_Capabilities snapshot does. Both fail on the current code.
**Out of scope:** Upgrade handling for sites with OPT_CAPS already set (no 3.1.0 has shipped); the doctor caps_granted row.
**Verification:** foundry_verify with the touched files: constraints clean, all verify commands green, composer test green with the DAM.
**Depends on:** none

### R2-05: Pin every plain-permalink link generator and query form at 3.0.1 behaviour (SPEC G5)
**Goal:** SPEC G5 (464750b) says the characterisation tests pin plain-permalink behaviour; today only get_open_link() is pinned, and deleting the plain-permalink 'alt' query arg in Rewrites::link() survives the whole suite.
**Files touched:** tests/integration/test-characterisation-routing.php
**Design constraints:** Test-only; no production code changes. Use the 3.0.1 public API (\WP_Publication_Archive::get_*_link()) as the existing characterisation tests do, set_permalink_structure( '' ) inside each test, and fixture slugs from V3_Site. Expected strings are the literal 3.0.1 outputs (add_query_arg of the endpoint name, then 'alt'). No literal plugin-prefixed names (names-in-keys-only): use Keys::QV_OPEN / Keys::QV_DOWNLOAD for the query forms.
**Acceptance tests:** test_download_link_with_plain_permalinks_matches_301 -> home_url( '/?publication=attached-report&download=yes' ); test_alternate_open_link_with_plain_permalinks_matches_301 -> home_url( '/?publication=alternates-report&altview=yes&alt=English' ); test_alternate_download_link_with_plain_permalinks_matches_301 -> home_url( '/?publication=alternates-report&altdown=yes&alt=English' ); test_open_query_form_resolves_with_plain_permalinks and test_download_query_form_resolves_with_plain_permalinks (go_to the ?publication=…&<QV>=yes form, assert the query var is 'yes' and the queried object is the fixture). The alternate tests fail if Rewrites::link()'s add_query_arg( Keys::QUERY_ALT_KEY, … ) is deleted.
**Out of scope:** Changing any link output; wppa_open-style link generation (retired in 4.0).
**Verification:** foundry_verify with files [tests/integration/test-characterisation-routing.php]: all verify commands green, composer test green with the DAM.
**Depends on:** none
