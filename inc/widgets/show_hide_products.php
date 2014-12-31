<?php
/**
 * [register_foo_widget description]
 * @return {[type]} [description]
 */

function sjf_et_register_show_hide_products_widget() {
	register_widget( 'SJF_ET_Show_Hide_Products_Widget' );
}
add_action( 'widgets_init', 'sjf_et_register_show_hide_products_widget' );

/**
 * Adds Foo_Widget widget.
 */
class SJF_ET_Show_Hide_Products_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		parent::__construct(
			$namespace . 'show_hide_products', // Base ID
			__( 'Ecwid Widgets Avalanche: Show/Hide Products', 'sjf_et' ), // Name
			array( 'description' => __( 'A list of product, by category, as a show/hide.', 'sjf_et' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
	
		$out = $this -> products_list();

		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		
		$title = '';
		if ( ! empty( $instance['title'] ) ) {
			$before_title = $args['before_title'];
			$after_title  = $args['after_title'];
			$title        = $before_title . apply_filters( 'widget_title', $instance['title'] ) . $after_title;
		}
		
		$out = $before_widget . $title . $out . $after_widget;

		echo $out;

	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
				$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

	function products_list() {

		$out = '';

		$collection = new SJF_Ecwid_Collection( 'products' );

		$result = $collection -> get_collection();
	
		$body = json_decode( $result['body'], TRUE );

		$items = $body['items'];

		foreach( $items as $item ) {

			$href  = esc_url( $item['url'] );
			$name = esc_html( $item['name'] );
			$out  .= "<li><a href='$href'>$name</a></li>";
		}

		if( ! empty( $out ) ) {
			$out = "<ul>$out</ul>";
		}

		return $out;

	}

} // class Foo_Widget