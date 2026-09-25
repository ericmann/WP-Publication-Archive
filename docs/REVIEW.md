# Review — build/2026-09-24
Round: 2

**Verdict: CHANGES REQUESTED**

Scope: the whole branch `e52acc84a75e..464750b`, 45 tasks, all `[x]`, none blocked or skipped. This round's new work:
- the seven round-1 fix commits, R1-01 to R1-07;
- the product-owner SPEC revision `464750b` ("decisions after review round 1"), which landed after R1 was implemented. It adds four requirements: Contributor caps (§6.1), `nosniff` and active-content disposition in `Streamer` (§6.2), plain permalinks pinned at 3.0.1 behaviour (G5), and D8's shortcode change accepted.

Evidence I gathered myself:
- **`foundry_verify`:** every one of the 29 constraints passes its fixture self-test with 0 hits, including the new `capability-names-in-keys`. Lint, analyse, test:map (29 files) and test:unit (66 tests) are green. `composer test` with the DAM loaded: 325 tests, OK, 1 expected skip, 13m37s. It finished with no `COMPOSER_PROCESS_TIMEOUT` override, which confirms R1-01.
- **`foundry_mutate` samples:**
  - **Killed:**
    - `Delivery`: dropping the `Keys::POST_TYPE` check (R1-05) is caught by `test_open_query_var_on_non_publication_is_ignored`.
    - `Publication_Item::the_thumbnail()`: going back to `wp_kses_post()` (R1-03) is caught by `Test_Publication_Item_Dam::test_d18_the_thumbnail_echoes_placeholder_for_anonymous`.
    - `Cli`: checking `'edit_posts'` in place of `Keys::CAP_MAP['edit_posts']` (R1-02) is caught by `test_caps_granted_row_follows_keys_cap_map`.
    - `composer.json`: removing `process-timeout` (R1-01) is caught by `Test_Composer_Config`.
    - `readme.txt`: restoring the "Fix a rewrite-rule collision" wording (R1-07) is caught by `test_d5_entry_does_not_claim_a_rule_fix`.
    - `admin-media.js`: reintroducing `$( this )` (R1-06) is caught by `test_admin_media_js_uses_jquery_only_for_document_delegation`.
  - **Survived:** `Rewrites::link()`, deleting the plain-permalink `add_query_arg( Keys::QUERY_ALT_KEY, … )`. See finding 3.
- **By hand in the wp-env `cli` container:** `wp_kses_post( 'https://example.com/a.pdf?x=1&y=2' )` returns `…?x=1&amp;y=2`, and `esc_url_raw()` keeps the `&amp;`. See finding 2.

## Findings (most severe first)

### 1. [Category 2 — Boundaries] `Delivery` imports `Icons`, which the §4.2 module map does not allow
- **Where:** `includes/class-delivery.php:23,40,64-66,246` (the `Icons $icons` constructor parameter, property and accessor, and `$this->icons->mime_for( $url )`); `includes/class-plugin.php:103`.
- **What is wrong:** SPEC §4.2 lets `class-delivery.php` import only `Keys`, `Hooks`, `Flags`, `Url_Policy`, `Streamer` and `Dam_Bridge`. `Icons` is a presentation helper. PLAN P0-09 and P1-01 wrote `Icons` into the constructor signature, so this is a PLAN deviation from SPEC, not an implementer slip. Round 1 did not flag it.
- **What breaks:** delivery, the security-critical endpoint module, depends on a presentation module. 4.0 extends this module map (G7) and would inherit the edge.
- **Minimal fix:** resolve the §6.2 step 1 content type in `Delivery` with `wp_check_filetype()` on the URL path's basename, as `Icons::mime_for()` does today. Drop `Icons` from `Delivery`'s constructor, property, accessor, the `Plugin` wiring and the two tests that construct `Delivery`.
- **Task:** P1-06 (Delivery) and P0-09.

### 2. [Category 3 — Correctness regression introduced by R1-04] Saving the meta box turns `&` in every URL into `&amp;`
- **Where:** `includes/class-meta-boxes.php:160-161` (doc and thumbnail) and `:171` (alternates, `map_deep( …, 'wp_kses_post' )`).
- **What is wrong:** R1-04 replaced `sanitize_text_field()` with `wp_kses_post()` as the immediate sanitiser. `wp_kses()` runs `wp_kses_normalize_entities()`, so every bare `&` in a query string is stored as `&amp;`. I confirmed it in wp-env: `https://example.com/a.pdf?x=1&y=2` is stored as `https://example.com/a.pdf?x=1&amp;y=2`.
- **What breaks:** any document, thumbnail or alternate URL with more than one query parameter, such as signed S3 or CDN URLs or `?id=…&export=download`, is silently corrupted on save. Delivery then redirects to a URL whose second parameter is named `amp;y`. The meta box re-renders the value through `esc_attr()`, the browser posts `&` back, and every save corrupts it again. 3.0.1 (`esc_url_raw()`) and the pre-R1 code both kept `&`. This is also a stored-format change (G4).
- **Why tests miss it:** `test_save_preserves_percent_encoded_urls` uses only single-parameter URLs.
- **Minimal fix:** use `wp_strip_all_tags()` as the immediate sanitiser, for the two scalar fields and in the alternates `map_deep()`. It is in WPCS's `SanitizationHelperTrait` sanitising list, so `InputNotSanitized` accepts it, and it leaves `%xx`, `&` and the legacy `|` untouched. I verified in wp-env that `esc_url_raw( normalise( wp_strip_all_tags( … ) ) )` gives:
  - `https://example.com/a.pdf?x=1&y=2` → itself;
  - `https|example.com/My%20Report.pdf` → `https://example.com/My%20Report.pdf`;
  - `https://example.com/r%C3%A9sum%C3%A9.png` → itself.
- **Task:** R1-04.

### 3. [Category 3 — Tests] Plain-permalink link generation is pinned for the open link only; the `alt` branch is untested
- **Where:** `tests/integration/test-characterisation-routing.php:174-180` and `tests/integration/test-rewrites.php:78-84` pin only `get_open_link()` under an empty `permalink_structure`. The code is `includes/class-rewrites.php:67-72`.
- **What is wrong:** the revised G5 says "Plain permalinks keep their exact 3.0.1 behaviour in 3.x … the link generators return what 3.0.1 returned … and the query forms resolve as before. The characterisation tests pin this behaviour." Nothing pins the download, altview and altdown generators under plain permalinks, or the `wppa_open` and `wppa_download` query forms on a plain-permalink site. `foundry_mutate` deleting `$new = add_query_arg( Keys::QUERY_ALT_KEY, $key, $new );` **survived** the full suite.
- **What breaks:** a later change, or 4.0 groundwork, can alter the plain-permalink alternate links 3.x sites emit, and no test notices.
- **Minimal fix:** add characterisation tests under `set_permalink_structure( '' )` for:
  - `get_download_link()` → `home_url( '/?publication=attached-report&download=yes' )`;
  - `get_alternate_open_link( …, 'English' )` → `home_url( '/?publication=alternates-report&altview=yes&alt=English' )`;
  - `get_alternate_download_link()` → the `altdown` equivalent;
  - the `wppa_open=yes` and `wppa_download=yes` query forms resolving to the right post and query var.
- **Task:** P0-06 (characterisation), extended by SPEC `464750b`.

### 4. [Category 5 — Spec drift, SPEC `464750b` §6.2 steps 3-4] `Streamer` sends no `nosniff` and serves active content inline on view
- **Where:** `includes/class-streamer.php:56-63`; `includes/class-delivery.php:246-255`; `includes/class-keys.php` has no `ACTIVE_CONTENT_TYPES`.
- **What is wrong:** the revised §6.2 requires two things of `Streamer::send()`:
  - always send `X-Content-Type-Options: nosniff`;
  - send `Content-Disposition: attachment; filename="…"` for a view request whenever the resolved type is in `Keys::ACTIVE_CONTENT_TYPES` (`text/html`, `application/xhtml+xml`, `image/svg+xml`, `text/xml`, `application/xml`, `text/javascript`, `application/javascript`).
  
  Neither exists. For anonymous visitors, `wp_check_filetype()` drops `htm|html` (no `unfiltered_html`) and has no `svg`. So proxy mode falls back to the remote `Content-Type` and serves remote HTML or SVG inline from the site's own origin.
- **What breaks:** with `wppa_mask_url` on, an Author who links an external `.html` or `.svg` gets script execution on the site's origin through `/publication/view/{slug}`.
- **Minimal fix:** add the constant. In `Streamer::send()`, send `nosniff` as step 3 and the attachment disposition as step 4 whenever a filename is given or the media type is active. The media type is the header value with `;` parameters stripped, trimmed and lowercased. `Delivery` passes the sanitised basename as `$filename` for a download or for an active-content view.
- **Task:** P1-06 (Delivery/Streamer), extended by SPEC `464750b`.

### 5. [Category 5 — Spec drift, SPEC `464750b` §6.1] Contributors still get no publication capabilities
- **Where:** `includes/class-keys.php:100` (`CAP_ROLES` lists administrator, editor and author only); `includes/class-capabilities.php` (the docblock says "three §6.1 roles").
- **What is wrong:** the revised §6.1 adds `contributor`, with the same subset it has for `post`. The test it requires is also missing: after `grant()`, a Contributor can create and edit their own draft publication but cannot publish it or edit another user's.
- **What breaks:** every 3.0.1 Contributor who manages draft publications loses access on upgrade.
- **Minimal fix:** add `'contributor'` to `Keys::CAP_ROLES`. `grant()` already maps by `has_cap()`, so contributors get `edit_publications` and `delete_publications`. Then update `test-keys.php` and add the §6.1 test.
- **Task:** P2-02, extended by SPEC `464750b`.

## Spec issues
- **§4.2 vs PLAN P0-09 and P1-01.** PLAN gave `Delivery` an `Icons` dependency that the SPEC module map does not allow (finding 1). If the product owner prefers `Delivery` to reuse `Icons::mime_for()`, §4.2 should list `Icons` for `class-delivery.php`. Otherwise the fix task stands.
- **§6.2 step 4, view-mode filename.** The `send( $path, $content_type, $filename_or_null )` signature gives `Streamer` no filename on a view request. Yet step 4 wants `attachment; filename="…"` for active-content views. The fix task resolves this by having `Delivery` pass the sanitised basename for active-content views, and by having `Streamer` fall back to a bare `attachment` when none is given. SPEC could say so explicitly.
- **Carried from round 1 and resolved by `464750b`:** Contributors (§6.1), proxy Content-Type (§6.2), plain permalinks (G5; PLAN Spec issue 15) and D8's list-title links (PLAN Spec issue 3).
- **Carried from round 1, still open:** D5 as written does not reproduce. The only real collisions are the CPT's own sub-routes (`/publication/view/feed/`, `/embed/` and `/trackback/`) for a publication slugged `view` or `download`.

## Manual checks still owed (from HANDOFF.md)
- **Phase 0:** SPEC §8 Phase 0 item 5. On a clean clone after `bash bin/fetch-dam.sh` and `npx wp-env start`, the front-end list and single pages and wp-admin → Publications look the same as on 3.0.1, with both plugins active.
- **Phase 1:**
  - Exercise the §5.3 proxy tunables.
  - As an Author, save `/etc/passwd` as the document URL; the field comes back empty and the view URL returns 404.
  - Embargo the linked attachment in the DAM; the view URL 404s logged out and redirects for an Editor.
  - With `wppa_mask_url` returning true, a 60 MB file redirects rather than proxies.
- **Phase 2:**
  1. In the block editor, the three meta boxes appear, and Upload opens the media modal and fills the URL.
  2. The Legacy Widget block previews all three widgets.
  3. The list and dropdown shortcodes and the single and archive pages render with no `debug.log` notices, with the DAM active and again with it deactivated.
- **R1-06:** in wp-admin, Upload fills the doc, thumbnail and alternate-row inputs, and Add Row and Delete work.
- **Phase 3:**
  1. Install `dist/wp-publication-archive.zip` on a fresh WordPress 7.1 site restored from a 3.0.1 database: no activation errors, every G5 URL resolves, and the German translation loads with `WPLANG=de_DE`.
  2. Repeat with the DAM active.
  3. Mark the PR ready if the summarizer could not.

## Notes
- `Streamer::send()` still checks containment with `0 === strpos( $real_path, $real_temp_dir )` and no trailing separator (round-1 note). Fix task R2-03 edits this method and now closes it.
- `Meta_Boxes::save()`: a hand-crafted, nonce-valid POST whose `wpa-alternates[url]` is a string rather than an array, or holds a nested array, reaches `count()` or `Url_Policy::normalise( string )` with the wrong type and throws a `TypeError` on PHP 8. It is nonce-gated and was never user-reachable through the form. R2-01 casts it while it is in these lines.
- `Publication_Item::the_thumbnail()` now allows `data:` for the whole echoed fragment, not just the `<img src>`. The fragment is built entirely from the default `$before`/`$after` plus an `esc_url()`'d source, so there is no exposure.
- `Test_Cli::test_caps_granted_row_follows_keys_cap_map` mutates the administrator role in memory. The class-level `set_up`/`tear_down` snapshot restores it, which is correct.
