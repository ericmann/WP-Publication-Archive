# Review — build/2026-09-24
Round: 5

**Verdict: APPROVED**

Scope: the whole branch, `e52acc84a75e..ce74b83`. It has 52 tasks, all `[x]`. None are blocked or skipped. The only new work since round 4 is R4-01 (`22b114e`), which is test only. Rounds 1–4 reviewed every Phase 0–3, R1, R2 and R3 task commit by commit. This round I read R4-01 against its PLAN task and SPEC §6.2 step 5 (D6). I then re-ran the whole-branch constraint and test checks and sampled mutations again, including five mechanics that earlier rounds did not sample.

Evidence I gathered myself:
- **`foundry_verify`**, run with files `[tests/unit/test-streamer.php]` so that the wp-env suite ran:
  - All 29 constraints pass their fixture self-tests with 0 hits.
  - `lint`, `analyse` and `test:map` (29 files) pass. `test:unit` passes: 75 tests, 434 assertions.
  - `composer test` with the DAM loaded passes: 346 tests, 1748 assertions, 1 expected skip. It took 1180 s against a 1200 s limit (see Spec issues).
- **`foundry_mutate` samples:**
  - **Killed:**
    - `Streamer::send()`: deleting the whole `while ( ob_get_level() > $this->ob_floor ) { ob_end_clean(); }` loop. The run used `composer test:unit` only. `test_d6_discards_buffered_output_above_the_floor` fails with `'stray-outputfile-bytes'` against the expected `'file-bytes'`. This closes round 4's finding 1.
    - `Dam_Bridge::indexed_attachment_ids()` (D19): deleting `$candidates[] = $alternate['url'];`.
    - `Publication_Item` (D3, front end): removing the `esc_html()` around the filtered title.
    - `Upgrade::maybe_upgrade()` (D9): replacing the schema-version guard with `if ( true )`.
  - **Timeout caveat for those three:** all three wp-env runs hit the 1200 s timeout, and the tool counted each timeout as a kill. For each one I checked `stdoutTail` myself. Each shows a real `F` before the timeout, so the kill verdicts stand.
  - **Inconclusive, and not a coverage gap:** `Meta_Boxes::save()` (D2): changing the description `map_deep()` callback from `'sanitize_text_field'` to `'strval'`.
    - The run timed out at 341 of 346 tests with no failure. The tool still reported `killed: true`, which I logged as pipeline friction.
    - The mutation is expected to survive. `add_post_meta()` runs the `sanitize_callback` that `Post_Type` registers, and that callback calls `sanitize_text_field()` on the description. The save-side call is therefore a redundant layer.
    - The behaviour is still pinned. `test_d2_save_strips_markup_from_alternate_description` covers the save path end to end. `test_alternates_rest_write_sanitises_description_and_validates_url` covers the registered callback directly, through REST.
- **R4-01 diff:** the diff matches the task exactly.
  - It adds one test to `tests/unit/test-streamer.php`.
  - It follows the design constraints: it records the floor, opens an outer capture buffer, builds the `Streamer` with `ob_floor = $floor + 1`, echoes a stray marker into an inner buffer, and drains any remaining buffers in `finally`.
  - `test_d6_no_notice_with_zero_output_buffers` is unchanged.
  - No production code changed, and there are no plugin-prefixed literals.
- **Branch hygiene:** since round 4, the only changes outside `docs/` and `.foundry/` are the one test file. `.gitignore` and `.github/workflows/verify.yml` last changed in the P0-01 scaffold, which that task called for and round 1 reviewed.

## Findings (most severe first)

None. Categories 1–3 are clean across the branch, and no tasks are blocked or skipped.

## Spec issues
- **§7.1 / `docs/foundry.json` `extraVerify` timeout:** `composer test` with the DAM loaded now takes about 1180 s. Each `extraVerify` entry allows `timeoutMs: 1200000`, which is 1200 s.
  - In this round's four wp-env mutation runs, the suite hit the limit every time.
  - An unmutated `foundry_verify` could start timing out after a few more tests or on a slower host.
  - SPEC §7.1 forbids the planner and implementer from changing `extraVerify`, so a human should raise the limit (for example to 1800000) or speed up the suite.
- **Carried from rounds 1–4, still open:**
  - **D5:** as written, the defect does not reproduce. The only real collisions are the CPT's own sub-routes (`/publication/view/feed/`, `/embed/` and `/trackback/`) for a publication slugged `view` or `download`. The branch closes D5 with regression tests and release notes that say so (R1-07). SPEC's D5 text could be corrected to match.
  - **§6.2 step 4:** SPEC could say explicitly that `Delivery` passes the basename as the filename for active-content views. The code does this, and R3-01 tests it.
  - **PLAN P0-09's module map:** `docs/PLAN.md:578` still gives `Delivery` an `Icons` parameter. That line is a historical task record, superseded by R2-02.
  - **§6.2 step 5:** SPEC says "only while `ob_get_level() > 0`". The code uses an injectable floor that defaults to 0 in production. That matches SPEC's intent, and R4-01 now tests the discard. SPEC could mention the floor.

## Manual checks still owed (from HANDOFF.md)
- **Phase 0:** SPEC §8 Phase 0 item 5. On a clean clone, after `bash bin/fetch-dam.sh` and `npx wp-env start`, the front-end list and single pages and wp-admin → Publications look the same as on 3.0.1, with both plugins active.
- **Phase 1:**
  - Exercise the §5.3 proxy tunables: `Keys::DEFAULT_PROXY_TIMEOUT` = 30 and `Keys::DEFAULT_PROXY_MAX_BYTES` = 52428800, both ⚠️ ASSUMPTION.
  - As an Author, save `/etc/passwd` as the document URL. The field should come back empty, and the view URL should return 404.
  - Embargo the linked attachment in the DAM. The view URL should return 404 when logged out and redirect for an Editor.
  - With `wppa_mask_url` returning true, a 60 MB file should redirect rather than proxy.
- **Phase 2:**
  1. In the block editor, Publications → Add New shows the three meta boxes, and Upload opens the media modal and fills the URL.
  2. The Legacy Widget block previews all three widgets.
  3. The list and dropdown shortcodes and the single and archive pages render with no `debug.log` notices, both with the DAM active and with it deactivated.
- **R1-06:** in wp-admin, Upload fills the doc, thumbnail and alternate-row inputs, and Add Row and Delete still work after the jQuery-to-DOM rewrite.
- **Phase 3:**
  1. Install `dist/wp-publication-archive.zip` on a fresh WordPress 7.1 site restored from a 3.0.1 database. Expect no activation errors, every G5 URL resolving, and the German translation loading with `WPLANG=de_DE`.
  2. Repeat with the DAM active.
  3. Mark the PR ready (§7.2) if the summarizer could not.
- **Push:** HANDOFF reports that the GitHub remote is archived and read-only, and every push in this flight failed. Someone must un-archive the repository or change the remote before a PR can open.

## Notes
- R4-01's new test carries `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` on its test-only `echo`. `phpcs.xml.dist` already excludes `EscapeOutput` for `tests/*`, so the ignore is dead but harmless, and `no-security-ignores` does not cover `tests/`.
- The Meta_Boxes save-side sanitisation duplicates `Post_Type`'s registered `sanitize_callback`, as described under Evidence. This is defence in depth, not a defect. Mutation testing cannot tell the two layers apart through the save path.
- These round-3 notes still apply and are not findings:
  - the sibling-directory containment test catches its mutation only indirectly;
  - the `(string)` cast of a possibly repeated `content-type` header;
  - `filename=""` for a path with no basename.
