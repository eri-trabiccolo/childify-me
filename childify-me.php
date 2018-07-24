<?php
/**
 * Plugin Name: Childify Me
 * Plugin URI: https://github.com/eri-trabiccolo/childify-me
 * Description: Create a child theme from the Theme Customizer panel
 * Version: 1.2.0
 * Author: Rocco Aliberti
 * Author URI: https://github.com/eri-trabiccolo
 * Text Domain: childify-me
 * Domain Path: /lang
 * License: GPL2+
 *
 * @package Childify-Me
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main function responsible for returning the one ChildifyMe Instance to functions everywhere.
 *
 * @return Childify_Me|null The Childify-Me instance.
 */
function cm_childifyme() {
	return Childify_Me::instance();
}


require dirname( __FILE__ ) . '/class-childify-me.php';
cm_childifyme();
