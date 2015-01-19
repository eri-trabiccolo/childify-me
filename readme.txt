=== Childify Me ===
Contributors: d4z_c0nf
Author URI: https://github.com/eri-trabiccolo/
Plugin URI: https://github.com/eri-trabiccolo/childify-me
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J8TFWAWQ8U3DN
Tags: child, theme, themes
Requires at least: 3.4
Tested up to: 4.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create a child-theme from the Theme Customizer.

== Description ==

**Create child themes from any non-child theme directly from the Theme Customizer panel.**

Works also when previewing a theme before activation!

Multisite compatible.

= Plugin Features =

Ftp credential requests handled in the Customizer.
**Child-theme's screenshot generated** from the parent screenshot, with a ribbon dynamically added.
Child-theme's author dynamically generated from the user Name and Last Name, if set, otherwise falls back on the username.

= Translations =

The plugin is [translation ready](http://codex.wordpress.org/Translating_WordPress), the default .mo and .po files are inluded in /lang.

= Credits =

* Thanks to [@nikeo](http://themesandco.com) for spurring me, testing my plugin and for his valuable advices
* Thanks to [@rdellconsulting](http://www.rdellconsulting.com) for testing my plugin even on multisite and for his valuable advices
* Image child.png generated on http://oojits.com/info/img/corner-ribbons/

== Installation ==

1. Install the plugin right from your WordPress admin in plugins > Add New. 
1-bis. Download the plugin, unzip the package and upload it to your /wp-content/plugins/ directory
2. Activate the plugin
3. Go to Appearance -> Customize and you'll see the "Childify Me" button. 
3-bis. In Appearance -> Themes, hover on a non-child theme and click on Live Preview, once there you'll see the "Childify Me" button

== Frequently Asked Questions ==

= I'm in Appearance -> Customize but I cannot find the "Childify Me" button, why? =

Is your theme a non-child theme? Childify Me is designed to make child themes only for non-child themes.

= I created a child theme from Preview, but when I back to Appearance -> Themes I cannot see it, why? =

Please, refresh the page.

= How can I create a child-theme in multisite? =

Go to Appearance -> Customize (or to Appearance -> Themes, if you want to create a child-theme of a
non-active theme, hover on a non-child theme and click on Live Preview)


== Screenshots ==

1. Childify Me creating a child-theme of Customizr-Pro's from the Customizer
2. Childify Me asking for ftp credentials (if you don't have direct access to the filesystem)
3. Childify Me succesfully created your child-theme - Yor child-theme in Appearance -> Themes
4. Childify Me in multisite environment creating a child-theme of Twenty Twelve's from Live Preview

== Changelog ==
= 1.0.2 : January 19, 2018 =
* Fix: cancel button bug, css form padding 

= 1.0.1 : January 19, 2018 =
* Fix: improve compatibility with PHP versions < 3.5.3
* Fix: css, change some padding

= 1.0.0 : January 03, 2015 =
* First offical release!
