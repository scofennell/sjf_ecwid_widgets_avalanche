<?php

/**
 * Product sortable table widget.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 1.4
 */

function sjf_et_register_sortable() {
	register_widget( 'SJF_ET_Sortable' );
}
add_action( 'widgets_init', 'sjf_et_register_sortable' );

/**
 * Adds Foo_Widget widget.
 */
class SJF_ET_Sortable extends WP_Widget {

	public $products_already_output = array();

	public $which_categories = array();

	/**
	 * The slug for our feed.
	 */
	public function get_feed_slug() {
		return strtolower( __CLASS__ );
	}

	/**
	 * The class that identifies the div into which the widget will load.
	 */
	public function get_loader_class() {
		return SJF_Ecwid_Formatting::get_class_name( __CLASS__ ) . '-load';
	}

	public function get_feed_url() {
		$slug = $this -> get_feed_slug();
		$out  = get_feed_link( $slug );
		return $out;
	}

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		add_shortcode( SJF_Ecwid_Formatting::get_class_name( __CLASS__ ), array( $this, 'shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 999 );

		add_filter( 'SJF_Ecwid_Admin_Documentation_get_docs', array( $this, 'get_docs' ), 70 );

		// We need to wait until init before we add a feed.
		add_action( 'init', array( $this, 'init' ) );

		parent::__construct(

			// Base ID.
			SJF_Ecwid_Formatting::get_class_name( __CLASS__ ),
			
			// Name.
			sprintf( __( '%s: Sortable Table', 'sjf-et' ), SJF_Ecwid_Helpers::get_plugin_short_title() ),

			// Args.
			array(
				'description' => __( 'Products as a sortable table.', 'sjf-et' ),
			)
		
		);
	}

	public function enqueue() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// The php vars for this widget.
		$local = $this -> script();

		// Send to the plugin-wide JS file.
		wp_localize_script( $namespace . '_scripts', __CLASS__, $local );

		// Grab the bxsortable script.
		wp_enqueue_script( 'tablesorter' );

	}

	/**
	 * Register our feed with WordPress.
	 */
	public function init() {
		add_feed( $this -> get_feed_slug(), array( $this, 'the_feed' ) );
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
			'which_categories' => '',
			'title'            => '',
			'before_title'     => "<h3 class='$base_class-title'>",
			'after_title'      => '</h3>',
			'before_widget'    => "<div class='$base_class'>",
			'after_widget'     => '</div>',
		), $atts, __CLASS__ );
	
		/**
		 * Dealing with which_categories is tricky since the shortcode expects
		 * the cat ID's as the array key, as that's how the are saved by
		 * the form checkboxes.
		 */

		// Convert the comma-sep list into an array.
		$which_categories_array = explode( ',', $args['which_categories'] );
		
		// Sanitize each member.
		$which_categories_san = array_map( 'absint', $which_categories_array );
		
		// Read each value as a key.
		$which_categories_out = array_flip( $which_categories_san );
		
		$instance['which_categories']= $which_categories_out;
		$instance['title']= $args['title'];
		
		$out = $this -> widget( $args, $instance, FALSE );

		$out = apply_filters(  __CLASS__ . '_' . __FUNCTION__, $out );

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
		
		// From which categories are we grabbing?
		$which_categories = '';
		if( isset( $instance['which_categories'] ) ) {
			$which_categories = $instance['which_categories'];
			$which_categories = implode( ',', array_keys( $which_categories ) );

		}

		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		
		$title = '';
		if ( ! empty( $instance['title'] ) ) {
			$before_title = $args['before_title'];
			$after_title  = $args['after_title'];
			$title        = $before_title . apply_filters( 'widget_title', $instance['title'] ) . $after_title;
		}

		$id           = SJF_Ecwid_Formatting::get_class_name( $args['widget_id'] );
		$loader_class = $this -> get_loader_class();
	
		// This div gets the table ajax'd into it.
		$load   = "<div id='$id' class='$loader_class' data-which_categories='$which_categories'></div>";

		$out = "
			$before_widget
				$title
				$load
			$after_widget
		";

		if( $echo ) {
			echo $out;
		} else {
			return $out;
		}

	}

	// The feed is a url that hosts the html for the table.  That url gets loaded via ajax.
	public function the_feed() {
		$which_categories = '';
		if( ! isset( $_GET['which_categories'] ) ) { return FALSE; }
		$cats = explode( ',', $_GET['which_categories'] );
		$which_categories = array();
		foreach( $cats as $c ) {
			$which_categories[ $c ]= 1;
		}

		$out = $this -> get_sortable( $which_categories );
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

		$title            = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$which_categories = ! empty( $instance['which_categories'] ) ? $instance['which_categories'] : array();

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'which_categories' ); ?>"><?php _e( 'Which Categories:' ); ?></label> 
			<?php echo SJF_Ecwid_Admin_Helpers::get_collection_as_checkboxes( 'categories', $which_categories, $this -> get_field_name( 'which_categories' ), TRUE ); ?>
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
		$instance['title']            = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['which_categories'] = ( ! empty( $new_instance['which_categories'] ) ) ? array_map( 'absint', $new_instance['which_categories'] ) : array();
		
		return $instance;
	}

	/**
	 * Get the product sortable.
	 * 
	 * @param  array $which_categories An array of product IDs.
	 * @return string A product sortable.
	 */
	function get_sortable( $which_categories ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// A class for the sortable module.
		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		// Which columns will we loop through for each product?
		$which_columns = $this -> get_which_columns();

		// A class for each row.
		$row_class = "$namespace-row";

		// Make sure we have some products to loop through.
		$num_cats = 0;
		if( is_array( $which_categories ) ) {
			$which_categories = array_keys( $which_categories );

			// If there are no categories specified, get all categories.
			$num_cats = count( $which_categories );

		}

		if( empty( $num_cats ) ) {
			$which_categories = SJF_Ecwid_Helpers::get_all_category_ids();	
		}

		$out = '';

		// For each product ID, make a remote request (I know, right?) and add a row to the sortable.
		foreach( $which_categories as $which_category ) {

			$out .= $this -> get_category_rows( $which_category, $which_columns, $base_class );
		}

		// The sortable wrap.
		if( ! empty( $out ) ) {

			$head = $this -> get_table_head( $which_columns );

			$out = "
				<table class='$base_class'>
					$head
					$out
				</table>
			";
		}

		$out = apply_filters(  __CLASS__ . '_' . __FUNCTION__, $out );

		return $out;

	}

	function get_category_rows( $which_category, $which_columns, $base_class ) {
		
		$out = '';

		/**
		 * Grab products, from this category, from Ecwid.
		 * 
		 * @todo It's unfortunate there is not a way to grab multiple products by category ID.
		 */
		$args = array(
			'category'          => $which_category,
			'with_subcategories' => TRUE,
		);
		$collection   = new SJF_Ecwid_Collection( "products", $args );
		$get_products = $collection -> get_collection();

		// If our query was weird, forget it.
		if( ! isset( $get_products['items'] ) ) { return FALSE; }
		
		$products = $get_products['items'];
		
		// If our products are weird, forget it.
		if( ! is_array( $products ) ) { return FALSE; }

		$num_products = count( $products );
		if( empty( $num_products ) ) {
			return FALSE;
		}

		// For each product from this category ...
		foreach( $products as $product ) {
		
			// If this product has already been used in the table, skip it.
			if( in_array( $product['id'], $this -> products_already_output ) ) { continue; }

			// Add this product to the array of products that have already appeared, so we don't show the same one multiple times in case it is in multiple cats.
			$this -> products_already_output[]= $product['id'];

			$out .= $this -> get_product_row( $product, $which_columns, $base_class );

		}
	
		return $out;

	}

	function get_product_row( $product, $which_columns, $base_class ) {

		$out = '';

		$url  = esc_url( $product['url'] );
		$name = esc_html( $product['name'] );

		$row = '';

		// Way earlier in the script, we defined an array of columns for our table.  For each column...
		foreach( $which_columns as $col_key => $col_array ) {

			// If the product does not have a value for this column, just give it a space so we don't send an empty cell.
			if( ! isset( $product[ $col_key ] ) ) {
				$val = '&nbsp;';

			} else {

				$val = $product[ $col_key ];

				// If we're grabbing the price, format it as such.
				if( $col_key == 'price' ) {
					
					// Prices seem to have trouble sorting properly, so let's add a machine-radable string for sorting.
					$sort = absint( $val );
					$val = "<span style='display: none;'>$sort</span>" . SJF_Ecwid_Formatting::get_money( $val );
				
				// If we are giving a list of categories, we do need to do some work to output that.
				} elseif( $col_key == 'categoryIds' ) {
					$val = $this -> cat_ids_to_link_list( $val );

				// If it happened to be an array, dig in a little.
				} elseif( is_array( $val ) ) {
					
					$val_array = $val;
					$val = '';
					foreach( $val_array as $v ) {
						$val .= $v;
					}
				}

				// If this field needs to link to something, wrap it in a link.
				if( isset( $col_array['link_to'] ) ) {

					// Maybe link to the single product view.
					if( $col_array['link_to'] == 'url' ) {
						$val = "<a class='$base_class-cell-link' href='$url'>$val</a>";
					}

				}

			}

			// Wrap the cell in a td.
			$cell = "<td class='$base_class-cell'>$val</td>";

			// Add the cell to the row.
			$row .= $cell;

		}

		$row = apply_filters(  __CLASS__ . '_' . __FUNCTION__ . '_row', $row, $product );

		$out .= "<tr class='$base_class-row'>$row</tr>";

		return $out;

	}

	/**
	 * Define an array of columns for our table.
	 * 
	 * @return array A multi-dim array of columns for our table.
	 */
	function get_which_columns() {
		
		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		$out = array(

			'name' => array(
				'label'   => esc_html__( 'Name', 'sjf-et' ),
				'link_to' => 'url',
			),

			'categoryIds' => array(
				'label' => esc_html__( 'Categories' , 'sjf-et' ),
			),

			'price' => array(
				'label' => esc_html__( 'Price', 'sjf-et' ),
			),

		);

		$out = apply_filters( $base_class, $out );

		return $out;

	}

	/**
	 * Get the header row for our table.
	 * 
	 * @return string The header row for our table.
	 */
	function get_table_head() {

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_'.  __FUNCTION__ );

		$out = '';

		// Which columns will be in our table head?
		$which_columns = $this -> get_which_columns();

		// Grab a sort dashicon.
		wp_enqueue_style( 'dashicons' );
		$sort  = "<span class='$base_class-dashicons dashicons dashicons-sort'></span>";

		foreach( $which_columns as $k => $v ) {

			$label = esc_html( $v['label'] );

			$th = "<th class='$base_class-th $base_class-th-$k'><a class='$base_class-th-link' href='#'>$label&nbsp;$sort</a></th>";

			$th = apply_filters( "$base_class-th", $th );

			$out .= $th;

		}

		if( ! empty( $out ) ) {
			$out = "
				<thead class='$base_class-head'>
					<tr class='$base_class-row'>$out</tr>
				</thead>
			";

			$out = apply_filters( $base_class, $out );

		}

		return $out;

	}

	/**
	 * Grab a list of links to categories.
	 * 
	 * @param  array $array_of_cat_ids An array of category ids.
	 * @return string A list of links to product categories.
	 */
	function cat_ids_to_link_list( $array_of_cat_ids ) {

		$out = '';

		$count = count( $array_of_cat_ids );

		// We'll add a comman after all but the last category link.
		$comma = esc_html__( ", ", 'sjf-et' );

		$i = 0;
		foreach( $array_of_cat_ids as $category_id ) {

			$i++;

			/**
			 * Making a remote call for each category.
			 * 
			 * @todo Need to find a way to grab multiple categories by ID.
			 */
			$collection = new SJF_Ecwid_Collection( "categories/$category_id" );
			$cat = $collection -> get_collection();
			
			// If there is something weird about this category, bail.
			if( ! isset( $cat['url'] ) ) { continue; }

			$url  = esc_url( $cat['url'] );
			$name = esc_html( $cat['name'] );

			$out .= "<a href='$url'>$name</a>";

			// Add a comma if we are not at the end of the list.
			if( $i < $count ) {
				$out .= $comma;
			}

		}

		return $out;

	}

	/**
	 * Turn PHP args for our slider into JS args.
	 */
	function script() {
		
		$loader_class = $this -> get_loader_class();

		$base_url = $this -> get_feed_url();
	
		// Localize the args.
		$local = array(
			'base_url'     => $base_url,
			'loader_class' => $loader_class,
		);

		// Return the localized version of the args so it can be used in a JS file.
		return $local;

	}

	/**
	 * Grab info about the sortable shortcode.
	 * 
	 * @return string Info about the sortable shortcode.
	 */
	function get_docs( $in ) {

		$docs = new SJF_Ecwid_Admin_Documentation;

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'Sortable Shortcode', 'sjf-et' );
		
		$content_1 = '<p>' . esc_html__( 'The sortable shortcode can be used like this:', 'sjf-et') . '</p>'; 
		$content_2 = '<p><code>[sjf_et_sortable which_categories="12296386, 1191910"]</code></p>';
		$content_3 = '<p>' . esc_html__( 'You may specify categories, by ID number, comma-seperated, or the shortcode will output products from all categories.', 'sjf-et' ) . '</p>';

		$content = $content_1 . $content_2 . $content_3;

		$out = $docs -> get_doc( $label, __FUNCTION__, $content );

		return $in . $out;

	}

}