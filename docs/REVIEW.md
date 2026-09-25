# Review — build/2026-09-24
Round: 4

**Verdict: CHANGES REQUESTED**

Scope: the whole branch, `e52acc84a75e..1234cdc`. It has 51 tasks, all `[x]`. None are blocked or skipped. The only new work since round 3 is R3-01 (`a6c91b5`), which is test and doc only. Earlier rounds reviewed every Phase 0–3, R1 and R2 task commit by commit. This round I read R3-01 against its PLAN task and SPEC §6.2 step 4. I then re-ran the whole-branch constraint and test checks and sampled mutations again, this time including mechanics earlier rounds did not sample.

Evidence I gathered myself:
- **`foundry_verify`**, run with files `[tests/integration/test-delivery.php, CLAUDE.md]` so that the wp-env suite ran:
  - All 29 constraints pass their fixture self-tests with 0 hits.
  - `lint`, `analyse` and `test:map` (29 files) pass. `test:unit` passes: 74 tests, 432 assertions.
  - `composer test` with the DAM loaded passes: 345 tests, 1746 assertions, 1 expected skip, 18m27s.
- **`foundry_mutate` samples:**
  - **Killed:**
    - `Delivery::proxy()`: replacing `( $is_download || Streamer::is_active_content( $content_type ) )` with `$is_download` is now caught. Both `test_proxy_view_of_html_is_attachment_with_nosniff` and `test_proxy_view_of_svg_is_attachment_with_nosniff` fail. This closes round 3's finding 1.
    - `Url_Policy::validate()`: replacing the `$is_safe_external` check with `true` is caught by the loopback and metadata-service rows of `test_d1_validate_table`.
  - **Survived:** `Streamer::send()`: deleting the whole `while ( ob_get_level() > $this->ob_floor ) { ob_end_clean(); }` loop. It survived all of `lint`, `analyse`, `test:map`, `test:unit`, and `composer test` with the DAM loaded. See finding 1.
- **R3-01 diff:** the diff matches the task exactly.
  - Both tests now `assertContains` the exact named header.
  - The nosniff and PDF tests are unchanged.
  - The `CLAUDE.md` Delivery row now matches SPEC §4.2.
  - No production code changed.
- **Branch hygiene:** since P0-01's scaffold, which earlier rounds reviewed, no commit touches `.gitignore`, `.gitattributes`, editor config or CI.

## Findings (most severe first)

### 1. [Category 3 — Tests] No test checks that `Streamer::send()` discards buffered output before streaming
- **Where:** the loop is in `includes/class-streamer.php:85-87`. The tests are in `tests/unit/test-streamer.php`, `tests/integration/test-streamer.php` and `tests/integration/test-delivery.php:101`.
- **What is wrong:** SPEC §6.2 step 5 (D6) says `send()` calls `ob_end_clean()` while `ob_get_level()` is above zero, and 3.0.1 did the same with `ob_clean()`. The purpose is to throw away whatever the request has already buffered before the file bytes go out. The one D6 test, `test_d6_no_notice_with_zero_output_buffers`, only covers the guard: with zero buffers there is no notice. The loop body never runs in any test:
  - Every in-process test builds `Streamer` with `ob_floor = ob_get_level()` at the moment `send()` runs.
  - The D6 child process has zero buffers.

  Deleting the whole loop therefore **survived** the full suite, wp-env with the DAM included.
- **What breaks:** a later change can drop the buffer discard and no test fails. After that:
  - any output a theme or plugin had buffered is sent ahead of the file bytes, which corrupts PDFs and other binary downloads;
  - `readfile()` writes into the open buffer instead of straight to the client, so a file up to `wppa_proxy_max_bytes` (50 MB) sits in PHP memory.
- **Minimal fix:** test only. Add `test_d6_discards_buffered_output_above_the_floor` to `tests/unit/test-streamer.php`:
  1. Record `$floor = ob_get_level()`.
  2. `ob_start()` an outer capture buffer.
  3. Build the `Streamer` with `ob_floor = $floor + 1`.
  4. `ob_start()` an inner buffer and echo a stray marker such as `stray-output`.
  5. Call `send()` on a temp file containing `file-bytes`. The exit callable throws.
  6. Take `ob_get_clean()` of the outer buffer. Assert it is exactly `file-bytes` and that `ob_get_level()` is back to `$floor`.

  In a `finally`, end any buffers still above `$floor`, so that the mutated code does not leak buffers into PHPUnit. With the loop deleted, the captured output is `stray-outputfile-bytes` and the test fails.
- **Task:** P1-06 (`Streamer::send()`, D6).

## Spec issues
- **Carried from rounds 1–3, still open:** D5 as written does not reproduce. The only real collisions are the CPT's own sub-routes (`/publication/view/feed/`, `/embed/` and `/trackback/`) for a publication slugged `view` or `download`. The branch closes D5 with regression tests and release notes that say so (R1-07). SPEC's D5 text could be corrected to match.
- **§6.2 step 4, view-mode filename:** the code handles this, and R3-01 now tests it. `Delivery` passes the basename for active-content views. SPEC could still say this explicitly.
- **PLAN P0-09's module map** still gives `Delivery` an `Icons` parameter (`docs/PLAN.md:578`). That is a historical task record, superseded by R2-02, so it is noted here and not edited.
- **§6.2 step 5** says "only while `ob_get_level() > 0`". The code uses an injectable floor, which defaults to 0 in production (`Plugin` builds `new Streamer( get_temp_dir() )`), so tests can keep their own capture buffer. That matches SPEC's intent. SPEC could mention the floor.

## Manual checks still owed (from HANDOFF.md)
- **Phase 0:** SPEC §8 Phase 0 item 5. On a clean clone, after `bash bin/fetch-dam.sh` and `npx wp-env start`, the front-end list and single pages and wp-admin → Publications look the same as on 3.0.1, with both plugins active.
- **Phase 1:**
  - Exercise the §5.3 proxy tunables (`Keys::DEFAULT_PROXY_TIMEOUT` = 30 and `Keys::DEFAULT_PROXY_MAX_BYTES` = 52428800, both ⚠️ ASSUMPTION).
  - As an Author, save `/etc/passwd` as the document URL. The field should come back empty and the view URL should return 404.
  - Embargo the linked attachment in the DAM. The view URL should 404 when logged out and redirect for an Editor.
  - With `wppa_mask_url` returning true, a 60 MB file should redirect rather than proxy.
- **Phase 2:**
  1. In the block editor, Publications → Add New shows the three meta boxes, and Upload opens the media modal and fills the URL.
  2. The Legacy Widget block previews all three widgets.
  3. The list and dropdown shortcodes and the single and archive pages render with no `debug.log` notices, with the DAM active and again with it deactivated.
- **R1-06:** in wp-admin, Upload fills the doc, thumbnail and alternate-row inputs, and Add Row and Delete still work after the jQuery-to-DOM rewrite.
- **Phase 3:**
  1. Install `dist/wp-publication-archive.zip` on a fresh WordPress 7.1 site restored from a 3.0.1 database. Expect no activation errors, every G5 URL resolving, and the German translation loading with `WPLANG=de_DE`.
  2. Repeat with the DAM active.
  3. Mark the PR ready (§7.2) if the summarizer could not.
- **Push:** HANDOFF reports that the GitHub remote is archived and read-only, and every push in this flight failed. Someone must un-archive the repository or change the remote before a PR can open.

## Notes
- The R3-01 HANDOFF says `foundry_mutate` was not available to the implementer, so the kill was checked by hand. I re-ran it with the tool this round, and the mutation was killed.
- These round-3 notes still apply and are not findings:
  - the sibling-directory containment test catches its mutation only indirectly;
  - the `(string)` cast of a possibly repeated `content-type` header;
  - `filename=""` for a path with no basename.
