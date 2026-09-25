# Review — build/2026-09-24
Round: 3

**Verdict: CHANGES REQUESTED**

Scope: the whole branch `e52acc84a75e..e8b6f88`. It has 50 tasks, all `[x]`, and none are blocked or skipped. New work since round 2 is the five round-2 fix commits, R2-01 to R2-05. Earlier rounds reviewed every Phase 0–3 task and every R1 task commit by commit. This round I re-checked the whole branch against constraints and tests. Then I read each R2 commit against its PLAN task and the SPEC sections it cites (§4.2, §6.1, §6.2 steps 3–4, G5).

Evidence I gathered myself:
- **`foundry_verify`**, with the Streamer and Delivery files passed so the wp-env suite ran:
  - All 29 constraints pass their fixture self-tests with 0 hits.
  - `lint`, `analyse` and `test:map` (29 files) are green. `test:unit` is green: 74 tests, 432 assertions.
  - `composer test` with the DAM loaded is green: 345 tests, 1746 assertions, 1 expected skip, 17m17s.
- **`foundry_mutate` samples:**
  - **Killed:**
    - `Streamer`: deleting the `X-Content-Type-Options: nosniff` header (R2-03) is caught by `test_send_always_sends_nosniff` and by the header-index assertions.
    - `Streamer`: going back to the prefix-only containment check `strpos( $real_path, $real_temp_dir )` (R2-03) is caught by `test_send_refuses_a_sibling_dir_sharing_the_temp_dir_prefix`.
    - `Streamer::is_active_content()`: dropping `strtolower()` is caught by the `IMAGE/SVG+XML` case of `test_send_forces_attachment_for_active_content_with_no_filename`.
    - `Meta_Boxes::save()`: putting `wp_kses_post()` back on the doc field (R2-01) is caught by `test_save_preserves_ampersands_in_urls`.
  - **Survived:** `Delivery::proxy()`, replacing `( $is_download || Streamer::is_active_content( $content_type ) )` with `$is_download` (R2-03). See finding 1.
- **R2-02:** `Delivery` now imports only what §4.2 allows. `wp_check_filetype()` is inlined exactly as `Icons::mime_for()` did it, and `Plugin` wiring and both test constructors are updated.
- **R2-04:** contributors get `edit_publications` and `delete_publications` only, and the §6.1 behavioural test is present.
- **R2-05:** the expected strings match 3.0.1's `get_link()` at base (`add_query_arg( $endpoint, 'yes' )`, then `'alt'`).
- **Branch hygiene:** no round-2 commit touches `.gitignore`, `.gitattributes`, editor config or CI.

## Findings (most severe first)

### 1. [Category 3 — Tests] No test checks that an active-content view gets a named filename
- **Where:** `includes/class-delivery.php:254-256`. The tests are `tests/integration/test-delivery.php:545-557`: `test_proxy_view_of_html_is_attachment_with_nosniff` and `test_proxy_view_of_svg_is_attachment_with_nosniff`.
- **What is wrong:** SPEC §6.2 step 4 says to send `Content-Disposition: attachment; filename="…"` for every download, and to send the same header for a view whose type is active content. R2-03's design constraint puts that in `Delivery::proxy()`: it passes `sanitize_file_name( basename( URL path ) )` when `$is_download || Streamer::is_active_content( $content_type )`. The two integration tests only check that the headers contain `Content-Disposition: attachment`. `Streamer::send()` also has a fallback: it sends a bare `Content-Disposition: attachment` for active content when no filename is given. That fallback satisfies the assertion on its own. So `foundry_mutate` removing the active-content branch from `Delivery` **survived** the full wp-env suite.
- **What breaks:** a later change can drop the filename from active-content views, or stop `Delivery` computing it, and no test fails. The response would then carry a bare `attachment`, not SPEC's `attachment; filename="…"`. The security property still holds today because of the fallback, but the mechanic the task specified is not tested.
- **Minimal fix:** change the html and svg tests to assert the exact header: `Content-Disposition: attachment; filename="a.html"` and `Content-Disposition: attachment; filename="a.svg"`. The fixture URLs are `/wp-content/uploads/a.html` and `a.svg`. Test-only change.
- **Also in this task:** `CLAUDE.md:46` still lists `Icons` among `class-delivery.php`'s allowed imports. R2-02 removed that dependency to match SPEC §4.2, and CLAUDE.md is what the implementer reads, so the stale row invites the same boundary violation again. Delete `Icons` from that row.
- **Task:** R2-03 (and R2-02 for the CLAUDE.md row).

## Spec issues
- **Carried from rounds 1 and 2, still open:** D5 as written does not reproduce. The only real collisions are the CPT's own sub-routes (`/publication/view/feed/`, `/embed/` and `/trackback/`) for a publication slugged `view` or `download`. The branch closes D5 with regression tests and release notes that say so (R1-07). SPEC's D5 text could be corrected to match.
- **§6.2 step 4, view-mode filename.** Round 2 raised this and it is resolved in code. `Delivery` passes the basename for active-content views, and `Streamer` falls back to a bare `attachment` when none is given. SPEC could still say this explicitly.
- **PLAN P0-09's module map** still gives `Delivery` an `Icons` parameter (`docs/PLAN.md:578`). That is a historical task record, superseded by R2-02; it is noted here, not edited.

## Manual checks still owed (from HANDOFF.md)
- **Phase 0:** SPEC §8 Phase 0 item 5. On a clean clone after `bash bin/fetch-dam.sh` and `npx wp-env start`, the front-end list and single pages and wp-admin → Publications look the same as on 3.0.1, with both plugins active.
- **Phase 1:**
  - Exercise the §5.3 proxy tunables (`Keys::DEFAULT_PROXY_TIMEOUT` = 30 and `Keys::DEFAULT_PROXY_MAX_BYTES` = 52428800, both ⚠️ ASSUMPTION).
  - As an Author, save `/etc/passwd` as the document URL. The field comes back empty and the view URL returns 404.
  - Embargo the linked attachment in the DAM. The view URL 404s logged out and redirects for an Editor.
  - With `wppa_mask_url` returning true, a 60 MB file redirects rather than proxies.
- **Phase 2:**
  1. In the block editor, Publications → Add New shows the three meta boxes, and Upload opens the media modal and fills the URL.
  2. The Legacy Widget block previews all three widgets.
  3. The list and dropdown shortcodes and the single and archive pages render with no `debug.log` notices, with the DAM active and again with it deactivated.
- **R1-06:** in wp-admin, Upload fills the doc, thumbnail and alternate-row inputs, and Add Row and Delete work after the jQuery-to-DOM rewrite.
- **Phase 3:**
  1. Install `dist/wp-publication-archive.zip` on a fresh WordPress 7.1 site restored from a 3.0.1 database. Expect no activation errors, every G5 URL resolving, and the German translation loading with `WPLANG=de_DE`.
  2. Repeat with the DAM active.
  3. Mark the PR ready (§7.2) if the summarizer could not.
- **Push:** HANDOFF reports that the GitHub remote is archived and read-only. Every push in this flight failed. Someone must un-archive the repository or change the remote before a PR can open.

## Notes
- `test_send_refuses_a_sibling_dir_sharing_the_temp_dir_prefix` does catch the containment mutation, but only indirectly. With the mutation, `Streamer` streams and deletes the sibling file, and the `unlink()` in the test's `finally` then errors. `expectException` is never the thing that fails. A clearer version would assert the file still exists after the call. The test still does its job.
- `Delivery::proxy()` casts `wp_remote_retrieve_header( $response, 'content-type' )` to `(string)`. If the remote repeats the header, that value is an array. This predates this round, and `nosniff` together with the step-1 extension lookup limits the effect.
- An active-content view whose URL path has no basename (e.g. `https://host/`) sends `filename=""`. That is harmless: browsers fall back to a generated name, and the response is still an attachment.
