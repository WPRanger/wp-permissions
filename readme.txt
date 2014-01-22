=== WP Upload Permissions ===
Contributors: wpranger
Tags: uploads, permissions, media
Requires at least: 3.7.0
Tested up to: 3.8.0
Stable tag: stable
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Lists the WordPress uploads directory file permissions.  

The purpose of the plugin is to provide a visual check of the currrent file 
and directory permissions of a WordPress installation. It's a read-only tool 
and does not perform any physical changes to your site or files. 

Use it to quickly debug file permission issues from the convenience of your
WordPress dashboard. Use FTP or SSH (or even cPanel) to actually change 
things. 

Watch this video for step by step instructions on how to use FTP to change 
file permissions. It features me and my northern voice.

[Change File Permissions with FTP](https://vimeo.com/81014108 "Changing File Permisions")

== Features ==

* Recursively lists the directory tree of a WordPress site
* Results are tabulated and can be filtered
* Lists just directories or can include files
* Automatic plugin updates (if downloaded from the WPRanger site)

__Untested on Windows server, I don't have access to one__

I suspect it may not behave.

== Installation ==

1. Upload the unzipped plugin directory to your WordPress site 
2. Alternatively, simply upload the zip file  tthe WordPress plugin page
3. Activate
4. Link to the plugin can be found in Dashboard -> Media menu

== Screenshots ==

1. The lovely table as displayed in the WordPress Dashboard. 

== Frequently Asked Questions ==

* Nobody asked a question yet

== Changelog ==

= 0.7.3 =
* Changed the order of the table headers to Read - Write
* Check for is_readable improved

= 0.7.2 =
* Typo fix

= 0.7.1 =
* Moved menu entry to media.  Probably a more logical place for it

= 0.7.0
* Added TableTools to Datatables for download of tables

= 0.6.2 =
* Had to add closing PHP statement because of update plugin 

= 0.6.1 =
* Changed to using default DataTables CSS + Images

= 0.6.0 =
* Can now optionally load just directories or both files and directories

= 0.5.0 =
* Added Owner and Group info (internal release)

= 0.4.0 =
* INITIAL RELEASE: WordPress Upload Permissions is released upon the world.

= 0.3.0 =
* Development Version

= 0.2.0 =
* Development Version

= 0.1.0 =
* Development Version 

