<?php

/**
 * A jQuery show/hide for categories and products.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_register_accordion() {
	register_widget( 'SJF_ET_Accordion' );
}
add_action( 'widgets_init', 'sjf_et_register_accordion' );

/**
 * Adds SJF_ET_Show_Hide_Products_Widget widget.
 */
class SJF_ET_Accordion extends WP_Widget {

	/**
	 * Register SJF_ET_Show_Hide_Products_Widget with WordPress.
	 */
	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		add_shortcode( SJF_Ecwid_Formatting::get_class_name( __CLASS__ ), array( $this, 'shortcode' ) );

		add_filter( 'SJF_Ecwid_Admin_Documentation_get_docs', array( $this, 'get_docs' ), 20 );

		parent::__construct(

			// Base ID.
			SJF_Ecwid_Formatting::get_class_name( __CLASS__ ),

			// Name.
			sprintf( __( '%s: Accordion', 'sjf-et' ), SJF_Ecwid_Helpers::get_plugin_short_title() ),

			// Args.
			array(
				'description' => __( 'A list of products, by category, as an accordion show/hide.', 'sjf-et' ),
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

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ );

		$args = shortcode_atts( array(
			'title'         => '',
			'before_title'  => "<h3 class='$base_class-title'>",
			'after_title'   => '</h3>',
			'before_widget' => "<div class='$base_class-wrapper'>",
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
	public function widget( $args = array(), $instance = array(), $echo = TRUE ) {
	
		// The bulk of the widget -- a nested list of products and categories.
		$out = $this -> get_categories_list();

		$before_widget = '';
		if( isset( $args['before_widget'] ) ) {
			$before_widget = $args['before_widget'];
		}

		$after_widget = '';
		if( isset( $args['after_widget'] ) ) {
			$after_widget  = $args['after_widget'];
		}

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
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo esc_html__( 'Title:', 'sjf-et' ); ?></label> 
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
	 * A recursive function to build a nested list of categories & products.
	 * 
	 * @param  mixed $parent_cat Restrict the list to those categories that are a child of a given category.
	 * @return string A nested list of categories & products.
	 */
	function get_categories_list( $parent_cat = FALSE ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ );

		$out = '';

		// If there is a parent cat, restrict the results to children of that cat.
		if( $parent_cat ) {
			$args = array(
				'parent' => $parent_cat,
			);

		// Else, ecwid expects a value of 0 to grab all categories.
		} else {
			$args = array(
				'parent' => 0,
			);
		}

		// Get the categories.
		$collection = new SJF_Ecwid_Collection( 'categories', $args );
		$result = $collection -> get_collection();
		if( ! isset( $result['items'] ) ) {
			return FALSE;
		}

		$items = $result['items'];	
		if( ! is_array( $items ) ) {
			return FALSE;
		}	
		
		// For each category...
		foreach( $items as $cat ) {

			$id   = absint( $cat['id'] );
			$href = esc_url( $cat['url'] );
			$name = esc_html( $cat['name'] );

			// Build the linked title.
			$linked_title = "<a class='$base_class-cat-title' href='$href'>$name</a>";
			$linked_title = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_linked_title', $linked_title, $cat );					

			// Grab the product count.
			$product_int   = absint( $cat['productCount'] );
			$product_count = sprintf( esc_html__( '(%s)', 'sjf-et' ), $product_int );
			$product_count = "<span class='$base_class-cat-product-count'>$product_count</span>";
			$product_count = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_product_count', $product_count, $cat );					

			// Grab child cats recursively.
			$child_cats = $this -> get_categories_list( $id );
			$child_cats = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_child_cats', $child_cats, $cat );					

			// Grab products that fall under this category.
			$products = $this -> get_products_list( $id );
			$products = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_products', $products, $cat );					

			// If there are products or child cats, provide a link to show them.
			$toggle = '';
			if( ! empty( $products ) || ! empty( $child_cats ) ) {
	
				$toggle = SJF_Ecwid_Helpers::get_toggle( array( "$base_class-cat-toggle" ) );
				$toggle = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_toggle', $toggle, $cat );
			}

			// Output this category with all the sub cats and products nested under it.
			$this_cat = "
				<li class='$base_class-cat $namespace-toggle-parent'>
					$linked_title
					$product_count
					$toggle 
					$products 
					$child_cats
				</li>
			";
			$this_cat = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_cat', $this_cat, $cat );

			$out .= $this_cat;
			
		}

		if( ! empty( $out ) ) {

			// We're gonna need dashicons.
			wp_enqueue_style( 'dashicons' );

			// If this cat is udner a parent cat, hide it.
			if( $parent_cat ) {

				$out = "<ul class='$base_class-cats $base_class'>$out</ul>";

			} else {
	
				$out = "<ul class='$base_class-cats'>$out</ul>";

			}

		}

		$out = apply_filters( __CLASS__ . '_' . __FUNCTION__, $out );
		
		return $out;

	}

	/**
	 * Get an HTML list of products from a given category.
	 * 
	 * @param  int $cat_id The category from which we'll grab products.
	 * @return string an HTML list of products from a given category.
	 */
	function get_products_list( $cat_id ) {

		$out = '';

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ );

		// Get the products.
		$args = array(
			'category' => $cat_id,
		);
		$collection = new SJF_Ecwid_Collection( 'products', $args );
		$result = $collection -> get_collection();
		if( ! is_array( $result ) ) {
			return FALSE;
		}
		if( ! isset( $result['items'] ) ) {
			return FALSE;
		}
		$items = $result['items'];

		// For each product...
		foreach( $items as $item ) {

			// Build a link to that product.
			$href = esc_url( $item['url'] );
			$name = esc_html( $item['name'] );
			$name = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_name', $name, $item );

			$product_link = "<a class='$base_class-prod-link' href='$href'>$name</a>";
			$product_link = apply_filters( __CLASS__ . '_' . __FUNCTION__ . '_link', $product_link, $item );

			$out .= "
				<li class='$base_class-prod $base_class-prod-item'>
					$product_link
				</li>
			";

		}

		if( ! empty( $out ) ) {
			$out = "<ul class='$base_class-prods $base_class'>$out</ul>";
			$out = apply_filters( __CLASS__ . '_' . __FUNCTION__, $out );
		}

		return $out;

	}

	/**
	 * Get info about the accordion shortcode.
	 * 
	 * @return string Info about the accordion shortcode.
	 */
	function get_docs( $in ) {

		$docs = new SJF_Ecwid_Admin_Documentation;

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'Accordion Shortcode', 'sjf-et' );
		
		$content_1 = '<p>' . esc_html__( 'The accordion shortcode can be used like this:', 'sjf-et') . '</p>'; 
		$content_2 = '<p><code>[sjf_et_accordion]</code></p>';
		$content_3 = '<p>' . esc_html__( 'This shortcode does not feature any options.', 'sjf-et' ) . '</p>';

		$content = $content_1 . $content_2 . $content_3;

		$out = $docs -> get_doc( $label, __FUNCTION__, $content );

		return $in . $out;

	}

}