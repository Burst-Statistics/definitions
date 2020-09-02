<?php
/**
 * Plugin Name: Definitions
 * Plugin URI: https://www.really-simple-plugins.com
 * Description: Plugin to autoreplace in the content of a page or post every instance of a word that is defined in the definitions 
 * Version: 1.0.0
 * Text Domain: definitions
 * Domain Path: /lang
 * Author: Hidde Nauta, Rogier Lankhorst
 * Author URI: https://www.really-simple-plugins.com
 * License: GPL2
 */

/*  Copyright 2014  Really Simple Plugins  (email : support@really-simple-plugins.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    rldh: Rogier Lankhorst Definitions hyperlinks
*/

defined('ABSPATH') or die("you do not have acces to this page!");

if ( ! function_exists( 'wpdef_activation_check' ) ) {
	/**
	 * Checks if the plugin can safely be activated, at least php 5.6 and wp 4.6
	 *
	 * @since 1.0.0
	 */
	function wpdef_activation_check() {
		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Complianz GDPR cannot be activated. The plugin requires PHP 5.6 or higher',
				'definitions' ) );
		}

		global $wp_version;
		if ( version_compare( $wp_version, '4.6', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Complianz GDPR cannot be activated. The plugin requires WordPress 4.6 or higher',
				'definitions' ) );
		}
	}

	register_activation_hook( __FILE__, 'wpdef_activation_check' );
}

if ( ! class_exists( 'DEFINITIONS' ) ) {
	class DEFINITIONS {
		public static $instance;
		public static $post_type;
		public static $text_parser;
		public static $shortcode;
		public static $review;
		public static $widget;


		private function __construct() {
			self::setup_constants();
			self::includes();
			self::hooks();

			self::$post_type         = new wpdef_posttype();
			self::$text_parser       = new wpdef_text_parser();
			self::$shortcode         = new wpdef_shortcode();
			if ( is_admin() ) {
				self::$review        = new wpdef_review();
			}
		}

		/**
		 * Setup constants for the plugin
		 */

		private function setup_constants() {

			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$plugin_data = get_plugin_data( __FILE__ );

			define( 'WPDEF_URL', plugin_dir_url( __FILE__ ) );
			define( 'WPDEF_PATH', plugin_dir_path( __FILE__ ) );
			define( 'WPDEF_PLUGIN', plugin_basename( __FILE__ ) );
			$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : '';
			define( 'WPDEF_VERSION', $plugin_data['Version'] . $debug );
			define( 'WPDEF_PLUGIN_FILE', __FILE__ );
			define( 'DEFINITIONS_COUNT', 5 );
		}

		/**
		 * Instantiate the class.
		 *
		 * @return DEFINITIONS
		 * @since 1.0.0
		 *
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance )
			     && ! ( self::$instance instanceof COMPLIANZ )
			) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function includes() {
			require_once( WPDEF_PATH . 'post_type.php' );
			require_once( WPDEF_PATH . 'text_parser.php' );
			require_once( WPDEF_PATH . 'shortcode.php' );
			require_once( WPDEF_PATH . 'widget.php' );

			if ( is_admin() ) {
				require_once( WPDEF_PATH . 'review.php' );
			}
		}

		private function hooks() {

		}
	}

	/**
	 * Load the plugins main class.
	 */
	add_action(
		'plugins_loaded',
		function () {
			DEFINITIONS::get_instance();
		},
		9
	);
}

if ( ! function_exists( 'wpdef_set_activation_time_stamp' ) ) {
	/**
	 * Set an activation time stamp
	 *
	 * @param $networkwide
	 */
	function wpdef_set_activation_time_stamp( $networkwide ) {
		update_option( 'wpdef_activation_time', time() );
	}

	register_activation_hook( __FILE__, 'wpdef_set_activation_time_stamp' );

}


