# HANDOFF

Branch: `build/2026-09-24`
Base: `e52acc84a75e`
Head: `bcd1150`
Tasks: 38 total, 38 done, 0 blocked, 0 skipped, 0 open.

## Blocked / skipped tasks

None. Every task in `docs/PLAN.md` reached `[x]`.

## Push / CI (read this first)

This GitHub repository (`git@github.com:ericmann/WP-Publication-Archive.git`)
is **archived and read-only**. Every `git push` attempt in this flight —
at P0-16, P1-09, P2-10 and P3-03 — failed with `ERROR: This repository
was archived so it is read-only.` This is an operator-level GitHub repo
state issue, not a code defect, and `foundry_run_finish`'s own push will
almost certainly hit the same wall. If a draft PR is required, the
repository will need to be un-archived (or the remote changed) first.

## A recurring environment flake (not a regression)

Across P2-05 through P3-03, `WPPA_DAM=0 composer test` and `composer
verify`/`composer test` (DAM loaded) intermittently failed a handful of
tests — always the same signature: `Object of class WP_Error could not
be converted to int` inside `tests/fixtures/class-v3-site.php:69`
(`$factory->attachment->create_upload_object()` returning a `WP_Error`),
occasionally cascading into a category/author term or a DAM `Test_Dam_Bridge_Dam`
assertion failing too. It never reproduced when the affected test class
was re-run in isolation, and a clean re-run of the full suite on an
otherwise-idle machine always went green. Logged via
`foundry_feedback_log` (5 entries this flight). If the reviewer or CI
sees this pattern, re-run rather than assume a regression — but if it
starts reproducing in isolation, it deserves real investigation (shared
Docker host under load, DB connection limits, or a fixture race are the
likely suspects; not investigated here since every task it touched left
the fixture itself untouched).

## Interpretation choices (by task ID)

- **P0-01**: dropped the template's multisite fixture entirely per SPEC;
  `Loader::activate()` calls only `init()`.
- **P0-02**: `Since` column in `docs/HOOKS.md` uses "≤ 3.0.1" throughout
  (3.0.1 had no per-hook `@since`); `DEFAULT_PROXY_TIMEOUT`/`MAX_BYTES`
  deferred to P1-01 (not in P0-02's named constant list).
- **P0-03**: `WPPA\Tests\Unit`/`WPPA\Tests\Integration` namespaces used
  only where a flat `WPPA\Tests` classmap would collide (e.g. two
  `Test_Plugin` classes); flat namespace elsewhere.
- **P0-04**: same collision-avoidance namespacing for `Test_Cli`.
- **P0-05**: DAM contract stubs copied verbatim from the pinned DAM ref;
  `Lifecycle` stubbed as an empty class (no method named by any consumer
  yet).
- **P0-06/P0-07**: pinned, not fixed, several real 3.0.1 quirks later
  named by D-items (D5's "bare endpoint resolves to the literally-slugged
  publication" behaviour; D8's post-permalink hijack; the archive
  widget's dead `orderby`/`order_by` key mismatch; the related widget's
  "always most-recent" fallback when no queried object is set).
- **P0-08**: `Upgrade::run()`'s `wp_update_post()` call passes a partial
  array, not the whole cast `WP_Post`, to satisfy PHPStan's stub.
- **P0-09**: `wp_safe_remote_head()` replaces 3.0.1's `sslverify=false`
  call (P10 constraint); `Icons::mime_for()` accepts either a full URL or
  a bare filename.
- **P0-10**: nonce action name moved to `Keys` constants (not treated as
  a preserved defect, since no D-item names it).
- **P0-11**: kept phpdoc-only types on `Publication_Item` per Decisions;
  `Delivery::resolve_uri()` instantiates `\WPPA\Publication_Item`
  directly (new code, not a literal 3.0.1 site).
- **P0-12**: `query_publications()` left where it was (no new owner
  named); `WordPress.Files.FileName` sniff left un-enabled, satisfied by
  inactivity (VIP-Go doesn't pull it in) rather than an explicit
  exemption rule.
- **P0-13**: `get_terms()` uses the modern single-`$args` signature
  (PHPStan's stub requires it); two real 3.0.1 defects (the `dropdown_categories()`
  `'name'` key collision, and the legacy 2-arg `get_terms()` convention)
  pinned as characterised behaviour, not fixed.
- **P0-14**: two more 3.0.1 quirks pinned verbatim (the archive widget's
  `unset($wppa_publications)` only clearing the local reference; the
  `orderby`/`order_by` key mismatch, same defect class as P0-13 above).
- **P0-15**: `Legacy\Publication_Archive::the_content()` reads `get_post()`
  instead of `global $post` (the no-globals allowlist excludes `$post`).
- **P0-16 (Gate 0)**: appended `no-mimetype-class`/`no-legacy-string-callables`
  constraints verbatim.
- **P1-01**: added `Url_Policy::is_safe_external()` and
  `Delivery::with_redirect_host()` beyond the task's named method list —
  both are real readers/writers PHPStan required, not suppressions.
- **P1-02**: the DAM usage-index filter registers unconditionally; the
  `dam` doctor row is absent/pass when the DAM is inactive.
- **P1-04**: closed D1/D2/D3(admin)/D10 by making sanitisation real
  (`Url_Policy::validate()`, `sanitize_text_field()`, `map_deep()`, a
  loop-bound fix) rather than suppressing the sniffs that flagged them.
- **P1-05**: `Dam_Bridge::is_withheld()`/`display_url()` per §6.9's exact
  rule; tests exercise the DAM's own Abilities rather than writing its
  meta keys directly, per the task's instruction.
- **P1-06**: `Streamer::send()`'s `ob_floor` design point (ends buffers
  only above the floor, D6); a real bare-PHP child-process fixture
  (`tests/fixtures/streamer-child.php`) proves the "nothing to end" case
  in isolation from PHPUnit's own buffering.
- **P1-07**: redirect is the default delivery mode (mask off); proxy is
  opt-in; `wp_http_validate_url()` already blocks private/link-local
  ranges, confirmed by reading WP core rather than assumed.
- **P1-08**: `esc_url()`'s default protocol allowlist excludes `data:`,
  so the DAM's placeholder needed `wp_allowed_protocols() + 'data'`
  passed explicitly (a real bug found via the D18 test, not assumed).
- **P1-09 (Gate 1)**: appended `no-security-ignores`/`no-ob-clean`
  constraints; closing the last `WordPress.Security` ignores changed the
  pinned `WIDGET_CAT_COUNT_DROPDOWN` characterisation string (named D3,
  per the task's own allowance for a fix that needs a test change).
- **P2-01**: closed D11 (deleted the `fopen_notice` admin notice
  entirely) and D8's filter half (deleted the dead `post_type_link`
  hijack); D9's *timing* moved to an `init` hook here, its buggy *body*
  fixed later at P2-06; updated the `LIST_ALL`/`LIST_LIMIT_2_PAGE_2`
  characterisation strings, naming D8.
- **P2-02**: `WP_Roles`'s DB-persistence quirk (only writes when
  `$wp_user_roles` was empty at construction) meant capability tests
  snapshot/restore `wp_roles()`'s in-memory state themselves.
- **P2-03**: `register_taxonomy()` silently drops `rewrite` under plain
  permalinks, so `Test_Cli`'s `set_up()` re-registers post
  type/taxonomy/rewrites under pretty permalinks before flushing.
- **P2-04**: `META_DOC`/`META_IMAGE`/`META_ALTERNATES` all use `edit`
  REST context (the Decisions reading of "without it for anonymous");
  `register_post_meta()`'s `sanitize_callback` only runs on write, so a
  pre-existing 3.0.1 pipe-form value in the DB stays untouched until
  something writes it again — proved, not assumed, by a raw-`$wpdb` test.
- **P2-05**: D5 is **not reproducible** on the P0-08 `Rewrites` class
  (the endpoint rules already require a non-empty path segment); the
  four acceptance tests were written first, passed unmodified, and were
  kept as regression tests per the task's own "if it already passes"
  branch.
- **P2-06**: `Upgrade::maybe_upgrade()`'s "already current" branch now
  does nothing at all (no `add_option`, no flush) — the old
  `Flags::add_schema_version()` call and method were deleted entirely,
  not just stopped;  `flush_rewrite_rules()` switched to a soft flush;
  `Flags::set_schema_version()` now sets autoload `false`, matching
  §5.2. `Flags::rewrite_rules()`'s compiled option keys keep the leading
  `^` from `add_rewrite_rule()`'s own regex — confirmed by inspection,
  not assumed.
- **P2-07**: `Meta_Boxes::render_alternates()` prints its rows through
  `wp_kses()` with an explicit allowed-tags array rather than a raw
  echo, since P1-09's `no-security-ignores` constraint forbids the
  `WordPress.Security` ignore phpcs would otherwise need; a hidden
  `<template>` row (not a `<script>`) is the single source of truth for
  the Add Row field names, cloned by `assets/js/admin-media.js`. New
  `Keys::ADMIN_SCRIPT_OBJECT` alongside P2-01's admin-script constants.
- **P2-08**: D7's date now goes through `Clock::format()` +
  `get_post_time('U', true, $post)` per the task's exact call; D8's
  three delegates (`the_content`, `the_title`, `publication_link`) all
  just return their first argument.
- **P2-09**: two real findings from reading WP core (not guessed): the
  widget-types REST `encode` endpoint's `form_data` param must be a
  urlencoded string shaped `widget-<id_base>[<number>][<field>]=<value>`,
  not a JSON object; and `WP_Widget_Factory::_register_widgets()` silently
  drops any widget class whose `id_base` is already registered, so
  proving a 3.0.1-shaped option renders means calling `->_register()`
  directly on the already-booted widget object. `test_widget_renders_from_301_shaped_option`'s
  `tear_down()` deliberately never touches `$wp_widget_factory`/
  `$wp_registered_widgets` globally, since an earlier draft did and broke
  every later test file in the same process.
- **P2-10 (Gate 2)**: appended `no-thickbox`/`no-allow-url-fopen`
  constraints verbatim; fixed a real `names-in-keys-only` hit the
  whole-repo scan surfaced only now (P2-09's `test-widgets.php` used a
  literal `'wppa-test-sidebar'` id four times — renamed, behaviour
  unchanged).
- **P3-01**: `uninstall.php`/`Flags::delete_all()`/`.distignore`/
  `bin/build-zip.sh` all copied/implemented verbatim from the task text;
  the build script's dev-leftover check initially mis-flagged the zip's
  own bare `vendor/` directory entry as a stray file, fixed by requiring
  a non-empty path segment after `/vendor/`.
- **P3-02**: the 3.1.0 changelog/`CHANGELOG.md` group D2/D3 and D17/D18
  into one bullet each (same underlying fix); `docs/HOOKS.md`'s new
  Tunables table intentionally leaves the "Default" column un-backticked,
  since `tests/unit/test-hooks-doc.php`'s table parser reads any
  `` | `x` | `lowercase_word` | `` row as a `Hooks::` method-name
  assertion, and a plain `false`/`30` isn't one (found by running the
  pre-existing suite, not assumed). `.pot` regenerated with `wp i18n
  make-pot` (available in this environment; no fallback needed).
- **P3-03 (final gate)**: no code fix was needed; every acceptance check
  passed as-is.

## ⚠️ ASSUMPTION config keys

- `Keys::DEFAULT_PROXY_TIMEOUT` = `30` (filter `wppa_proxy_timeout`) —
  not tuned; SPEC §5.3 states this default explicitly and names no
  tuning task.
- `Keys::DEFAULT_PROXY_MAX_BYTES` = `52428800` (filter
  `wppa_proxy_max_bytes`) — not tuned, same reasoning.

Both were introduced at P1-01 and are exercised only by the Phase 1
manual check (not automated tuning).

## What a human must check by hand, per phase

- **Phase 0** (from P0-16's log): SPEC §8 Phase 0 item 5's clean-clone /
  `wp-env` visual check. **NOT VERIFIED (human)**.
- **Phase 1**: the §5.3 proxy tunables' manual exercise noted above (no
  separate gate-logged item beyond the ⚠️ ASSUMPTION note).
- **Phase 2** (from P2-10's log), SPEC §8 Phase 2's three checks, **NOT
  VERIFIED (human)**:
  1. In the block editor, Publications → Add New shows the three meta
     boxes, and Upload opens the media modal and fills the URL.
  2. The Legacy Widget block previews all three widgets.
  3. The list and dropdown shortcodes and the single and archive pages
     render with no notices in `debug.log`, first with the DAM active
     and again with it deactivated. (This flight's own automated check —
     deactivate/reactivate the plugin itself, both with and without the
     DAM — produced zero `debug.log` lines either way, which is a good
     sign but is not the same as browsing every surface by hand.)
- **Phase 3** (from P3-03's log), SPEC §8 Phase 3's checks, **NOT
  VERIFIED (human)**:
  1. Install `dist/wp-publication-archive.zip` on a fresh WordPress 7.1
     site restored from a 3.0.1 database: no activation errors, every
     G5 URL resolves, and the German translation loads with
     `WPLANG=de_DE`.
  2. Repeat with the DAM active.
  3. Mark the PR ready (§7.2) if the summarizer could not (relevant only
     if the archived-repo push problem above is resolved and a PR
     actually gets opened).

## Notes for the reviewer

- `docs/PROGRESS.md`'s Log section has one entry per task (P0-01 through
  P3-03) with full detail; this file only pulls the Interpretation
  choices and manual-check items forward. Read a task's own log entry
  for its test list and exact verification output.
- `wp-env` in this environment collides with an unrelated project's
  containers on ports 8888/8889; every wp-env command in this flight's
  history used `WP_ENV_PORT=18888 WP_ENV_TESTS_PORT=18889` to work
  around it. That is a local-machine artifact, not anything committed to
  the repo (`.wp-env.json` is unchanged).
- `dist/` is gitignored and untracked, as it was before this flight
  started; `composer build` regenerates it and it is not part of any
  commit.
- Every `phpcs:ignore`/`disable` in the tree carries a `-- reason:`, and
  none silences a `WordPress.Security` sniff (enforced by
  `no-security-ignores` since P1-09). `grep -rn "phpcs:(ignore|disable).*WordPress.Security" includes templates wp-publication-archive.php uninstall.php`
  prints nothing.
- `grep -rn "TB_iframe|send_to_editor|thickbox" includes assets/js`
  prints nothing (D13 closed at P2-07, locked by the `no-thickbox`
  constraint at P2-10).
