<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if (!class_exists('wpdef_posttype')) {
	class wpdef_posttype {
		public function __construct() {
			add_action( 'init', array( $this, 'create_definitions_post_type' ) );
			add_action( 'init', array( $this, 'register_definitions' ) );
		}

		public function register_definitions() {
			register_taxonomy(
				'definitions_title',
				array('definition'),
				array(
					'label' => __( 'Definition Title', 'definitions'),
					'publicly_queryable' => false,
					'hierarchical' => false,
					'show_ui' => true,
					'show_in_nav_menus' => true,
					'show_in_rest' => false,
					'rewrite' => array( 'slug' => 'definitions_title' ),
				)
			);
		}

		public function create_definitions_post_type() {
			$labels   =  array(
				'name'               => __( 'Definitions', 'definitions' ),
				'singular_name'      => __( 'Definition', 'definitions' ),
				'add_new'            => __( 'New definition', 'definitions' ),
				'add_new_item'       => __( 'Add new definition', 'definitions' ),
				'parent_item_colon'  => __( 'definition', 'definitions' ),
				'parent'             => __( 'definition parentitem', 'definitions' ),
				'edit_item'          => __( 'Edit definition', 'definitions' ),
				'new_item'           => __( 'New definition', 'definitions' ),
				'view_item'          => __( 'View definition', 'definitions' ),
				'search_items'       => __( 'Search definitions', 'definitions' ),
				'not_found'          => __( 'No definitions found', 'definitions' ),
				'not_found_in_trash' => __( 'No definitions found in trash', 'definitions' ),
			);
			$rewrite  = array(
				'slug'  => __( 'definitions', 'definitions' ),
				'pages' => true
			);
			$supports =  array(
				'title',
				'editor',
				'thumbnail',
				'revisions',
				'page-attributes',
				'excerpt'
			);

			$args = array(
				'labels'              => $labels,
				'public'              => true,
				'exclude_from_search' => false,
				'show_ui'             => true,
				'show_in_admin_bar'   => true,
				'rewrite'             => $rewrite,
				'menu_position'       => 5,
				'menu_icon'           => 'dashicons-lightbulb',
				'supports'            => $supports,
				'has_archive'         => false,
				'hierarchical'        => true,
				'taxonomies'            => array('definitions_title'),
			);

			register_post_type( 'definition', $args );
		}

	}
}