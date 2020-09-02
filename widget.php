<?php
defined('ABSPATH') or die("you do not have acces to this page!");

function wpdef_register_widget() {
	register_widget(  'wpdef_widget');
}
add_action('widgets_init',  'wpdef_register_widget');

class wpdef_widget extends WP_Widget {
	public function __construct() {
		$widget_ops = array( 'description' => __( "A list of your most used definitions.", 'definitions') );
		parent::__construct('rldl_definitions_widget', __('Definitions list'), $widget_ops);
	}

	public function widget( $args, $instance ) {
		if ( !empty($instance['title']) ) {
			$title = $instance['title'];
		} else {
			$title = __('Definitions', 'definitions');
		}

		if (!empty($instance['nr_of_definitions'])) {
			$nr_of_definitions = $instance['nr_of_definitions'];
		} else {
			$nr_of_definitions = DEFINITIONS_COUNT;
		}

		$title = apply_filters( 'rldh_widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo '<div class="rldh-definitions-list-wrapper">';
		//return a list of definitions here
		$definitions_args = array(
			'post_type'        => __('Definition','definitions'),
			'post_status'      => 'publish',
			'orderby'          => 'rand',
			'numberposts'      => $nr_of_definitions,
		);
		$definitions = get_posts( apply_filters( 'rldh_definitions_widget_list_query',$definitions_args ));
		if ( $definitions ) {

		}

		echo $args['after_widget'];
	}

	public function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['nr_of_definitions'] = stripslashes($new_instance['nr_of_definitions']);
		return $instance;
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Definitions', 'zip-recipes' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( esc_attr( 'Title:' ) ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('nr_of_definitions'); ?>"><?php echo __('Number of definitions listed','definitions'); ?></label>
			<input type="text" size="3" id="<?php echo $this->get_field_id('nr_of_definitions'); ?>" name="<?php echo $this->get_field_name('nr_of_definitions'); ?>" value="<?php if (isset ( $instance['nr_of_definitions'])) {echo esc_attr( $instance['nr_of_definitions'] );} else {echo DEFINITIONS_COUNT;} ?>" />
		</p>
		<?php

	}
}


