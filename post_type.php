<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if (!class_exists('wpdef_posttype')) {
	class wpdef_posttype {
		public $plugin_url;
		const tooltip_html = '<a href="rldh_REPLACE_URL" class="rldh_definition" data-toggle="tooltip" title="rldh_REPLACE_TITLE">rldh_REPLACE_LINK</a>';
		const default_nr_of_definitions = 5;//used for the default nr of defs in the widget
		const max_nr_of_columns_in_shortcode_list = 12;

		public function __construct() {
			$this->plugin_url = trailingslashit( WP_PLUGIN_URL )
			                    . trailingslashit( dirname( plugin_basename( __FILE__ ) ) );
			add_action( 'init', array( $this, 'load_translation' ) );
			add_action( 'wp_enqueue_scripts',
				array( $this, 'enqueue_assets' ) );
			add_action( 'init',
				array( $this, 'create_definitions_post_type' ) );
			add_filter( 'the_content',
				array( $this, 'replace_definitions_with_links' ) );
			add_shortcode( 'rldh-definitions-list',
				array( $this, 'add_shortcode' ) );
		}

		public function load_translation() {
			load_plugin_textdomain( 'definitions', false,
				dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		}

		public function enqueue_assets() {
			wp_register_style( 'rldh-tooltipcss',
				$this->plugin_url . 'css/tooltip.css' );
			wp_enqueue_style( 'rldh-tooltipcss' );
			wp_enqueue_script( "rldh-tooltipjs",
				$this->plugin_url . "js/tooltip.js", array( 'jquery' ), '1.0.0',
				true );
			wp_enqueue_script( "rldh-js", $this->plugin_url . "js/main.js",
				array( 'jquery' ), '1.0.0', true );
		}

		public function create_definitions_post_type() {
			$labels   = apply_filters( 'rldh_post_type_labels', array(
				'name'               => __( 'Definitions',
					'definitions' ),
				'singular_name'      => __( 'definition',
					'definitions' ),
				'add_new'            => __( 'New definition',
					'definitions' ),
				'add_new_item'       => __( 'Add new definition',
					'definitions' ),
				'parent_item_colon'  => __( 'definition',
					'definitions' ),
				'parent'             => __( 'definition parentitem',
					'definitions' ),
				'edit_item'          => __( 'Edit definition',
					'definitions' ),
				'new_item'           => __( 'New definition',
					'definitions' ),
				'view_item'          => __( 'View definition',
					'definitions' ),
				'search_items'       => __( 'Search definitions',
					'definitions' ),
				'not_found'          => __( 'No definitions found',
					'definitions' ),
				'not_found_in_trash' => __( 'No definitions found in trash',
					'definitions' ),
			) );
			$rewrite  = array(
				'slug'  => __( 'definitions', 'definitions' ),
				'pages' => true
			);
			$supports = apply_filters( 'rldh_post_type_support', array(
				'title',
				'editor',
				'thumbnail',
				'revisions',
				'page-attributes',
				'excerpt'
			) );

			$args = apply_filters( 'rldh_post_type_args', array(
				'labels'              => $labels,
				'public'              => true,
				'exclude_from_search' => false,
				'show_ui'             => true,
				'show_in_admin_bar'   => true,
				'rewrite'             => $rewrite,
				'menu_position'       => 5,
				'menu_icon'           => 'dashicons-lightbulb',
				//plugins_url( 'css/images/menu-icon.png', dirname( __FILE__ ) ),
				'supports'            => $supports,
				'has_archive'         => false,
				'hierarchical'        => true
			) );

			register_post_type( 'definition', $args );
		}

		private function get_tooltip_link( $url, $link, $title ) {
			return apply_filters( 'rldh_tooltip_html', str_replace( array(
				"rldh_REPLACE_URL",
				"rldh_REPLACE_LINK",
				"rldh_REPLACE_TITLE"
			), array( $url, $link, $title ), self::tooltip_html ) );
		}

		public function replace_definitions_with_links( $content ) {
			$count          = 0;
			$content_length = strlen( $content );
			$unlikely_nr    = $content_length + 10;

			//find definitions in buffer
			$args = array(
				'post_type'        => 'definition',
				'post_status'      => 'publish',
				'numberposts'      => - 1,
				'suppress_filters' => true
			);

			$definitions = get_posts( apply_filters( 'rldh_definitions_query',
				$args ) );
			if ( $definitions ) {
				foreach ( $definitions as $definition ) {
					_log( $definition );
					//check if this post IS this definition, else skip to next definition
					if ( get_the_ID() != $definition->ID ) {
						$url              = get_permalink( $definition->ID );
						$name             = apply_filters( 'the_title', $definition->post_title );
						$excerpt          = apply_filters( 'the_excerpt', $definition->post_excerpt );
						//remove tags from excerpt
						$excerpt = strip_tags( $excerpt );
						//remove quotes
						$excerpt = str_replace( '"', "", $excerpt );
						$excerpt = str_replace( "'", "", $excerpt );
						if ( strlen( $excerpt ) > 0 ) {
							$excerpt = $excerpt . " ";
						}
						$excerpt .= __( 'Click for more information', 'definitions' );

						$excerpt_arr[]     = $excerpt;
						$placeholder       = $count . '_rldh_excerpt';
						$placeholder_arr[] = $placeholder;

						$link = $this:: get_tooltip_link( $url, $name, $placeholder );

						$pattern = '/(?![^<]*>)(wordpress)/i';
						$content = preg_replace( $pattern, $link, $content );

						$count ++;
					}
				}
			}

			if ( isset( $placeholder_arr ) ) {
				$content = str_replace( $placeholder_arr, $excerpt_arr,
					$content );
			}

			return apply_filters( 'rldh_replace_definitions', $content );
		}

	}
}