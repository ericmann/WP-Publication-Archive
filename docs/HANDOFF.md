# HANDOFF

Branch: `build/2026-09-24`
Base: `e52acc84a75e`
Head: `e21ce4c`
Tasks: 45 total, 45 done, 0 blocked, 0 skipped, 0 open.

## Round 1 (review-fix)

Branch: `build/2026-09-24`
Head before this round: `7f444e5` (`chore: start review-fix round 1`)
Head after this round: `e21ce4c`
Tasks this round: 7 total (R1-01..R1-07), 7 done, 0 blocked, 0 skipped, 0 open.

### Blocked / skipped tasks

None. All seven R1 fix tasks reached `[x]` on the first attempt.

### What each R1 task fixed (REVIEW findings 1-7)

- **R1-01** (`f74e535`): added `"process-timeout": 0` to `composer.json`'s
  `config` block so `composer test`/`verify` can run past Composer's
  default 300s process timeout without a caller-side
  `COMPOSER_PROCESS_TIMEOUT` override. New
  `tests/unit/test-composer-config.php`. Verified `composer test` running
  654s-800s+ across this round with no timeout and no leftover `phpunit`
  process in the `tests-cli` container afterward.
- **R1-02** (`8c9e258`): removed the hard-coded `'edit_publications'`/
  `'administrator'` literals from `Cli::caps_granted_row()`; added
  `Keys::ROLE_ADMINISTRATOR`; added the `capability-names-in-keys`
  constraint to `docs/foundry.json` so an unprefixed `*_publications`
  capability literal cannot reappear outside `Keys` un-caught.
- **R1-03** (`e695fc9`): `Publication_Item::the_thumbnail()` now echoes
  through `wp_kses( $html, 'post', wp_allowed_protocols() + 'data' )`
  instead of `wp_kses_post()`, so the DAM's `data:` placeholder URI for a
  withheld image survives being echoed (it was being stripped before);
  `get_the_thumbnail()` now normalises the raw stored value through
  `Url_Policy::normalise()` before `Dam_Bridge::display_url()`, so legacy
  `http|`/`https|` pipe-form thumbnails resolve correctly again.
- **R1-04** (`c4d1c39`): `Meta_Boxes::save()` was silently deleting `%xx`
  octets out of every stored doc/thumbnail/alternate URL because
  `sanitize_text_field()` decodes/strips percent-escapes. It now
  sanitises with `wp_kses_post()` (the immediate wrap
  `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized` needs),
  then `Url_Policy::normalise()`, then `esc_url_raw()`, then
  `validated_url()` — see this task's Interpretation note below for why
  the order matters.
- **R1-05** (`f42cd92`): `Delivery::deliver()` had lost 3.0.1's no-op for
  view/download query vars on a non-publication post, and `resolve_uri()`
  was instantiating `Publication_Item` (calling `setup_postdata()` and
  building an excerpt on every delivery, and reaching outside
  `Delivery`'s module-map imports). Fixed both: `deliver()` now checks
  `Keys::POST_TYPE === $post->post_type`, and `resolve_uri()` reads
  `Keys::META_DOC`/`Keys::META_ALTERNATES` with `get_post_meta()`
  directly.
- **R1-06** (`6b6d834`): `assets/js/admin-media.js` used `$( this )` and a
  jQuery-wrapped `$row` for the alternates row upload/delete handlers,
  contradicting the SPEC §6.7/P2-07 rule that the only jQuery use is
  `jQuery( document ).on()` delegation. Replaced with
  `this.closest( 'tr' )`, a plain `querySelector(...).value = url`, and
  `Element.remove()`.
- **R1-07** (`5ab486c`): reworded the D5 changelog bullet in `readme.txt`
  and `CHANGELOG.md`, which claimed a rewrite-rule fix that P2-05 (round
  0) found was not reproducible/needed — D5 was closed by adding
  regression tests, not by fixing a collision that didn't exist.

### Interpretation choices this round (by task ID)

- **R1-04**: the task's design-constraint example order was
  `esc_url_raw( Url_Policy::normalise( wp_unslash( … ) ) )`, i.e.
  `esc_url_raw()` as the outermost call. That order fails
  `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized`, which
  (confirmed empirically) only recognises a sanitiser as the function
  **immediately** wrapping the `$_POST`/`wp_unslash()` expression, not a
  sanitiser further out in the call chain. It also breaks the legacy
  pipe-form case: `esc_url_raw()` prepends `http://` to any string with
  no `:` in it, so calling it on the raw `"https|example.com/a.pdf"`
  *before* `normalise()` corrupts it into
  `"http://https|example.com/a.pdf"`. The implemented order is
  `wp_kses_post( wp_unslash( … ) )` (the immediate, percent-encoding-safe
  sanitiser the sniff wants) → `Url_Policy::normalise()` (pipe-form fix,
  now safe since `wp_kses_post()` leaves both `%xx` and the pipe
  character untouched) → `esc_url_raw()` (final hardening pass, now safe
  since `normalise()` has already given every valid input a real scheme)
  → `validated_url()`. All of D1/D2/D10, the existing pipe-form save
  test, and the new percent-encoding test pass with this order; verified
  by hand with `wp eval` against `esc_url_raw()`/`wp_kses_post()` on the
  exact fixture strings before writing the final version.
- All other R1 tasks matched their design constraints exactly with no
  open interpretation.

### ⚠️ ASSUMPTION config keys

No new `⚠️ ASSUMPTION` keys were introduced this round. `Keys::DEFAULT_PROXY_TIMEOUT`/`Keys::DEFAULT_PROXY_MAX_BYTES` from round 0
are unchanged.

### What a human must check by hand this round

- **R1-06**: NOT VERIFIED (human) — in wp-admin, on a publication's edit
  screen, confirm Upload still fills the doc/thumbnail/alternate-row
  inputs via the media modal, and that Add Row/Delete Row still work
  after the jQuery-to-DOM rewrite of the click handlers.
- Round 0's Phase 0/2/3 manual-check items (listed below, unchanged by
  this round) are still outstanding and were not re-verified here since
  none of R1-01..R1-07 touch those surfaces.

### Notes for the reviewer (this round)

- Every fix in this round is scoped to the file(s) the reviewer named in
  its finding; no other production code changed.
- `foundry_verify` (all constraints + `lint`/`analyse`/`test:map`/
  `test:unit`/`test`) is green on every task's commit and again on the
  full tree at the end of the round. `WPPA_DAM=0 composer test` was also
  run to green after R1-01, R1-03, R1-04's dependency chain, and again
  after R1-05 and R1-06 (the two touching `Delivery`/admin JS), each
  confirmed independently in this session's shell output.
- R1-02's new `capability-names-in-keys` constraint's own `shouldMatch`
  fixture is the literal offending line from before the fix, so its
  self-test alone would have caught REVIEW finding 1 had it existed
  before round 0 shipped.

## Round 2 (review-fix)

Branch: `build/2026-09-24`
Head before this round: `13bc029` (`review: round 2`)
Head after this round: `aafaaac`
Tasks this round: 5 total (R2-01..R2-05), 5 done, 0 blocked, 0 skipped, 0 open.

### Blocked / skipped tasks

None. All five R2 fix tasks reached `[x]` on the first attempt.

### What each R2 task fixed

- **R2-01** (`72b9dee`): `Meta_Boxes::save()` was storing every `&` in a
  doc/thumbnail/alternate URL as `&amp;`, because `wp_kses_post()` runs
  `wp_kses_normalize_entities()` — a regression R1-04 introduced while
  fixing the percent-encoding defect. Replaced `wp_kses_post()` with
  `wp_strip_all_tags()` as the immediate sanitiser (still satisfies
  `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized` per
  `no-security-ignores`, since `wp_strip_all_tags` is in
  `SanitizationHelperTrait`'s list) on the doc/thumbnail fields and as the
  `map_deep()` callback for alternate URLs; kept the rest of the chain
  (`Url_Policy::normalise()` → `esc_url_raw()` → `validated_url()`)
  unchanged. Also cast the posted description/url collections to `(array)`
  and skip non-string url elements, closing a hand-crafted-POST path to
  `count()`/`normalise( string )` with the wrong type.
- **R2-02** (`07fa6f7`): `Delivery` imported `Icons`, which SPEC §4.2's
  module map does not allow it (`Keys, Hooks, Flags, Url_Policy, Streamer,
  Dam_Bridge` only). Removed the `Icons` constructor parameter, `$icons`
  property and `icons()` accessor; `proxy()` now resolves content type
  with `wp_check_filetype( basename( wp_parse_url( $url, PHP_URL_PATH ) ) )['type']`
  directly, falling back to the response's content-type header then
  `Keys::CONTENT_TYPE_FALLBACK` — the same logic `Icons::mime_for()` used
  internally, just inlined. Updated `Plugin::__construct()`'s wiring and
  both test files that construct `Delivery`.
- **R2-03** (`70120fa`): implemented the product-owner decision in SPEC
  `464750b`: every proxied response now always sends
  `X-Content-Type-Options: nosniff`, and a **view** request for active
  content (`Keys::ACTIVE_CONTENT_TYPES`: html/xhtml/svg/xml×2/js×2) is
  forced to an attachment, so the plugin never serves HTML/SVG/XML/JS
  inline from the site's origin. New `Streamer::is_active_content()`
  (static, strips `;` params, trims, lowercases, membership check).
  `Streamer::send()`'s header order is now Content-Type, Content-Length,
  always `X-Content-Type-Options: nosniff`, then Content-Disposition
  (named when `$filename` given, a bare `attachment` backstop when
  `is_active_content()` and no filename). `Delivery::proxy()` passes a
  filename when `$is_download` **or** `Streamer::is_active_content(
  $content_type )`. Also made the temp-dir containment check exact:
  `$real_path` must start with `rtrim( $real_temp_dir, DIRECTORY_SEPARATOR
  ) . DIRECTORY_SEPARATOR`, closing a sibling-directory-prefix bypass
  (e.g. a temp dir `X` no longer accepts a file under `X-evil/`).
- **R2-04** (`d3a3125`): implemented the product-owner decision in SPEC
  `464750b`: added `'contributor'` to `Keys::CAP_ROLES` (after
  `'author'`), so `Capabilities::grant()`'s existing `has_cap()`-based
  mapping also grants contributors `edit_publications`/
  `delete_publications` (the same subset of `Keys::CAP_MAP` they already
  have for `post`), keeping 3.0.1's ability for a contributor to create
  and edit their own draft publications without granting `publish`/`edit
  others`.
- **R2-05** (`e2ac0c1`): test-only. SPEC G5 (`464750b`) calls for the
  characterisation tests to pin *every* plain-permalink link generator and
  query form, not just `get_open_link()`. Added
  `test_download_link_with_plain_permalinks_matches_301`,
  `test_alternate_open_link_with_plain_permalinks_matches_301`,
  `test_alternate_download_link_with_plain_permalinks_matches_301` (the
  alternate tests fail if `Rewrites::link()`'s `add_query_arg(
  Keys::QUERY_ALT_KEY, … )` is ever deleted), and
  `test_open_query_form_resolves_with_plain_permalinks`/
  `test_download_query_form_resolves_with_plain_permalinks`.

### Interpretation choices this round (by task ID)

- **R2-03**: `Delivery::proxy()` computes `$filename` itself (`$is_download
  || Streamer::is_active_content( $content_type )`) rather than relying
  solely on `Streamer::send()`'s bare-`attachment` backstop, so an active-
  content view still gets a real filename in its
  `Content-Disposition: attachment; filename="…"` header, which is
  friendlier than the backstop's bare `Content-Disposition: attachment`
  that `Streamer::send()` would otherwise add on its own for defence in
  depth (that backstop still exists and is exercised by
  `test_send_forces_attachment_for_active_content_with_no_filename`, in
  case a future caller of `Streamer::send()` forgets to do this).
- All other R2 tasks matched their design constraints exactly with no open
  interpretation.

### ⚠️ ASSUMPTION config keys

No new `⚠️ ASSUMPTION` keys were introduced this round.

### What a human must check by hand this round

Nothing new. R2-01/R2-02/R2-04 are server-side sanitisation/wiring/
capability fixes with full automated coverage; R2-03's `nosniff`/
attachment-forcing behaviour is covered by `tests/unit/test-streamer.php`
and `tests/integration/test-delivery.php`'s new proxy-view tests; R2-05 is
test-only.

### Notes for the reviewer (this round)

- Every fix in this round is scoped to the file(s) the reviewer named in
  its finding; no other production code changed.
- `foundry_verify` (all constraints + `lint`/`analyse`/`test:map`/
  `test:unit`/`test` with the DAM loaded) is green on every task's commit,
  and `lint`/`analyse`/`test:map`/`test:unit` are green again on the full
  tree at the end of the round (no files were passed to the closing
  `foundry_verify`, so it skipped the ~15-20 minute `composer test` — every
  task's own `foundry_verify` already ran it to green individually).
- R2-01 and R2-03 both touch `Meta_Boxes`/`Delivery`/`Streamer` sanitiser
  and header logic; read them together, since R2-03's `Delivery::proxy()`
  edit lands right next to R2-02's Icons-removal edit in the same method.

## Round 3 (review-fix)

Branch: `build/2026-09-24`
Head before this round: `3a91b82` (`chore: start review-fix round 3`)
Head after this round: `f608ba0`
Tasks this round: 1 total (R3-01), 1 done, 0 blocked, 0 skipped, 0 open.

### Blocked / skipped tasks

None. R3-01 reached `[x]` on the first attempt.

### What R3-01 fixed

- **R3-01** (`a6c91b5`): the R2-03 mechanic that names the file on an
  active-content proxy *view* (`Delivery::proxy()`'s
  `( $is_download || Streamer::is_active_content( $content_type ) )`
  ternary) was untested: the existing
  `test_proxy_view_of_html_is_attachment_with_nosniff`/
  `test_proxy_view_of_svg_is_attachment_with_nosniff` only asserted the
  substring `'Content-Disposition: attachment'`, which
  `Streamer::send()`'s bare-`attachment` backstop for active content
  satisfies on its own — so replacing that ternary with `$is_download`
  survived the suite. Tightened both tests to `assertContains()` the
  exact header `Content-Disposition: attachment; filename="a.html"` /
  `"a.svg"` on `$this->headers`, which only `Delivery::proxy()` passing a
  non-null `$filename` produces. Confirmed by hand that the ternary's
  removal (leaving bare `$is_download`) now makes both view tests (mask
  off, no download) fail, since `Streamer::send()` would then fall back
  to the bare `Content-Disposition: attachment` backstop instead of a
  named filename.
- Also fixed `CLAUDE.md`'s `class-delivery.php` module-map row: dropped
  the stale `Icons` entry from its "May import from" list — R2-02
  removed `Delivery`'s `Icons` dependency entirely, but left the doc row
  unupdated.
- No production code changed; test and doc only, per the task's design
  constraints.

### Interpretation choices this round (by task ID)

None. R3-01 matched its design constraints exactly (exact fixture
strings from `assert_proxy_view_headers`'s `home_url( '/wp-content/uploads/a.' . $extension )`
URLs, confirmed against `Streamer::send()`'s literal header-format
string before writing the assertions).

### ⚠️ ASSUMPTION config keys

No new `⚠️ ASSUMPTION` keys were introduced this round.

### What a human must check by hand this round

Nothing new. R3-01 is test/doc-only with no runtime behaviour change.

### Notes for the reviewer (this round)

- `foundry_verify` with files `[tests/integration/test-delivery.php,
  CLAUDE.md]` ran constraints, lint, analyse, test:map, test:unit, and
  the full `composer test` (DAM loaded, ~18 minutes) to green on R3-01's
  commit. A closing `foundry_verify` with no files (constraints + lint +
  analyse + test:map + test:unit) was also green on the final tree.
- The task's own Verification section also asks for a `foundry_mutate` run
  against `includes/class-delivery.php` (replacing the `||
  Streamer::is_active_content(...)` clause with `$is_download`) reporting
  `killed: true`; no `foundry_mutate` tool was available in this session,
  so that specific mutation-kill check was instead verified by hand: with
  the same substitution applied locally, both tightened tests failed with
  the expected bare-`attachment` header (no `filename=`) instead of the
  asserted named one, then the substitution was reverted before
  committing. The reviewer may want to re-run this via `foundry_mutate`
  directly if that tool is available in their environment.
- This repository (`git@github.com:ericmann/WP-Publication-Archive.git`)
  was reported archived/read-only by earlier rounds' pushes (see "Push /
  CI" note below); `foundry_run_finish` will attempt to push this round's
  commits and may hit the same wall.

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
