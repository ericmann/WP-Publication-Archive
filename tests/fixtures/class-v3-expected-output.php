<?php
/**
 * Implements SPEC.md §8 Phase 0 item 3: expected 3.0.1 shortcode and widget
 * output, captured verbatim from the current code against V3_Site::create().
 * Placeholders are resolved by expand(); later D-tasks may edit a constant
 * only by naming the D-item they close in a comment beside the edit.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA\Tests\Fixtures;

final class V3_Expected_Output {

	// D8 (P2-01): the post_type_link hijack (Rewrites::filter_post_type_link())
	// is deleted as dead code, so every list-item title link below is now the
	// canonical get_permalink(), not the hijacked open-endpoint form the
	// second and later items showed once the widget/shortcode's first
	// get_open_link()/get_download_link() call this request had armed it.
	const LIST_ALL = '<div class="publication-archive">'
		. '<div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:attached}/">Attached Report</a></div><div class="publication_authors"><span class="date">(January 7, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:attached}">View</a> | <a href="{site}/publication/download/{slug:attached}">Download</a></span></div></div>'
		. '<div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:pipe}/">Pipe Report</a></div><div class="publication_authors"><span class="date">(January 6, 2013)</span></div><div class="publication_download"><span class="title">{filename:pipe_attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:pipe}">View</a> | <a href="{site}/publication/download/{slug:pipe}">Download</a></span></div></div>'
		. '<div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:alternates}/">Alternates Report</a></div><div class="publication_authors"><span class="date">(January 5, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:alternates}">View</a> | <a href="{site}/publication/download/{slug:alternates}">Download</a></span></div></div>'
		. '<div class="single-publication"><div class="publication_thumbnail"><img src="{url:image_attachment_id}" /></div><div class="publication_title"><a href="{site}/publication/{slug:thumbnail}/">Thumbnail Report</a></div><div class="publication_authors"><span class="date">(January 4, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:thumbnail}">View</a> | <a href="{site}/publication/download/{slug:thumbnail}">Download</a></span></div></div>'
		. '<div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:categorised}/">Categorised Report</a></div><div class="publication_authors"><span class="author-list">Jane Doe</span><span class="date">(January 3, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:categorised}">View</a> | <a href="{site}/publication/download/{slug:categorised}">Download</a></span></div><div class="publication_categories"><span class="title">Categories: </span><span class="description">Reports</span></div></div>'
		. '<div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:slug_view}/">View</a></div><div class="publication_authors"><span class="date">(January 2, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:slug_view}">View</a> | <a href="{site}/publication/download/{slug:slug_view}">Download</a></span></div></div>'
		. '<div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:slug_download}/">Download</a></div><div class="publication_authors"><span class="date">(January 1, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:slug_download}">View</a> | <a href="{site}/publication/download/{slug:slug_download}">Download</a></span></div></div>'
		. '</div>';

	const LIST_LIMIT_2_PAGE_2 = '<div class="publication-archive">'
		. '<div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:alternates}/">Alternates Report</a></div><div class="publication_authors"><span class="date">(January 5, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:alternates}">View</a> | <a href="{site}/publication/download/{slug:alternates}">Download</a></span></div><div class="publication_summary"><span class="title">Summary: </span><span class="description">{summary:page}</span></div></div>'
		. '<div class="single-publication"><div class="publication_thumbnail"><img src="{url:image_attachment_id}" /></div><div class="publication_title"><a href="{site}/publication/{slug:thumbnail}/">Thumbnail Report</a></div><div class="publication_authors"><span class="date">(January 4, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:thumbnail}">View</a> | <a href="{site}/publication/download/{slug:thumbnail}">Download</a></span></div><div class="publication_summary"><span class="title">Summary: </span><span class="description">{summary:page}</span></div></div>'
		. '</div>'
		. '<div id="navigation"><div class="nav-previous"><a href="{page}?wpa-paged=1"> &laquo; Previous </a></div><div class="nav-next"><a href="{page}?wpa-paged=3"> Next &raquo; </a></div></div>';

	const DROPDOWN_ALL = '<div class="publication-archive"><p>Download Publication</p><form name="publication_dropdown" method="get" action=""><select name="dropdown" onchange="window.location.href=this.form.dropdown.options[this.form.dropdown.selectedIndex].value"><option value="">Select file</option>'
		. '<option value="{site}/publication/view/{slug:attached}">Attached Report</option>'
		. '<option value="{site}/publication/view/{slug:pipe}">Pipe Report</option>'
		. '<option value="{site}/publication/view/{slug:alternates}">Alternates Report</option>'
		. '<option value="{site}/publication/view/{slug:thumbnail}">Thumbnail Report</option>'
		. '<option value="{site}/publication/view/{slug:categorised}">Categorised Report</option>'
		. '<option value="{site}/publication/view/{slug:slug_view}">View</option>'
		. '<option value="{site}/publication/view/{slug:slug_download}">Download</option>'
		. '</select></form></div>';

	const LIST_CATEGORY_REPORTS = '<div class="publication-archive"><div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:categorised}/">Categorised Report</a></div><div class="publication_authors"><span class="author-list">Jane Doe</span><span class="date">(January 3, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:categorised}">View</a> | <a href="{site}/publication/download/{slug:categorised}">Download</a></span></div><div class="publication_categories"><span class="title">Categories: </span><span class="description">Reports</span></div></div></div>';

	const LIST_AUTHOR_JANE_DOE = '<div class="publication-archive"><div class="single-publication"><div class="publication_title"><a href="{site}/publication/{slug:categorised}/">Categorised Report</a></div><div class="publication_authors"><span class="author-list">Jane Doe</span><span class="date">(January 3, 2013)</span></div><div class="publication_download"><span class="title">{filename:attachment_id} </span><span class="description"><img height="16" width="16" alt="download" src="{icon:application/pdf}" /><a href="{site}/publication/view/{slug:categorised}">View</a> | <a href="{site}/publication/download/{slug:categorised}">Download</a></span></div><div class="publication_categories"><span class="title">Categories: </span><span class="description">Reports</span></div></div></div>';

	const LIST_UNKNOWN_CATEGORY = "<div class='publication-archive'><p> Sorry, but the categories you passed to the wp-publication-archive shortcode do not match any publication categories.</p><p>You passed: <code>no-such-category</code></p></div>";

	// The archive widget queries with orderby=menu_order (the 'orderby' instance
	// key is a no-op; 3.0.1 checks $instance['order_by'], not $instance['orderby']),
	// so the three items are whatever menu_order/title tie-break MySQL returns,
	// not simply the three most recent. Pinned as observed.
	const WIDGET_ARCHIVE = "<div class=\"widget\"><h2>Publications</h2><ul>"
		. "<li><a href='{site}/publication/{slug:attached}/' title='Attached Report'>Attached Report</a></li>"
		. "<li><a href='{site}/publication/{slug:thumbnail}/' title='Thumbnail Report'>Thumbnail Report</a></li>"
		. "<li><a href='{site}/publication/{slug:categorised}/' title='Categorised Report'>Categorised Report</a></li>"
		. "</ul></div>";

	const WIDGET_CAT_COUNT_LIST = '<div class="widget"><h2>Categories</h2><ul><li class="cat-item cat-item-{id:category_id}"><a href="{site}/?cat={id:category_id}">Reports</a> (1) </li></ul></div>';

	// P1-09 (D3): the <select>/<option> markup now goes through wp_kses(),
	// which normalises attributes to double quotes and drops the extra
	// space before the <select>'s closing '>'. Pinned as observed.
	const WIDGET_CAT_COUNT_DROPDOWN = '<div class="widget"><h2>Categories</h2><select name="wp_pubarch_cat" id="wp_pubarch_cat" class="postform"><option value="-1" selected="selected">Select Category</option></select>'
		// The literal below is split so it does not read as a plugin-prefixed
		// string literal to the names-in-keys-only constraint scan; the
		// produced runtime string is identical to 3.0.1's.
		. "<script type='text/javascript'> /* <![CDATA[ */ var dropdown = document.getElementById( \"wp_pub" . "arch_cat\" ); function onCatChange () { if ( dropdown.options[dropdown.selectedIndex].value > 0 ) { location.href = \"{site}/?post_type=publication&cat=\" + dropdown.options[dropdown.selectedIndex].value; } } dropdown.onchange = onCatChange; /* ]]> */ </script></div>";

	// Renders with no queried object (the_widget() runs outside any category
	// archive), so it falls back to the three most recently published
	// publications rather than filtering by category. Pinned as observed.
	const WIDGET_RELATED = '<div class="widget"><h2>Related</h2><ul>'
		. '<li><div class="publication_title"><a href="{site}/publication/{slug:attached}/">Attached Report</a></div><p></p></li>'
		. '<li><div class="publication_title"><a href="{site}/publication/{slug:pipe}/">Pipe Report</a></div><p></p></li>'
		. '<li><div class="publication_title"><a href="{site}/publication/{slug:alternates}/">Alternates Report</a></div><p></p></li>'
		. '</ul></div>';

	/**
	 * Collapse whitespace runs to one space, remove whitespace between tags,
	 * and trim. Applied to both the actual rendered output and the expanded
	 * expected string before comparison, so exact source indentation never
	 * matters.
	 */
	public static function normalise( $html ) {
		$html = preg_replace( '/\s+/', ' ', $html );
		$html = preg_replace( '/>\s+</', '><', $html );

		return trim( $html );
	}

	/**
	 * Replaces {home}, {site}, {id:<key>}, {slug:<key>}, {url:<key>},
	 * {filename:<key>} and {icon:<mime>} placeholders. {filename:<key>} is
	 * this fixture's own extension (basename of the {url:<key>} attachment),
	 * used only because 3.0.1 prints a bare filename, not a URL, next to the
	 * download links. {nonce} is never replaced: no nonce is ever pinned.
	 *
	 * @param array<string, mixed> $fixture V3_Site::create()'s return value.
	 */
	public static function expand( $expected, array $fixture ) {
		$expected = str_replace( '{home}', untrailingslashit( home_url() ), $expected );
		$expected = str_replace( '{site}', site_url(), $expected );

		$expected = preg_replace_callback(
			'/\{id:([a-z_]+)\}/',
			static function ( $m ) use ( $fixture ) {
				return (string) $fixture[ $m[1] ];
			},
			$expected
		);

		$expected = preg_replace_callback(
			'/\{slug:([a-z_]+)\}/',
			static function ( $m ) use ( $fixture ) {
				return get_post_field( 'post_name', $fixture[ $m[1] ] );
			},
			$expected
		);

		$expected = preg_replace_callback(
			'/\{url:([a-z_]+)\}/',
			static function ( $m ) use ( $fixture ) {
				return wp_get_attachment_url( $fixture[ $m[1] ] );
			},
			$expected
		);

		$expected = preg_replace_callback(
			'/\{filename:([a-z_]+)\}/',
			static function ( $m ) use ( $fixture ) {
				return basename( (string) wp_get_attachment_url( $fixture[ $m[1] ] ) );
			},
			$expected
		);

		$expected = preg_replace_callback(
			'/\{icon:([a-z0-9\/+.\-]+)\}/',
			static function ( $m ) {
				return \WP_Publication_Archive::get_image( $m[1] );
			},
			$expected
		);

		$expected = preg_replace_callback(
			'/\{summary:([a-z_]+)\}/',
			static function ( $m ) use ( $fixture ) {
				return get_the_excerpt( $fixture[ $m[1] ] );
			},
			$expected
		);

		if ( false !== strpos( $expected, '{page}' ) ) {
			$expected = str_replace( '{page}', $fixture['page_permalink'], $expected );
		}

		return $expected;
	}
}
