<?php

/**
 * A product popup widget.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_register_popup() {
	register_widget( 'SJF_ET_Popup' );
}
add_action( 'widgets_init', 'sjf_et_register_popup' );

/**
 * Adds Foo_Widget widget.
 */
class SJF_ET_Popup extends WP_Widget {

	// Set the value for which product ID we have in the popup.
	public $which_product;

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		add_shortcode( SJF_Ecwid_Formatting::get_class_name( __CLASS__ ), array( $this, 'shortcode' ) );

		add_filter( 'SJF_Ecwid_Admin_Documentation_get_docs', array( $this, 'get_docs' ), 70 );

		parent::__construct(

			 // Base ID.
			SJF_Ecwid_Formatting::get_class_name( __CLASS__ ),

			// Name.
			sprintf( __( '%s: Popup', 'sjf-et' ), SJF_Ecwid_Helpers::get_plugin_short_title() ),

			// Args.
			array(
				'description' => __( 'Feature a product as a popup overlay.', 'sjf-et' ),
			)
			
		);
	}

	/**
	 * Send the widget as a shortcode.
	 * 
	 * @param  array $atts An array of shortcode args.
	 * @return string      The widget html output.
	 */
	public function shortcode( $atts ) {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_'.  __FUNCTION__ );

		$args = shortcode_atts( array(
			'which_product' => '',
			'title'         => '',
			'before_title'  => "<h3 class='$base_class-title'>",
			'after_title'   => '</h3>',
			'before_widget' => "<div class='$base_class'>",
			'after_widget'  => '</div>',
		), $atts, __CLASS__ );
	
		if( empty( $args['which_product'] ) ) { return FALSE; }

		$instance['which_product']= $args['which_product'];
		$instance['title']= $args['title'];

		$out = $this -> widget( $args, $instance, FALSE );

		$out = apply_filters( __CLASS__ . '_' . __FUNCTION__, $out );

		return $out;

	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args = array(), $instance = array(), $echo = TRUE  ) {

		// We need to have chosen a product in order for this to be worthwhile.
		if( ! isset( $instance['which_product'] ) ) {
			return FALSE;
		}
		$which_product = $instance['which_product'];

		// We want to be able to reference this product in other subsequent functions.
		$this -> which_product = $which_product;

		// Grab the cookie that is saved when the user closes the popup for this product.
		$cookie = $this -> get_cookie_name( $which_product );
			
		// If there is such a cookie, don't bother showing the popup again.
		if( isset( $_COOKIE[ $cookie ] ) ) {
			return FALSE;
		}

		$title = '';
		if ( ! empty( $instance['title'] ) ) {
			$before_title = $args['before_title'];
			$after_title  = $args['after_title'];
			$title        = $before_title . apply_filters( 'widget_title', $instance['title'] ) . $after_title;
		}

		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];

		// Grab the popup.
		$out = $this -> get_popup( $which_product, $title, $before_widget, $after_widget );
		
		if( ! $out ) { return FALSE; }
		
		if( $echo ) {
			echo $out;
		} else {
			return $out;
		}

	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		
		echo SJF_Ecwid_Helpers::get_nag();

		$title         = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$which_product = ! empty( $instance['which_product'] ) ? $instance['which_product'] : '';
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'which_product' ); ?>"><?php _e( 'Which Product:', 'sjf-et' ); ?></label> 
			<?php echo $this -> get_products_as_dropdown( $which_product, $this->get_field_name( 'which_product' ) ); ?>
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
		
		/**
		 * When the transients class is initiated with a value of FALSE (first arg), it dumps caches.
		 * However the second arg, FALSE, tells it not to dump rewrite rules, since that would break the
		 * widget customizer screen.
		 */
		$trans = new SJF_Ecwid_Transients( FALSE, FALSE );

		$instance = array();

		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['which_product'] = ( ! empty( $new_instance['which_product'] ) ) ? absint( $new_instance['which_product'] ) : array();
		
		return $instance;
	}

	/**
	 * Get an HTML select menu for choosing which product.
	 * 
	 * @param  int $which_product The current product ID.
	 * @param  string $name The name for the select element.
	 * @return string An HTML select menu for choosing which product.
	 */
	function get_products_as_dropdown( $which_product, $name ) {

		$out = '';

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Get all products.
		$collection = new SJF_Ecwid_Collection( 'products' );
		$result = $collection -> get_collection();
		
		if( ! isset ( $result['items'] ) ) {
			return FALSE;
		}

		$items = $result['items'];
		if( ! is_array( $items ) ) {
			return FALSE;
		}

		// For each product...
		foreach( $items as $item ) {

			$title = esc_html( $item['name'] );
			$id    = esc_attr( $item['id'] );

			// Is this item the sticky value?
			$selected = selected( $which_product, $id, FALSE );
			
			$out .= "<option $selected value='$id'>$title</option>";

		}

		if( ! empty( $out ) ) {
			$out = "<select name='$name'>$out</select>";
		}

		return $out;

	}

	/**
	 * Get a product popup.
	 * 
	 * @param  int $which_product An ecwid product ID.
	 * @param  string $widget_title  The widget title, wrapped in theme html.
	 * @param  string $before_widget The theme-defined html for pre-widget.
	 * @param  string $after_widget  The theme-defined html for post-widget.
	 * @return string A popup popup.
	 */
	function get_popup( $which_product, $widget_title, $before_widget, $after_widget ) {

		$out = '';

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		if( ! is_scalar( $which_product ) ) { return FALSE; }

		// Get the product.
		$collection = new SJF_Ecwid_Collection( "products/$which_product" );
		$result = $collection -> get_collection();
		if( ! isset( $result['id'] ) ) {
			return FALSE;		
		}
		if( ! is_array( $result ) ) {
			return FALSE;
		}

		// We're gonna need dashicons.
		wp_enqueue_style( 'dashicons' );

		// We're gonna need the cookie script.
		wp_enqueue_script( 'cookie' );

		$href = esc_url( $result['url'] );
		$name = esc_html( $result['name'] );
		$id   = esc_attr( $result['id'] );

		// Get the product image.
		$img = '';
		if( isset( $result[ 'imageUrl' ] ) ) {
			$src = esc_url( $result[ 'imageUrl' ] );
			if( ! empty( $src ) ) {
				$img = "
					<a class='$base_class-image-link' href='$href'>
						<img class='$base_class-image' src='$src'>
					</a>
				";
			}		
		}
		$img = apply_filters(__CLASS__ . '_' . __FUNCTION__ . '_image', $img, $result );

		$description = '';
		if( isset( $result['description'] ) ) {
			if( ! empty( $result['description'] ) ) {
				$description = SJF_Ecwid_Formatting::get_words( $result['description'], 50 );
				$description = "
					<div class='$base_class-description'>
						$description
					</div>
				";
			}
		}
		$description = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_description', $description, $result );

		// Build a button to close the popup.
		$close_icon  = '<span class="dashicons dashicons-dismiss"></span>';
		$close_text  = esc_html__( 'Close', 'sjf-et' );
		$close_label = "<span class='$namespace-hide-text'>$close_text</span> $close_icon";
		$close_label = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_close_label', $close_label, $result );
		$close       = "<a href='#' class='$base_class-close'>$close_label</a>";
		
		// Build the title.
		$title = "
			<h4 class='$base_class-title'>
				<a class='$base_class-title-link' href='$href'>$name</a>
			</h4>
		";
		$title = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_title', $title, $result );

		$inner = "
			<div class='$base_class-inner'>
				$before_widget
					$widget_title
					$img
					$title
					$description
					$close
				$after_widget
			</div>
		";
		$inner = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_inner', $inner, $result );

		$cookie_name = $this -> get_cookie_name( $which_product );

		$out = "
			<div class='$base_class' data-cookie='$cookie_name'>
				$inner
			</div>
		";
		$out = apply_filters( __CLASS__ . '_' . __FUNCTION__, $out, $result );
		return $out;

	}

	/**
	 * Grab the name of the cookie for this product.
	 * 
	 * @param  int $which_product An ecwid product ID.
	 * @return string The name of the cookie for this product.
	 */
	function get_cookie_name( $which_product ) {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		if( ! is_scalar( $which_product ) ) { return FALSE; }

		$cookie_name = __CLASS__ .'_' .  __FUNCTION__ . '_' . $which_product;

		$out = apply_filters( __CLASS__ . '_' . __FUNCTION__, $cookie_name, $which_product );
		return $out;
	}

	/**
	 * Grab info about the popup shortcode.
	 * 
	 * @return string info about the popup shortcode.
	 */
	function get_docs( $in ) {

		$docs = new SJF_Ecwid_Admin_Documentation;

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'Popup Shortcode', 'sjf-et' );
		
		$content_1 = '<p>' . esc_html__( 'The popup shortcode can be used like this:', 'sjf-et') . '</p>'; 
		$content_2 = '<p><code>[sjf_et_popup which_product="46093237"]</code></p>';
		$content_3 = '<p>' . sprintf( esc_html__( 'You must specify a product, by ID number, or the shortcode will not output anything. In the above example, %s is a product ID number.', 'sjf-et' ), '<code>46093237</code>' ) . '</p>';

		$content = $content_1 . $content_2 . $content_3;

		$out = $docs -> get_doc( $label, __FUNCTION__, $content );

		return $in . $out;


	}

}