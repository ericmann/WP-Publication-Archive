=== WP Publication Archive ===
Contributors: ericmann
Donate link: http://jumping-duck.com/wordpress/plugins
Tags: document management, pdf, doc, archive
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 2.5
License GPLv2

Allows users to upload, manage, search, and download publications, documents, and similar content (PDF, Power-Point, etc.).

== Description ==

WP Publication Archive adds a custom content type for storing, tagging, and categorizing downloadable content external to
standard WordPress posts and pages.  You can add downloadable PDF files, Word documents, and PowerPoint presentations.
These files will be stored in the standard WordPress uploads directory but will be managed separately through a custom
post type interface in the WordPress admin area.

*This plugin requires PHP5*

== Installation ==

1. Upload the entire `wp-publication-archive` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the "Add Publications" menu to begin adding publications.
1. Use the built-in menu system to manage your publications.

== Frequently Asked Questions ==

= How do I upload a new file? =

Use the built-in media uploader on the "Add Publication" screen.  This is a change from previous versions where you could manually specify the full URL of the attachment.  That format exposed your site to a potential security vulnerability and has been removed.

= How do I list my publications? =

You can display a list of publications either by includeing the [wp-publication-archive] shortcode on a post/page or by placing <?php wp_publication_archive(); ?> in your theme template files.  WordPress will render your publication list automatically.

= Can I filter the list by category =

Yes!  Just include `categories="cat-1,cat2"` in your shortcode where "cat-1" and "cat-2" are the *slugs* of the categories you want to display.

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

== Screenshots ==

No screenshots are available at this time.

== Changelog ==

= 2.5 =
* Removed antiquated openfile.php (allow direct file downloads)
* Add advanced search shortcode

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