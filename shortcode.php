<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if (!class_exists('wpdef_shortcode')) {
	class wpdef_shortcode {
		const max_nr_of_columns_in_shortcode_list = 12;

		public function __construct() {
			add_shortcode( 'definitions', array( $this, 'add_shortcode' ) );
		}

		/**
		 * Insert shortcode overview of definitions
		 * @param $atts
		 *
		 * @return mixed|void
		 */

		public function add_shortcode( $atts ) {
			extract( shortcode_atts( array(
				'cols'       => 2,
				'characterheadings' => 'yes'
			), $atts, 'definitions-list' ) );
			if ( ! isset( $nrofcolumns ) ) {
				$nrofcolumns = 2;
			} elseif ( $nrofcolumns
			           > $this::max_nr_of_columns_in_shortcode_list
			) {
				$nrofcolumns = $this::max_nr_of_columns_in_shortcode_list;
			} else {
				switch ( $nrofcolumns ) {
					case 5:
						$nrofcolumns = 4;
						break;
					case 6:
					case 7:
					case 8:
						$nrofcolumns = 6;
						break;
					case 9:
					case 10:
					case 11:
						$nrofcolumns = 12;
						break;
				}
			}

			$col = 12 / $nrofcolumns;

			//return a list of definitions here
			$definitions_args = array(
				'post_type'   => __( 'Definition',
					'definitions' ),
				'post_status' => 'publish',
				'orderby'     => 'title',
				'order'       => 'ASC',
				'numberposts' => - 1,
			);

			$definitions
				                     = get_posts( apply_filters( 'rldh_definitions_shortcode_list_query',
				$definitions_args ) );
			$html                    = "";
			$html_column             = "";
			$total_nr_of_definitions = count( $definitions );
			$i                       = $total_nr_of_definitions / $nrofcolumns;
			$definitions_per_column  = round( $i );
			$old_char                = "";
			$counter                 = 1;
			if ( $definitions ) {
				foreach ( $definitions as $definition ) {
					$url      = get_permalink( $definition->ID );
					$name     = apply_filters( 'the_title',
						$definition->post_title );
					$new_char = strtoupper( substr( $name, 0, 1 ) );
					if ( $new_char != $old_char ) {
						$html_column .= "<div class='rldh-firstletter'><h2>"
						                . $new_char . "</h2></div>";
						$old_char    = $new_char;
					}
					$html_column .= "<div><a href='" . $url . "'>" . $name
					                . "</a></div>";
					//wrap in a column if $defitions per column is at multiple value of $counter,
					//or when it is the last column
					if ( ( ( $counter % $definitions_per_column == 0 )
					       && $counter != 0 )
					     || ( ( $total_nr_of_definitions - $counter )
					          < $definitions_per_column )
					) {
						$html        .= "<div class='rldh-columnheader col-md-"
						                . $col . "'>" . $html_column . "</div>";
						$html_column = "";
					}
					$counter ++;
				}
			}
			$html = "<div class='col-md-12'>" . $html . "</div>";

			return apply_filters( 'rldh_insert_shortcode', $html );
		}

	}
}