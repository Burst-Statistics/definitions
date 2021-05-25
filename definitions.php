<?php
/**
 * Plugin Name: Definitions - Internal Linkbuilding
 * Plugin URI: https://www.really-simple-plugins.com
 * Description: Automatically replace your posts' keywords on your website with an internal link and tooltip. 
 * Version: 1.0.0.3
 * Text Domain: definitions
 * Domain Path: /lang
 * Author: Really Simple Plugins
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

    Rogier Lankhorst Definitions - Internal Linkbuilding
*/

defined('ABSPATH') or die("you do not have acces to this page!");

if ( ! function_exists( 'rspdef_activation_check' ) ) {
	/**
	 * Checks if the plugin can safely be activated, at least php 5.6 and wp 4.6
	 *
	 * @since 1.0.0
	 */
	function rspdef_activation_check() {
		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Definitions - Internal Linkbuilding cannot be activated. The plugin requires PHP 5.6 or higher',
				'definitions' ) );
		}

		global $wp_version;
		if ( version_compare( $wp_version, '4.6', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Definitions - Internal Linkbuilding cannot be activated. The plugin requires WordPress 4.6 or higher', 'definitions' ) );
		}
	}

	register_activation_hook( __FILE__, 'rspdef_activation_check' );
}

if ( ! class_exists( 'DEFINITIONS' ) ) {
	class DEFINITIONS {
		public static $instance;
		public static $post_type;
		public static $text_parser;
		public static $review;
		public static $widget;
		public static $tour;
		public static $target_post_types;
		public static $source_post_types;


		private function __construct() {
			self::setup_constants();
			self::includes();
			self::hooks();
			self::$target_post_types = apply_filters('rspdef_target_post_types', array('page','post'));
			self::$source_post_types = apply_filters('rspdef_source_post_types', array('post'));
			self::$post_type         = new rspdef_posttype();
			self::$text_parser       = new rspdef_text_parser();
			if ( is_admin() ) {
				self::$review        = new rspdef_review();
                self::$tour          = new rspdef_tour();
			}
		}

		/**
		 * Setup constants for the plugin
		 */

		private function setup_constants() {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$plugin_data = get_plugin_data( __FILE__ );

			define( 'RSPDEF_URL', plugin_dir_url( __FILE__ ) );
			define( 'RSPDEF_PATH', plugin_dir_path( __FILE__ ) );
			define( 'RSPDEF_PLUGIN', plugin_basename( __FILE__ ) );

			/**
			 * SQL does not support negative look aheads and negative lookbehinds. So we have to use a broader match
			 */

			$start = ' \>';//string can start with either a tag (>) or a space
			$end = '\n\< \,\.\;\!\?';//string can end with any punctuation, space, break, or tag
			define( 'RSPDEF_PATTERN_SQL', "(.*[^<]*[$start]({definition})[$end])|([$start]({definition})[".$end."][^<].*)" );

			/**
			 * With PHP we can use a negative lookbehind and lookahead
			 *
			 * Not between a b h1-9
			 * between space, punctuation, p or div tag
			 */

			define( 'RSPDEF_PATTERN_PHP', "/((?!<(a|b|h[1-9])[^>]*?>)(?:^|(<div.*>)|(<p.*>)|\s)({definition})(?:\s|\.|\,|\?|\!|(<\/p>)|(<\/div>))(?![^<]*?<\/(a|b|h[1-9])>))/i" );

			/**
			 * This is the matching group that contains our match.
			 */
			define( 'RSPDEF_PATTERN_PHP_MATCHING_GROUP', 5 );

			$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : '';
			define( 'RSPDEF_VERSION', $plugin_data['Version'] . $debug );
			define( 'RSPDEF_PLUGIN_FILE', __FILE__ );
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
			require_once( RSPDEF_PATH . 'post_type.php' );
			require_once( RSPDEF_PATH . 'text_parser.php' );

			if ( is_admin() ) {
				require_once( RSPDEF_PATH . 'review.php' );
                require_once( RSPDEF_PATH . 'shepherd/tour.php' );
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

if ( ! function_exists( 'rspdef_set_activation_time_stamp' ) ) {
	/**
	 * Set an activation time stamp
	 *
	 * @param $networkwide
	 */
	function rspdef_set_activation_time_stamp( $networkwide ) {
		update_option( 'rspdef_activation_time', time() );
	}

	register_activation_hook( __FILE__, 'rspdef_set_activation_time_stamp' );

}



if ( ! function_exists( 'rspdef_start_tour' ) ) {
    /**
     * start tour for plugin
     */
    function rspdef_start_tour(){
        if (!get_site_option('rspdef_tour_shown_once')){
            update_site_option('rspdef_tour_started', true);
        }
    }

    register_activation_hook( __FILE__, 'rspdef_start_tour' );
}
