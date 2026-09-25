# Changelog

## 3.1.0

Safety and compatibility release. Rebuilt on a modern toolchain, tested
side by side with the VIP Digital Asset Manager (the DAM), no behaviour
change beyond the fixes below.

- Fix a local file disclosure / SSRF hole: a publication's file, image and
  alternate URLs are now validated before they are ever opened or streamed
  (D1).
- Fix stored XSS in alternate file descriptions and every place a
  publication's title, thumbnail, download links or alternate descriptions
  were echoed unescaped (D2, D3).
- Fix an off-by-one in the alternates save loop that read one row past the
  end (D10).
- Remove a dead, unsafe search filter that rewrote WordPress 3.x-era SQL
  and interpolated the raw search term into a regex (D4).
- Confirmed that publications slugged "view" or "download" stay reachable
  at their own permalinks; regression tests added (D5).
- Fix a PHP 8 notice from calling `ob_clean()` with no output buffer open
  during file delivery (D6).
- Publication dates are now formatted in the site's own timezone and
  locale, not the server's wall-clock time (D7).
- Remove dead code: an unhooked post-permalink filter, and three
  never-hooked content/title/search filter methods that now simply return
  their input unchanged (D8).
- The schema upgrade now runs once, on `init`, instead of writing options
  or flushing rewrite rules on every request (D9).
- Remove the misleading admin notice about `allow_url_fopen`, which
  nothing in this version needs (D11).
- The publication post type, the author taxonomy and the three stored
  meta keys are now exposed to the REST API and the block editor (D12).
- Replace the Thickbox media uploader and its inline JavaScript with a
  modern `wp.media` uploader (D13).
- The bundled widgets can now be added, edited and previewed in the
  Legacy Widget block (D14).
- Remove `extract()` from the shortcode handler and the bundled templates
  (D15).
- General directory and dependency hygiene: dead files removed, MIME
  lookups modernised (D16).
- When the VIP Digital Asset Manager is active, file delivery and
  thumbnails now honor its embargo, lifecycle and trash state instead of
  bypassing it (D17, D18).
- When the VIP Digital Asset Manager is active, it can now see which
  attachments a publication uses even when the URL is stored in the
  legacy `http|`/`https|` pipe form, so it will not let you delete an
  attachment a publication still links to (D19).
- Downloads now redirect by default instead of streaming through the
  server; streaming is available as an opt-in proxy for large files.
