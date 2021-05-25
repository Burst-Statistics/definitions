<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if (!class_exists('rspdef_posttype')) {
	class rspdef_posttype {
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
                'name'                          => __( 'Definitions', 'definitions-internal-linkbuilding' ),
                'singular_name'                 => __( 'Definition', 'definitions-internal-linkbuilding' ),
                'add_new'                       => __( 'New definition', 'definitions-internal-linkbuilding' ),
                'add_new_item'                  => __( 'Add new definition', 'definitions-internal-linkbuilding' ),
                'parent_item_colon'             => __( 'definition', 'definitions-internal-linkbuilding' ),
                'parent'                        => __( 'definition parentitem', 'definitions-internal-linkbuilding' ),
                'edit_item'                     => __( 'Edit definition', 'definitions-internal-linkbuilding' ),
                'new_item'                      => __( 'New definition', 'definitions-internal-linkbuilding' ),
                'view_item'                     => __( 'View definition', 'definitions-internal-linkbuilding' ),
                'search_items'                  => __( 'Search definitions', 'definitions-internal-linkbuilding' ),
                'not_found'                     => __( 'No definitions found', 'definitions-internal-linkbuilding' ),
                'not_found_in_trash'            => __( 'No definitions found in trash', 'definitions-internal-linkbuilding' ),
                'choose_from_most_used'         => __( '', 'definitions-internal-linkbuilding' ),
                'separate_items_with_commas'    => __( '', 'definitions-internal-linkbuilding' ),
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

            register_taxonomy( 'definitions_title', DEFINITIONS::$source_post_types, $args );
		}


		/**
		 * Add a metabox
		 */
		public function add_definitions_meta_box() {
            add_meta_box(
                'definitions_box_id',
                'Internal linkbuilding',
                array( $this, 'definitions_meta_box_html' ),
	            apply_filters('rspdef_source_post_types', DEFINITIONS::$source_post_types),
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
                if ( in_array($post->post_type, DEFINITIONS::$source_post_types ) ) {
                    wp_register_style( 'rspdef-metabox', trailingslashit( RSPDEF_URL ) . "assets/css/metabox.css", "", RSPDEF_VERSION );
                    wp_enqueue_style( 'rspdef-metabox' );
	                wp_enqueue_script( 'tags-box' );
                    if( $this->uses_gutenberg() ) {
	                    wp_enqueue_script( 'tags-box' );
                    }

                    $minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

                    wp_enqueue_script( 'rspdef',
                        RSPDEF_URL . "assets/js/metabox$minified.js", array('jquery'),
                        RSPDEF_VERSION, true );

                    $args = array(
                            'taxonomy' => 'definitions_title',
                            'orderby' => 'count',
                            'hide_empty' => true,
                    );
	                $definitions = array_column( get_terms( $args ), 'name');

                    wp_localize_script(
                        'rspdef',
                        'rspdef',
                        array(
                            'url' => admin_url('admin-ajax.php'),
                            'strings'=> array(
                                'read-more' => __("Read more", "definitions-internal-linkbuilding"),
                                'add-term' => __('Add a term to see the occurrence count', "definitions-internal-linkbuilding"),
                                'already-in-use-plural' => sprintf(__('%s are already in use. Choose another', "definitions-internal-linkbuilding"), '{definitions}'),
                                'already-in-use-single' => sprintf(__('"%s" is already in use. Choose another', "definitions-internal-linkbuilding"), '{definitions}'),
                                'not-in-use-plural' => sprintf(__('%s are not used before!', "definitions-internal-linkbuilding"), '{definitions}'),
                                'not-in-use-single' => sprintf(__('"%s" has not been used before!', "definitions-internal-linkbuilding"), '{definitions}'),
                                'terms-in-posts' => sprintf(__('Estimated %s terms in %s posts', "definitions-internal-linkbuilding"), '{terms_count}','{posts_count}'),
                                'way-too-many-terms' => __('Your term occurs in a lot of posts. Try to be more specific.', "definitions-internal-linkbuilding"),
                                'too-many-terms' => __('Your term occurs in a lot of posts. Try to be more specific.', "definitions-internal-linkbuilding"),
                                'positive-ratio-terms' => __('The number of times this term occurs is good.', "definitions-internal-linkbuilding"),
                                'few-terms' => __('There are not many matches for this term.', "definitions-internal-linkbuilding"),
                                'no-terms' => __('No matches were found.', "definitions-internal-linkbuilding"),
                                'retrieving-status' => __('Calculating results...', "definitions-internal-linkbuilding"),
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
			if ( get_user_meta( $user_id, 'rspdef_sorted_metaboxes', true) ){
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
			update_user_meta( $user_id, 'rspdef_sorted_metaboxes', true);
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
            <span class="rspdef-comment"><?php _e("If you want to know all the possibilities with Definitions - Internal Linkbuilding, have a look at our documentation.", "definitions-internal-linkbuilding") ?> <a target="_blank" href="https://really-simple-plugins.com/definitions-internal-linkbuilding/documentation"><?php _e("Read more", "definitions-internal-linkbuilding") ?></a></span>
            <h3><?php _e("Settings", "definitions-internal-linkbuilding")?></h3>
            <?php $this->definition_tag_field( $post ); ?>
            <div class="rspdef-field rspdef-definition-add-notice">
                <div class="rspdef-icon-bullet rspdef-icon-bullet-invisible"></div><span class="rspdef-comment"><?php _e("Add a term to see the occurrence count", "definitions-internal-linkbuilding") ?></span>
            </div>

            <div class="rspdef-field rspdef-show-if-term rspdef-disabled">
                <label for="rspdef-link-type">
                    <select name="rspdef-link-type">
                        <option value="preview"><?php _e("Preview on hover", "definitions-internal-linkbuilding")?></option>
                        <option value="hyperlink" <?php if ($link_type ==='hyperlink') echo "selected"?> ><?php _e("Hyperlink", "definitions-internal-linkbuilding")?></option>
                    </select>
                </label>
            </div>

            <div class="rspdef-field rspdef-show-if-term  rspdef-disabled">
                <input type="hidden" class="rspdef-disable-image" name="rspdef-disable-image" value=""/>
                <input type="checkbox" class="rspdef-disable-image" name="rspdef-disable-image" <?php echo $disable_image ?>/>
                <label><?php _e("Disable Featured Image", "definitions-internal-linkbuilding")?></label>
            </div>

            <h3 class="rspdef-performance-notice-header rspdef-show-if-term rspdef-disabled"><?php _e("Status", "definitions-internal-linkbuilding")?></h3>
            <div class="rspdef-performance-notice rspdef-show-if-term rspdef-disabled"></div>

            <div class="rspdef-field">
                <span class="rspdef-save-changes"><?php _e("Settings changed, don't forget to save!", "definitions-internal-linkbuilding")?></span>
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
                            <input type="button" class="button tagadd" name="rspdef-definition-add" value="<?php esc_attr_e( 'Add' ); ?>" />
                        </div>
                        <p class="howto" id="new-tag-<?php echo esc_attr($tax_name); ?>-desc"><?php echo $taxonomy->labels->separate_items_with_commas; ?></p>
                    <?php elseif ( empty( $terms_to_edit ) ) : ?>
                        <p><?php echo $taxonomy->labels->no_terms; ?></p>
                    <?php endif; ?>
                </div>
                <ul class="tagchecklist rspdef-post-definition-list" role="list"></ul>
            </div>
            <?php
        }

		/**
         * Save the definitions
		 * @param $post_id
		 */
        function save_postdata_definitions( $post_id ) {
	        if (!current_user_can('edit_posts')) return;

	        if ( array_key_exists( 'rspdef-link-type', $_POST ) ) {
                $options = array(
                        'hyperlink',
                        'preview',
                );
                $link_type = sanitize_title($_POST['rspdef-link-type']);
                if (in_array($link_type, $options)) {
	                update_post_meta(
		                $post_id,
		                'definition_link_type',
		                $link_type
	                );
                }
            }

            if ( array_key_exists( 'rspdef-disable-image', $_POST ) ) {
	            $disable_image = sanitize_title($_POST['rspdef-disable-image']) === 'on' ? 'on' : false;
	            update_post_meta(
                    $post_id,
                    'definition_disable_image',
		            $disable_image
                );
            }
        }

	}
}