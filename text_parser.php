<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists('wpdef_text_parser')) {
	class wpdef_text_parser {

		public $tooltip_type = 'preview'; //or preview
        private $current_term_match;
        private $current_replace_link;

		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'script_loader_tag', array( $this, 'defer_replacement_script' ), 10, 3 );

			add_filter( 'the_content', array( $this, 'replace_definitions_with_links' ) );

			add_action( 'wp_ajax_nopriv_wpdef_load_preview', array( $this, 'wpdef_load_preview' ) );
			add_action( 'wp_ajax_wpdef_load_preview', array( $this, 'wpdef_load_preview' ) );

			add_action( 'wp_ajax_wpdef_scan_definition_count', array( $this, 'wpdef_scan_definition_count' ) );

            add_action( 'save_post', array( $this, 'save_used_definitions_in_post' ), 10, 1 );
		}

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


        function defer_replacement_script($tag, $handle, $src) {

            if ($handle === 'wpdef') {
                $tag = str_replace('<script ', '<script defer ', $tag);
            }

            return $tag;

        }


		public function wpdef_load_preview(){
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
					'content' => $excerpt,
					'permalink' => get_permalink( $definitions_id ),

				);

				$previews[] = array(
					'id' => $definitions_id,
					'html' => $this->load_template( 'preview.php', $args ) );
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
            $use_tooltip = get_post_meta($post_id, 'definition_use_tooltip', true);

            $class = 'wpdef-'.sanitize_title($title);

			if ( $use_tooltip ){
                // https://stackoverflow.com/questions/40531029/how-to-create-a-pure-css-tooltip-with-html-content-for-inline-elements
                $tooltip_html = '<span class="'.$class.' wpdef-preview"><a href="{url}"><dfn title="{title}" class="wpdef-definition" data-definitions_id="{post_id}"></dfn></a></span>';
                $tooltip_html = str_replace( array( "{url}", "{tooltip}", "{title}", "{post_id}"), array( $url, $tooltip, $title , $post_id), $tooltip_html );
			} else {
                $tooltip_html = '<a href="{url}" class="'.$class.'"><span>{title}</span></a>';
                $tooltip_html = str_replace( array( "{url}", "{title}"), array( $url, $title ), $tooltip_html );
			}

			return apply_filters( 'wpdef_tooltip_html', $tooltip_html );
		}


		/**
		 * @param string $content
		 *
		 * @return string
		 */

		public function replace_definitions_with_links( $content )
        {
            $terms = $this->load_used_definitions_in_post(get_the_ID());

            foreach ($terms as $post_id_term) {
                $post_id_term_array_object = explode(':', $post_id_term);
                $post_id = $post_id_term_array_object[0];
                $term = $post_id_term_array_object[1];


                $url = get_permalink();
                $excerpt = $this->get_excerpt(get_post($post_id));
                $link = $this->get_tooltip_link($url, $excerpt, $term, $post_id);

                //continue until end of string, or break
                // use regex instead https://stackoverflow.com/questions/958095/use-regex-to-find-specific-string-not-in-html-tag
                //regex: replace $name in $content with $link
                $this->current_term_match = $term;
                $this->current_replace_link = $link;

                $pattern = $this->get_regex($term);
                $content = preg_replace_callback($pattern, [$this, 'definition_preg_replace_callback'], $content, 1);
            }

            return apply_filters('wpdef_content', $content);
        }

        function definition_preg_replace_callback( $matches ) {
            return preg_replace( "/(\s)({$this->current_term_match})([\s\,\.\;\!\?])/", "$1{$this->current_replace_link}$3", $matches[0], 1 );
        }


		private function get_regex( $term ) {
		    return "/(<p>[^<]*( {$term}[ \,\.\;\!\?]))|(( {$term}[ \,\.\;\!\?])[^<]*<\/p)/";
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
			}

			return $excerpt;
		}


        public function wpdef_scan_definition_count(){

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
            $definitions = $_GET['definitions'];
            $post_id = $_GET['post_id'];

            // Count definitions from this post used in all other posts
            $sql = "select count(*) from wp_posts as post " .
                   "where ID != {$post_id} and ";
            $term_conditions = [];
            foreach ( $definitions as $definition ) {
                $term_conditions[] = "post.post_content REGEXP '(<p>[^<]*( {$definition}[ \,\.\;\!\?]))|(( {$definition}[ \,\.\;\!\?])[^<]*<\/p)' ";
            }
            $sql .= implode(' or ', $term_conditions);
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


        public function save_used_definitions_in_post( $this_post_id )
        {
            global $wpdb;

            // Pattern: (<p>[^<]*( definition[ \,\.\;\!\?]))|(( definition[ \,\.\;\!\?])[^<]*<\/p)
            // <p>Commodi totam quam perferendis dicta definition.
            // or
            // Commodi totam quam perferendis dicta definition.</p>
            // Where not encountered another html tag like <h3>, using [^<]

            // Meta value: 'post_id:definition'

            // Delete postmeta: definitions from this post used in all other posts
            $sql = "delete from wp_postmeta where meta_key = 'used_definitions' and meta_value LIKE '{$this_post_id}:%'";
            $wpdb->query($sql);

            // Save postmeta: definitions from this post used in all other posts
            //
            // Cross join every post with terms used in the saved post
            // Create table for postmeta with structure (post_id, meta_key, meta_value)
            // Filter this table with post_content REGEXP pattern and definition_enable
            $sql =
                "insert into wp_postmeta (post_id, meta_key, meta_value) " .
                "select post.ID as post_id, 'used_definitions' as meta_key, CONCAT('{$this_post_id}:', term.name) as meta_value from wp_posts as post, wp_terms as term " .
                "join wp_term_taxonomy as term_taxonomy  " .
                "on term.term_id = term_taxonomy.term_id " .
                "join wp_term_relationships as term_relationships " .
                "on term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id " .
                "where term_taxonomy.taxonomy = 'definitions_title'" .
                "and term_relationships.object_id = {$this_post_id} " .
                "and post.post_content REGEXP CONCAT('(<p>[^<]*( ', term.name, '[ \,\.\;\!\?]))|(( ', term.name,'[ \,\.\;\!\?])[^<]*<\/p)') " .
                "and (select meta_value from wp_postmeta where post_id = {$this_post_id}  and meta_key = 'definition_enable') = 'on'";
            $wpdb->query($sql);


            // Delete postmeta: definitions from other posts found in this post
            $sql = "delete from wp_postmeta where meta_key = 'used_definitions' and post_id = {$this_post_id}";
            $wpdb->query($sql);

            // Save postmeta: definitions from other posts found in this post
            //
            // Create table for postmeta with structure (post_id, meta_key, meta_value)
            // Select post_content of the saved post
            // Create list (post_id, definition) for every post
            //
            // Save meta_value 'post_id:definition' where post_content REGEXP pattern
            // Only when definition_enable for post with post_id
            $sql =
                "insert into wp_postmeta (post_id, meta_key, meta_value) " .
                "select a.post_id, a.meta_key, a.meta_value from " .
                "(select " .
                "'{$this_post_id}' as post_id, " .
                "'used_definitions' as meta_key, " .
                "CONCAT(definitions.post_id, ':', definitions.definition) as meta_value, " .
                "CONCAT('(<p>[^<]*( ', definitions.definition, '[ \,\.\;\!\?]))|(( ', definitions.definition,'[ \,\.\;\!\?])[^<]*<\/p)') as pattern, " .
                "(select post_content from wp_posts where ID = {$this_post_id}) as content " .
                "from " .
                "(select term_relationships.object_id as post_id, term.name as definition " .
                "from wp_term_taxonomy as term_taxonomy " .
                "join wp_terms as term " .
                "on term.term_id = term_taxonomy.term_id " .
                "join wp_term_relationships as term_relationships " .
                "on term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id " .
                "where term_taxonomy.taxonomy = 'definitions_title'" .
                "and (select meta_value from wp_postmeta where post_id = term_relationships.object_id  and meta_key = 'definition_enable') = 'on'" .
                ") as definitions) as a " .
                "where a.content REGEXP a.pattern";
            $wpdb->query($sql);
        }

        public function load_used_definitions_in_post( $post_id ) {
		    $used_definitions = get_post_meta( $post_id, 'used_definitions', false );
		    if ( $used_definitions ) {
                return $used_definitions;
            } else {
		        return array();
            }
		}
    }

}
