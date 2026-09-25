# Build summary: WP Publication Archive 3.1.0

**Merge line:** `build/2026-09-24`, `e52acc84a75e` → `981ad65`, 134 commits (plus this summary commit). 5 review rounds, 4 of them fix rounds. 52 of 52 tasks done, 0 blocked, 0 skipped. **Final verdict: APPROVED** (round 5).

> **Read first:** the GitHub remote (`ericmann/WP-Publication-Archive`) was archived and read-only for the initial build (P0-01 to P3-03), so those pushes failed. It was un-archived during review round 1. The controller then pushed `master` and the branch and opened draft PR #41 (https://github.com/ericmann/WP-Publication-Archive/pull/41), which is now marked ready for review. Every push from review round 1 onward succeeded. CI (`.github/workflows/verify.yml`) runs on the PR.

## What was built

**Phase 0: Foundation (P0-01 to P0-16).** The plugin's toolchain was scaffolded from the eamann plugin template: Composer, PHPCS, PHPStan, PHPUnit, wp-env with the VIP DAM plugin, and CI. The 3.0.1 code was first parked under `lib/` so that the repo-wide constraints could hold from the first task. Characterisation tests then pinned 3.0.1's URLs, link generators, shortcode output and widget output. The code was restructured in eight steps into namespaced `WPPA\` classes under `includes/`: Post_Type, Rewrites, Upgrade, Delivery, Streamer, Icons, Meta_Boxes, Publication_Item, Shortcode, Templates, Categories, the widgets, and a `Legacy\` alias layer so that 3.0.1 class names keep working. `lib/` was then deleted. Other additions: `Keys`, `Flags`, `Hooks`, `Clock`, `docs/HOOKS.md`, the `wp publication-archive doctor` CLI, and a REST lineage route. The Gate 0 task added two more constraints.

**Phase 1: Security and DAM bridge (P1-01 to P1-09).** `Url_Policy::validate()` was added and wired into meta-box saves and delivery, closing the SSRF/LFI hole (D1) and the unsanitised or unescaped meta issues (D2, D3, D10). `Dam_Bridge` withholds embargoed DAM attachments and substitutes the DAM's placeholder for their thumbnails (D17, D18). It also feeds the DAM's usage index (D19) and stays inert when the DAM is absent. Delivery now redirects by default. Proxying is opt-in behind `wppa_mask_url` and streams through a single `wp_tempnam()` + `readfile()` in `Streamer` (P11, D6). The front-end templates and Publication_Item escape every output. Gate 1 locked in "no security ignores" and "no ob_clean".

**Phase 2: Correctness and compatibility (P2-01 to P2-10).** The post type, taxonomy and registered meta are now exposed to REST and the block editor (D12), with edit-context-only meta. Dedicated publication capabilities are granted on activation and init. Upgrades run once on `init` and no longer write options on every request (D9). Thickbox and inline scripts are replaced by `admin-media.js` using the WP media modal (D13). Dates use the site timezone through `Clock` (D7). D8's permalink hijack and its dead delegates are removed. The widgets work in the Legacy Widget block and keep their 3.0.1 `id_base` (D14). D5's slug collision could not be reproduced, so it is closed with regression tests. D11's `allow_url_fopen` notice is gone. Gate 2 locked in "no thickbox" and "no allow_url_fopen".

**Phase 3: Release (P3-01 to P3-03).** This phase added `uninstall.php` (it deletes the three plugin options), `.distignore` and a `composer build` zip script. It also produced the 3.1.0 readme and CHANGELOG, the version bump, a final pass over HOOKS.md, and a regenerated `.pot`.

**Review fixes (R1-01 to R4-01).**
- **Round 1 fixes:**
  - Disabled Composer's 300 s timeout, which was orphaning phpunit.
  - Moved capability and role names in Cli into `Keys`, and added a new constraint for them.
  - D18 thumbnail echo now keeps the `data:` placeholder, and the thumbnail read path normalises pipe-form values.
  - Percent-encoded URLs survive a meta-box save.
  - Delivery ignores non-publications.
  - `admin-media.js` uses jQuery only for delegation.
  - Corrected the D5 release-note wording.
- **Round 2 fixes:**
  - `&` survives a meta-box save (a regression from R1-04).
  - Delivery drops its `Icons` dependency.
  - Proxied responses send `nosniff`, and active content is forced to download.
  - Contributors get publication caps.
  - Every plain-permalink link generator is now pinned.
- **Round 3 fix:** pinned the named filename on active-content views.
- **Round 4 fix:** tested D6's buffer discard.

## Decisions that shaped it

From PLAN.md Decisions:
- **Q1 (P0-01):** kept SPEC's test stack (`phpunit ^9.6`, `yoast/phpunit-polyfills ^3.0`, `wp-phpunit ^6.7`) on a PHP 7.4 platform.
- **Q2 (P1-07):** proxy mode stays behind `wppa_mask_url`, which now defaults to `false`. The proxied body goes to a `wp_tempnam()` file, and a single `readfile()` reads it.
- **Q3 (P1-02):** `Dam_Bridge::active()` checks `class_exists`, so the bridge is inert wherever the DAM cannot load (DAM floor WP 6.9/PHP 8.2 vs. the plugin's WP 6.7/PHP 7.4).
- **P0-01:** the 3.0.1 code was moved to `lib/`, outside every constraint, lint and analysis path, until P0-15 deleted it. This is how "outside the lint paths for this task only" was read.
- **P0-08, P0-12, P0-09:** live constraints forced some defects to close early. D4 closed in P0-08 and D15 in P0-12, not in Phase 2. D14's escaping landed in Phase 0. D1's `readfile` sat in an interim `Streamer::passthrough()` until P1-07.
- **P0-08, P2-01:** D8 is not a no-op. After the first `get_link()`, it hijacks publication permalinks in the same request. The hijack was ported faithfully, then removed. That changes the list shortcode's title links for item 2 onward.
- **P1-02, P2-02:** contracts tasks never wire a live hook to a throwing stub. As a result, D19 closed in P1-02 and Capabilities was implemented in P2-02.
- **Module-map amendments:**
  - Publication_Item and the widgets reach their services through `Plugin::instance()`.
  - Categories uses `Flags`.
  - Url_Policy uses `Keys::ERR_INVALID_URL`.
- **P0-11, P0-15:** aliased 3.0.1 classes stay non-final, keep their 3.0.1 signatures, and get no native types, so a theme subclass must not fatal.
- **P0-13, P0-14:** widgets are registered under their 3.0.1 class names, so `the_widget()` in themes keeps working.
- **P0-04, P1-02, P2-02, P2-03:** the doctor rows are exactly SPEC §6.8's list. Each row landed with its feature.
- **P1-02:** inert-bridge tests carry `@group nodam`. `bin/test.sh` excludes one group or the other depending on whether the DAM is loaded, and the gates run both modes.
- **P0-01, P3-02:** `Keys::VERSION` stayed `3.1.0-dev` until P3-02.
- **P0-02:** `Flags` uses per-site `get_option()`, not `get_site_option()`.
- **P1-06:** P11 vs. §6.2: `Streamer::send()` refuses any path whose `realpath()` is outside its temp directory.
- **P1-07:** a scoped `allowed_redirect_hosts` callback allows exactly the validated host while a redirect runs, because `wp_safe_redirect()` would otherwise send external hosts to wp-admin.
- **P0-12:** the shortcode reads the page number with `get_query_var()`, not `$_GET`, to avoid a nonce-sniff ignore.
- **P2-04:** registered meta uses the REST `edit` context only, which hides it from anonymous readers.
- **P0-04:** fixtures write 3.0.1-shaped and hostile values with a raw `$wpdb` insert (`V3_Site::raw_meta()`) so that the sanitize callbacks do not rewrite them.
- **P3-01:** `uninstall.php` deletes all three §5.2 options. §4.1 says "two".
- **P0-06, R2-05:** plain-permalink links keep their broken 3.0.1 form (`?view=yes` and `?download=yes`), which the endpoints ignore. They are preserved and pinned.
- **P0-01, P1-09, P2-10:** each gate added the constraints that could only hold once its phase had landed.

From HANDOFF.md Interpretation choices:
- **P0-01:** dropped the template's multisite fixture. `Loader::activate()` calls only `init()`.
- **P0-02:** the HOOKS.md `Since` column says "≤ 3.0.1" throughout. The proxy tunables were deferred to P1-01.
- **P0-03, P0-04:** Unit and Integration sub-namespaces are used only where flat test class names would collide.
- **P0-05:** the DAM contract stubs were copied verbatim, and `Lifecycle` was stubbed as an empty class.
- **P0-06, P0-07, P0-13, P0-14:** several real 3.0.1 quirks were pinned rather than fixed:
  - the archive widget's `orderby`/`order_by` key mismatch;
  - the Related widget's "most recent" fallback;
  - the `'name'` key collision in `dropdown_categories()`;
  - `unset()` clearing only a local reference.
- **P0-08:** `Upgrade::run()` passes a partial array to `wp_update_post()`.
- **P0-09:** `wp_safe_remote_head()` replaces the `sslverify=false` HEAD request.
- **P0-10:** the nonce action name moved to `Keys`.
- **P0-12:** the `WordPress.Files.FileName` sniff was left disabled.
- **P0-15:** `the_content()` reads `get_post()`, not `global $post`.
- **P1-01:** added `Url_Policy::is_safe_external()` and `Delivery::with_redirect_host()` beyond the named method list.
- **P1-02:** the DAM usage filter registers unconditionally, and the `dam` doctor row passes when the DAM is inactive.
- **P1-08:** the DAM placeholder needs `data:` added to the allowed protocols explicitly.
- **P1-09:** closing the last security ignore changed the pinned `WIDGET_CAT_COUNT_DROPDOWN` string (D3).
- **P2-01:** D11's notice was deleted outright. D9's timing moved to `init` here, and its body was fixed in P2-06.
- **P2-02:** capability tests snapshot and restore `wp_roles()` themselves, because of how WP_Roles persists roles to the database.
- **P2-04:** `sanitize_callback` runs only on write, so pipe-form values already stored in the DB stay unchanged until something rewrites them.
- **P2-05:** D5 was not reproducible. Its tests passed unmodified and were kept as regression tests.
- **P2-06:**
  - The "already current" upgrade branch does nothing.
  - The rewrite flush is soft.
  - The schema option is not autoloaded.
- **P2-07:** alternate rows print through `wp_kses()` with an explicit allowlist. A hidden `<template>` row is the source for Add Row.
- **P2-09:** widget tests call `_register()` directly, and the `form_data` passed to the REST `encode` endpoint is a urlencoded string.
- **P3-01:** the build script's dev-leftover check requires a non-empty path segment after `/vendor/`.
- **P3-02:** the changelog groups D2 with D3 and D17 with D18. The HOOKS.md Tunables defaults are not backticked, so the doc-test parser skips them.
- **R1-04 (later superseded by R2-01):** the sanitiser order is: sanitiser immediately around `wp_unslash()`, then `normalise()`, then `esc_url_raw()`, then `validated_url()`. Two reasons:
  - Calling `esc_url_raw()` first corrupts pipe-form values.
  - The sniff recognises only the sanitiser that directly wraps the input.
- **R2-03:** `Delivery` computes a real filename for active-content views. `Streamer` also keeps a bare `attachment` fallback.

## Assumptions still in play

| Key | Filter | Final default | Status |
|---|---|---|---|
| `Keys::DEFAULT_PROXY_TIMEOUT` | `wppa_proxy_timeout` | `30` (seconds) | Not tuned; still a guess (SPEC §5.3 names no tuning task) |
| `Keys::DEFAULT_PROXY_MAX_BYTES` | `wppa_proxy_max_bytes` | `52428800` (50 MB) | Not tuned; still a guess |

## Spec issues (edits to make to SPEC.md)

Still open:
1. **`docs/foundry.json` `extraVerify` timeout (§7.1):** `composer test` with the DAM takes about 1180 s, and the limit is 1200 s (`timeoutMs: 1200000`). Raise the limit (for example to 1800000) or speed up the suite. Agents are not allowed to change this.
2. **D5:** as written, the defect does not reproduce. The only real collisions are the CPT's own sub-routes (`/publication/view/feed/`, `/embed/` and `/trackback/`) for a publication slugged `view` or `download`. Correct the D5 text.
3. **§6.2 step 4:** state that `Delivery` passes the basename as the filename for active-content views.
4. **§6.2 step 5:** mention the injectable output-buffer floor, which defaults to 0 in production.
5. **Constraints have no "active from" point.** Reconcile Phase 0 item 1 ("outside the lint paths for this task only") with repo-wide constraints. The build parked the code in `lib/`.
6. **Defect phase placement:** D4 and D15 closed in Phase 0, and D14's escaping landed in Phase 0, because live constraints forbid the 3.0.1 code. The SPEC table puts D4 and D15 in Phase 2.
7. **P11 vs. §6.2:** P11 says `wp_tempnam()` is called "in the same method" as `readfile()`, but §6.2 splits the two calls between Delivery and Streamer. Reword P11 to "a path inside the temp dir".
8. **§6.2:** mention the `allowed_redirect_hosts` scoping that external redirects need.
9. **§4.2 module map:**
   - Add `Plugin::instance()` access for Publication_Item and the widgets.
   - Add `Flags` for Categories.
   - Keep `Icons` out of Delivery, as it is now.
10. **Contracts ordering:** the D19 filter and Capabilities need second contracts tasks. The `dam` doctor row cannot exist in Phase 0.
11. **Test groups:** §7 says "runs every group". In practice the `nodam` and `dam` groups are mutually exclusive per run.
12. **§4.1 "two options" vs. §5.2's three.**
13. **The shortcode's `$_GET` page read:** document `get_query_var()`.
14. **REST meta visibility:** document the edit-only context.
15. **Fixtures need a raw-meta writer** once sanitize callbacks are registered.
16. **Icon URLs move to `assets/icons/` (§6.3)** while the characterisation tests must pass unchanged. Note that the expected strings resolve through `get_image()`.
17. **The `cabinet.png` deletion (§4.1) vs. the dashicon (§6.1, Phase 2):** the dashicon landed in P0-08.
18. **Headers and output buffers cannot be observed under the CLI SAPI.** Mention the injectable header, exit and buffer-floor callables.
19. **`widget_title` arity:** 3.0.1 passes 1 argument in one widget and 3 in the others. Mention this.
20. **The `the_widget()` factory key** must be the 3.0.1 class name.

Resolved by SPEC `464750b` during review (no edit needed; listed for the record): Contributor caps (§6.1), proxy nosniff and active-content handling (§6.2), plain-permalink behaviour (G5, PLAN issue 15), and the D8 list-title link change (PLAN issue 3).

## Manual checks owed (all NOT VERIFIED (human))

- **Phase 0:** on a clean clone, run `bash bin/fetch-dam.sh` and then `npx wp-env start`. With both plugins active, the front-end list and single pages and wp-admin → Publications should look the same as on 3.0.1.
- **Phase 1:**
  - Exercise the proxy tunables (timeout 30 s, max 50 MB). With `wppa_mask_url` returning true, a 60 MB file should redirect rather than proxy.
  - As an Author, save `/etc/passwd` as the document URL. The field should come back empty, and the view URL should return 404.
  - Embargo the linked attachment in the DAM. The view URL should return 404 when logged out and redirect for an Editor.
- **Phase 2:**
  1. In the block editor, Publications → Add New shows the three meta boxes, and Upload opens the media modal and fills the URL.
  2. The Legacy Widget block previews all three widgets.
  3. The list and dropdown shortcodes and the single and archive pages render with no `debug.log` notices, with the DAM both active and deactivated.
- **R1-06 (admin JS):** on a publication's edit screen, Upload fills the doc, thumbnail and alternate-row inputs, and Add Row and Delete still work after the jQuery-to-DOM rewrite.
- **Phase 3:**
  1. Install `dist/wp-publication-archive.zip` on a fresh WP 7.1 site restored from a 3.0.1 database. Expect no activation errors, every G5 URL resolving, and German loading with `WPLANG=de_DE`.
  2. Repeat with the DAM active.
  3. ~~Un-archive the repo, push, and mark the PR ready (§7.2).~~ Done by the flight controller: PR #41 is ready for review.

## Review history

| Round | Verdict | Findings | Fix tasks | Recurrence |
|---|---|---|---|---|
| 1 | CHANGES REQUESTED | 7 | 7 (R1-01..R1-07) | n/a (first round) |
| 2 | CHANGES REQUESTED | 5 | 5 (R2-01..R2-05) | Yes. P2-02 recurred from round 1 (Contributors' caps, after the SPEC revision). Finding 2 was a regression introduced by fix R1-04. |
| 3 | CHANGES REQUESTED | 1 | 1 (R3-01) | No task ID recurred. The finding was a test gap in fix R2-03, which is the same Delivery/Streamer area as round 2. |
| 4 | CHANGES REQUESTED (flagged non-converging) | 1 | 1 (R4-01) | No consecutive recurrence. P1-06 (Streamer) was named again after round 2. |
| 5 | APPROVED | 0 | 0 | n/a |

**Round 5 approved with notes.** These were flagged but not queued as work:
- R4-01's test carries a redundant `phpcs:ignore` on a test-only `echo`. It is dead but harmless.
- `Meta_Boxes::save()` sanitises the description, which duplicates `Post_Type`'s registered `sanitize_callback`. This is defence in depth, and mutation testing cannot tell the two layers apart.
- Three round-3 notes still stand:
  - the sibling-directory containment test catches its mutation only indirectly;
  - a possibly repeated `content-type` header is cast to `(string)`;
  - a path with no basename gets `filename=""`.

## Pipeline friction

8 entries were logged, in order:
1. **plan / constraints-no-activation:** `foundry_verify` scans the whole repo for every constraint, and a constraint cannot declare "active from task X". On a brownfield repo, the planner had to move the legacy code outside the scanned paths and defer regression-lock constraints to the gate tasks. A per-constraint `activeFrom` or a baseline of existing hits would remove the workaround.
2. **implement / environment:** P0-16's push failed with "repository was archived so it is read-only", the same failure as `basePush` at flight start. There was no CI to check. This is an operator-level repo state issue.
3. **implement / tooling:** a background `composer test` running at the same time as `foundry_verify` shared the tests DB. The result was spurious "table doesn't exist" errors and deadlocks (P1-08), and about 10 minutes spent diagnosing.
4. **implement / other:** Composer's 300 s timeout killed the host wrapper but left phpunit orphaned in the tests-cli container. The next run deadlocked the DB, and the orphan had to be killed by hand.
5. **implement / flaky-environment:** in P2-04 through P2-07, `create_upload_object()` intermittently returned `WP_Error` in `class-v3-site.php:69`, with occasional MySQL deadlocks. It never reproduced in isolation, and each retry cost about 7 minutes.
6. **review / misleading-verdict:** `foundry_mutate` reports `killed:true` when only an inner process timeout fired. The timed-out run also orphaned phpunit. The tool should return "inconclusive" when stderr shows a timeout.
7. **review / mutation-side-effects:** after a timed-out mutation run, the orphaned phpunit wrote mutated role caps into the shared tests DB. They survived later installs, and they had to be removed by hand with wp-cli. `foundry_mutate` restores the file but cannot undo environment state.
8. **review / mutate-timeout-counted-as-kill:** `foundry_mutate` reported `killed:true` for a Meta_Boxes mutation that only hit the 1200 s `extraVerify` limit (341/346 tests, no failures). The suite now takes about 1180 s, so every integration mutation risks this false verdict.
