<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists('wpdef_text_parser')) {
	class wpdef_text_parser {

		public $tooltip_type = 'preview'; //or preview

		public function __construct() {
			add_action( 'wp_enqueue_scripts',
				array( $this, 'enqueue_assets' ) );

			add_filter( 'the_content',
				array( $this, 'replace_definitions_with_links' ) );

			add_action( 'wp_ajax_nopriv_wpdef_load_preview',
				array( $this, 'wpdef_load_preview' ) );
			add_action( 'wp_ajax_wpdef_load_preview',
				array( $this, 'wpdef_load_preview' ) );
		}

		public function enqueue_assets() {
			wp_register_style( 'wpdef-tooltip', WPDEF_URL . 'assets/css/tooltip.css' , array(), WPDEF_VERSION );
			wp_enqueue_style( 'wpdef-tooltip' );

			$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? ''
				: '.min';

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

		public function wpdef_load_preview(){
			$definitions_ids = array_map( 'intval' , $_GET['ids']);
			$error          = false;
			$previews = array();
			foreach ($definitions_ids as $definitions_id ) {

				$definition = get_post($definitions_id);
				$excerpt          = $this->get_excerpt( $definition );

				$img = get_the_post_thumbnail($definition, 'medium' );
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
			$classes[] = 'wpdef-'.sanitize_title($title);
			$classes[] = 'wpdef-'.$this->tooltip_type;
			$class = implode(' ', apply_filters( 'wpdef-classes', $classes) );
			if ($this->tooltip_type === 'tooltip'){
				$tooltip_html = '<a href="{url}" class="'.$class.'"><span data-hover="{tooltip}">{title}</span></a>';
			} else {
				$tooltip_html = '<span class="'.$class.'"><a href="{url}"><dfn title="{title}" class="wpdef-definition" data-definitions_id="{post_id}"></dfn></a></span>';
				// https://stackoverflow.com/questions/40531029/how-to-create-a-pure-css-tooltip-with-html-content-for-inline-elements
			}
			return apply_filters( 'wpdef_tooltip_html', str_replace( array(
				"{url}",
				"{tooltip}",
				"{title}",
				"{post_id}"
			), array( $url, $tooltip, $title , $post_id), $tooltip_html ) );
		}

		/**
		 * @param string $content
		 *
		 * @return string
		 */

		public function replace_definitions_with_links( $content ) {
			//find definitions in buffer
			$args = array(
				'post_type'        => 'definition',
				'post_status'      => 'publish',
				'numberposts'      => - 1,
				'suppress_filters' => true
			);

			$definitions = get_posts( apply_filters( 'wpdef_definitions_query', $args ) );
			if ( $definitions ) {
				foreach ( $definitions as $definition ) {
					//check if this post IS this definition, else skip to next definition
					if ( get_the_ID() != $definition->ID ) {
						$terms = get_the_terms( $definition->ID, 'definitions_title' );
						if ( !$terms ) continue;

						foreach ($terms as $term ) {
							$url              = get_permalink( $definition->ID );
							$excerpt          = $this->get_excerpt( $definition );

							$link = $this::get_tooltip_link( $url, $excerpt, $term->name , $definition->ID);

							//continue until end of string, or break
							// use regex instead https://stackoverflow.com/questions/958095/use-regex-to-find-specific-string-not-in-html-tag
							//regex: replace $name in $content with $link

							$pattern = '/(?![^<]*>)(' . $term->name . ')/i';
							$limit = 1; //how many times a found definition can be replaced
							$content = preg_replace( $pattern, $link, $content, $limit );
						}
					}
				}
			}

			return apply_filters( 'wpdef_content', $content );
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
					$excerpt = substr(strip_tags($definition->post_content), 0, 250 ).'...';
				}
			}

			return $excerpt;
		}

	}
}
