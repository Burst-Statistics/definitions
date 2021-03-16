<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists('wpdef_text_parser')) {
	class wpdef_text_parser {

		public $tooltip_type = 'preview'; //or preview

		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'script_loader_tag', array( $this, 'defer_replacement_script' ), 10, 3 );

			add_filter( 'the_content', array( $this, 'replace_definitions_with_links' ) );

			add_action( 'wp_ajax_nopriv_wpdef_load_preview', array( $this, 'wpdef_load_preview' ) );
			add_action( 'wp_ajax_wpdef_load_preview', array( $this, 'wpdef_load_preview' ) );

			add_action( 'wp_ajax_wpdef_scan_definition_count', array( $this, 'wpdef_scan_definition_count' ) );
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

		public function replace_definitions_with_links( $content ) {
			//find definitions in buffer
			$args = array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'numberposts'      => - 1,
				'suppress_filters' => true
			);

			$posts = get_posts( apply_filters( 'wpdef_definitions_query', $args ) );
			if ( $posts ) {
				foreach ( $posts as $post ) {
					//check if this post IS this definition, else skip to next definition
					if ( get_the_ID() != $post->ID ) {
                        $enable = get_post_meta( $post->ID, 'definition_enable', true );
					    if ( !$enable ) continue;

						$terms = get_the_terms( $post->ID, 'definitions_title' );
						if ( !$terms ) continue;

						shuffle($terms);
						$terms = array_slice( $terms, 0, 3);

						foreach ($terms as $term ) {
							$url              = get_permalink( $post->ID );
							$excerpt          = $this->get_excerpt( $post );

							$link = $this->get_tooltip_link( $url, $excerpt, $term->name , $post->ID);

							//continue until end of string, or break
							// use regex instead https://stackoverflow.com/questions/958095/use-regex-to-find-specific-string-not-in-html-tag
							//regex: replace $name in $content with $link

							$pattern = $this->get_regex( $term->name );
							$replacement = " {$link}$2";
							$limit = 1; //how many times a found definition can be replaced
							$content = preg_replace( $pattern, $replacement, $content, $limit );
						}
					}
				}
			}

			return apply_filters( 'wpdef_content', $content );
		}

		private function get_regex( $term ) {
            return "/(?![^<]*>)( {$term}( |\,|\.|\;|\!|\?))/i";
        }

		/**
		 * @param WP_POST $definition
		 *
		 * @return string
		 */

		public function get_excerpt( $definition ) {
			$excerpt = apply_filters( 'the_excerpt', $definition->post_excerpt );

			if ( strlen( $excerpt ) == 0 ) {
				$excerpt = $definition->post_content;
				if ( strlen($excerpt)>250 ){
					$excerpt = substr($definition->post_content, 0, 250 ).'...';
				}
			}

			return $excerpt;
		}


        public function wpdef_scan_definition_count(){

            if ( !isset($_GET['definitions']) ) {
                $response = array(
                    'success' => true,
                    'count' => 0,
                );

                $response = json_encode($response);
                header( "Content-Type: application/json" );
                echo $response;
                exit;
            }

            $definitions = $_GET['definitions'];

            $count = 0;
            $posts = get_posts(array('numberposts' => -1));

            foreach ( $posts as $post ) {
                $content     = $post->post_content;
                foreach( $definitions as $definition ) {
                    $pattern = $this->get_regex( $definition );
                    if ( preg_match_all($pattern, $content) ) {
                        $count++;
                    }
                }
            }

            $response = array(
                'success' => true,
                'count' => $count,
            );

            $response = json_encode($response);
            header( "Content-Type: application/json" );
            echo $response;
            exit;
        }



	}
}
