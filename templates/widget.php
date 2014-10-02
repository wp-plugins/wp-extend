<?php
/**
* Custom Widget: Name of Widget
*
* This is all you need to set up a custom widget in the Dashboard.
* Configure the widget settings, configure the form for the user (if there are values
* that the user can save) and then output the saved values.
*
* WPX is used for uniqueness; you can change this to anything.
*
* @package    WordPress
* @subpackage Your Theme 
* @since 3.0
*/

add_action( 'widgets_init', 'widget_sample_widget' );

function widget_sample_widget() {
	register_widget( 'widget_sample_widget' );
}

class widget_sample_widget extends WP_Widget {
	
	// some basic configuration options
	function widget_sample_widget() {
		$widget_ops = array( 'classname' => 'widget-sample', 'description' => 'Displays a sample widget.' );
		$control_ops = array( 'width' => 300, 'height' => 650, 'id_base' => 'widget-sample' );
		$this->WP_Widget( 'widget-sample', 'Sample Widget', $widget_ops, $control_ops );
	}

	// we create field options here; delete this function if the widget
	// has no values that can be saved by the user
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>Fill out the below fields to configure the Sample widget.</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'value1' ); ?>">Value 1:</label><br />
			<input id="<?php echo $this->get_field_id( 'value1' ); ?>" style="width: 250px;" name="<?php echo $this->get_field_name( 'value1' ); ?>" value="<?php echo $instance['value1']; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'value2' ); ?>">Value 2:</label><br />
			<input id="<?php echo $this->get_field_id( 'value2' ); ?>" style="width: 250px;" name="<?php echo $this->get_field_name( 'value2' ); ?>" value="<?php echo $instance['value2']; ?>" class="widefat" />
		</p>
	<?php
	}

	// save the field values (delete this function otherwise)
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		// extract all saved values
		$instance['value1'] = $new_instance['value1'];
		$instance['value2'] = $new_instance['value2'];
		
		return $instance;
	}

	// output the widget data
	function widget( $args, $instance ) {
		extract( $args );

		// extract any values
		$value1 = $instance['value1'];
		$value2 = $instance['value2'];

		echo $before_widget; ?>

		<!-- widget output -->
		
		<?php echo $after_widget;
	}

}
?>