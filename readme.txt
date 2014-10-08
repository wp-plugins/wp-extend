=== Plugin Name ===
Contributors: djquinn
Donate link: http://dquinn.net
Tags: plugin, admin, posts, custom post type, custom taxonomy, custom
Requires at least: 3.6.1
Tested up to: 4.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A developer-centric framework for creating custom post types, taxonomies, metaboxes, options pages and more.

== Description ==

WP Extend (WPX) is a framework that makes it easier to use WordPress as a CMS. It tries to bridge the gap between WordPress' native ability to make custom post types, taxonomies, options pages, and metaboxes and the Dashboard, by providing a GUI interface for developers to work with outside of templates. It also provides a library of commonly used functions geared toward CMS architecture. 

What can you do with WPX?

*   Make **custom post types** in the Dashboard (and extend Posts & Pages)
*   Make **custom taxonomies** in the Dashboard (and extend Tags & Categories)
*   Group **custom fields into metaboxes** in the Dashboard
*   Assign **custom fields** to post types, taxonomies, or options pages in the Dashboard
*   Create your own **custom field types** to use in WPX 
*   Make **options pages** in the Dashboard
*   Access a **library of common functions** for CMS theme building
* 	Use the **WPX API** to programmatically create custom content outside the Dashboard

What custom field types does WPX provide out-of-box?

* Checkbox
* File Upload
* Image Upload
* Gallery
* Post
* Term
* Text
* Textarea
* Visual Editor
* User

*Post, Term, and User allow users to pick one or multiple posts or terms to associate with a post.* 

How does it work?

WPX registers several "core" post types to use as an interface for registering post types, taxonomies, and options pages in the Dashboard, then loops through the posts of those types to register the custom content. The plugin makes use of transients to store data so as to avoid creating a performance drain as the data is used to register new structures in the Dashboard. The plugin does not create new database tables, and the interface you use to set up your custom content is made up of the same elements as the rest of the Dashboard. With the exception of saving serialized options to store term and options page metadata, WPX doesn't use any structures that WordPress doesn't already use to store data. It also cleans up transients only when a post from a core WPX post type is saved, and all its data can be completely flushed from an installation via its uninstall option in the Settings page.

Last but not least...

WPX needs your help. Please contribute, refactor, and add new features.

https://github.com/alkah3st/wpx

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `/wpx/` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it!

== Frequently Asked Questions ==

= How do I do XYZ? =

Check out the documentation here: https://github.com/alkah3st/wpx/wiki

= Where is the support forum? =

Log all your bugs and feature requests here: https://github.com/alkah3st/wpx/issues

== Screenshots ==

1. The WPX settings page.
2. Edit screen for a custom post type. 
3. Edit screen for a meta field. 

== Changelog ==

= 1.0.2 =
* Fixed link to local environment in admin.php. Ignored Thumbs.db.

= 1.0.1 =
* Removed extraneous files from beta version.

= 1.0.0 =
* First stable version.