=== WP Publication Archive ===
Contributors: ericmann
Donate link: http://jumping-duck.com/wordpress/plugins
Tags: document management, pdf, doc, archive
Requires at least: 3.5
Tested up to: 3.6
Stable tag: 3.0.1
License: GPLv2

Allows users to upload, manage, search, and download publications, documents, and similar content (PDF, Power-Point, etc.).

== Description ==

WP Publication Archive adds a custom content type for storing, tagging, and categorizing downloadable content external to standard WordPress posts and pages.  You can add downloadable PDF files, Word documents, and PowerPoint presentations. These files will be stored in the standard WordPress uploads directory but will be managed separately through a custom post type interface in the WordPress admin area.

== Installation ==

1. Upload the entire `wp-publication-archive` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the "Add Publications" menu to begin adding publications.
1. Use the built-in menu system to manage your publications.

== Frequently Asked Questions ==

= How do I upload a new file? =

**Option 1:** Use the built-in media uploader on the "Add Publication" screen.

**Option 2:** Upload a file to your server via FTP (or to a remote host like AWS) and place the *full* URL (including the `http://`) in the Publication field on the Edit Publication page.

= How do I list my publications? =

You can display a list of publications either by includeing the [wp-publication-archive] shortcode on a post/page or by placing <?php wp_publication_archive(); ?> in your theme template files.  WordPress will render your publication list automatically.

= Can I show publications in a dropdown rather than as a list? =

Yes!  Simply add `showas="dropdown"` as a parameter within the regular shortcode, and the plugin will use a dropdown template rather than a list template.  For example:

    [wp-publication-archive showas="dropdown" /]

= Can I filter the list by category =

Yes!  Just include `categories="cat1,cat2"` in your shortcode where "cat1" and "cat2" are the *names* of the categories you want to display.

= Can I filter the list by author? =

Yes!  Just include `author="author-name"` in your shortcode where "author-name" is the *slug* of the author you want to display.

= What File Types are Available =

By default, the plug-in contains icons for several common file types.  The icons include:

- Standard document files
- Spreadsheet formats (i.e. Excel, Open Office Calc)
- Video
- Image
- Audio
- Adobe PDF
- Zip

All other file types will feature a generic "file" icon.

= Some of my files aren't downloading. What's wrong? =

There could be any of a hundred causes for this.  Most likely, your files are just too large to be handled by your server.

By default, both file opens and downloads are streamed to your server.  This means the plugin will attempt to open the file (whether it's remote or locally-hosted) and will stream the contents of the file to the browser.  This has the advantage of never exposing the raw download URL to the user.*

Unfortunately, it means your server has to download the file first before it can pass it along to the user.  For smaller files, this isn't an issue.  But for much larger files, connections can time out.

If you are serving large files, you can force the **file open** URL to forward connections rather than stream them.  This means requests to your file open URL (i.e. `http://site.com/publication/title/wppa_open`) will receive a 303 "See Other" redirect pointing them to the original resource.  It's less work for your server, but the end user *will* see the original URL.

Just add `add_filter( 'wppa_mask_url', '__return_false' );` to your `functions.php` file to turn off URL masking and use the redirect method instead.

When you add this filter, the **file download** URL will behave the exact same way - redirecting requests to the original resource rather than streaming the file to the browser.  Unfortunately, this leaves the exact behavior of the link up to the browser - some will attempt to download the file, some will open it instead.

* A future version of the plugin will allow you to have password-protected downloads. Hiding the raw URL of a file is important for this feature to work.

= How do I use the thumbnail feature? =

Thumbnail support is extended through the plugin, but not actually implemented in any templates.  You can upload a custom thumbnail for each Publication, and the URL will be stored in the Publication's meta field in the database under the `wpa-upload_image` key.

You can get the raw thumbnail URL by requesting it directly with `get_post_meta()`.  Alternatively, the `WP_Publication_Archive_Item` class contains some useful helper methods for printing the thumbnail URL wrapped in proper HTML tags.  You can use code similar to the following:

    <?php
    $pub = get_post( $publication_id );
    $pub = new WP_Publication_Archive_Item( $pub->ID, $pub->post_title, $pub->post_date );

    // Return the Publication thumbnail for use elsewhere
    $thumb = $pub->get_the_thumbnail();

    // Echo/print the thumbnail to the browser
    $pub->the_thumbnail();

These helper methods will generate HTML tags similar to:

    <div class="publication_thumbnail">
        <img src="http://site.com/thumbnail.png" />
    </div>

Also, the actual thumbnail URL printed will be passed through a filter.  You can intercept this and do whatever you need to with the URL (add query arguments, switch http/https protocols, etc).

    add_filter( 'wpa-upload_image', 'your_custom_function', 10, 2 );

The filter passes two arguments, the raw URL for the thumbnail and the ID of the publication.

= Why are some files downloaded even though I clicked "open?" =

The open behavior is 100% dependent on your browser.  In some cases (i.e. PDFs) the file will open in the browser using a built-in viewer.  In other cases (i.e. Zips) there is no in-browser viewer for that file type, so the browser will download the file instead.

The plugin makes every attempt possible to return the correct MIME type for each file so that the browser know what to do with it.  But in some cases (i.e. Zips or unknown file types), the plugin will return a type of `application/octet-stream` which the browser sees as a generic file and just downloads to a generic, often extensonless, filename.

If you're coming up against a situation like this, I recommend you use the Download link instead.

== Screenshots ==

No screenshots are available at this time.

== Changelog ==

= 3.0.1
* Revert the dropdown template's file opening functionality to auto-open the file

= 3.0 =
* Add publication landing pages (with template)
* Add publication archive pages (with template)
* Update streaming methods to use `readfile()`
* Allow category archives for publications
* Add category count widget for publications
* Add related publication sidebar widget
* Update content field to store summary in post content

= 2.5.7.2 =
* Fix a short PHP open tag.

= 2.5.7.1 =
* Hotfix to correct a search term issue.

= 2.5.7 =
* Normalize PHP file endings to remove extra whitespace.

= 2.5.6 =
* Fix a data sanitation but that was mistakenly removing links from publication summaries.

= 2.5.5 =
* Fix a broken include path for the Widget.
* Allow targetting a blank tab/window with the `wp_pubarch_open_in_blank` filter.

= 2.5.4 =
* Convert `wppa_` function and translation prefix to `wp_pubarch_` to avoid a conflict with WP Photo Album +.
* Update strings

= 2.5.3 =
* Re-add a deprecated tag that I thought no one was actually using.

= 2.5.2 =
* Fix an issue caused by unnecessary whitespace.

= 2.5.1 =
* Add some checking to prevent All-in-One Event Calendar from triggering a warning with an improper filter call.

= 2.5 =
* Removed antiquated openfile.php (allow direct file downloads).
* Made the publication list template-ready.
* Change the "download" link to a pair of "download" or "open" links.
* Included publication description in WordPress search.
* Enable URL masking for file downloads.

= 2.3.4 =
* Add thumbnail support

= 2.3.3 =
* Immediate fix to a typo

= 2.3.2.1 =
* Add "author" filter to shortcode

= 2.3.2 =
* Fixes pagination bug
* Fixes issue where some installations could not load files starting with "http://"

= 2.3.1 =
* URGENT SECURITY UPDATE
* Fix some outstanding bugs

= 2.3 =
* Add publications to standard WordPress search results

= 2.2 =
* Add pagination for more than 10 publications
* Add category filter to shortcode

= 2.1.1 =
* Remove an extra space that was throwing a PHP parsing error

= 2.1.0 =
* Remove erroneous CSS declaration from post/page edit screens
* Silence a $_POST[] error
* Fix a typo on displaying the list of archived files
* Adds simple pagination

= 2.0.1 =
* Fix PHP error in bootstrap function

= 2.0 =
* Rewrite plug-in to use custom post types rather than extra database tables
* Add new UI for adding publications
* Hard-code file type icons

= 1.1.1 =
* Minor repairs to abanoned script to make it compatible with WP>2.5

= 1.1 = 
* Original release of WP Publications Archive by Luis Lino

== Upgrade Notice ==

= 3.0 =
Please flush your permalinks by visiting the Settings >> Permalinks page in WordPress, otherwise your download links WILL NOT WORK.

= 2.5 =
Major changes have been made to the way publications are linked and downloaded. If you had previously changed any code for `openfile.php` or the linking/downloading mechanism, be prepared to manually update your Publications should any of them break.

= 2.3.1 =
URGENT SECURITY UPDATE!!!

= 2.2 =
This version will *only* work with PHP 5 or above!

= 2.0 =
Upgrading from 1.1 or 1.1.1 to 2.0 will *not* automatically transfer your publications to the new system.  

== Licenses ==

WP Publication Archive is licensed under the GNU General Public License, version 2.

This system was based on the original wp-publications-archive plug-in published by Luis Lino and Siemens Networks, S.A. at http://code.google.com/p/wp-publications-archive/.

The filing cabinet menu icon was created by Glyphish (http://glyphish.com) and is distributed under a Creative Commons Attribution license.

Filetype icons come from the Crystal Project (http://www.everaldo.com/crystal/) released under the LGPL (http://www.everaldo.com/crystal/?action=license).