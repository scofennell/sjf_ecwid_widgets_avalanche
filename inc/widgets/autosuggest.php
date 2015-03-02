<?php

/**
 * Product autosuggest widget.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 1.2
 */

function sjf_et_register_autosuggest() {
	register_widget( 'SJF_ET_Autosuggest' );
}
add_action( 'widgets_init', 'sjf_et_register_autosuggest' );

/**
 * Adds Foo_Widget widget.
 */
class SJF_ET_Autosuggest extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		add_shortcode( SJF_Ecwid_Formatting::get_class_name( __CLASS__ ), array( $this, 'shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		add_filter( 'SJF_Ecwid_Admin_Documentation_get_docs', array( $this, 'get_docs' ), 30 );

		parent::__construct(

			// Base ID.
			SJF_Ecwid_Formatting::get_class_name( __CLASS__ ),

			// Name.
			sprintf( __( '%s: Autosuggest', 'sjf-et' ), SJF_Ecwid_Helpers::get_plugin_short_title() ),

			// Args.
			array(
				'description' => __( 'Products as an autosuggest.', 'sjf-et' ),
			)
		
		);
	}

	/**
	 * Grab our php variables for this widget and load them into our plugin-wide JS file.
	 */
	function enqueue() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// The php vars for this widget.
		$local = $this -> script();

		// Send to the plugin-wide JS file.
		wp_localize_script( $namespace . '_scripts', __CLASS__, $local );

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
			'title'         => '',
			'before_title'  => "<h3 class='$base_class-title'>",
			'after_title'   => '</h3>',
			'before_widget' => "<div class='$base_class'>",
			'after_widget'  => '</div>',
		), $atts, __CLASS__ );
	
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
	
		$out = $this -> get_autosuggest();

		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		
		$title = '';
		if ( ! empty( $instance['title'] ) ) {
			$before_title = $args['before_title'];
			$after_title  = $args['after_title'];
			$title        = $before_title . apply_filters( 'widget_title', $instance['title'] ) . $after_title;
		}
		
		$out = $before_widget . $title . $out . $after_widget;

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

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		
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
		
		return $instance;
	}

	/**
	 * Get products as an autosuggest source array.
	 * 
	 * @see http://jqueryui.com/autocomplete/
	 * @return array Products as an autosuggest source array.
	 */
	function get_products_as_autosuggest_source() {

		$out = '';

		$autosuggest_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ );

		// Get all products.
		$collection = new SJF_Ecwid_Collection( 'products' );
		$result = $collection -> get_collection();
		$items = $result['items'];
		if( ! is_array( $items ) ) {
			return FALSE;
		}

		// For each product...
		$out = array();
		foreach( $items as $item ) {

			// Grab the product title and url.
			$title = $item['name'];
			$url   = $item['url'];

			// The source for this product in the autosuggest.
			$out[]= array(
				'label' => wp_strip_all_tags( $title ),
				'value' => esc_attr( $url ),
			);
			
		}

		$out = apply_filters( __CLASS__ . '_' . __FUNCTION__, $out );

		return $out;

	}

	/**
	 * Get the product autosuggest.
	 * 
	 * @return string A product autosuggest.
	 */
	function get_autosuggest() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// If there are no products to get, don't show a form.
		if( ! $this -> get_products_as_autosuggest_source() ) { return FALSE; }

		// Grab the jquery-ui-autocomplete  script.
		wp_enqueue_script( 'jquery-ui-autocomplete' );

		// We're gonna need dashicons.
		wp_enqueue_style( 'dashicons' );

		// A class for the autosuggest module.
		$autosuggest_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ );

		// Build search icon.
		$icon = "<span class='$autosuggest_class-dashicons $autosuggest_class-dashicons-search dashicons dashicons-search'></span>";
		
		// Build some screen reader text.
		$search_text = esc_html__( 'Search Products:', 'sjf-et' );
		$search_text = "<span class='$namespace-hide-text'>$search_text</span>";

		// Build the form label.
		$label = "<label class='$autosuggest_class-label' for='$autosuggest_class-input'>$search_text $icon</label>";
		$label = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_label', $label );		

		// Build the form input.
		$input = "<input required id='$autosuggest_class-input' class='$autosuggest_class-input' type='search'>";
		$input = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_input', $input );		

		// Build the autosuggest output.
		$output = "<output class='$autosuggest_class-suggestions'></output>";
		$output = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_output', $output );

		// The autosuggest wrap.
		$out = "
			<form class='ui-widget $autosuggest_class sjf_et-clear'>
				$label
				$input
				$output
			</form>
		";
		$out = apply_filters( __CLASS__ . '_' . __FUNCTION__, $out );

		return $out;

	}

	/**
	 * Turn PHP args for our autosuggest into JS args.
	 */
	function script() {
		
		$namespace  = SJF_Ecwid_Helpers::get_namespace();
		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ );

		// Grab the products as autosuggest source.
		$source = $this -> get_products_as_autosuggest_source();

		// Warning text in case the user tries to submit the form with some garbage value.
		$error = '<p class="' . $base_class . '-error">' . esc_html__( 'Please choose from the list of terms.', 'sjf-et' ) . '</p>';
		
		// Localize the args.
		$local = array(
			'source' => $source,
			'error'  => $error,
			'class'  => $base_class,
		);

		// Return the localized version of the args so it can be used in a JS file.
		return $local;

	}

	/**
	 * Get info about the autosuggest shortcode.
	 * 
	 * @return string Info about the autosuggest shortcode.
	 */
	function get_docs( $in ) {
		
		$docs = new SJF_Ecwid_Admin_Documentation;
	
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'Autosuggest Shortcode', 'sjf-et' );
		
		$content_1 = '<p>' . esc_html__( 'The autosuggest shortcode can be used like this:', 'sjf-et') . '</p>'; 
		$content_2 = '<p><code>[sjf_et_autosuggest]</code></p>';
		$content_3 = '<p>' . esc_html__( 'This shortcode does not feature any options.', 'sjf-et' ) . '</p>';

		$content = $content_1 . $content_2 . $content_3;
		
		$out = $docs -> get_doc( $label, __FUNCTION__, $content );

		return $in . $out;
	
	}

}