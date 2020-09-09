<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if (!class_exists('wpdef_text_parser')) {
	class wpdef_text_parser {
		const tooltip_html = '<a href="{url}" class="wpdef-definition" data-toggle="tooltip" title="{tooltip}">{title}</a>';

		public function __construct() {
			add_action( 'wp_enqueue_scripts',
				array( $this, 'enqueue_assets' ) );

			add_filter( 'the_content',
				array( $this, 'replace_definitions_with_links' ) );
		}

		public function enqueue_assets() {
			wp_register_style( 'wpdef-tooltipcss',
				WPDEF_URL . 'css/tooltip.css' );
			wp_enqueue_style( 'wpdef-tooltipcss' );
		}


		/**
		 * Return html with hyperlink and tooltip
		 * @param string $url
		 * @param string $tooltip
		 * @param string $title
		 *
		 * @return string
		 */

		private function get_tooltip_link( $url, $tooltip, $title ) {
			return apply_filters( 'rldh_tooltip_html', str_replace( array(
				"{url}",
				"{tooltip}",
				"{title}"
			), array( $url, $tooltip, $title ), self::tooltip_html ) );
		}

		/**
		 * @param string $content
		 *
		 * @return string
		 */

		public function replace_definitions_with_links( $content ) {
			$count          = 0;

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
					_log($definition);
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
						$excerpt .= __( 'Click for more information', 'definitions' );

						$excerpt_arr[]     = $excerpt;
						$placeholder       = $count . '_rldh_excerpt';
						$placeholder_arr[] = $placeholder;

						$link = $this:: get_tooltip_link( $url, $name, $placeholder );

						//continue until end of string, or break
						// use regex instead https://stackoverflow.com/questions/958095/use-regex-to-find-specific-string-not-in-html-tag
						//regex: replace $name in $content with $link

						$pattern = '/(?![^<]*>)(' . $name . ')/i';
						$content = preg_replace( $pattern, $link, $content );

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
