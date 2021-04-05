<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists('wpdef_text_parser')) {
	class wpdef_text_parser {
		private $current_term_match;
        private $current_replace_link;

		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'script_loader_tag', array( $this, 'defer_replacement_script' ), 10, 3 );
			add_filter( 'the_content', array( $this, 'replace_definitions_with_links' ) );
			add_action( 'wp_ajax_nopriv_wpdef_load_preview', array( $this, 'load_preview' ) );
			add_action( 'wp_ajax_wpdef_load_preview', array( $this, 'load_preview' ) );
			add_action( 'wp_ajax_wpdef_scan_definition_count', array( $this, 'scan_definition_count' ) );
            add_action( 'save_post', array( $this, 'save_used_definitions_in_post' ), 10, 1 );

			do_action( 'delete_term', array( $this,'clear_used_definitions'), 10, 5 );
		}

		/**
		 * Enqueue our assets
		 */
		public function enqueue_assets() {
			wp_register_style( 'wpdef-tooltip', WPDEF_URL . 'assets/css/tooltip.css' , array(), WPDEF_VERSION );
			wp_enqueue_style( 'wpdef-tooltip' );

			$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_script( 'wpdef',
				WPDEF_URL . "assets/js/definitions$minified.js", array('jquery'),
				WPDEF_VERSION, true );
			wp_localize_script(
				'wpdef',
				'wpdef',
				array(
					'url'=> admin_url('admin-ajax.php'),
				)
			);
		}

		/**
		 * Add a defer tag
		 * @param string $tag
		 * @param string $handle
		 * @param string $src
		 *
		 * @return string
		 */
        function defer_replacement_script($tag, $handle, $src) {

            if ($handle === 'wpdef') {
                $tag = str_replace('<script ', '<script defer ', $tag);
            }

            return $tag;
        }

		/**
		 * Load the preview for our definitions
		 */

		public function load_preview(){
			$definitions_ids = array_map( 'intval' , $_GET['ids']);
			$error          = false;
			$previews = array();
			foreach ($definitions_ids as $definitions_id ) {
				$definition = get_post($definitions_id);
				$excerpt    = $this->get_excerpt( $definition );
				$disable_image = get_post_meta( $definitions_id, 'definition_disable_image', true );
				if ( $disable_image ) {
				    $img = '';
                } else {
                    $img = get_the_post_thumbnail($definition, 'medium' );
                }

                $args = array(
					'image' => $img,
					'content' => do_shortcode($excerpt),
					'permalink' => get_permalink( $definitions_id ),
				);
				$previews[] = array(
					'id' => $definitions_id,
					'html' => $this->load_template( 'preview.php', $args )
				);
			}

			$response = array(
				'success' => !$error,
				'previews' => $previews,
			);

			$response = json_encode($response);
			header( "Content-Type: application/json" );
			echo $response;
			exit;
		}

		/**
		 * Load a template, and replace fields if any
		 * @param string $filename
		 * @param array $args
		 *
		 * @return false|string|string[]
		 */
		public function load_template( $filename, $args = array() ) {

			$file       = trailingslashit( WPDEF_PATH ) . 'templates/' . $filename;
			$theme_file = trailingslashit( get_stylesheet_directory() )
			              . trailingslashit( basename( WPDEF_PATH ) )
			              . 'templates/' . $filename;

			if ( file_exists( $theme_file ) ) {
				$file = $theme_file;
			}

			if ( strpos( $file, '.php' ) !== false ) {
				ob_start();
				require $file;
				$contents = ob_get_clean();
			} else {
				$contents = file_get_contents( $file );
			}

			if ( !empty($args) && is_array($args) ) {
				foreach ( $args as $fieldname => $fieldvalue ) {
					$contents = str_replace( "{".$fieldname."}", $fieldvalue, $contents );
				}
			}

			return $contents;
		}

		/**
		 * Return html with hyperlink and tooltip
		 * @param string $url
		 * @param string $tooltip
		 * @param string $title
		 * @param int $post_id
		 *
		 * @return string
		 */

		private function get_tooltip_link( $url, $tooltip, $title , $post_id) {
            $link_type = get_post_meta($post_id, 'definition_link_type', true);
            $class = 'wpdef-'.sanitize_title($title);

            //defaults to preview
			if ( $link_type === 'hyperlink' ){
				$tooltip_html = '<a href="{url}" class="'.$class.'"><span>{title}</span></a>';
				$tooltip_html = str_replace( array( "{url}", "{title}"), array( $url, $title ), $tooltip_html );
			} else {
				// https://stackoverflow.com/questions/40531029/how-to-create-a-pure-css-tooltip-with-html-content-for-inline-elements
				$tooltip_html = '<span class="'.$class.' wpdef-preview"><a href="{url}"><dfn title="{title}" class="wpdef-definition" data-definitions_id="{post_id}"></dfn></a></span>';
				$tooltip_html = str_replace( array( "{url}", "{tooltip}", "{title}", "{post_id}"), array( $url, $tooltip, $title , $post_id), $tooltip_html );
			}

			return apply_filters( 'wpdef_tooltip_html', $tooltip_html );
		}


		/**
		 * The actual definitions replacement.
		 * @param string $content
		 *
		 * @return string
		 */

		public function replace_definitions_with_links( $content )
        {
            $terms = $this->load_used_definitions_in_post(get_the_ID());
            foreach ( $terms as $post_id_term ) {
                $post_id_term_array_object = explode(':', $post_id_term);
                $post_id = $post_id_term_array_object[0];
                $term = $post_id_term_array_object[1];
                $url = get_permalink($post_id);
                $excerpt = $this->get_excerpt(get_post($post_id));
                $link = $this->get_tooltip_link($url, $excerpt, $term, $post_id);

                //continue until end of string, or break
                // use regex instead https://stackoverflow.com/questions/958095/use-regex-to-find-specific-string-not-in-html-tag
                //regex: replace $name in $content with $link
                $this->current_term_match = $term;
                $this->current_replace_link = $link;
                $pattern = $this->get_regex($term, 'PHP');
                $content = preg_replace_callback($pattern, [$this, 'definition_preg_replace_callback'], $content, 1);
            }

            return apply_filters('wpdef_content', $content);
        }

		/**
		 * Callback for the pregreplace function
		 * @param array $matches
		 *
		 * @return mixed|string|string[]
		 */

        function definition_preg_replace_callback( $matches ) {
			return str_replace($matches[apply_filters('wpdef_matching_group', WPDEF_PATTERN_PHP_MATCHING_GROUP)], $this->current_replace_link, $matches[0]);
        }

		/**
		 * Get regex pattern
		 * @param string $term
		 * @param string $type
		 *
		 * @return string
		 */
		private function get_regex( $term, $type = 'PHP' ) {

			if ($type === 'SQL' ) {
				$pattern = str_replace('{definition}', $term, apply_filters('wp_definitions_pattern', WPDEF_PATTERN_SQL) );
			} else {
				$pattern = str_replace('{definition}', $term, apply_filters('wp_definitions_pattern', WPDEF_PATTERN_PHP) );
			}
			return $pattern;
		}


		/**
		 * @param WP_POST $definition
		 *
		 * @return string
		 */

		public function get_excerpt( $post ) {
			$excerpt = apply_filters( 'the_excerpt', $post->post_excerpt );

			if ( strlen( $excerpt ) == 0 ) {
				$excerpt = $post->post_content;
				if ( strlen($excerpt)>250 ){
					$excerpt = substr($post->post_content, 0, 250 ).'...';
				}
				//when our html breaks because we cut of a string, we fix this by passing it through the DOMDocument parser.
				$x = new DOMDocument;
				//this may generate warnings if the html is bad (which is what we're trying to fix here), so we suppress this.
				libxml_use_internal_errors(true);
				$x->loadHTML($excerpt);
				$excerpt = $x->saveXML();
			}

			return $excerpt;
		}

		/**
		 * Get count of number of posts that use definitions
		 * @return int
 		 */
		public function count_posts_with_definitions(){
			global $wpdb;
			$sql = "select count(*) from (SELECT DISTINCT post_id FROM wp_postmeta  WHERE meta_key = 'used_definitions') as postids";
			return $wpdb->get_var($sql);
		}

		/**
		 * Check how often a definition is used
		 */
        public function scan_definition_count(){
	        if (!current_user_can('edit_posts')) return;

            if ( !isset($_GET['definitions']) || !isset($_GET['post_id']) ) {
                $response = array(
                    'success' => true,
                    'count' => 0,
                );

                $response = json_encode($response);
                header( "Content-Type: application/json" );
                echo $response;
                exit;
            }

            global $wpdb;
            $definitions = array_map('sanitize_text_field', $_GET['definitions']);
            $post_id = intval($_GET['post_id']);

            // Count definitions from this post used in all other posts
            $sql = "select count(post.ID) from $wpdb->posts as post " .
                   "where post.ID != {$post_id} and post.post_status = 'publish' and ";
            $term_conditions = [];
            foreach ( $definitions as $definition ) {
            	$pattern = $this->get_regex($definition, 'SQL');
                $term_conditions[] = "post.post_content REGEXP '$pattern'";
            }
            $sql .= '(' . implode(' OR ', $term_conditions) . ')';
            $count = $wpdb->get_var($sql);

            $response = array(
                'success' => true,
                'count' => $count,
            );

            $response = json_encode($response);
            header( "Content-Type: application/json" );
            echo $response;
            exit;
        }

		/**
		 * Clear used definitions on posts
		 * @param int $term
		 * @param int $tt_id
		 * @param string $taxonomy
		 * @param WP_Term $deleted_term
		 * @param array $object_ids
		 */

        public function clear_used_definitions( $term, $tt_id, $taxonomy, $deleted_term, $object_ids){
        	global $wpdb;
	        $sql = "delete from $wpdb->postmeta where meta_key = 'used_definitions' and meta_value LIKE '%:$deleted_term'";
	        $wpdb->query($sql);
        }

		/**
		 * Save our definitions in the database
		 * @param int $this_post_id
		 */
        public function save_used_definitions_in_post( $this_post_id )
        {
        	if (!current_user_can('edit_posts')) return;

            global $wpdb;
	        $this_post_id = intval($this_post_id);

	        // Delete postmeta: definitions from other posts found in this post
	        $sql = "delete from $wpdb->postmeta where meta_key = 'used_definitions' and post_id = {$this_post_id}";
	        $wpdb->query($sql);

	        //((?<!h[1-9]\>))text(?!\<\/h[1-9])
            // Pattern: (<p>[^<]*( definition[ \,\.\;\!\?]))|(( definition[ \,\.\;\!\?])[^<]*<\/p)
            // <p>Commodi totam quam perferendis dicta definition.
            // or
            // Commodi totam quam perferendis dicta definition.</p>
            // Where not encountered another html tag like <h3>, using [^<]

            // Meta value: 'post_id:definition'

            // Delete postmeta: definitions from this post used in all other posts
            $sql = "delete from $wpdb->postmeta where meta_key = 'used_definitions' and meta_value LIKE '{$this_post_id}:%'";
            $wpdb->query($sql);

            // Save postmeta: definitions from this post used in all other posts
            //
            // Cross join every post with terms used in the saved post
            // Create table for postmeta with structure (post_id, meta_key, meta_value)
            // Filter this table with post_content REGEXP pattern
	        $pattern = $this->get_regex("', term.name, '", 'SQL');
	        $sql =
                "insert into $wpdb->postmeta (post_id, meta_key, meta_value) " .
                "select post.ID as post_id, 'used_definitions' as meta_key, CONCAT('{$this_post_id}:', term.name) as meta_value from $wpdb->posts as post, $wpdb->terms as term " .
                "join $wpdb->term_taxonomy as term_taxonomy  " .
                "on term.term_id = term_taxonomy.term_id " .
                "join $wpdb->term_relationships as term_relationships " .
                "on term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id " .
                "where term_taxonomy.taxonomy = 'definitions_title'" .
                "and term_relationships.object_id = {$this_post_id} " .
                "and post.post_content REGEXP CONCAT('$pattern') " .
                "and post.ID != {$this_post_id} " .
                "and post.post_status = 'publish' ".
                "and (post.post_type = 'post' OR post.post_type= 'page')";
            $wpdb->query($sql);

            // Save postmeta: definitions from other posts found in this post
            //
            // Create table for postmeta with structure (post_id, meta_key, meta_value)
            // Select post_content of the saved post
            // Create list (post_id, definition) for every post
            //
            // Save meta_value 'post_id:definition' where post_content REGEXP pattern
	        $pattern = $this->get_regex("', definitions.definition,'", 'SQL');

	        $sql =
                "insert into $wpdb->postmeta (post_id, meta_key, meta_value) " .
                "select a.post_id, a.meta_key, a.meta_value from " .
                "(select " .
                "'{$this_post_id}' as post_id, " .
                "'used_definitions' as meta_key, " .
                "CONCAT(definitions.post_id, ':', definitions.definition) as meta_value, " .
                "CONCAT('$pattern') as pattern, " .
                "(select post_content from $wpdb->posts where ID = {$this_post_id}) as content " .
                "from " .
                "(select term_relationships.object_id as post_id, term.name as definition " .
                "from $wpdb->term_taxonomy as term_taxonomy " .
                "join $wpdb->terms as term " .
                "on term.term_id = term_taxonomy.term_id " .
                "join $wpdb->term_relationships as term_relationships " .
                "on term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id " .
                "where term_taxonomy.taxonomy = 'definitions_title' " .
                "and (select post_status from $wpdb->posts where ID = term_relationships.object_id) = 'publish' " .
                "and term_relationships.object_id != {$this_post_id}" .
                ") as definitions) as a " .
                "where a.content REGEXP a.pattern";
            $wpdb->query($sql);
        }

		/**
		 * Callback to sort an array by length
		 * @param $a
		 * @param $b
		 *
		 * @return int
		 */
		private function sort_by_value_length($a,$b){
			return strlen($b)-strlen($a);
		}

		/**
		 * Get definitions for a post_id
		 *
		 * @param int $post_id
		 *
		 * @return array
		 */

        public function load_used_definitions_in_post( $post_id ) {
	        $post_id = intval($post_id);
		    $used_definitions = get_post_meta( $post_id, 'used_definitions', false );
		    if ( $used_definitions ) {
			    usort($used_definitions, array($this, 'sort_by_value_length'));

			    return $used_definitions;
            } else {
		        return array();
            }
		}
    }

}
