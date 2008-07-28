=== Script Compressor ===
Contributors: Regen
Tags: compress, javascript, css
Requires at least: 2.5
Tested up to: 2.6
Stable tag: 1.4.1

This plugin compresses javascript files and css files.

== Description ==

This plugin compresses javascript files and CSS files loaded by the theme or other plugins automatically.
Extra spaces, lines, and comments will be deleted.
The compressor is based on [jscsscomp](http://code.google.com/p/jscsscomp/).

= Features =

*   Auto-compression for wp_head()
*   Template tags which provide javascript compression
*   You can turn on/off compressions in the admin page
*   Editable CSS compression condition

== Installation ==

1. Upload the extracted plugin folder and contained files to your /wp-content/plugins/ directory
2. Give the write permission to /wp-content/plugins/script-compressor/jscsscomp/cache
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to Settings -> Script Compressor

== Frequently Asked Questions ==

= CSS does not work =

After opening CSS directly by your browser, do super-reloading (Ctrl+F5).

== Screenshots ==

1. A part of the admin page.
