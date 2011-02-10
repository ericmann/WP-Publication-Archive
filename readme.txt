=== WP Publication Archive ===
Contributors: ericmann
Donate link: http://jumping-duck.com/wordpress
Tags: document management, pdf, doc, archive
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 2.1.0

Allows users to upload, manage, search, and download publications, documents, and similar content (PDF, Power-Point, etc.).

== Description ==

WP Publication Archive adds a custom content type for storing, tagging, and categorizing downloadable content external to standard WordPress posts and pages.  You can add downloadable PDF files, Word documents, and PowerPoint presentations.  These files will be stored in the standard WordPress uploads directory but will be managed separately through a custom post type interface in the WordPress admin area.

== Installation ==

1. Upload the entire `wp-publication-archive` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the "Add Publications" menu to begin adding publications.
1. Use the built-in menu system to manage your publications.

== Frequently Asked Questions ==

= How do I upload a new file? =

There are two ways to upload files:

1. Upload a file via FTP to any location on your server
1. Use the built-in media uploader on the "Add Publication" screen

Whether you use FTP or the built-in uploader, make sure you use the *full URL* of the file, otherwise WordPress won't know where to find it.

= How do I list my publications? =

You can display a list of publications either by includeing the [wp-publication-archive] shortcode on a post/page or by placing <?php wp_publication_archive(); ?> in your theme template files.  WordPress will render your publication list automatically.

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

= Why does the archive only list 10 files? =

We're still working on pagination ... so right now you can only list 10 files from the archive.  In the future, you will be able to list an arbitrary number of posts.

== Screenshots ==

No screenshots are available at this time.

== Changelog ==

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

= 2.0 =
Upgrading from 1.1 or 1.1.1 to 2.0 will *not* automatically transfer your publications to the new system.  

== Licenses ==

WP Publication Archive is licensed under the GNU General Public License, version 3.

This system was based on the original wp-publications-archive plug-in published by Luis Lino and Siemens Networks, S.A. at http://code.google.com/p/wp-publications-archive/.

The filing cabinet menu icon was created by Glyphish (http://glyphish.com) and is distributed under a Creative Commons Attribution license.

Filetype icons come from the Crystal Project (http://www.everaldo.com/crystal/) released under the LGPL (http://www.everaldo.com/crystal/?action=license).