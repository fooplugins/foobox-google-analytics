<?php
/**
 * FooBox Google Analytics Extension
 *
 * Integrates FooBox with Google Analytics image tracking
  *
 * @package   foobox-google-analytics
 * @author    Brad Vincent <brad@fooplugins.com>
 * @license   GPL-2.0+
 * @link      https://github.com/fooplugins/foobox-google-analytics
 * @copyright 2013 FooPlugins LLC
 *
 * @wordpress-plugin
 * Plugin Name: FooBox Google Analytics Extension
 * Plugin URI:  https://github.com/fooplugins/foobox-google-analytics
 * Description: Allows you to track Google Analytics events when viewing images in FooBox
 * Version:     1.0.0
 * Author:      bradvin
 * Author URI:  http://fooplugins.com
 * Text Domain: foobox
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//include plugin class
require_once( plugin_dir_path( __FILE__ ) . 'class-foobox-google-analytics.php' );

//run it baby!
FooBox_Extension_For_Google_Analytics::get_instance();