<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if (!class_exists('wpdef_posttype')) {
	class wpdef_posttype {
		public function __construct() {
			add_action( 'init', array( $this, 'register_definitions' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_definitions_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_postdata_definitions' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'add_definitions_meta_box_script' ) );
		}

		/**
		 * Register our taxonomy
		 */
		public function register_definitions() {
            $labels   =  array(
                'name'                          => __( 'Definitions', 'definitions' ),
                'singular_name'                 => __( 'Definition', 'definitions' ),
                'add_new'                       => __( 'New definition', 'definitions' ),
                'add_new_item'                  => __( 'Add new definition', 'definitions' ),
                'parent_item_colon'             => __( 'definition', 'definitions' ),
                'parent'                        => __( 'definition parentitem', 'definitions' ),
                'edit_item'                     => __( 'Edit definition', 'definitions' ),
                'new_item'                      => __( 'New definition', 'definitions' ),
                'view_item'                     => __( 'View definition', 'definitions' ),
                'search_items'                  => __( 'Search definitions', 'definitions' ),
                'not_found'                     => __( 'No definitions found', 'definitions' ),
                'not_found_in_trash'            => __( 'No definitions found in trash', 'definitions' ),
                'choose_from_most_used'         => __( '', 'definitions' ),
                'separate_items_with_commas'    => __( '', 'definitions' ),
            );

            $args = [
                'hierarchical'      => false,
                'labels'            => $labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => false,
                'query_var'         => true,
                'rewrite'           => array( 'slug' => 'definitions_title' ),
            ];

            register_taxonomy( 'definitions_title', apply_filters('wpdef_post_types', array('post') ), $args );
		}


		/**
		 * Add a metabox
		 */
		public function add_definitions_meta_box() {
            add_meta_box(
                'definitions_box_id',
                'Internal linkbuilding',
                array( $this, 'definitions_meta_box_html' ),
	            apply_filters('wpdef_post_types', ['post']),
                'side'
            );

			$this->maybe_sort_metabox('burst_edit_meta_box');

		}

		/**
         * On posts, we add some scripts to handle the definitions
		 * @param $hook
		 */
        function add_definitions_meta_box_script( $hook ) {
	        if (!current_user_can('edit_posts')) return;

            global $post;

            if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
                if ( in_array($post->post_type, apply_filters('wpdef_post_types', array('post') )) ) {
                    wp_register_style( 'wpdef-metabox', trailingslashit( WPDEF_URL ) . "assets/css/metabox.css", "", WPDEF_VERSION );
                    wp_enqueue_style( 'wpdef-metabox' );
	                wp_enqueue_script( 'tags-box' );
                    //if( $this->uses_gutenberg() ) {
                        $suffix = wp_scripts_get_suffix();
//	                    wp_enqueue_script( 'tags-box' );
                        wp_enqueue_script('wpdef-tagbox-js', "/wp-admin/js/tags-box$suffix.js", array('jquery', 'tags-suggest'), false, true);
                 //   }

                    $minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

                    wp_enqueue_script( 'wpdef',
                        WPDEF_URL . "assets/js/metabox$minified.js", array('jquery'),
                        WPDEF_VERSION, true );

                    $args = array(
                            'taxonomy' => 'definitions_title',
                            'orderby' => 'count',
                            'hide_empty' => true,
                    );
	                $definitions = array_column( get_terms( $args ), 'name');

                    wp_localize_script(
                        'wpdef',
                        'wpdef',
                        array(
                            'url' => admin_url('admin-ajax.php'),
                            'strings'=> array(
                                'read-more' => __("Read more", "definitions"),
                                'add-term' => __('Add a term to see the occurrence count', "definitions"),
                                'already-in-use-plural' => sprintf(__('%s are already in use. Choose another', "definitions"), '{definitions}'),
                                'already-in-use-single' => sprintf(__('"%s" is already in use. Choose another', "definitions"), '{definitions}'),
                                'not-in-use-plural' => sprintf(__('%s are not used before!', "definitions"), '{definitions}'),
                                'not-in-use-single' => sprintf(__('"%s" has not been used before!', "definitions"), '{definitions}'),
                                'terms-in-posts' => sprintf(__('%s terms in %s posts', "definitions"), '{terms_count}','{posts_count}'),
                                'way-too-many-terms' => __('Your term occurs in a lot of posts. Try to be more specific.', "definitions"),
                                'too-many-terms' => __('Your term occurs in a lot of posts. Try to be more specific.', "definitions"),
                                'positive-ratio-terms' => __('The number of times this term occurs is good.', "definitions"),
                                'few-terms' => __('There are not many matches for this term.', "definitions"),
                                'no-terms' => __('No matches were found.', "definitions"),
                            ),
                            'post_count' => wp_count_posts()->publish,
                            'existing_definitions' => $definitions,
                        )
                    );
                }
            }
        }

		/**
		 * Put our metabox right below the publish post box
		 * @param string $key
		 */
		public function maybe_sort_metabox($key) {
			$user_id = get_current_user_id();
			//only do this once
			if ( get_user_meta( $user_id, 'wpdef_sorted_metaboxes', true) ){
				return;
			}

			global $post;
			$post_type = get_post_type($post);

			$order = get_user_option("meta-box-order_$post_type", get_current_user_id() );
			if ( !$order['side'] ) {
				$new_order = array(
					'submitdiv',
					$key,
				);
			} else  {
				$new_order = explode(",", $order['side'] );
				for( $i=0 ; $i < count ($new_order) ; $i++ ){
					$temp = $new_order[$i];
					if ( $new_order[$i] == $key && $i != 1) {
						$new_order[$i] = $new_order[1];
						$new_order[1] = $temp;
					}
				}
			}

			$order['side'] = implode(",",$new_order);
			update_user_option( $user_id, "meta-box-order_".$post_type, $order, true);
			update_user_meta( $user_id, 'wpdef_sorted_metaboxes', true);
		}


		/**
         * Check if this setup uses Gutenberg or not
		 * @return bool
		 */
		private function uses_gutenberg() {

			if ( function_exists( 'has_block' )
			     && ! class_exists( 'Classic_Editor' )
			) {
				return true;
			}

			return false;
		}


		/**
         * Get the metabox html
		 * @param WP_POST $post
		 */

        public function definitions_meta_box_html( $post ) {
	        if (!current_user_can('edit_posts')) return;

	        $link_type    = get_post_meta( $post->ID, 'definition_link_type', true );
            $disable_image  = get_post_meta( $post->ID, 'definition_disable_image', true )  ? 'checked="checked"' : '';
            ?>
            <span class="dfn-comment"><?php _e("If you want to know all the possibilities with Definitions - Internal Linkbuilding, have a look at our documentation.", "definitions") ?> <a href="https://really-simple-plugins.com/definitions-internal-linkbuilding/documentation"><?php _e("Read more", "definitions") ?></a></span>
            <h3><?php _e("Settings", "definitions")?></h3>
            <?php $this->definition_tag_field( $post ); ?>
            <div class="dfn-field dfn-definition-add-notice">
                <div class="dfn-icon-bullet dfn-icon-bullet-invisible"></div><span class="dfn-comment"><?php _e("Add a term to see the occurrence count", "definitions") ?></span>
            </div>

            <div class="dfn-field dfn-show-if-term dfn-disabled">
                <label for="dfn-link-type">
                    <select name="dfn-link-type">
                        <option value="preview"><?php _e("Preview on hover", "definitions")?></option>
                        <option value="hyperlink" <?php if ($link_type ==='hyperlink') echo "selected"?> ><?php _e("Hyperlink", "definitions")?></option>
                    </select>
                </label>
            </div>

            <div class="dfn-field dfn-show-if-term  dfn-disabled">
                <input type="hidden" class="dfn-disable-image" name="dfn-disable-image" value=""/>
                <input type="checkbox" class="dfn-disable-image" name="dfn-disable-image" <?php echo $disable_image ?>/>
                <label><?php _e("Disable Featured Image", "definitions")?></label>
            </div>

            <h3 class="dfn-performance-notice-header dfn-show-if-term dfn-disabled"><?php _e("Status", "definitions")?></h3>
            <div class="dfn-performance-notice dfn-show-if-term dfn-disabled"></div>

            <div class="dfn-field">
                <span class="dfn-save-changes"><?php _e("Settings changed, don't forget to save!", "definitions")?></span>
            </div>
            <?php
        }

		/**
         * Get the definition tag field
		 * @param $post
		 */
        private function definition_tag_field( $post ) {
	        if (!current_user_can('edit_posts')) return;

	        $tax_name              = 'definitions_title';
            $taxonomy              = get_taxonomy( 'definitions_title' );
            $user_can_assign_terms = current_user_can( $taxonomy->cap->assign_terms );
            $comma                 = _x( ',', 'tag delimiter' );
            $terms_to_edit         = get_terms_to_edit( $post->ID, $tax_name );
            if ( ! is_string( $terms_to_edit ) ) {
                $terms_to_edit = '';
            }
            ?>
            <div class="tagsdiv" id="<?php echo esc_attr($tax_name); ?>">
                <div class="jaxtag">
                    <div class="nojs-tags hide-if-js">
                        <label for="tax-input-<?php echo esc_attr($tax_name); ?>"><?php echo $taxonomy->labels->add_or_remove_items; ?></label>
                        <p><textarea name="tax_input[<?php echo esc_attr($tax_name); ?>]" rows="3" cols="20" class="the-tags" id="tax-input-<?php echo esc_attr($tax_name); ?>" <?php disabled( ! $user_can_assign_terms ); ?> aria-describedby="new-tag-<?php echo esc_attr($tax_name); ?>-desc"><?php echo str_replace( ',', $comma . ' ', $terms_to_edit ); // textarea_escaped by esc_attr() ?></textarea></p>
                    </div>
                    <?php if ( $user_can_assign_terms ) : ?>
                        <div class="ajaxtag hide-if-no-js">
                            <label class="screen-reader-text" for="new-tag-<?php echo esc_attr($tax_name); ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
                            <input data-wp-taxonomy="<?php echo esc_attr($tax_name); ?>" type="text" id="new-tag-<?php echo $tax_name; ?>" name="newtag[<?php echo $tax_name; ?>]" class="newtag form-input-tip" size="16" autocomplete="off" aria-describedby="new-tag-<?php echo $tax_name; ?>-desc" maxlength="30" value="" />
                            <input type="button" class="button tagadd" name="dfn-definition-add" value="<?php esc_attr_e( 'Add' ); ?>" />
                        </div>
                        <p class="howto" id="new-tag-<?php echo esc_attr($tax_name); ?>-desc"><?php echo $taxonomy->labels->separate_items_with_commas; ?></p>
                    <?php elseif ( empty( $terms_to_edit ) ) : ?>
                        <p><?php echo $taxonomy->labels->no_terms; ?></p>
                    <?php endif; ?>
                </div>
                <ul class="tagchecklist dfn-post-definition-list" role="list"></ul>
            </div>
            <?php
        }

		/**
         * Save the definitions
		 * @param $post_id
		 */
        function save_postdata_definitions( $post_id ) {
	        if (!current_user_can('edit_posts')) return;

	        if ( array_key_exists( 'dfn-link-type', $_POST ) ) {
                $options = array(
                        'hyperlink',
                        'preview',
                );
                if (in_array($_POST['dfn-link-type'], $options)) {
	                $link_type = sanitize_title($_POST['dfn-link-type']);
	                update_post_meta(
		                $post_id,
		                'definition_link_type',
		                $link_type
	                );
                }
            }

            if ( array_key_exists( 'dfn-disable-image', $_POST ) ) {
	            $disable_image = $_POST['dfn-disable-image'] === 'on' ? 'on' : false;
	            update_post_meta(
                    $post_id,
                    'definition_disable_image',
		            $disable_image
                );
            }
        }

	}
}