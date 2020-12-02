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
			error_log("ajax call");
			error_log(print_r($_GET, true));
			$definitions_ids = array_map( 'intval' , $_GET['ids']);
			error_log(print_r($definitions_ids, true));
			//get_post();

			$error          = false;
			$previews = array();
			foreach ($definitions_ids as $definitions_id ) {

				$postid = $definitions_id;
				$content_post = get_post($postid);
				$content = $content_post->post_content;
				$img = get_the_post_thumbnail($content_post, 'medium' );

				$previews[] = array(
					'id' => $definitions_id,
					'html' => '<div class="wpdef-preview-content"><div class="wpdef-preview-image">'.$img.' </div><div class="wpdef-preview-text"> '.$content.' </div><div class="wpdef-read-more"><a href="#">Read more</a></div></div>',
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
		 * Return html with hyperlink and tooltip
		 * @param string $url
		 * @param string $tooltip
		 * @param string $title
		 * @param int $post_id
		 *
		 * @return string
		 */

		private function get_tooltip_link( $url, $tooltip, $title , $post_id) {
			$classes[] = sanitize_title($title);
			$classes[] = 'wpdef-definition';
			$classes[] = 'wpdef-'.$this->tooltip_type;
			$class = implode(' ', apply_filters( 'wpdef-classes', $classes) );
			if ($this->tooltip_type === 'tooltip'){
				$tooltip_html = '<a href="{url}" class="'.$class.'"><span data-hover="{tooltip}">{title}</span></a>';
			} else {
				$tooltip_html = '<dfn title="{title}" class="'.$class.'" data-definitions_id="{post_id}"></dfn>';
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
			$count = 0;

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
						$excerpt .= __( 'Click for more information', 'definitions');

						$excerpt_arr[]     = $excerpt;
						$placeholder       = $count . '_rldh_excerpt';
						$placeholder_arr[] = $placeholder;
						$tooltip = $excerpt;
						$link = $this::get_tooltip_link( $url, $tooltip, $name , $definition->ID);

						//continue until end of string, or break
						// use regex instead https://stackoverflow.com/questions/958095/use-regex-to-find-specific-string-not-in-html-tag
						//regex: replace $name in $content with $link

						$pattern = '/(?![^<]*>)(' . $name . ')/i';
						$limit = 10; //how many times a found definition can be replaced


						$content = preg_replace( $pattern, $link, $content, $limit );

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
