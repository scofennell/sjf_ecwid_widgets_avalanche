<?php

/**
 * Product slider widget.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_register_slider() {
	register_widget( 'SJF_ET_Slider' );
}
add_action( 'widgets_init', 'sjf_et_register_slider' );

/**
 * Adds Foo_Widget widget.
 */
class SJF_ET_Slider extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		add_shortcode( SJF_Ecwid_Formatting::get_class_name( __CLASS__ ), array( $this, 'shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		add_filter( 'SJF_Ecwid_Admin_Documentation_get_docs', array( $this, 'get_docs' ), 100 );

		parent::__construct(

			// Base ID.
			SJF_Ecwid_Formatting::get_class_name(  __CLASS__ ),

			// Name.
			sprintf( __( '%s: Slider', 'sjf-et' ), SJF_Ecwid_Helpers::get_plugin_short_title() ),

			// Args.
			array(
				'description' => __( 'Products as a slider.', 'sjf-et' ),
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
			'which_products' => '',
			'image_size'     => 'imageUrl',
			'title'          => '',
			'before_title'   => "<h3 class='$base_class-title'>",
			'after_title'    => '</h3>',
			'before_widget'  => "<div class='$base_class'>",
			'after_widget'   => '</div>',
		), $atts, __CLASS__ );
	
		/**
		 * Dealing with which_products is tricky since the shortcode expects
		 * the product ID's as the array key, as that's how the are saved by
		 * the form checkboxes.
		 */
		
		// If there are no products specified, bail.
		if( empty( $args['which_products'] ) ) {
			return FALSE;
		}

		// Convert the comma-sep list into an array.
		$which_products_array = explode( ',', $args['which_products'] );
		
		// Sanitize each member.
		$which_products_san = array_map( 'absint', $which_products_array );
		
		// Read each value as a key.
		$which_products_out = array_flip( $which_products_san );
		
		$instance['which_products']= $which_products_out;
		$instance['title']= $args['title'];
		$instance['image_size']= $args['image_size'];

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
	
		if( ! isset( $instance['which_products'] ) ) {

			$out = FALSE;
		
		} else {

			$which_products = $instance['which_products'];

			if( ! is_array( $which_products ) ) {
				
				$out = FALSE;

			} else {

				$count = count( $which_products );

				if( empty( $count ) ) {
					
					$out = FALSE;
		
				} else {

					$out = $this -> get_slider( $which_products, $instance['image_size'] );

				}
			}			
		}

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

		$title          = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$image_size     = ! empty( $instance['image_size'] ) ? $instance['image_size'] : '';
		$which_products = ! empty( $instance['which_products'] ) ? $instance['which_products'] : array();

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'which_products' ); ?>"><?php _e( 'Which Products:' ); ?></label> 
			<?php echo SJF_Ecwid_Admin_Helpers::get_collection_as_checkboxes( 'products', $which_products, $this -> get_field_name( 'which_products' ), TRUE ); ?>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><?php _e( 'Image Size:' ); ?></label> 
			<?php echo $this -> get_image_sizes_as_dropdown( $image_size, $this->get_field_name( 'image_size' ) ); ?>
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
		$instance['title']          = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['which_products'] = ( ! empty( $new_instance['which_products'] ) ) ? array_map( 'absint', $new_instance['which_products'] ) : array();
		$instance['image_size']     = ( ! empty( $new_instance['image_size'] ) ) ? sanitize_text_field( $new_instance['image_size'] ) : '';
		
		return $instance;
	}

	/**
	 * Get the ecwid image sizes as a select menu.
	 * 
	 * @param  string $image_size The currently selected size, to power selected().
	 * @param  string $name       The name for this input.
	 * @return string             The ecwid image sizes as a select menu.
	 */
	function get_image_sizes_as_dropdown( $image_size, $name ) {

		$out = '';

		// It's a pity this doesn't ship as part of the Ecwid API.  Basically copy and pasted from the API docs here.
		$sizes = array(
			'thumbnailUrl'      => esc_html__( 'Thumbnail: Size defined in store settings.', 'sjf-et' ),
			'imageUrl'          => esc_html__( 'Image: 500px x 500px, soft crop.', 'sjf-et' ),
			'smallThumbnailUrl' => esc_html__( 'Small Thumbnail: 80px x 80px, soft crop', 'sjf-et' ),
			'originalImageUrl'  => esc_html__( 'Original: Not resized.', 'sjf-et' ),
		);

		// For each image size, build an option and check for selected().
		foreach( $sizes as $k => $v ) {

			$selected = selected( $image_size, $k, FALSE );
			
			$out .= "<option $selected value='$k'>$v</option>";

		}

		// If there were options, wrap them in a select.
		if( ! empty( $out ) ) {
			$out = "<select name='$name'>$out</select>";
		}

		return $out;

	}

	/**
	 * Get the product slider.
	 * 
	 * @param  array $which_products An array of product IDs.
	 * @param  string $image_size The name of an ecwid image size.
	 * @return string A product slider.
	 */
	function get_slider( $which_products, $image_size ) {

		// Make sure we have some products to loop through.
		$which_products = array_keys( $which_products );

		$count = count( $which_products );
		if( empty( $count ) ) { return FALSE; }

		$out = '';

		// Grab the bxslider script.
		wp_enqueue_script( 'bxslider' );

		// Grab our widget script to instantiate the bx slider.
		add_action( 'wp_footer', array( $this, 'script' ) );

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// A class for the sldier module.
		$slider_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ );

		// A class for each slide.
		$slide_class = $slider_class . '-slide';

		// For each product ID, make a remote request (I know, right?) and add a slide to the slider.
		foreach( $which_products as $which_product ) {

			/**
			 * Grab that product from Ecwid.
			 * 
			 * @todo It's unfortunate there is not a way to grab multiple products by ID.
			 */
			$collection = new SJF_Ecwid_Collection( "products/$which_product" );
			$result = $collection -> get_collection();

			if( ! is_array( $result ) ) {
				continue;
			}

			$href  = esc_url( $result['url'] );
			$title = esc_html( $result['name'] );

			// If this product has an image, build a linked image.
			$linked_image = '';
			if( isset( $result[ $image_size ] ) ) {
				$src = esc_url( $result[ $image_size ] );
				$linked_image = "
					<a class='$slide_class-image-link' href='$href'>
						<img src='$src' class='$slide_class-image' >
					</a>
				";

				$linked_image = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_linked_image', $linked_image, $result );

			}

			// If this product has a description, grab the excerpt for it.
			$description = '';
			if( isset( $result['description'] ) ) {

				$description = SJF_Ecwid_Formatting::get_words( $result['description'], 50 );
					
				$description = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_description', $description, $result );

				if( ! empty( $description ) ) {
					$description = "
						<div class='$slide_class-description'>
							$description
						</div>
					";
				}
			
			}

			// Build the slide title.
			$linked_title = "
				<h4 class='$slide_class-title'>
					<a class='$slide_class-title-link' href='$href'>
						$title
					</a>
				</h4>
			";
			$linked_title = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_linked_title', $linked_title, $result );

			// Build the slide.
			$slide = "
				$linked_image
				<div class='$slide_class-caption'>
					$linked_title
					$description
				</div>
			";

			$slide = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_slide', $slide, $result );

			$out .= "<li class='$slide_class'>$slide</li>";

		}

		// The slider wrap.
		if( ! empty( $out ) ) {
			$out = "
				<ul class='bxslider $slider_class'>
					$out
				</ul>
			";
		}

		$out = apply_filters( __CLASS__ . '_' . __FUNCTION__, $out );

		return $out;

	}

	/**
	 * Turn PHP args for our slider into JS args.
	 */
	function script() {
		
		// We're gonna need dashicons.
		wp_enqueue_style( 'dashicons' );

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ );

		// Build custom next arrow.
		$next_text = esc_html__( 'Next', 'sjf-et' );
		$next      = "<span class='$namespace-hide-text'>$next_text</span><span class='dashicons dashicons-arrow-right'></span>";
		$next      = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_next', $next );

		// Build custom prev arrow.		
		$prev_text = esc_html__( 'Previous', 'sjf-et' );
		$prev      = "<span class='$namespace-hide-text'>$prev_text</span><span class='dashicons dashicons-arrow-left'></span>";
		$prev      = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_prev', $prev );

		// Build custom stop button.
		$stop_text = esc_html__( 'Stop', 'sjf-et' );
		$stop      = "<span class='$namespace-hide-text'>$stop_text</span><span class='dashicons dashicons-controls-pause'></span>";
		$stop      = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_stop', $stop );

		// Build custom play button.		
		$play_text = esc_html__( 'Play', 'sjf-et' );
		$play      = "<span class='$namespace-hide-text'>$play_text</span><span class='dashicons dashicons-controls-play'></span>";
		$play      = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_play', $play );

		// These are are used the the BX Slider.
		$args = array(
			'nextText'            => "$next",
	  		'prevText'            => "$prev",
	  		'stopText'            => "$stop",
	  		'startText'           => "$play",
	  		'autoControls'        => true,
	  		'auto'                => true,
	  		'autoControlsCombine' => true,
	  		'autoHover'           => true,
	  		'pause'               => 5000,
		);

		// Let the user customize the options.
		$args = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_args', $args );

		// Turn the args into JSON.
		$args = json_encode( $args );

		// Localize the args.
		$local = array(
			'args'  => $args,
			'class' => $base_class,
		);

		// Return the localized version of the args so it can be used in a JS file.
		return $local;

	}


	/**
	 * Grab info about the blog itself.
	 * 
	 * @return string Info about the blog itself
	 */
	function get_docs( $in ) {

		$docs = new SJF_Ecwid_Admin_Documentation;

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'Slider Shortcode', 'sjf-et' );

		$content_1 = '<p>' . esc_html__( 'The slider shortcode can be used like this:', 'sjf-et') . '</p>'; 
		$content_2 = '<p><code>[sjf_et_slider image_size="" which_products="6093245, 46093237"]</code></p>';
		$content_3 = '<p>' . esc_html__( 'You may specify products, by ID number, comma-seperated, or the shortcode will not output anything.', 'sjf-et' ) . '</p>';
		$content_4 = '<p>' . esc_html__( 'You may also specify an image size: "thumbnailUrl", "imageUrl", "smallThumbnailUrl", "originalImageUrl"', 'sjf-et' ) . '</p>';

		$content = $content_1 . $content_2 . $content_3 . $content_4;
		
		$out = $docs -> get_doc( $label, __FUNCTION__, $content );

		return $in . $out;
	}

}