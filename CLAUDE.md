# WP Publication Archive 3.1.0

Safety and compatibility release of the 3.0.1 plugin, rebuilt on the
`eamann-plugin-template` foundation and proven side by side with the VIP
Digital Asset Manager (the DAM). `docs/SPEC.md` is the source of truth;
`docs/PLAN.md` is the task list derived from it.

## Principles
- [ ] Every name (option, meta key, form field, query var, rewrite tag, hook incl. legacy, shortcode, handle, CLI command, REST namespace, capability, widget id_base, legacy class name) is a `Keys` constant, written once (P1).
- [ ] Options are read and written only in `Flags` (P2). `add_action`/`add_filter`/`remove_*`/`add_shortcode` only in `Plugin` (P3). `do_action`/`apply_filters` only in `Hooks`, one static method per hook, passing exactly the arguments 3.0.1 passed (P4).
- [ ] 3.0.1 "add filter around a call" patterns become one callback registered in `Plugin` that acts only while its owning service holds a private flag, set and cleared in `try`/`finally` (P3).
- [ ] Stubs are exactly `throw new NotImplementedException( __METHOD__ );`. No TODO/FIXME (P5). A contracts task never wires a live hook to a throwing stub.
- [ ] phpcs `WordPress-VIP-Go` + §7.3 sniffs: zero errors, zero warnings. Every `phpcs:ignore`/`disable` carries `-- reason:` on the same line (P6). A `WordPress.Security.*` ignore must end `-- reason: D1|D2|D3|D10` and is deleted by the task that closes that defect; none remain after P1-09 (P7).
- [ ] PHPStan level 6, no baseline. PHP 7.4 syntax only: no union types, `match`, enums, `readonly`, named args, constructor promotion, `mixed`, `str_contains`/`str_starts_with` (§5.4).
- [ ] Time and date formatting only in `Clock`; display dates go through `Clock::format()` or `get_the_date()`; tests use `FixedClock( Keys::EPOCH )` (P8).
- [ ] Namespaced (`WPPA`, `WPPA\Legacy`, `WPPA\Widgets`), no file-scope functions, no `global $` except `$wpdb`, `$wp_version`, `$wppa_container`, `$wppa_publications` (P9). Use `get_post()`, `get_query_var()`, `get_queried_object()`.
- [ ] Remote requests only through `wp_safe_remote_get`/`wp_safe_remote_head`; never `sslverify` (P10). The one `readfile(` is in `Streamer::send()` on a path inside the temp dir (P11).
- [ ] Every `includes/**/class-*.php` has `tests/unit/test-<slug>.php` or `tests/integration/test-<slug>.php`, a `WPPA` namespace, a `SPEC.md §` reference and an `@author` tag, written in the same task (P15). A task that closes a D-item adds `test_d<n>_…` methods that fail on the pre-fix code.
- [ ] Stored data is frozen: only the three §5.1 meta keys are written; tests that need 3.0.1-shaped or hostile values write them with `V3_Site::raw_meta()` (raw `$wpdb`), never `update_post_meta()` (P16).
- [ ] Classes aliased to 3.0.1 names (`Publication_Item`, `widgets/*`, `legacy/*`) stay non-final, keep 3.0.1 method names, parameters, defaults and public properties, and add no native parameter or return types to those methods (phpdoc only).
- [ ] Output is escaped at the point of echo. Anything the spec does not settle is written under Interpretation in the commit, not guessed silently.

## Commands
- Install: `composer install && npm install`
- DAM checkout (needs SSH to github.a8c.com): `bash bin/fetch-dam.sh`
- Environment: `npx wp-env start` (re-run after changing `.wp-env.json` or fetching the DAM)
- After adding, moving or deleting any class file: `composer dump-autoload -o`
- Lint: `composer lint` · Analyse: `composer analyse` · Test map: `composer test:map`
- Unit tests (host, no Docker): `composer test:unit`
- All tests in wp-env, DAM loaded: `composer test` · without the DAM: `WPPA_DAM=0 composer test`
- Static verify: `composer verify:static` · Full verify: `composer verify`
- Smoke: `npx wp-env run cli wp publication-archive doctor`
- Build (from P3-01): `composer build` → `dist/wp-publication-archive.zip`
- Foundry runs lint, analyse, test:map and test:unit after every task, plus `composer test` (20 min timeout) when a task touches `includes/`, `templates/`, `tests/`, `bin/`, `.wp-env.json`, the bootstrap, `uninstall.php` or `composer.json`. wp-env must be running.

## Module map
| Path (under `includes/` unless noted) | Responsibility | May import from |
|---|---|---|
| `wp-publication-archive.php` | Headers, guards, legacy constants, `Plugin::boot()`, activation hooks | autoloader, `Keys`, `Plugin` |
| `class-plugin.php` | Composition root; the only `add_*` site; accessors and `replace()` | everything |
| `class-keys.php`, `class-clock.php`, `class-not-implemented-exception.php`, `class-url-policy.php` | Leaves (`Url_Policy` may read `Keys` constants, needs no `use`) | nothing |
| `class-flags.php` | Every option read and write | `Keys`, `Hooks` |
| `class-hooks.php` | Every `do_action`/`apply_filters` | `Keys` |
| `class-streamer.php` | The one `readfile` of a temp file | `Keys` |
| `class-dam-bridge.php` | The only file that knows the DAM | `Keys`, `Hooks`, `Url_Policy` |
| `class-delivery.php` | view/download/altview/altdown: redirect or proxy | `Keys`, `Hooks`, `Flags`, `Url_Policy`, `Streamer`, `Dam_Bridge` |
| `class-post-type.php`, `class-capabilities.php`, `class-rewrites.php` (rules, query vars, link generation), `class-upgrade.php` | Model and routing | `Keys`, `Hooks`, `Flags`, `Url_Policy` |
| `class-publication-item.php`, `class-icons.php`, `class-categories.php` | Presentation helpers | `Keys`, `Hooks`, `Clock`, `Url_Policy`, `Dam_Bridge`, `Icons`; `Categories` also `Flags`; `Publication_Item` reaches services only via `Plugin::instance()` |
| `class-meta-boxes.php`, `class-shortcode.php`, `class-templates.php`, `widgets/*` | Surfaces | anything above except `Plugin`; widgets (constructed by WordPress) may call `Plugin::instance()` |
| `legacy/*` | `class_alias` for the six 3.0.1 classes; static and singleton delegates | `Plugin::instance()`, anything above |
| `class-cli.php`, `class-assets.php`, `class-rest.php` | Template services | `Keys`, `Flags`, `Hooks`; `Cli` also `Dam_Bridge` |
| `templates/classic/` | Bundled theme-overridable templates (3.0.1 file names) | 3.0.1 public API |
| `tests/` | `bootstrap.php`, shims, `fixtures/` (`V3_Site`, `V3_Expected_Output`, theme), `unit/`, `integration/`, `integration/dam/` (`@group dam`) | anything |

Until P0-15, the 3.0.1 code runs from `lib/` through `lib/class.wp-publication-archive-loader.php`; `lib/` is outside every lint, analysis and constraint path and is deleted by P0-15.

## Constraints
Checked by `foundry_verify` from `docs/foundry.json` (id in brackets). A hit is a failing test.
- No string literal starting `wppa_`, `wppa-`, `wpa_`, `wpa-`, `wp_pubarch`, `wp-publication-archive` outside `class-keys.php`/`test-keys.php` in `includes/`, bootstrap, `uninstall.php`, `tests/` (text domain as last i18n arg and HTML attribute values exempt) [names-in-keys-only]
- No `get_/update_/add_/delete_(site_)option(` outside `class-flags.php` [options-read-in-flags-only]
- No `add_action(`/`add_filter(`/`remove_action(`/`remove_filter(`/`add_shortcode(` outside `class-plugin.php` [hooks-register-in-plugin-only]
- No `do_action(`/`apply_filters(` outside `class-hooks.php`, templates included [hooks-fire-in-hooks-only]
- No `TODO`, `FIXME`, `return null; // stub` [no-stub-markers]; stubs are exactly `NotImplementedException( __METHOD__ )` [stub-form-exact]
- Every `phpcs:ignore|disable` has `-- reason:` [phpcs-ignore-needs-reason]; a Security one names D1/D2/D3/D10 [security-ignores-name-a-defect]
- No `time(`, `current_time(`, `date(`, `gmdate(`, `microtime(`, `strtotime(`, `wp_date(`, `date_i18n(` outside `class-clock.php` [no-wall-clock]
- No `function` at column 0 under `includes/` or in the bootstrap [no-file-scope-functions]
- No `global $` except the four allowed names [no-globals]
- No `WP_CLI` under `includes/` except `class-cli.php`, `class-plugin.php` [wp-cli-confined]
- No `use` line in `Keys`, `Clock`, `NotImplementedException`, `Url_Policy` [leaf-files-import-nothing]; `Url_Policy` calls no WordPress function [url-policy-is-pure]
- No `'7.4'`, `'6.7'`, `'3.1.0'` literal outside `Keys` [no-hardcoded-versions]
- No `wp_remote_get|head|post|request(`, no `sslverify` [safe-remote-only]
- No `readfile(`, `fpassthru(`, `fopen(`, `file_get_contents(`, `file(` outside `class-streamer.php` [raw-file-read-confined]
- No `extract(` [no-extract]
- No `$_POST`/`$_GET`/`$_REQUEST` outside `class-meta-boxes.php`, `class-shortcode.php` [superglobals-confined]
- No `VIP\DAM\` or `VIP_DAM_` outside `class-dam-bridge.php` (and `tests/`) [dam-symbols-confined]
- No `lib/`, `lang/`, `images/`, `includes/front-end.css` references [no-legacy-asset-paths]
- No tunable literal (`52428800`, `'timeout' => N`, `'limit'|'count'|'number'|'numberposts'|'posts_per_page' => N`, `Hooks::x( N )`) outside `Keys` [tunables-in-keys-only]
- Added by P0-16: no `mimetype` class use [no-mimetype-class]; no hook callable naming a 3.0.1 class as a string [no-legacy-string-callables]
- Added by P1-09: no `phpcs:ignore|disable` of any `WordPress.Security` sniff [no-security-ignores]; no `ob_clean(` [no-ob-clean]
- Added by P2-10: no Thickbox, `TB_iframe`, `send_to_editor`, `media-upload` handle [no-thickbox]; no `allow_url_fopen` [no-allow-url-fopen]
- Reviewer checks by reading (not a line pattern): imports follow the module map; exactly one `readfile(` in `class-streamer.php` after P1-07 and its argument is the temp-dir-checked path; no code writes a meta key other than the three §5.1 keys; aliased classes keep 3.0.1 signatures (also `tests/integration/test-aliases.php`); no hook is registered with a `WPPA\Legacy\*` callable; `composer test:map` covers the per-class test rule.

## Commit template
```
<ID>: <title>

Goal: <one sentence>
Tests: <test files and test_ methods added or changed>
Closes: <D-items closed, or "none">
Interpretation: <every choice the task left open, or "none">
Measurement: <tuning tasks only, else "n/a">
Manual check: <"n/a", or "NOT VERIFIED (human): <what to check>">
```

SPEC.md wins over PLAN.md, and PLAN.md wins over code comments.
