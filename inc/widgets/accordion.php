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

		parent::__construct(

			// Base ID.
			$namespace . '-accordion',

			// Name.
			sprintf( __( '%s: Accordion', 'sjf-et' ), SJF_Ecwid_Helpers::get_plugin_title() ),

			// Args.
			array(
				'description' => __( 'A list of products, by category, as an accordion show/hide.', 'sjf-et' ),
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
	
		// The bulk of the widget -- a nested list of products and categories.
		$out = $this -> get_categories_list();

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
		
		// When the transients class is initiated with a value of FALSE, it dumps caches.
		$trans = new SJF_Ecwid_Transients( FALSE );

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
			$linked_title = "<a class='$namespace-accordion-cat-title' href='$href'>$name</a>";
			$linked_title = apply_filters( "$namespace-accordion-cat-title", $linked_title, $cat );					

			// Grab the product count.
			$product_int   = absint( $cat['productCount'] );
			$product_count = sprintf( esc_html__( '(%s)', 'sjf-et' ), $product_int );
			$product_count = "<span class='$namespace-accordion-cat-product-count'>$product_count</span>";
			$product_count = apply_filters( "$namespace-accordion-cat-product_count", $product_count, $cat );					

			// Grab child cats recursively.
			$child_cats = $this -> get_categories_list( $id );
			$child_cats = apply_filters( "$namespace-accordion-cat-child_cats", $child_cats, $cat );					

			// Grab products that fall under this category.
			$products = $this -> get_products_list( $id );
			$products = apply_filters( "$namespace-accordion-cat-products", $products, $cat );					

			// If there are products or child cats, provide a link to show them.
			$toggle = '';
			if( ! empty( $products ) || ! empty( $child_cats ) ) {
	
				$dashicon = "<span class='dashicons dashicons-arrow-down-alt'></span>";
				$toggle   = "<a class='$namespace-toggle $namespace-accordion-cat-toggle' href='#'>$dashicon</a>";
				$toggle   = apply_filters( "$namespace-accordion-cat-toggle", $toggle, $cat );
			}

			// Output this category with all the sub cats and products nested under it.
			$this_cat = "
				<li class='$namespace-accordion-cat $namespace-toggle-parent'>
					$linked_title
					$product_count
					$toggle 
					$products 
					$child_cats
				</li>
			";
			$this_cat = apply_filters( "$namespace-accordion-cat", $this_cat, $cat );

			$out .= $this_cat;
			
		}

		if( ! empty( $out ) ) {

			// We're gonna need dashicons.
			wp_enqueue_style( 'dashicons' );

			// We're gonna need some JS to do show/hide.
			add_action( 'wp_footer', array( $this, 'show_hide_script' ) );

			// If this cat is udner a parent cat, hide it.
			if( $parent_cat ) {

				$out = "<ul class='$namespace-accordion-cats $namespace-accordion'>$out</ul>";

			} else {
	
				$out = "<ul class='$namespace-accordion-cats'>$out</ul>";

			}

		}

		$out = apply_filters( "$namespace-accordion-cats", $out );		

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
			$name = apply_filters( "$namespace-accordion-prod-name", $name, $item );

			$product_link = "<a class='$namespace-accordion-prod-link' href='$href'>$name</a>";
			$product_link = apply_filters( "$namespace-accordion-prod-link", $product_link, $item );

			$out .= "
				<li class='$namespace-accordion-prod $namespace-accordion-prod-item'>
					$product_link
				</li>
			";

		}

		if( ! empty( $out ) ) {
			$out = "<ul class='$namespace-accordion-prods $namespace-accordion'>$out</ul>";
			$out = apply_filters( "$namespace-accordion-prods", $out );
		}

		return $out;

	}

	/**
	 * Output some JS to power our show/hide.
	 */
	function show_hide_script() {

		if( is_admin() ) { return FALSE; }

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$out = <<<EOT
			<script>
				jQuery( document ).ready( function( $ ) {
			        var hide = $( '.$namespace-accordion' );
    				$( hide ).hide();

    				var toggle = $( '.$namespace-toggle' );
    				$( toggle ).click( function( event ) {
    					event.preventDefault();
    					$( this ).siblings( '.$namespace-accordion' ).slideToggle();
    					$( this ).find( '.dashicons' ).toggleClass( 'dashicons-arrow-down-alt dashicons-arrow-up-alt' );
    				});

    			});
			</script>
EOT;

		echo $out;
	}

}