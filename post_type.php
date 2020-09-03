<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if (!class_exists('wpdef_posttype')) {
	class wpdef_posttype {
		public function __construct() {

			add_action( 'init',
				array( $this, 'create_definitions_post_type' ) );

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

	}
}