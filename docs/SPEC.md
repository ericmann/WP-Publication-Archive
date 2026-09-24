# WP Publication Archive 3.1.0 — Specification

Version: 2.0
Status: ready for planning
Date: 2026-09-24
Supersedes: `PHASE_0_SPEC.md` (v1.0, 2026-09-20)

This is a Foundry spec for **Foundry 0.3.2**, whose implement stage is serial. Parallelism is planned by hand (§8.0): the plan groups independent work into workstreams, so it can be split across implementers manually now, and mapped onto Foundry's own streams once they ship. `docs/foundry.json` is committed **before** planning on purpose: it is the only routing layer that reaches the planner (§7.1). The planner keeps it and extends it. It does not replace it.

Background and rationale are in `V4-ASSESSMENT.md` §2–3 at the repository root. The target of the following release is in `V4_SPEC.md` at the root. Neither file is in `docs/` and the planner does not need either one. This spec is self-contained.

## 1. Overview

WP Publication Archive 3.1.0 is a safety and compatibility release of the 3.0.1 plugin. 3.0.1 was last released in 2013 and is still installed on about 400 wordpress.org sites. The release has three jobs:

1. **Close the defects in §1.1.** Four of them are security holes.
2. **Rebuild the plugin on the `eamann-plugin-template` foundation.** That means:
   - a composition root, with names, options, hook registration and hook firing each in one place;
   - wp-env, a unit and integration test split, a test map, VIP-Go phpcs, PHPStan level 6 and CI;
   - Foundry-aware docs.

   4.0 (`V4_SPEC.md`) will be built on this foundation instead of starting from nothing.
3. **Prove it works alongside the VIP Digital Asset Manager** (`git@github.a8c.com:mrchriswdixon/vip-digital-asset-manager.git`, "the DAM"). Both plugins run in one wp-env. The rules are:
   - When the DAM is active, a publication respects the DAM's decisions about the attachments it points at, and tells the DAM which attachments it uses.
   - When the DAM is absent, nothing changes.
   - Neither plugin requires the other.

Public behaviour is frozen: stored data, URLs, the shortcode, the widgets, the theme-template overrides and the hook names. The only exceptions are changes a defect fix requires, each listed in §1.1.

**Done means all of the following:**

- every item in §1.1 is closed with a test;
- `composer verify` passes in wp-env with the DAM loaded, and CI passes without it;
- the plugin activates on WordPress 7.1 / PHP 8.3 with no notices, both with and without the DAM active;
- `composer build` produces an installable zip;
- `readme.txt` says "Tested up to: 7.1";
- the build branch is pushed and its PR against `master` is ready for review (§7.2).

### 1.1 Defects this release closes

The methods named below are in the 3.0.1 source. Classes are under `lib/`, templates under `includes/`. The restructure in Phase 0 moves them (§4.1). Each D-item names its behaviour, not its location.

**Security**

- **D1. Local file disclosure and SSRF.**
  - `WP_Publication_Archive::save_meta()` stores the document URL through `esc_url_raw()`, which accepts values beginning with `/`.
  - `open_file()` and `download_file()` then `readfile()` that value after a `wp_remote_head()` whose failure is ignored, with `sslverify => false`.
  - As a result, any Author can read `/etc/passwd` or `wp-config.php` through `/publication/view/{slug}`, or make the server fetch internal URLs.
- **D2. Stored XSS.** `save_meta()` saves alternate-file descriptions from `$_POST` unsanitized. `WP_Publication_Archive_Item::list_downloads()` echoes them unescaped.
- **D3. Unescaped output.** Each of these is echoed without escaping:
  - `Item::get_the_thumbnail()` echoes the filtered thumbnail URL into `src`;
  - `Item::get_the_title()` echoes the filtered title;
  - the three admin meta boxes echo meta values into `value=""`;
  - `template.wppa_publication_dropdown.php` echoes `post_title`.
- **D10. Alternates loop off by one.** `save_meta()` loops `for ( $i = 0; $i <= count( ... ); $i++ )`, which reads one index past the end.

**Correctness**

- **D4. Dead, unsafe search filter.** `WP_Publication_Archive::search()` hooks `posts_where_request`. It regex-rewrites the WordPress 3.x search SQL, which modern core no longer produces, and interpolates the raw search term into a regex. It adds `search_join`/`search_distinct` at run time.
- **D5. Rewrite collisions.** The `publication/view|download|altview|altdown` rules are added `'top'`, so a publication whose slug is `view` or `download` cannot be reached.
- **D6. `ob_clean()` with no buffer** in both delivery paths raises a notice on PHP 8.
- **D7. `date()` instead of a site-timezone formatter.** `Item::get_the_*` date output ignores the site's timezone and locale.
- **D8. Dead code and no-op filter juggling.**
  - `the_content()`, `the_title()` and `publication_link()` are defined but never hooked.
  - `get_link()` removes and re-adds a `post_type_link` filter that nothing adds.
- **D9. Side effects at bootstrap.** On the upgrade branch, `flush_rewrite_rules()` runs at plugin load. On the new-install branch, `add_option()` runs on every request.
- **D11. Misleading `allow_url_fopen` admin notice.** Nothing in 3.1.0 needs `allow_url_fopen`.

**Compatibility**

- **D12. No REST or block editor.** The CPT, the `publication-author` taxonomy and the three meta keys have no `show_in_rest`.
- **D13. Thickbox media uploader.** The meta boxes open `media-upload.php?TB_iframe=1` and override `window.send_to_editor`. The JavaScript is inline.
- **D14. Legacy widgets** lack `show_instance_in_rest`, so the Legacy Widget block cannot preview them.
- **D15. `extract()`** is used in the templates and the shortcode handler.
- **D16. Directory hygiene.**
  - The text domain `wp_pubarch_translate` does not match the slug, so language packs never load.
  - Several plugin headers are missing or out of date.
  - The menu icon is a PNG.
  - `lib/class.mimetype.php`, a 2002 BSD-licensed extension table, duplicates `wp_check_filetype()`.

**DAM compatibility** (new in this release; all behaviour lives in `Dam_Bridge`, §6.9)

- **D17. Delivery bypasses DAM withholding.** A publication pointing at a same-site attachment redirects to the raw file URL. That happens even when the DAM has embargoed the attachment, scheduled it out of circulation, or trashed it. The DAM's own render filters never see the request.
- **D18. The thumbnail bypasses DAM withholding.** `wpa-upload_image` is echoed as a raw URL, so the DAM's placeholder substitution never applies.
- **D19. The DAM cannot see publication usage.** The DAM's usage index finds upload URLs in non-underscore meta. It misses the legacy `http|` / `https|` pipe form in `wpa_upload_doc`, so it can call an attachment unused and allow deleting it while a publication still links to it.

## 2. Goals and non-goals

**Goals**

- **G1.** Close D1–D19. Each behavioural fix has a regression test, written in the task that makes the fix (§3 P15, §8.0).
- **G2. Template foundation.** Adopt the layout, rules and tooling of `eamann-plugin-template` (§3, §4, §7):
  - a `WPPA` namespace and a Composer classmap over `includes/`;
  - `Plugin`, `Keys`, `Flags`, `Hooks`, `Clock`, `Cli`, `Assets`, `Rest` and `NotImplementedException`;
  - `bin/` scripts, `tests/unit` and `tests/integration`, and a test map;
  - `docs/HOOKS.md`, `docs/CONTRIBUTING.md` and `docs/adr/`;
  - passive template attribution: `humans.txt`, the `/eam` route, the `lineage` doctor row, `Keys::LINEAGE`/`EPOCH`/`ASSET_VERSION`, and `@author` docblocks.
- **G3. DAM side by side.** `bin/fetch-dam.sh` places the DAM at a pinned ref. wp-env loads both plugins. An integration group, `dam`, proves D17–D19 and proves that both plugins activate and render together with no notices.
- **G4. Zero changes to stored data.**
  - Meta keys `wpa_upload_doc`, `wpa-upload_image` and `wpa-upload_alternates` keep their names and value formats.
  - The option `wp-publication-archive-core` keeps its name and value format.
  - A site upgraded to 3.1.0 and downgraded to 3.0.1 has lost nothing.
- **G5. Zero changes to public URLs.** These all keep working:
  - `/publication/{slug}`, `/publication/view/{slug}` and `/publication/download/{slug}`;
  - `/publication/altview/{slug}/{key}` and `/publication/altdown/{slug}/{key}`;
  - `/publication/category/{slug}`;
  - the `?wppa_open=yes` and `?wppa_download=yes` query forms.
- **G6. Back-compat surface.** Everything in §6.3 and §6.4 still exists and behaves the same:
  - every 3.0.1 global class name, through `class_alias`;
  - every public method on them, with the same signature;
  - every filter name and its arguments;
  - every template file name a theme can override.
- **G7. A foundation for 4.0.** The module map in §4 is the one `V4_SPEC.md` will extend. This release names the modules 4.0 will grow, including `Url_Policy`, `Delivery`, `Post_Type`, `Dam_Bridge` and `Upgrade`, and creates no module 4.0 would have to delete.

**Non-goals** (out of scope for every task)

- No new user-facing features. None of these are in scope:
  - text extraction, embeddings or search improvements beyond removing D4;
  - blocks or block templates;
  - Abilities;
  - AI.
- No REST routes beyond two:
  - what `show_in_rest` provides for free;
  - the template's `GET /wp-json/wp-publication-archive/v1/eam` lineage route.
- No data model changes. No attachment IDs in meta, no new meta keys, no new tables, no featured-image conversion. `Dam_Bridge` resolves URLs to attachment IDs at read time and stores nothing.
- No hard dependency on the DAM. No DAM API call outside `Dam_Bridge`. No DAM-only behaviour when the DAM is inactive.
- No template redesign. Template markup changes only where escaping requires it (D2, D3, D15).
- No removal of the shortcode, the three widgets or the theme-template override mechanism.
- No multisite-specific work. `MULTISITE=0` in `bin/wp-env.conf`.
- No PHP floor above 7.4 (§5.4). This is a security release for sites the wordpress.org updater would otherwise strand.
- Callbacks registered under the 3.0.1 global class callables are not preserved as hook identities. For example, `remove_action( 'save_post', array( 'WP_Publication_Archive', 'save_meta' ) )` stops having an effect. The readme's upgrade notice says so. Hooks never fire under those callables. Every 3.0.1 method still exists as a public delegate (§6.3).

## 3. Engineering principles

Each rule can be checked by a grep, a tool or a test. Every rule tagged `[constraint: <id>]` is already an entry in `docs/foundry.json`, where it self-tests against fixture lines, and `foundry_verify` runs it on every task. The reviewer treats a violation as the most severe finding.

- **P1. One place for every name.** Every one of these is a constant in `includes/class-keys.php`:
  - option names, meta keys and form field names;
  - query vars, rewrite tags and hook names, including the legacy names in §6.4;
  - the shortcode tag, script and style handles, and the CLI command;
  - the REST namespace and the capability names.

  A string literal that begins with `wppa_`, `wppa-`, `wpa_`, `wpa-`, `wp_pubarch` or `wp-publication-archive` appears nowhere else under `includes/`, in the bootstrap, in `uninstall.php` or in `tests/`. There are two exemptions: the text domain as the last argument of an i18n call, and HTML attribute values. `tests/unit/test-keys.php` asserts every value. `[constraint: names-in-keys-only]`
- **P2. Options are read and written in one class.** Option functions appear only in `includes/class-flags.php`. `[constraint: options-read-in-flags-only]`
- **P3. Hooks register in one place.** `add_action`, `add_filter`, `remove_action`, `remove_filter` and `add_shortcode` appear only in `includes/class-plugin.php`.
  - Each 3.0.1 pattern that added a filter around a call is replaced by a callback registered once in `Plugin`. The patterns are `excerpt_length` in the category widget, `term_link` and `terms_clauses` in the utilities, and `post_type_link` in `get_link()`.
  - The callback does nothing unless the owning service has set a private `$scoped` flag, which it sets and clears in `try`/`finally` around the call.

  `[constraint: hooks-register-in-plugin-only]`
- **P4. Hooks fire through one helper.** `do_action` and `apply_filters` appear only in `includes/class-hooks.php`, with one static method per hook. That includes core hooks the plugin applies itself (`widget_title`, `list_cats`, `wp_dropdown_cats`, `widget_categories_args`, `widget_categories_dropdown_args`, `wp_list_categories`, `content_save_pre`) and every legacy name in §6.4. The rule also covers `templates/`. `[constraint: hooks-fire-in-hooks-only]`
- **P5. Stubs fail loudly.** Out-of-scope or not-yet-built behaviour is exactly `throw new NotImplementedException( __METHOD__ );`. There is no `TODO` or `FIXME`. `[constraint: no-stub-markers]`
- **P6. Lint is clean with reasons.** `WordPress-VIP-Go` plus the extra sniffs in §7.3 report zero errors and zero warnings. Every `phpcs:ignore` or `phpcs:disable` carries `-- reason:` on the same line. `[constraint: phpcs-ignore-needs-reason]`
- **P7. Security ignores are temporary and named.** A `phpcs:ignore` that silences any `WordPress.Security.*` sniff must end in `-- reason: D1`, `D2`, `D3` or `D10`, naming the open defect. The task that closes the defect deletes the ignore. At the Phase 1 gate, `grep -rn "phpcs:\(ignore\|disable\).*WordPress\.Security" includes templates wp-publication-archive.php uninstall.php` returns nothing. `[constraint: security-ignores-name-a-defect]`
- **P8. No wall clock, one date formatter.** Time and date-formatting functions appear only in `includes/class-clock.php`. That covers `time`, `current_time`, `date`, `gmdate`, `microtime`, `strtotime`, `wp_date` and `date_i18n`. `Clock` exposes `now(): int` and `format( string $format, int $timestamp ): string`, which wraps `wp_date()`. Display dates go through `Clock::format()` or `get_the_date()`. Tests use `FixedClock( Keys::EPOCH )`. `[constraint: no-wall-clock]`
- **P9. Namespaced, no globals.** Every class under `includes/` is in `WPPA` or `WPPA\Legacy`. There are no functions at file scope, including in the bootstrap. There is no `global $` except `$wpdb`, `$wp_version` and the two frozen template globals `$wppa_container` and `$wppa_publications` (§6.5). `[constraint: no-file-scope-functions]` `[constraint: no-globals]`
- **P10. Only safe remote requests.** Only `wp_safe_remote_get` and `wp_safe_remote_head` are used. The string `sslverify` does not appear. `[constraint: safe-remote-only]`
- **P11. No raw file reads.** `readfile(`, `fpassthru(`, `fopen(`, `file_get_contents(` and `file(` appear only in `includes/class-streamer.php`. There, `readfile(` appears exactly once, and its argument is a path returned by `wp_tempnam()` in the same method. `[constraint: raw-file-read-confined]`
- **P12. No `extract(`.** `[constraint: no-extract]`
- **P13. Superglobals are confined.** `$_POST`, `$_GET` and `$_REQUEST` appear only in `includes/class-meta-boxes.php` (nonce-checked save) and `includes/class-shortcode.php` (`wpa-paged`). `[constraint: superglobals-confined]`
- **P13a. WP-CLI is confined.** `WP_CLI` appears under `includes/` only in `class-cli.php` and `class-plugin.php`. Every public method on `Cli` is a subcommand. `[constraint: wp-cli-confined]`
- **P14. DAM symbols are confined.** `VIP\DAM\…` and `VIP_DAM_…` appear only in `includes/class-dam-bridge.php` and in `tests/`. `[constraint: dam-symbols-confined]`
- **P15. Every class has a test, written in the same task.**
  - Every `includes/**/class-*.php` has `tests/unit/test-<slug>.php` or `tests/integration/test-<slug>.php`, a `WPPA` namespace, a `SPEC.md §` reference and an `@author` tag. `composer test:map` fails otherwise.
  - Every task that closes a D-item names the test methods that fail on 3.0.1 behaviour and pass after the fix.
- **P16. Stored data formats are frozen.** No code writes a meta key other than the three in §5.1. `tests/fixtures/class-v3-site.php` creates 3.0.1-shaped data, including the `https|` pipe form, and every read path is tested against it.
- **P17. Leaf classes import nothing.** `Keys`, `Clock`, `NotImplementedException` and `Url_Policy` import nothing. `Url_Policy` is pure: its WordPress dependencies arrive through its constructor (§6.2). `[constraint: leaf-files-import-nothing]`
- **P18. Versions live in `Keys`.** `MIN_PHP`, `MIN_WP` and `VERSION` are declared once. `[constraint: no-hardcoded-versions]`
- **P19. The 3.0.1 paths are gone.** Nothing references `lib/`, `lang/`, `images/` or `includes/front-end.css`. `[constraint: no-legacy-asset-paths]`
- **P20. Flag gates.** The template's `enabled` flag exists, but it defaults to `true` for this plugin (§5.3) and gates only `Assets::enqueue_front()`. A 3.0.1 site upgrading must not go dark.

## 4. Architecture

### 4.1 Layout

```
wp-publication-archive.php     Headers (§5.5), ABSPATH guard, vendor/autoload.php, PHP/WP guards
                               against Keys, legacy constants, Plugin::boot(), activation and
                               deactivation hooks
uninstall.php                  Deletes the two options (§5.2) only. Never post data
humans.txt                     Template attribution (served, read-only)
includes/
  class-plugin.php             Composition root. Builds every service, the only add_* site,
                               registers aliases, CLI on cli_init
  class-keys.php               Every name (P1). Final, constants only
  class-flags.php              Every option read and write (P2)
  class-hooks.php              Every do_action and apply_filters (P4), legacy names included
  class-clock.php              Clock, SystemClock, FixedClock (P8)
  class-not-implemented-exception.php
  class-cli.php                wp publication-archive doctor (§6.8)
  class-assets.php             Front-end stylesheet, admin-media.js (§6.7)
  class-rest.php               wp-publication-archive/v1 namespace, /eam lineage route
  class-url-policy.php         Pure URL normaliser and validator (§6.2, D1)
  class-delivery.php           View, download, altview and altdown endpoints; redirect or proxy
                               (§6.2, D1, D6, D17)
  class-streamer.php           The one readfile of a temp file (P11)
  class-post-type.php          CPT, taxonomy, register_post_meta, REST exposure (§5.2, D12)
  class-capabilities.php       publication caps, idempotent grants (§5.2)
  class-rewrites.php           Rewrite rules and query vars (§6.1, D5)
  class-meta-boxes.php         Meta box markup and save_meta (D1, D2, D3, D10, D13)
  class-shortcode.php          [wp-publication-archive] handler (D15)
  class-templates.php          template_include overrides and template location (§6.5)
  class-publication-item.php   The WP_Publication_Archive_Item implementation (D2, D3, D7)
  class-icons.php              MIME type to icon URL, using wp_check_filetype (D16)
  class-categories.php         Category dropdown, list and CPT term filtering (was Utilities)
  class-upgrade.php            3.0 schema upgrade, run on init behind a version check (D9)
  class-dam-bridge.php         The only file that knows the DAM (P14, §6.9, D17–D19)
  widgets/
    class-archive-widget.php   id_base and option name unchanged (§6.6)
    class-category-count-widget.php
    class-related-widget.php
  legacy/
    class-aliases.php          class_alias for every 3.0.1 global class name (§6.3)
    class-publication-archive.php    WPPA\Legacy\Publication_Archive: static delegates
    class-utilities.php        WPPA\Legacy\Utilities: singleton delegates
templates/
  classic/                     The 3.0.1 bundled templates, same file names (§6.5, D3, D15):
                               template.wppa_publication_list.php, template.wppa_publication_dropdown.php,
                               template.wppa_widget.php, single-publication.php, archive-publication.php
assets/
  css/base.css                 The one stylesheet: 3.0.1 front-end.css rules, plus --eam-* tokens
  js/admin-media.js            wp.media frame for the three meta boxes (D13)
  icons/*.png                  Moved from images/icons/; names unchanged
languages/                     wp-publication-archive.pot, wp-publication-archive-de_DE.{po,mo}
tests/
  bootstrap.php                Unit on the host, integration in wp-env, DAM loaded when WPPA_TEST_DAM=1
  class-*-shim.php             WP_CLI shims (template)
  class-spy-container.php      Template
  fixtures/class-v3-site.php   3.0.1-shaped data (P16)
  unit/  integration/          integration/dam/ holds @group dam tests
bin/
  setup-wp-env.sh  test.sh  test-map.php  wp-env.conf  fetch-dam.sh  build-zip.sh
docs/
  SPEC.md  foundry.json  HOOKS.md  CONTRIBUTING.md  adr/
.github/workflows/verify.yml
```

These 3.0.1 paths are deleted by the Phase 0 restructure (P19): `lib/` (including `class.mimetype.php`), `includes/*.php`, `includes/front-end.css`, `images/cabinet.png`, `images/icons/` (moved) and `lang/` (renamed).

`V4_SPEC.md` §4.1 describes a `src/` PSR-4 layout. This spec deliberately uses the template's `includes/class-*.php` classmap instead, and 4.0 will be re-specified onto it. The module names above are chosen to match 4.0's `Delivery`, `Model`, `Upgrade` and `Platform` responsibilities.

### 4.2 Module map and dependencies

| Path | Responsibility | May import from |
|---|---|---|
| `wp-publication-archive.php` | Bootstrap | `vendor/autoload.php`, `Keys`, `Plugin` |
| `class-plugin.php` | Composition root | everything under `includes/` |
| `class-keys.php`, `class-clock.php`, `class-not-implemented-exception.php`, `class-url-policy.php` | Leaves | nothing |
| `class-flags.php` | Options | `Keys`, `Hooks` |
| `class-hooks.php` | Hook firing | `Keys` |
| `class-streamer.php` | Byte output | `Keys` |
| `class-dam-bridge.php` | DAM adapter | `Keys`, `Hooks`, `Url_Policy` |
| `class-delivery.php` | Endpoints | `Keys`, `Hooks`, `Flags`, `Url_Policy`, `Streamer`, `Dam_Bridge` |
| `class-post-type.php`, `class-capabilities.php`, `class-rewrites.php`, `class-upgrade.php` | Model and routing | `Keys`, `Hooks`, `Flags`, `Url_Policy` |
| `class-publication-item.php`, `class-icons.php`, `class-categories.php` | Presentation helpers | `Keys`, `Hooks`, `Clock`, `Url_Policy`, `Dam_Bridge`, `Icons` |
| `class-meta-boxes.php`, `class-shortcode.php`, `class-templates.php`, `widgets/*` | Surfaces | anything above except `Plugin` |
| `legacy/*` | Back-compat delegates | `Plugin` (to reach services), anything above |
| `class-cli.php`, `class-assets.php`, `class-rest.php` | Template services | `Keys`, `Flags`, `Hooks`, and for `Cli` also `Dam_Bridge` |

Services are built by hand-written constructor injection in `Plugin::__construct()` (template ADR 0001). Each service has an accessor and a `replace()` case. Legacy delegates reach services only through `Plugin::instance()->…()`.

## 5. Data and configuration

### 5.1 Stored post data (frozen)

| Key (Keys constant) | Type | Format | Notes |
|---|---|---|---|
| `wpa_upload_doc` (`META_DOC`) | post meta, single | Absolute URL. Legacy values may use `http|` / `https|` | Read paths normalise through `Url_Policy::normalise()`. Write paths store the validated value, or `''` (§6.2) |
| `wpa-upload_image` (`META_IMAGE`) | post meta, single | Absolute URL | Same |
| `wpa-upload_alternates` (`META_ALTERNATES`) | post meta, repeated | `array( 'description' => string, 'url' => string )` per row | From 3.1.0 the description is written through `sanitize_text_field()`. Existing rows are escaped on output |

### 5.2 Options (read and written only in `Flags`)

| Constant | Option | Default | Notes |
|---|---|---|---|
| `OPT_SCHEMA` | `wp-publication-archive-core` | absent | Integer schema version. 3.1.0 keeps it at `3`. Autoload `no` |
| `OPT_CAPS` | `wp-publication-archive-caps` | absent | `1` once the capability grants in §6.1 have run. Autoload `no` |
| `OPT_ENABLED` | `wp-publication-archive-enabled` | absent → `true` | Template flag (P20). Filterable through `Hooks::filter_enabled()` |

### 5.3 Tunables (filters, read at the point of use through `Hooks`)

| Filter | Default | Meaning |
|---|---|---|
| `wppa_mask_url` | `false` | When `true`, delivery proxies the file through PHP (§6.2) instead of redirecting. It was `true` in 3.0.1. It is flipped because proxying ties up a PHP worker and is unsafe by default. The readme upgrade notice says so |
| `wppa_proxy_timeout` | `30` ⚠️ ASSUMPTION (`Keys::DEFAULT_PROXY_TIMEOUT`) | Seconds passed to `wp_safe_remote_get()` in proxy mode |
| `wppa_proxy_max_bytes` | `52428800` ⚠️ ASSUMPTION (`Keys::DEFAULT_PROXY_MAX_BYTES`) | When HEAD reports a larger `content-length`, proxy mode redirects instead |
| `wppa_list_limit`, `wpa-pubs_per_page` | `10` | Unchanged |
| `wpa-summary-length`, `wpa-widget-summary-length` | unchanged | Unchanged |

Defaults are `Keys` constants. No tunable's numeric literal appears outside `class-keys.php`. The two ⚠️ ASSUMPTION values need no tuning task: they are safety caps, and the manual check in Phase 1 exercises them.

### 5.4 Platform

| Key | Value | Where |
|---|---|---|
| PHP | `>=7.4` | `Keys::MIN_PHP`, `composer.json` `require.php` and `config.platform.php` = `7.4.0`, header |
| WordPress | `>=6.7` | `Keys::MIN_WP`, header |
| Tested with | WordPress 7.1, PHP 8.3 | `.wp-env.json` (`core: null`, `phpVersion: "8.3"`), CI |
| PHPStan level | `6`, no baseline | `phpstan.neon.dist` |
| Namespace | `WPPA`; `WPPA\Legacy`, `WPPA\Widgets` and `WPPA\Tests\…` for tests | Composer classmap over `includes/`; autoload-dev classmap over `tests/` |
| Runtime vendor | Composer autoloader only (no runtime packages). `composer build` runs `composer install --no-dev -o` into the zip staging directory | `bin/build-zip.sh` |
| Lineage | `Keys::LINEAGE` = `'eamann/plugin-template by Eric A. Mann (EAM)'`, `Keys::EPOCH` = `437184000`, `Keys::EPOCH_DATE` = `'1983-11-09'`, `Keys::ASSET_VERSION` = `'19831109'` | Template values, never renamed. `ASSET_VERSION` is the `ver` for every enqueue |

PHP 7.4 syntax only. `PHPCompatibilityWP` uses `testVersion 7.4-`. That means no union types, `match`, enums, `readonly`, named arguments, constructor promotion, `mixed`, or `str_contains()` without a polyfill. Typed properties and `?Type` are allowed.

### 5.5 Plugin headers

```
Plugin Name: WP Publication Archive
Plugin URI: https://github.com/ericmann/WP-Publication-Archive
Description: Manage, list, search and deliver publications (PDF, Office documents and other files) as a custom post type.
Version: 3.1.0
Requires at least: 6.7
Requires PHP: 7.4
Author: Eric Mann
Author URI: https://eamann.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wp-publication-archive
Domain Path: /languages
```

The legacy constants are kept for themes that read them:

- `WP_PUB_ARCH_VERSION` = `Keys::VERSION` (`'3.1.0'`);
- `WP_PUB_ARCH_URL` = `plugin_dir_url( __FILE__ )`;
- `WP_PUB_ARCH_DIR` = the plugin directory with a trailing slash.

`readme.txt` gets:

- `Tested up to: 7.1`, `Stable tag: 3.1.0`, `Requires PHP: 7.4`;
- a 3.1.0 changelog entry listing D1–D19 in plain language;
- a 3.1.0 Upgrade Notice covering two points: downloads now redirect by default and proxying is opt-in through `wppa_mask_url`; and hook callbacks named by global class callables are no longer removable (§2).

## 6. Interfaces

### 6.1 Model (D12)

**Post type and taxonomy**

- **Post type `publication`.** The 3.0.1 arguments stay, and 3.1.0 adds:
  - `show_in_rest => true` and `rest_base => 'publications'`;
  - `menu_icon => 'dashicons-media-document'`;
  - `capability_type => array( 'publication', 'publications' )` and `map_meta_cap => true`.
- **Taxonomy `publication-author`.** It gains:
  - `show_in_rest => true` and `public => true`;
  - `query_var => 'publication-author'`;
  - `rewrite => array( 'slug' => 'publication/author' )`.

**Registered meta.** `register_post_meta( 'publication', … )` registers the three keys in §5.1:

- `show_in_rest` with a schema: `string` (format `uri`) for the two URLs; for alternates, `single => false` and an object `{ description: string, url: string }`;
- `auth_callback` requires `edit_post` on the object;
- `sanitize_callback` for each URL runs `Url_Policy::validate()` and stores `''` when validation fails. Alternates get `sanitize_text_field()` on the description and `validate()` on the URL.

**Capabilities.** `Capabilities::grant()` runs on activation, and on `init` when `Flags::caps_granted()` is false. It gives each role the `publication` equivalent of the `post` caps that role has:

- `administrator`;
- `editor`;
- `author`, which gets the same subset it has for `post`.

It records `OPT_CAPS = 1`. Running it twice changes nothing. On 3.0.1 sites, publications may have been authored by any role that could edit posts. This mapping leaves that access unchanged.

### 6.2 URL policy and delivery (D1, D6, D17)

```php
namespace WPPA;

final class Url_Policy {
    /** @param callable(string):bool $is_safe_external wraps wp_http_validate_url() */
    public function __construct( string $site_host, callable $is_safe_external );
    public function normalise( string $stored ): string;           // http|x → http://x, https|x → https://x, trims
    /** @return string|\WP_Error  */
    public function validate( string $url );                        // steps 1–3 below
}
```

`Url_Policy` is a leaf (P17). `Plugin` constructs it with `wp_parse_url( home_url(), PHP_URL_HOST )` and a closure over `wp_http_validate_url()`. `validate()` works in three steps:

1. Normalise the URL.
2. Reject with `WP_Error( 'wppa_invalid_url' )` unless the scheme is `http` or `https` and the host is non-empty. The URL is parsed with `parse_url()`, which is the one PHP function a leaf may use for this. `WP_Error` is referenced by its fully qualified name and never imported.
3. Accept if the host equals `$site_host`. Otherwise accept only if `$is_safe_external( $url )` is true.

Its unit tests run on the host with no WordPress and use a stub callable. They are a table test over these inputs:

- `/etc/passwd` and `../wp-config.php`;
- `file:///etc/passwd` and `javascript:alert(1)`;
- `http://127.0.0.1/` and `http://169.254.169.254/latest/meta-data/`, where the stub rejects;
- a same-site uploads URL;
- an external HTTPS URL, where the stub accepts;
- `https|example.com/a.pdf`;
- the empty string.

`WP_Error` is not available on the host. The unit test bootstrap defines a minimal test-only `WP_Error` shim, `tests/class-wp-error-shim.php`, guarded by `class_exists( 'WP_Error', false )`.

**Delivery endpoints.** `Delivery::handle()` runs on `template_redirect`:

1. Read the stored URL for the view, download, altview or altdown endpoint.
2. Apply `wppa_open_url` or `wppa_download_url` through `Hooks`.
3. `validate()` the result. On error, `wp_die()` with status 404.
4. **D17.** If `Dam_Bridge::is_withheld( $url )` is true, `wp_die()` with status 404.
5. **Default mode** (`wppa_mask_url` false): `wp_safe_redirect( $url, 302 )` then `exit`. The download endpoint does the same. A redirect cannot force `Content-Disposition`, and the readme says so.
6. **Proxy mode** (`wppa_mask_url` true):
   1. `wp_safe_remote_head()`.
   2. If `content-length` exceeds `wppa_proxy_max_bytes`, redirect instead.
   3. Otherwise `wp_safe_remote_get()` with `stream => true`, `filename => wp_tempnam()` and `timeout => wppa_proxy_timeout`. On error, redirect.
   4. On success, call `Streamer::send( $path, $content_type, $filename_or_null )`.

**`Streamer::send()`** does the following, in order:

1. Sends `Content-Type`: from `wp_check_filetype()` on the URL basename, else the response header, else `application/octet-stream`.
2. Sends `Content-Length`: the temp file's size.
3. For downloads only, sends `Content-Disposition: attachment; filename="…"`, with the filename passed through `sanitize_file_name()`.
4. **D6.** Calls `ob_end_clean()` only while `ob_get_level() > 0`.
5. `readfile()`s the temp file, then unlinks it.
6. Exits. The exit goes through an injectable `exit` callable so tests can observe the call.

### 6.3 Back-compat classes (G6)

`Legacy\Aliases::register()`, called from `Plugin::boot()`, calls `class_alias()` for:

| 3.0.1 global name | 3.1.0 class |
|---|---|
| `WP_Publication_Archive` | `WPPA\Legacy\Publication_Archive` |
| `WP_Publication_Archive_Item` | `WPPA\Publication_Item` |
| `WP_Publication_Archive_Utilities` | `WPPA\Legacy\Utilities` |
| `WP_Publication_Archive_Widget` | `WPPA\Widgets\Archive_Widget` |
| `WP_Publication_Archive_Cat_Count_Widget` | `WPPA\Widgets\Category_Count_Widget` |
| `WP_Publication_Archive_Category_Widget` | `WPPA\Widgets\Related_Widget` |

`mimetype` (from `class.mimetype.php`) is not aliased. It is deleted (D16).

Every public method of those six 3.0.1 classes, static or instance, exists with the same name, parameters and return shape. They delegate to services. The integration test `tests/integration/test-aliases.php` holds a list of every 3.0.1 public method, written out literally in the test. It asserts `method_exists()` and parameter counts for each.

Four method groups need specific behaviour:

- **Methods that were never hooked** (`the_content`, `the_title`, `publication_link`; D8) remain as public methods that return their first argument unchanged.
- **`search`, `search_join` and `search_distinct`** (D4) remain as public methods that return their argument unchanged and are hooked nowhere.
- **`upgrade( $from )`** delegates to `Upgrade::run()`.
- **`get_image( $doctype )`** delegates to `Icons::url_for()`. Icon URLs now point at `assets/icons/`, still filtered by `wppa_publication_icon`.

### 6.4 Hooks (names and arguments unchanged, fired only through `Hooks`)

**Filters kept, with identical signatures:**

- delivery: `wppa_open_url`, `wppa_download_url`, `wppa_mask_url`;
- icons and list size: `wppa_publication_icon`, `wppa_list_limit`, `wpa-pubs_per_page` (deprecated, still applied first);
- templates: `wppa_list_template`, `wppa_dropdown_template`, `wppa_widget_template`, `wppa_single_template`, `wppa_archive_template`, `wppa_publication_list_container`;
- item fields: `wpa-title`, `wpa-upload_image`, `wpa-authors`, `wpa-summary`, `wpa-keywords`, `wpa-categories`;
- summary length and link target: `wpa-summary-length`, `wpa-widget-summary-length`, `wp_pubarch_open_in_blank`.

**Added:**

- the two tunables in §5.3;
- the template's `wp-publication-archive-enabled` filter and `wppa_booted` action.

**Removed:**

- the `posts_where_request` callback (D4);
- the `posts_join_request` and `posts_distinct_request` callbacks added at run time (D4);
- the `allow_url_fopen` `admin_notices` callback (D11).

**Consumed from the DAM:** `vip_dam_indexed_attachment_ids` (§6.9).

`docs/HOOKS.md` lists every exposed hook with its arguments and the 3.0.1 version it dates from.

### 6.5 Templates

**Theme overrides.** Theme overrides are found by these file names in the theme, through `locate_template()` exactly as in 3.0.1:

- `template.wppa_publication_list.php`;
- `template.wppa_publication_dropdown.php`;
- `template.wppa_widget.php`;
- `single-publication.php`;
- `archive-publication.php`.

The bundled fallbacks live in `templates/classic/`.

**Template globals.** The list and dropdown templates receive the global `$wppa_container`. Its keys are `publications`, `total_pubs`, `limit`, `offset`, `paged` and `post`. The widget template receives `$wppa_publications`, a `WP_Query`.

**D15.** Bundled templates read `$wppa_container['publications']` and the other keys explicitly. A theme's copy of a 3.0.1 template that calls `extract()` itself keeps working, because the global has not changed.

### 6.6 Widgets (D14)

`id_base` values, and therefore the `widget_<id_base>` option names, are the ones 3.0.1 computes. That includes the `false` id_base that `WP_Widget` derives from the class name. The derived value is written into `Keys` as a literal, **not** recomputed from the new class name. Otherwise existing sidebars would lose their widgets.

`tests/integration/test-widgets.php` checks this. It stores a `widget_<3.0.1 id_base>` option shaped like 3.0.1's, and asserts the widget renders from it.

Each constructor passes `'show_instance_in_rest' => true`. Output is escaped. Markup is unchanged.

### 6.7 Admin and assets (D13)

**Meta boxes.** The three meta boxes keep their IDs and field names:

- `wpa_upload_doc`;
- `wpa-upload_image`;
- `wpa-alternates[description][]` and `wpa-alternates[url][]`.

Their `value=""` attributes are escaped (D3).

**`assets/js/admin-media.js`**

- It is enqueued only on `post.php` and `post-new.php` for the `publication` post type, after `wp_enqueue_media()`, and depends on `media-editor`.
- On click of `#upload_doc_button`, `#wpa-upload_image_button` or `.wpa-upload-row`, it opens a `wp.media` frame (`multiple: false`) and writes the chosen attachment's `url` into that row's input.
- The alternates Add Row and Delete behaviours live in the same file.
- It uses no jQuery except `jQuery( document ).on()` delegation, and never touches `window.send_to_editor`.
- The inline `<script>` blocks and Thickbox are gone.

**Front-end styles.** `assets/css/base.css` is enqueued under the 3.0.1 handle `wp-publication-archive-frontend`, so theme dequeues keep working, with `Keys::ASSET_VERSION`.

### 6.8 CLI and REST (template)

`wp publication-archive doctor [--format=<table|json>]` exits 0 when every check passes. The rows are:

- `lineage`;
- `version`;
- `php` and `wp`, checked against the minimums;
- `post_type_registered`;
- `rest_enabled`;
- `rewrite_rules_present`, which checks the four endpoint rules and the §6.1 slug rules;
- `caps_granted`;
- `dam`. With the DAM absent this row reports `absent` and passes. With the DAM present it reports its version and whether the usage-index filter is attached, and passes only if the filter is attached.

`GET /wp-json/wp-publication-archive/v1/eam` returns `{ lineage, epoch, date, version }`. It is public and read-only.

### 6.9 DAM bridge (D17–D19)

```php
namespace WPPA;

final class Dam_Bridge {
    public function __construct( Url_Policy $policy );
    public function active(): bool;                         // class_exists( '\VIP\DAM\Embargo_Guard' ) && class_exists( '\VIP\DAM\Lifecycle' )
    public function attachment_id_for( string $url ): int;  // same-site only: attachment_url_to_postid( normalise( $url ) ), size suffix stripped; 0 otherwise
    public function is_withheld( string $url ): bool;       // D17
    public function display_url( string $url ): string;     // D18
    /** @param int[] $ids @return int[] */
    public function indexed_attachment_ids( array $ids, \WP_Post $post ): array; // D19
}
```

- **Inactive DAM.** When `active()` is false:
  - `is_withheld()` returns `false`;
  - `display_url()` returns its argument;
  - `indexed_attachment_ids()` returns `$ids` unchanged.

  No other code path branches on the DAM.
- **`is_withheld()`.** It returns true when all of these hold:
  - `attachment_id_for()` is non-zero;
  - `current_user_can( 'edit_post', $id )` is false;
  - one of the following is true: `\VIP\DAM\Embargo_Guard::is_hidden( $id )` (which covers embargo and lifecycle) or the attachment's `post_status` is `trash`.

  Editors always get the file.
- **`display_url()`.** It returns `\VIP\DAM\Embargo_Guard::placeholder_url()` when `is_withheld()` would be true for the current user. Otherwise it returns the URL. `Publication_Item::get_the_thumbnail()` passes the thumbnail through it after `wpa-upload_image` and before escaping.
- **`indexed_attachment_ids()`.** This is registered by `Plugin` on `vip_dam_indexed_attachment_ids` (priority 10, 2 args) unconditionally; with the DAM absent the filter never fires. For a `publication` post it:
  - adds `attachment_id_for()` of the normalised `wpa_upload_doc`, of `wpa-upload_image`, and of every alternate URL;
  - removes duplicates;
  - returns every other post type's `$ids` untouched.

  The DAM then counts pipe-form references too.

The DAM API surface this bridge relies on is pinned by `DAM_REF` (§7.4):

- `Embargo_Guard::is_hidden( int ): bool`;
- `Embargo_Guard::placeholder_url(): string`;
- `Usage_Index::get_usage( int )`, used by tests only;
- the filter `vip_dam_indexed_attachment_ids( int[] $ids, WP_Post $post )`.

`tests/integration/dam/test-dam-contract.php` asserts that those methods exist, with those parameter counts, so a DAM upgrade that breaks the contract fails loudly.

## 7. Commands

All commands are Composer scripts, and each exits non-zero on failure.

- **Install:** `composer install && npm install`
- **DAM:** `bash bin/fetch-dam.sh` (§7.4). This needs SSH access to github.a8c.com. It is idempotent.
- **Environment:** `npx wp-env start`. This runs `bin/setup-wp-env.sh`, which activates this plugin, and also the DAM when it has been fetched.
- **Lint:** `composer lint` → `vendor/bin/phpcs --standard=phpcs.xml.dist`
- **Analyse:** `composer analyse` → `vendor/bin/phpstan analyse --no-progress --error-format=raw --memory-limit=1G`
- **Test map:** `composer test:map` → `php bin/test-map.php`
- **Unit tests (no Docker):** `composer test:unit` → `vendor/bin/phpunit --testsuite unit`
- **All tests (wp-env):** `composer test` → `bash bin/test.sh`
  - With `DAM=1` in `bin/wp-env.conf`, the default, it **fails** if `.cache/vip-digital-asset-manager/index.php` is missing. Otherwise it runs every group with `WPPA_TEST_DAM=1`.
  - `WPPA_DAM=0 composer test` overrides that, adds `--exclude-group dam`, and does not load the DAM. CI uses this form.
- **Static verify:** `composer verify:static` → lint, analyse, test:map, test:unit
- **Full verify:** `composer verify` → verify:static, then test
- **Smoke:** `npx wp-env run cli wp publication-archive doctor` exits 0
- **Build:** `composer build` → `bash bin/build-zip.sh`, which writes `dist/wp-publication-archive.zip`:
  - stage the files that `.distignore` allows;
  - run `composer install --no-dev -o` in the staging directory, so only `vendor/autoload.php` and `vendor/composer/` ship;
  - zip the result.

Tooling installed in the Phase 0 scaffold:

- Composer, dev: the template's packages, plus `phpcompatibility/phpcompatibility-wp:^2.1`.
  - `yoast/phpunit-polyfills` is `^3.0`, as in the template.
  - `wp-phpunit/wp-phpunit` is `^6.7`.
  - `phpunit/phpunit` is `^9.6`, compatible with PHP 7.4.
- npm, dev: `@wordpress/env:^11`.

### 7.1 Foundry run settings

`docs/foundry.json` is committed before planning. It sets:

- `verify`, `extraVerify`, `build`, `baseBranch: "master"` and `branchPrefix: "build/"`;
- `roles`, with every model set by the user:

  | Role | Model | Effort |
  |---|---|---|
  | planner | Opus 5.5 (`claude-opus-5-5`) | high |
  | implementer | Sonnet (`sonnet`) | medium |
  | reviewer | Opus 5.5 | high |
  | summarizer | Opus 5.5 | medium |

- `permissionMode`, `policies`, and nineteen self-testing `constraints`.

The planner keeps every key and every constraint as written. It may add constraints for rules it can express as a single-line pattern. It must not change `roles`, `verify`, `extraVerify` or `policies`.

There is no `parallel` block: Foundry 0.3.2 has no streams. Do not add `**Stream:**` fields to `PLAN.md` either, because they are 0.4.0 syntax. Workstreams are recorded as `**Workstream:**` (§8.0), which 0.3.2 ignores.

`verify` is the Docker-free static set: lint, analyse, test:map and test:unit. The wp-env suite, `composer test`, runs through `extraVerify` for any task that touches `includes/`, `templates/`, `tests/`, `bin/`, `.wp-env.json`, the bootstrap, `uninstall.php` or `composer.json`. That is nearly every task, so every behavioural change is proven in wp-env, with the DAM loaded, in the task that makes it.

### 7.2 Branch and PR

- Foundry cuts `build/<date>` from `master` and pushes every commit (`policies.push: true`).
- At the end of implementation, `foundry_run_finish` opens a draft PR against `master` (`policies.pr: "draft"`).
- The PR title is `WP Publication Archive 3.1.0`.
- The PR body lists D1–D19 and links `docs/SUMMARY.md`.
- Review rounds push onto the same branch and PR.
- **Once `docs/SUMMARY.md` is committed and pushed, and only then**, the summarizer runs `gh pr ready` on that PR, if `gh` is available and authenticated. That makes it the final PR.
- Nothing merges it. A human does.

### 7.3 Lint configuration

`phpcs.xml.dist`:

- **Files:** `wp-publication-archive.php`, `uninstall.php`, `includes`, `templates`, `tests` and `bin`. Exclude `vendor`, `node_modules`, `.cache` and `dist`.
- **Rulesets:** `WordPress-VIP-Go`, plus these sniffs at error severity:
  - `WordPress.Security`;
  - `WordPress.WP.I18n` (`text_domain=wp-publication-archive`);
  - `WordPress.DateTime.RestrictedFunctions`;
  - `WordPress.PHP.DontExtract`;
  - `WordPress.PHP.DevelopmentFunctions`;
  - `PHPCompatibilityWP` (`testVersion 7.4-`).
- **Exemptions:**
  - `WordPress.Files.FileName` is excluded only for `templates/classic/`, because theme override names are frozen.
  - `WordPress.Security.EscapeOutput` is excluded for `tests/` and `bin/`, as in the template.

`phpstan.neon.dist`:

- level 6;
- paths `includes`, `templates`, `wp-publication-archive.php`, `uninstall.php` and `bin/test-map.php`;
- the `szepeviktor/phpstan-wordpress` extension, plus the wp-cli stubs;
- a bootstrap file that defines the three legacy constants;
- `scanDirectories: [ phpstan/stubs ]`, with the DAM stubs from §7.4.

### 7.4 The DAM in wp-env

**Pin.** `bin/wp-env.conf` adds:

```
DAM=1
DAM_REPO="git@github.a8c.com:mrchriswdixon/vip-digital-asset-manager.git"
DAM_REF="9d7f1667608eb0f1cd537351546d2e4d505e8202"   # DAM 4.0.2, main on 2026-09-24
```

**`bin/fetch-dam.sh`** does three things:

1. Clones or fetches `DAM_REPO` into `.cache/vip-digital-asset-manager` and checks out `DAM_REF` detached. `.cache/` is gitignored.
2. Writes `.wp-env.override.json`, which is gitignored, mapping `wp-content/plugins/vip-digital-asset-manager` to that directory. It merges into any existing override and never clobbers other keys.
3. Prints the DAM version it pinned.

The DAM needs no Composer or npm install to run. It loads its own classes, and its `.distignore` confirms it ships no `vendor/`.

**Environment and tests.**

- `bin/setup-wp-env.sh` activates `vip-digital-asset-manager` in both environments when the directory exists. It runs `wp rewrite structure '/%postname%/' --hard`, because both plugins need pretty permalinks.
- `tests/bootstrap.php` behaves according to `WPPA_TEST_DAM`:
  - With `WPPA_TEST_DAM=1`, it `require`s `wp-content/plugins/vip-digital-asset-manager/index.php` in `muplugins_loaded` before this plugin. If the file is missing it throws a `RuntimeException`, so tests fail rather than skip.
  - With `WPPA_TEST_DAM` unset, `@group dam` tests are excluded by `bin/test.sh`, never skipped from inside a test.

**Stubs.** `phpstan/stubs/vip-dam.php` declares the four symbols the bridge uses (§6.9), with their signatures, so `composer analyse` needs no DAM checkout, including in CI.

**CI** (`.github/workflows/verify.yml`, on GitHub.com, which has no github.a8c.com access):

- a `static` job runs `composer verify:static` on PHP 7.4 and 8.3;
- an `integration` job runs `npx wp-env start` and then `WPPA_DAM=0 composer test` on PHP 8.3 with Node 22.

The DAM group runs locally and in the Foundry flight, which runs on a machine with github.a8c.com access. A flight on a machine without that access must not pass silently: `bin/test.sh` fails, and the implementer blocks the task with that reason.

## 8. Phases

### 8.0 How to plan this spec: workstreams, contracts, gates

Implementation speed matters, and Foundry 0.3.2 runs one implementer serially. The plan therefore does the parallelism work by hand. **Every group of tasks that could run at the same time is planned as a wave of independent workstreams.** A human can then split a wave across several implementers manually, and the partition maps directly onto Foundry's streams once they ship. In this flight, the implementer runs the wave's tasks in `PROGRESS.md` order.

Each wave has this shape:

1. **A serial contracts task** does everything in the phase that would otherwise be a shared hotspot:
   - every `Keys` constant, `Hooks` method, `Flags` method and `docs/HOOKS.md` entry the phase needs;
   - the `Plugin` wiring and accessors;
   - the full public signature of every class the wave will fill in, with bodies of exactly `throw new NotImplementedException( __METHOD__ );`;
   - a test file for each new class, holding one structural test (the class exists and is final), so `test:map` passes and PHPUnit finds a test.

   After this task, no wave task needs to touch `class-keys.php`, `class-hooks.php`, `class-flags.php`, `class-plugin.php`, `docs/HOOKS.md`, `composer.json` or any file outside its own workstream.
2. **A wave of workstream tasks** follows. Each task carries `**Workstream:** <slug>` in `PLAN.md`, just after `**Depends on:**`. Within a wave:
   - each workstream owns a disjoint set of files, listed as backticked paths in `**Files touched:**`;
   - a task depends only on the contracts task or on earlier tasks in its own workstream, never on another workstream in the same wave;
   - `PROGRESS.md` lists the wave's tasks grouped by workstream, in the order of the tables below. That order matters in a serial flight: a workstream listed earlier may be consumed, through its contract signatures only, by one listed later.

   Each task fills in its classes and writes their unit and integration tests. Its verification runs the whole suite, including wp-env (§7.1).
3. **A serial gate task** follows each wave. It:
   - runs `composer verify` with the DAM loaded, and `WPPA_DAM=0 composer test`;
   - fixes anything the combination broke;
   - runs the phase's greps;
   - ends the phase: pushes the branch and lists the manual checks.

A wave has at most four workstreams. The workstreams below are the intended partition. The planner may split a workstream further when the file sets allow, but must not merge workstreams. If two workstreams need the same file, the planner moves that edit into the contracts task. Phase-end work is always in the gate task.

### Phase 0 — Foundation (serial; everything here is a hotspot)

1. **Scaffold.** Import the template: bootstrap shape, `Plugin`, `Keys`, `Flags`, `Hooks`, `Clock`, `NotImplementedException`, `Cli`, `Assets`, `Rest`, test shims, `bin/`, `.wp-env.json`, `phpcs.xml.dist`, `phpstan.neon.dist`, `phpunit.xml.dist`, `composer.json`, `package.json`, CI, `humans.txt`, and `docs/HOOKS.md`, `CONTRIBUTING.md` and `adr/0001`.
   - Rename everything: slug `wp-publication-archive`, namespace `WPPA`, CLI command `publication-archive`, and PHP 7.4 per §5.4.
   - The 3.0.1 code under `lib/` and `includes/` keeps loading from the bootstrap and is outside the lint and analysis paths for this task only.
   - Add `tests/fixtures/class-v3-site.php`. It creates:
     - a publication whose same-site `wpa_upload_doc` points at an attachment from the upload factory;
     - one with an `https|` pipe URL;
     - one with two alternates, one description containing `<script>alert(1)</script>`;
     - one with a thumbnail URL;
     - one in a category with a `publication-author` term;
     - one with slug `view`, and one with slug `download`.
   - Add smoke tests: the plugin boots and `doctor` passes.
2. **DAM in wp-env** (§7.4): `fetch-dam.sh`, the override, setup activation, the bootstrap loading, the PHPStan stubs, the `dam` doctor row, and `tests/integration/dam/test-dam-contract.php`.
3. **Characterisation.** Integration tests that pin 3.0.1 behaviour against the fixture, before anything moves:
   - the URLs in G5 resolve to the right query;
   - the link generators return today's URLs;
   - the shortcode list and dropdown output, whitespace-normalised, are stored as expected strings in the test;
   - each widget renders;
   - theme template overrides are located.

   Where a later D-fix changes an expected string, the D-task updates it and names the D-item in the commit.
4. **Restructure** (one task; the planner may split it into consecutive serial tasks, never into a wave):
   - Move 3.0.1 code into the §4.1 layout: services, `legacy/` delegates with aliases, `widgets/`, `templates/classic/`, `assets/` and `languages/`. Rename `.po` and `.mo` files with their msgids preserved, and switch every i18n call to `wp-publication-archive`.
   - Put all hook registration in `Plugin`, all firing in `Hooks`, all names in `Keys`, all options in `Flags`, and all date formatting through `Clock` (P3, P4, P1, P2, P8). Replace the scoped filter patterns (P3).
   - Remove `lib/`, `lang/`, `images/` and the old `includes/*.php`. Delete `class.mimetype.php` and route MIME lookups through `wp_check_filetype()`, keeping icon output unchanged (D16, part).
   - Behaviour is preserved, including the D-defects. A defect that lint forbids is kept with a `phpcs:ignore <sniff> -- reason: D<n>` (P7). Every other lint and PHPStan finding is fixed.
   - The characterisation tests pass unchanged.
5. **Gate 0.** `composer verify` is green with the DAM loaded. `WPPA_DAM=0 composer test` is green. CI is green on the pushed branch.
   - **Manual check:** `npx wp-env start` on a clean clone after `bash bin/fetch-dam.sh`. The front-end list and single pages, and wp-admin → Publications, look the same as on 3.0.1, with both plugins active.

### Phase 1 — Security and DAM bridge

- **Contracts:**
  - the `Keys`, `Hooks` and `Flags` entries for §5.3 and §6.2;
  - the `Plugin` construction of `Url_Policy` (with the site host and validator closure), `Delivery`, `Streamer` and `Dam_Bridge`, and the registration of `vip_dam_indexed_attachment_ids`;
  - signatures for the four classes;
  - `tests/class-wp-error-shim.php`.
- **Wave 1:**

  | Workstream | Tasks | Files (disjoint) | Closes |
  |---|---|---|---|
  | `url-policy` | 1. `Url_Policy` with the §6.2 table test. 2. Meta box save and render: validate URLs through `Url_Policy`; `sanitize_text_field()` on descriptions; fix the loop bound; escape the meta box values | `includes/class-url-policy.php`, `includes/class-meta-boxes.php`, `tests/unit/test-url-policy.php`, `tests/integration/test-meta-boxes.php` | D1 (save), D2 (save), D3 (admin), D10 |
  | `dam` | `Dam_Bridge` per §6.9. `@group dam` integration tests of the bridge methods themselves (Delivery is not built yet): `is_withheld()` is true for anonymous users and false for an editor on an embargoed attachment, and true for a lifecycle-archived or trashed one; `display_url()` returns the DAM placeholder for anonymous users; `Usage_Index::get_usage()` counts a pipe-form publication; the DAM refuses `media-delete` of an attachment a publication uses (`asset_in_use`). Plus non-DAM tests that the inactive bridge is inert | `includes/class-dam-bridge.php`, `tests/integration/test-dam-bridge.php`, `tests/integration/dam/test-dam-bridge-dam.php` | D17, D18, D19 |
  | `delivery` | `Delivery` and `Streamer` per §6.2, using `Url_Policy` and `Dam_Bridge` by their contract signatures (unit tests use doubles for them; in this serial flight the real `url-policy` and `dam` work is already done, so wp-env tests use the real classes). Unit tests use test doubles; integration tests: a same-site file gets a 302; stored `/etc/passwd` gets a 404 with no file bytes in the body; proxy mode against a `pre_http_request`-mocked response returns the body and headers; the size cap redirects; no notice with zero output buffers; plus `@group dam` end-to-end tests in `tests/integration/dam/test-delivery-dam.php`: an embargoed attachment gives a 404 for anonymous users and a 302 for an editor, and a trashed one gives a 404 | `includes/class-delivery.php`, `includes/class-streamer.php`, `tests/unit/test-delivery.php`, `tests/unit/test-streamer.php`, `tests/integration/test-delivery.php`, `tests/integration/test-streamer.php`, `tests/integration/dam/test-delivery-dam.php` | D1 (delivery), D6, D17 (end to end) |
  | `markup` | Escape `Publication_Item` and `templates/classic/template.wppa_publication_dropdown.php` and `template.wppa_publication_list.php`. Thumbnail through `Dam_Bridge::display_url()` (by contract). Tests: the `<script>` alternate renders as `&lt;script&gt;`; the thumbnail and title are escaped | `includes/class-publication-item.php`, `templates/classic/template.wppa_publication_dropdown.php`, `templates/classic/template.wppa_publication_list.php`, `tests/integration/test-publication-item.php` | D2 (output), D3 (front) |
- **Gate 1:**
  - `composer verify` with the DAM loaded;
  - zero `WordPress.Security` ignores (P7);
  - `grep -rn "readfile(" includes` shows exactly one hit, in `class-streamer.php`.
  - **Manual check:**
    - as an Author, save `/etc/passwd` as the document URL; the field comes back empty and the view URL returns 404;
    - embargo the linked attachment in the DAM; the view URL 404s logged out and redirects logged in as Editor;
    - with `add_filter( 'wppa_mask_url', '__return_true' )` in an mu-plugin, a 60 MB file redirects rather than proxies.

### Phase 2 — Correctness and compatibility

- **Contracts:**
  - `Keys`, `Hooks` and `Flags` for §6.1, §6.6 and §6.7;
  - remove the D4 and D11 registrations from `Plugin` and the bootstrap. D4 and D11 are closed here, with `has_filter` tests in `tests/integration/test-plugin.php`;
  - signatures for `Capabilities` and `Upgrade`;
  - the `Assets` admin handle.
- **Wave 2:**

  | Workstream | Tasks | Files (disjoint) | Closes |
  |---|---|---|---|
  | `model` | `Post_Type` and `Capabilities` per §6.1. Tests: the CPT and taxonomy report `show_in_rest`; `GET /wp/v2/publications` returns fixtures with `meta.wpa_upload_doc` for an Editor and without it for anonymous users; the REST write of `/etc/passwd` stores `''`; caps exist for the three roles after `grant()`, and running it twice is a no-op | `includes/class-post-type.php`, `includes/class-capabilities.php`, `tests/integration/test-post-type.php`, `tests/integration/test-capabilities.php` | D12 |
  | `routing` | `Rewrites` per §6.1 and D5; `Upgrade` moved onto `init` behind the `OPT_SCHEMA` comparison, flushing rewrites once on upgrade (D9). Tests: slug `view` resolves at `/publication/view/`, and `/publication/view/other-slug/` opens `other-slug`; the upgrade runs once, and later requests write no options | `includes/class-rewrites.php`, `includes/class-upgrade.php`, `tests/integration/test-rewrites.php`, `tests/integration/test-upgrade.php` | D5, D9 |
  | `admin` | `assets/js/admin-media.js`; remove the inline scripts and Thickbox from the meta box markup. Tests: the script is enqueued on the publication edit screen and not on the post edit screen; the markup contains no `<script` and no `TB_iframe` | `assets/js/admin-media.js`, `includes/class-meta-boxes.php`, `tests/integration/test-admin-media.php` | D13 |
  | `front` | Dates through `Clock::format()`; dead-code delegates per §6.3; `show_instance_in_rest` and escaping on the widgets with id_base preservation (§6.6); no `extract` in the shortcode and templates. Tests: a non-UTC `timezone_string` changes the rendered date; every widget has `show_instance_in_rest`; a 3.0.1-shaped widget option renders; the shortcode output matches the characterisation strings | `includes/class-publication-item.php`, `includes/legacy/class-publication-archive.php`, `includes/widgets/`, `includes/class-shortcode.php`, `templates/classic/template.wppa_widget.php`, `templates/classic/single-publication.php`, `templates/classic/archive-publication.php`, `tests/integration/test-widgets.php`, `tests/integration/test-shortcode.php` | D7, D8, D14, D15 |

  `class-meta-boxes.php` and `class-publication-item.php` were also edited in Wave 1. That is allowed: waves are separated by the Phase 1 gate. Within Wave 2, each file belongs to one workstream.
- **Gate 2:**
  - `composer verify` with the DAM loaded; `WPPA_DAM=0 composer test`;
  - `test-aliases.php` is green.
  - **Manual check:**
    - in the block editor, Publications → Add New shows the three meta boxes, and Upload opens the media modal and fills the URL;
    - the Legacy Widget block previews all three widgets;
    - the list and dropdown shortcodes, and the single and archive pages, render with no notices in `debug.log`, with the DAM active and again with it deactivated.

### Phase 3 — Release (serial; small, and every file is a hotspot)

- `uninstall.php` (§4.1), `.distignore`, `bin/build-zip.sh` and `composer build`.
- `readme.txt` and `CHANGELOG.md` per §5.5.
- The header and `Keys::VERSION` set to 3.1.0; the `docs/HOOKS.md` final pass.
- **Final gate:**
  - `composer verify`, `WPPA_DAM=0 composer test` and `composer build` all pass;
  - the zip has no `tests/`, `bin/`, `.cache/` or dev `vendor/` packages;
  - the branch is pushed.
- **Manual check:**
  - install `dist/wp-publication-archive.zip` on a fresh WordPress 7.1 site restored from a 3.0.1 database: no activation errors, every G5 URL resolves, and the German translation loads with `WPLANG=de_DE`;
  - repeat with the DAM active;
  - then mark the PR ready (§7.2), if the summarizer could not.

## 9. Open questions

- **Q1. The PHPUnit and polyfills pair on PHP 7.4.** `wp-phpunit` for 6.7+ accepts PHPUnit 9.6 with `yoast/phpunit-polyfills` ^3. If `composer install` cannot resolve that pair under `config.platform.php = 7.4.0`, pick the nearest pair that resolves and record it under Decisions. No spike.
- **Q2. Proxy mode.** This spec keeps proxy mode behind `wppa_mask_url`. If the planner judges that opt-in proxying cannot meet P11 cleanly, the alternative is to remove it and document `wppa_mask_url` as a no-op. Preferred: keep it.
- **Q3. The DAM floor versus ours.** The DAM needs WordPress 6.9 and PHP 8.2. We support WordPress 6.7 and PHP 7.4. `Dam_Bridge::active()` is class-based, so the bridge simply stays inert on hosts where the DAM cannot load. Nothing to decide unless the planner finds otherwise. Record the reading.

## Appendix: fixtures and references

- `docs/foundry.json`: Foundry config, roles and constraints (§7.1).
- `tests/fixtures/class-v3-site.php`: created in Phase 0 (§8).
- The 3.0.1 source in this repository is the behavioural reference. Read `lib/class.wp-publication-archive.php` and `lib/class.publication-markup.php` in full before planning.
- The plugin template: `https://github.com/ericmann/eamann-plugin-template`. Import from a clone at its `main` HEAD. Its `docs/SPEC.md` §3–7 are the source of P1–P9 and of the `bin/` scripts.
- The DAM: `git@github.a8c.com:mrchriswdixon/vip-digital-asset-manager.git` at `DAM_REF` (§7.4). `inc/class-embargo-guard.php`, `inc/class-lifecycle.php` and `inc/class-usage-index.php` are the only DAM files the bridge depends on.
