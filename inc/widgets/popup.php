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

		parent::__construct(

			 // Base ID.
			$namespace . '-popup',

			// Name.
			sprintf( __( '%s: Popup', 'sjf-et' ), SJF_Ecwid_Helpers::get_plugin_title() ),

			// Args.
			array(
				'description' => __( 'Feature a product as a popup overlay.', 'sjf-et' ),
			)
			
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
		
		$out = $before_widget . $out . $after_widget;

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
		
		// When the transients class is initiated with a value of FALSE, it dumps caches.
		$trans = new SJF_Ecwid_Transients( FALSE );

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

		// Get the popup script.
		add_action( 'wp_footer', array( $this, 'popup_script' ) );

		$href = esc_url( $result['url'] );
		$name = esc_html( $result['name'] );
		$id   = esc_attr( $result['id'] );

		// Get the product image.
		$img = '';
		if( isset( $result[ 'imageUrl' ] ) ) {
			$src = esc_url( $result[ 'imageUrl' ] );
			if( ! empty( $src ) ) {
				$img = "
					<a class='$namespace-popup-image-link' href='$href'>
						<img class='$namespace-popup-image' src='$src'>
					</a>
				";
			}		
		}
		$img = apply_filters( "$namespace-popup-image-link", $img, $result );

		$description = '';
		if( isset( $result['description'] ) ) {
			if( ! empty( $result['description'] ) ) {
				$description = SJF_Ecwid_Formatting::get_words( $result['description'], 50 );
				$description = "
					<div class='$namespace-popup-description'>
						$description
					</div>
				";
			}
		}
		$description = apply_filters( "$namespace-popup-description", $description, $result );

		// Build a button to close the popup.
		$close_icon  = '<span class="dashicons dashicons-dismiss"></span>';
		$close_text  = esc_html__( 'Close', 'sjf-et' );
		$close_label = "<span class='$namespace-hide-text'>$close_text</span> $close_icon";
		$close_label = apply_filters( "$namespace-popup-close-label", $close_label, $result );
		$close       = "<a href='#' class='$namespace-popup-close'>$close_label</a>";
		
		// Build the title.
		$title = "
			<h4 class='$namespace-popup-title'>
				<a class='$namespace-popup-title-link' href='$href'>$name</a>
			</h4>
		";
		$title = apply_filters( "$namespace-popup-title", $title, $result );

		$inner = "
			<div class='$namespace-popup-inner'>
				$before_widget
					$widget_title
					$img
					$title
					$description
					$close
				$after_widget
			</div>
		";
		$inner = apply_filters( "$namespace-popup-inner", $inner, $result );

		$out = "
			<div class='$namespace-popup'>
				$inner
			</div>
		";
		$out = apply_filters( "$namespace-popup", $out, $result );

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

		$cookie_name = "$namespace-popup-cookie-$which_product";

		$out = apply_filters( "$namespace-popup-cookie_name", $cookie_name, $which_product );

		return $out;
	}

	/**
	 * Output some JS for the popup script.
	 */
	function popup_script() {

		// Which product are we grabbing?
		$which_product = $this -> which_product;

		// Grab the cookie name for this product.
		$cookie = $this -> get_cookie_name( $which_product );

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// A class for the entire module.
		$popup_class = "$namespace-popup";
		
		// A class for the close button.
		$close_class = "$popup_class-close";

		// We're gonna need the cookie script.
		wp_enqueue_script( 'cookie' );

		$out = <<<EOT
		<script>

			jQuery( document).ready( function( $ ) {
	  			
	  			var popup = $( '.$popup_class' );

	  			var close = $( '.$close_class' );

	  			$( 'body' ).append( popup );

	  			// When we click the close button or the overlay BG, close the popup and save a cookie.
	  			$( [close, popup] ).each( function() {
	  				$( this ).click( function( event ) {
		  				event.preventDefault();
		  				$( popup ).fadeOut();
		  				$.cookie( '$cookie', '1', { expires: 1 } );
		  			});
	  			});

				// We want to be able to click the popup without triggering the call to fade it out.
				$( '.sjf_et-popup-inner' ).click( function( event ) {
					event.stopPropagation();
				});


			});
		</script>
EOT;

		echo $out;

	}

}