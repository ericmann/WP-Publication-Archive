# Review — build/2026-09-24
Round: 1

**Verdict: CHANGES REQUESTED**

Scope: the whole branch `e52acc84a75e..de26d15`, 38 tasks, all `[x]`, none blocked or skipped.

Evidence I gathered myself:
- `foundry_verify` (no files): every one of the 28 constraints passes its fixture self-test with 0 hits. Lint, analyse, test:map (29 files) and test:unit (64 tests) are green.
- `composer test` with the DAM loaded: 316 tests, OK (1 expected skip). I ran it with `COMPOSER_PROCESS_TIMEOUT=0`; see finding 1.
- `WPPA_DAM=0 composer test`: 298 tests, OK (1 expected skip), 10m27s.
- `foundry_mutate` samples, all killed:
  - `Url_Policy`: deleting the same-site short-circuit is caught by `test_d1_validate_table` and `test_same_site_url_never_calls_the_external_validator`.
  - `Streamer`: deleting the temp-dir guard is caught by `test_send_refuses_a_path_outside_the_temp_dir`. Replacing the D6 loop with a bare `ob_end_clean()` is caught by `test_d6_no_notice_with_zero_output_buffers`.
  - `Delivery`: the `decide()` cap is caught by a unit test. Deleting the `is_withheld()` 404 is caught by `Test_Delivery_Dam` D17.
  - `Dam_Bridge`: the trash branch is caught by the D17 trashed tests at the bridge and end to end.
  - `Capabilities`: the has-cap guard is caught by `test_grant_gives_author_the_same_subset_as_for_post`.

## Findings (most severe first)

### 1. [Category 1 — Constraint P1, by reading] A capability name and a role name are hard-coded in `Cli`
- **Where:** `includes/class-cli.php:142-143`: `get_role( 'administrator' )` and `->has_cap( 'edit_publications' )`.
- **What is wrong:** P1 (CLAUDE.md Principles) requires every capability name to be a `Keys` constant, written once. `edit_publications` is already `Keys::CAP_MAP['edit_posts']`, so this is a second, independent copy.
- **Why no rule caught it:** `names-in-keys-only` only matches the plugin prefixes, so it cannot see an unprefixed capability name. There is no mechanical rule for this shape.
- **What breaks:** if `CAP_MAP` changes, the `caps_granted` doctor row silently checks a stale capability.
- **Minimal fix:** read `Keys::CAP_MAP['edit_posts']` and a new `Keys::ROLE_ADMINISTRATOR`, and assert the new constant in `test-keys.php`. Add a `capability-names-in-keys` constraint to `docs/foundry.json`, using this diff's own line as its `shouldMatch` fixture, so the shape cannot come back.
- **Task:** P2-02 (introduced by the `caps_granted` row).

### 2. [Category 3 — Tests / verification harness] `composer test` and `composer verify` die at Composer's default 300 s process timeout
- **Where:** `composer.json:29-38` (the `config` block has no `process-timeout`).
- **What is wrong:** the wp-env suite takes about 9m40s with the DAM and 10m27s without it on this machine, so `composer test` and `composer verify` exit non-zero after 300 s unless the caller overrides the timeout.
  - That covers the SPEC Done criterion ("`composer verify` passes"), Foundry's `extraVerify` and CI's `WPPA_DAM=0 composer test`.
  - The implementer's logs note the timeout at P2-05 through P2-10 and worked around it.
- **Worse, the timeout is the root cause of the "recurring environment flake" in HANDOFF.** Killing the host-side process does not stop `phpunit` inside the `tests-cli` container. The orphan keeps running against the shared tests DB while the next run starts. I reproduced this three ways:
  - I saw two orphaned `phpunit` processes with `docker exec … ps`.
  - The next run hit `Deadlock found when trying to get lock` inside `create_upload_object()`, which is exactly the HANDOFF signature.
  - An orphan still running a mutated `Capabilities` class persisted role caps into the tests DB. That made a clean `Test_Capabilities` run fail until I removed the caps with wp-cli.
- **Minimal fix:** add `"process-timeout": 0` to `composer.json` `config`.
- **Task:** P0-01.

### 3. [Category 3 — Tests / correctness] D18 is broken on the path the templates actually use
- **Where:** `includes/class-publication-item.php:223-225`, `the_thumbnail()`.
- **What is wrong:** `get_the_thumbnail()` correctly allows `data:` for the DAM placeholder. But `the_thumbnail()`, which every bundled template calls, echoes that markup through `wp_kses_post()`, which strips the `data:` protocol. I confirmed in wp-env that `wp_kses_post( '<img src="data:image/gif;base64,…">' )` yields `src="image/gif;base64,…"`.
- **What breaks:** a withheld thumbnail renders as a broken, relative `<img>` that sends a junk request per image, not the DAM placeholder. The raw file URL does not leak, but the placeholder substitution D18 promises never renders.
- **Why tests miss it:** `test_d18_embargoed_thumbnail_renders_placeholder_for_anonymous` only checks `get_the_thumbnail()`.
- **Also here (Category 5, SPEC §5.1):** `wpa-upload_image` values go through no read-path normalisation (lines 123-129 normalise only `uri`). A legacy `https|host/x.png` thumbnail renders as `http://https|host/x.png`, but §5.1 says `META_IMAGE` read paths normalise through `Url_Policy::normalise()`.
- **Minimal fix:** echo through `wp_kses( $html, 'post', array_merge( wp_allowed_protocols(), array( 'data' ) ) )`. Also run `normalise()` on the thumbnail after the `wpa-upload_image` filter and before `display_url()`.
- **Task:** P1-08.

### 4. [Category 5 — Spec drift / data integrity] Meta box save corrupts percent-encoded URLs
- **Where:** `includes/class-meta-boxes.php:153-154` (doc and image) and `:163-167` (alternate urls via `map_deep( …, 'sanitize_text_field' )`).
- **What is wrong:** `sanitize_text_field()` deletes every `%xx` octet. I confirmed in wp-env:
  - `https://example.com/My%20Annual%20Report.pdf` becomes `https://example.com/MyAnnualReport.pdf`;
  - `…/r%C3%A9sum%C3%A9.pdf?x=a%2Fb` becomes `…/rsum.pdf?x=ab`.
- **What breaks:** the validated, corrupted value is stored silently. Every external document link with an encoded space or non-ASCII name breaks the next time an editor saves the post.
- **Why this is drift:** 3.0.1 used `esc_url_raw()`, which preserves these. SPEC §5.1 says "write paths store the validated value", and only the description gets `sanitize_text_field()`. The REST path (`Post_Type::sanitize_url_meta`) is not affected.
- **Minimal fix:** sanitise the URL fields with a URL-preserving sanitiser (`esc_url_raw()` after `Url_Policy::normalise()`, so the pipe form still normalises), then `validated_url()`. Keep `sanitize_text_field()` for descriptions and the nonce.
- **Task:** P1-04.

### 5. [Category 2 — Boundary; Category 5 — behaviour change] `Delivery` builds a presentation object and acts on non-publications
- **Where:** `includes/class-delivery.php:134-187`.
- **Boundary problem:** `resolve_uri()` builds a `Publication_Item`, which is a presentation helper. Neither SPEC §4.2 nor the CLAUDE.md module map lets `Delivery` import it. Its constructor runs `setup_postdata()`, `get_the_content()` and `get_the_excerpt()`, and `get_the_excerpt()` can run the full `the_content` filter chain (blocks, shortcodes, other plugins) on every file request, just to read two meta values.
- **Behaviour change:** `deliver()` never checks that the post is a `publication`. `?wppa_open=yes` or `?wppa_download=yes` appended to any post, page or the home page now ends in `wp_die()` 404. In 3.0.1 the empty URI returned silently and the page rendered. No D-item requires that change for non-publication requests.
- **Minimal fix:** return early unless `get_post()` is a `Keys::POST_TYPE` post. Read `Keys::META_DOC` and `Keys::META_ALTERNATES` directly with `get_post_meta()`. Keep the 3.0.1 alternate-key semantics.
- **Task:** P1-07.

### 6. [Category 5 — Spec drift] `admin-media.js` uses jQuery beyond `jQuery( document ).on()` delegation
- **Where:** `assets/js/admin-media.js:70-73` (`$( this ).closest( 'tr' )`, `$row.find( … ).val( url )`) and `:93` (`$( this ).closest( 'tr' ).remove()`).
- **What is wrong:** SPEC §6.7 and the PLAN P2-07 design constraint both say the only jQuery is `jQuery( document ).on()` delegation.
- **Minimal fix:** use `this.closest( 'tr' )`, `querySelector( 'input[name$="[url][]"]' ).value = url`, and `Element.remove()`.
- **Task:** P2-07.

### 7. [Category 5 — Spec drift, release notes] The 3.1.0 changelog claims a D5 fix that was never made
- **Where:** `readme.txt:137-138` and `CHANGELOG.md:19-20`: "Fix a rewrite-rule collision that made a publication slugged "view" or "download" unreachable at its own permalink (D5)."
- **What is wrong:** P2-05 found D5 not reproducible, changed no rule, and added regression tests. The notes shipped to about 400 sites describe a fix that does not exist.
- **Minimal fix:** reword the D5 bullet in both files to say that regression tests confirm these slugs resolve at their permalinks.
- **Task:** P3-02.

## Spec issues
- **§6.1, Contributors lose publication access.** 3.0.1's CPT used `capability_type => 'post'`, so Contributors could create and edit draft publications. §6.1 grants the publication caps only to administrator, editor and author, yet claims "This mapping leaves that access unchanged". It does not for Contributors. The code follows the spec's role list; SPEC should either add `contributor` to the role list or drop the claim.
- **D5 as written does not reproduce** (PLAN Spec issue 4, confirmed at P2-05). The only real collisions left are the CPT's own sub-routes for a publication slugged `view` or `download`. `/publication/view/feed/`, `/publication/view/embed/` and `/publication/view/trackback/` are captured by the `top` endpoint rule. SPEC should restate D5 or close it as not reproducible.
- **§6.2 proxy-mode Content-Type.** Step 1 falls back to the remote response's `Content-Type`, and view mode sends no `Content-Disposition` and no `X-Content-Type-Options: nosniff`. With opt-in proxying, a remote `text/html` document is served inline from the site's own origin. Consider forcing `application/octet-stream` or `attachment` for non-allowlisted types, and adding `nosniff`.
- **PLAN Spec issue 15** (plain-permalink links emit `?view=yes` and `?download=yes`, which the endpoints ignore) is still open for a human decision.
- **PLAN Spec issue 3** (the D8 change to list-item title links) asks a human to confirm it is the intended outcome.

## Manual checks still owed (from HANDOFF.md)
- **Phase 0:** SPEC §8 Phase 0 item 5. On a clean clone after `bash bin/fetch-dam.sh` and `npx wp-env start`, the front-end list and single pages and wp-admin → Publications look the same as on 3.0.1, with both plugins active.
- **Phase 1:**
  - The §5.3 proxy tunables.
  - As an Author, save `/etc/passwd` as the document URL; the field comes back empty and the view URL returns 404.
  - Embargo the linked attachment in the DAM; the view URL 404s logged out and redirects for an Editor.
  - With `wppa_mask_url` returning true, a 60 MB file redirects rather than proxies.
- **Phase 2:**
  1. In the block editor, the three meta boxes appear, and Upload opens the media modal and fills the URL.
  2. The Legacy Widget block previews all three widgets.
  3. The list and dropdown shortcodes and the single and archive pages render with no `debug.log` notices, with the DAM active and again with it deactivated.
- **Phase 3:**
  1. Install `dist/wp-publication-archive.zip` on a fresh WordPress 7.1 site restored from a 3.0.1 database: no activation errors, every G5 URL resolves, and the German translation loads with `WPLANG=de_DE`.
  2. Repeat with the DAM active.
  3. Mark the PR ready if the summarizer could not.
- **Push/CI:** HANDOFF reports that `origin` is archived and read-only. Nothing has been pushed and CI has never run on this branch.

## Notes
- `Streamer::send()` checks containment with `0 === strpos( $real_path, $real_temp_dir )` and no trailing separator, so `/tmpfoo/x` passes a `/tmp` check. The only caller passes a `wp_tempnam()` path, so there is no exposure today; appending `DIRECTORY_SEPARATOR` would make it exact.
- `Meta_Boxes::save()` still assumes that a posted `wpa-alternates` array has both `url` and `description` keys of equal length. A hand-crafted POST without `url` hits `count( null )`, a TypeError on PHP 8. It is nonce-gated, and 3.0.1 behaved the same.
- The tests DB (`wp_` prefix, shared with the `tests-wordpress` site) keeps `wp_user_roles` across phpunit installs, so role state written by one run survives into the next. The `Test_Capabilities` snapshot and restore only protects in-process state. Once finding 2 is fixed, orphans stop writing there, but it is worth knowing.
- HANDOFF's "re-run rather than assume a regression" advice for the `create_upload_object()`/`WP_Error` flake should be withdrawn once finding 2 lands. The flake is concurrent orphaned runs, not the machine.
