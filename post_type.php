<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if (!class_exists('wpdef_posttype')) {
	class wpdef_posttype {
		public function __construct() {
			add_action( 'init', array( $this, 'create_definitions_post_type' ) );
			add_action( 'init', array( $this, 'register_definitions' ) );

			add_action( 'add_meta_boxes', array( $this, 'add_definitions_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_postdata_definitions' ) );

            add_action( 'admin_enqueue_scripts', array( $this, 'add_definitions_meta_box_script' ) );
		}

		public function register_definitions() {
            $labels   =  array(
                'name'                          => __( 'Definitions', 'definitions' ),
                'singular_name'                 => __( 'Definition', 'definitions' ),
                'add_new'                       => __( 'New definition', 'definitions' ),
                'add_new_item'                  => __( 'Add new definition', 'definitions' ),
                'parent_item_colon'             => __( 'definition', 'definitions' ),
                'parent'                        => __( 'definition parentitem', 'definitions' ),
                'edit_item'                     => __( 'Edit Bert', 'definitions' ),
                'new_item'                      => __( 'New definition', 'definitions' ),
                'view_item'                     => __( 'View definition', 'definitions' ),
                'search_items'                  => __( 'Search definitions', 'definitions' ),
                'not_found'                     => __( 'No definitions found', 'definitions' ),
                'not_found_in_trash'            => __( 'No definitions found in trash', 'definitions' ),
                'choose_from_most_used'         => __( '', 'definitions' ),
                'separate_items_with_commas'    => __( '', 'definitions' ),
            );

			register_taxonomy(
				'definitions_title',
				array('definition'),
				array(
					'label' => __( 'Definition Title', 'definitions'),
					'labels' => $labels,
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
				'name'                          => __( 'Definitions', 'definitions' ),
				'singular_name'                 => __( 'Definition', 'definitions' ),
				'add_new'                       => __( 'New definition', 'definitions' ),
				'add_new_item'                  => __( 'Add new definition', 'definitions' ),
				'parent_item_colon'             => __( 'definition', 'definitions' ),
				'parent'                        => __( 'definition parentitem', 'definitions' ),
				'edit_item'                     => __( 'Edit Bert', 'definitions' ),
				'new_item'                      => __( 'New definition', 'definitions' ),
				'view_item'                     => __( 'View definition', 'definitions' ),
				'search_items'                  => __( 'Search definitions', 'definitions' ),
				'not_found'                     => __( 'No definitions found', 'definitions' ),
				'not_found_in_trash'            => __( 'No definitions found in trash', 'definitions' ),
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
				'taxonomies'          => array('definitions_title'),
			);

			register_post_type( 'definition', $args );
		}


		public function add_definitions_meta_box() {
            add_meta_box(
                'definitions_box_id',                 // Unique ID
                'Internal linkbuilding',      // Box title
                array( $this, 'definitions_meta_box_html' ),  // Content callback, must be of type callable
                'post'                            // Post type
            );
        }



        function add_definitions_meta_box_script( $hook ) {

            global $post;

            if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
                if ( 'post' === $post->post_type ) {
                    wp_register_style( 'wpdef-metabox', trailingslashit( WPDEF_URL ) . "assets/css/metabox.css", "", WPDEF_VERSION );
                    wp_enqueue_style( 'wpdef-metabox' );

                    //wp_enqueue_script( 'wpdef-metabox', WPDEF_URL . "assets/js/metabox.js", array( 'jquery' ), WPDEF_VERSION );

                    $minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

                    wp_enqueue_script( 'wpdef',
                        WPDEF_URL . "assets/js/metabox$minified.js", array('jquery'),
                        WPDEF_VERSION, true );

                    wp_localize_script(
                        'wpdef',
                        'wpdef',
                        array(
                            'url'=> admin_url('admin-ajax.php'),
                        )
                    );
                }
            }
        }


        public function definitions_meta_box_html( $post ) {
            $use_tooltip    = get_post_meta( $post->ID, 'definition_use_tooltip', true )    ? 'checked="checked"' : '';
            $disable_image  = get_post_meta( $post->ID, 'definition_disable_image', true )  ? 'checked="checked"' : '';
            $enable         = get_post_meta( $post->ID, 'definition_enable', true )         ? 'checked="checked"' : '';

            $all_definitions    = array_column( get_terms( 'definitions_title', array( 'orderby' => 'count', 'hide_empty' => 0 ) ), 'name');
            $definitions_string = implode( ',', $all_definitions );
            $post_count         = wp_count_posts()->publish;

            ?>
            <span class="dfn-comment">If you want to know all the possibilities with Bob the Linkbuilder, have a look at our documentation. <a>Read more</a></span>

            <h3>Settings</h3>
            <span class="dfn-comment">Limit terms to only one per post.</span>
            <?php $this->definition_tag_field( $post ); ?>
            <div class="dfn-field dfn-definition-add-notice">
                <div class="dfn-icon-bullet-invisible"></div><span class="dfn-comment">Add a term to see results</span>
            </div>
            <input type="hidden" class="dfn-existing-definitions" value="<?php echo $definitions_string ?>">

            <div class="dfn-field">
                <input type="hidden" class="dfn-use-tooltip" name="dfn-use-tooltip" value=""/>
                <input type="checkbox" class="dfn-use-tooltip" name="dfn-use-tooltip" <?php echo $use_tooltip ?>/>
                <label>Use Tooltip</label>
            </div>

            <div class="dfn-field">
                <input type="hidden" class="dfn-disable-image" name="dfn-disable-image" value=""/>
                <input type="checkbox" class="dfn-disable-image" name="dfn-disable-image" <?php echo $disable_image ?>/>
                <label>Disable Featured Image</label>
            </div>

            <h3>Status</h3>
            <div class="dfn-performance-notice">
                <div class="dfn-field">
                    <div class="dfn-icon-bullet-green"></div>
                    <span>0 terms in <?php echo $post_count ?> posts<span>
                    <input type="hidden" class="dfn-post-count" value="<?php echo $post_count ?>">
                </div>
            </div>

            <div class="dfn-field">
                <input type="hidden" class="dfn-enable" name="dfn-enable" value=""/>
                <input type="checkbox" class="dfn-enable" name="dfn-enable" <?php echo $enable ?>/>
                <label for="dfn-enable">Enable</label>
            </div>

            <?php
        }


        private function definition_tag_field( $post ) {
            $tax_name              = 'definitions_title';
            $taxonomy              = get_taxonomy( 'definitions_title' );
            $user_can_assign_terms = current_user_can( $taxonomy->cap->assign_terms );
            $comma                 = _x( ',', 'tag delimiter' );
            $terms_to_edit         = get_terms_to_edit( $post->ID, $tax_name );
            if ( ! is_string( $terms_to_edit ) ) {
                $terms_to_edit = '';
            }
            ?>
            <div class="tagsdiv" id="<?php echo $tax_name; ?>">
                <div class="jaxtag">
                    <div class="nojs-tags hide-if-js">
                        <label for="tax-input-<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->add_or_remove_items; ?></label>
                        <p><textarea name="<?php echo "tax_input[$tax_name]"; ?>" rows="3" cols="20" class="the-tags" id="tax-input-<?php echo $tax_name; ?>" <?php disabled( ! $user_can_assign_terms ); ?> aria-describedby="new-tag-<?php echo $tax_name; ?>-desc"><?php echo str_replace( ',', $comma . ' ', $terms_to_edit ); // textarea_escaped by esc_attr() ?></textarea></p>
                    </div>
                    <?php if ( $user_can_assign_terms ) : ?>
                        <div class="ajaxtag hide-if-no-js">
                            <label class="screen-reader-text" for="new-tag-<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
                            <input data-wp-taxonomy="<?php echo $tax_name; ?>" type="text" id="new-tag-<?php echo $tax_name; ?>" name="newtag[<?php echo $tax_name; ?>]" class="newtag form-input-tip" size="16" autocomplete="off" aria-describedby="new-tag-<?php echo $tax_name; ?>-desc" maxlength="30" value="" />
                            <input type="button" class="button tagadd" name="dfn-definition-add" value="<?php esc_attr_e( 'Add' ); ?>" />
                        </div>
                        <p class="howto" id="new-tag-<?php echo $tax_name; ?>-desc"><?php echo $taxonomy->labels->separate_items_with_commas; ?></p>
                    <?php elseif ( empty( $terms_to_edit ) ) : ?>
                        <p><?php echo $taxonomy->labels->no_terms; ?></p>
                    <?php endif; ?>
                </div>
                <ul class="tagchecklist dfn-post-definition-list" role="list"></ul>
            </div>
            <?php
        }


        function save_postdata_definitions( $post_id ) {
            if ( array_key_exists( 'dfn-use-tooltip', $_POST ) ) {
                update_post_meta(
                    $post_id,
                    'definition_use_tooltip',
                    $_POST['dfn-use-tooltip']
                );
            }

            if ( array_key_exists( 'dfn-disable-image', $_POST ) ) {
                update_post_meta(
                    $post_id,
                    'definition_disable_image',
                    $_POST['dfn-disable-image']
                );
            }

            if ( array_key_exists( 'dfn-enable', $_POST ) ) {
                update_post_meta(
                    $post_id,
                    'definition_enable',
                    $_POST['dfn-enable']
                );
            }
        }

	}
}