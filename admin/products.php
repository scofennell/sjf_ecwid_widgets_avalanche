<?php

/**
 * Display the products.
 *
 * Draws the products screen.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_admin_products_init() {
	new SJF_Ecwid_Admin_Products();
}
add_action( 'init', 'sjf_et_admin_products_init' );

class SJF_Ecwid_Admin_Products {

	/**
     * Adds actions for our class methods.
     */
    function __construct() {
            
		add_action( 'admin_menu', array( $this, 'products_menu_tab' ) );

    }

    function get_item_type() {
    	return 'products';
    }

	/**
	 * Add a menu item for our plugin.
	 */
	function products_menu_tab() {
	    
		// Add a primary menu item.
	    add_submenu_page(
	    	'sjf-et',
	    	esc_html__( 'Products', 'sjf_et' ),
	    	esc_html__( 'Products', 'sjf_et' ),
	    	SJF_Ecwid_Helpers::get_capability(),
	    	'product',
	    	array( $this, 'products_page' ),
	    	SJF_Ecwid_Admin_Helpers::get_dashicon_class(),
	    	6
	    );

	}

	/**
	 * A page for used for help / faq  / clearing transients, etc.
	 */
	function products_page() {
	    
		 // Check capability.
		if( ! current_user_can( SJF_Ecwid_Helpers::get_capability() ) ) { return false; }

		$namespace = SJF_Ecwid_Helpers::get_namespace();

	    $title = esc_html__( 'Products', 'sjf_et' );
		
		$id = false;
		if( isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
		}

		$crud_links = '';

		$new_link = SJF_Ecwid_Admin_Helpers::get_add_new_link( $this -> get_item_type() );

		$back_link = SJF_Ecwid_Admin_Helpers::get_back_to_browse_link();
		
		// If we are editing, give a link to go back to browse.
		if( SJF_Ecwid_Conditional_Tags::is_editing() ) {
			$content    = $this -> get_product( $id, 'update' );
			$title      = SJF_Ecwid_Admin_Helpers::get_item_title( $id, $this -> get_item_type() );
			$crud_links = SJF_Ecwid_Admin_Helpers::get_crud_links( $id, $this -> get_item_type() );

		// Else if we are browsing, give a link to edit.
		} elseif( SJF_Ecwid_Conditional_Tags::is_creating() ) {
			$content = $this -> get_product( false, 'create' );	
			$new_link = '';

		} elseif( SJF_Ecwid_Conditional_Tags::is_reviewing( 'single' ) ) {
			$content    = $this -> get_product( $id );
			$title      = SJF_Ecwid_Admin_Helpers::get_item_title( $id, $this -> get_item_type() );
			$crud_links = SJF_Ecwid_Admin_Helpers::get_crud_links( $id, $this -> get_item_type() );

		} elseif( SJF_Ecwid_Conditional_Tags::is_deleting() ) {
			$title   = SJF_Ecwid_Admin_Helpers::get_item_title( $id, $this -> get_item_type() );
			$content = $this -> get_product( $id, 'delete' );

		} elseif( SJF_Ecwid_Conditional_Tags::is_reviewing( 'index' ) ) {
			$content = $this -> get_products();

			$back_link  = '';
		} 
		
		$delete_feedback = SJF_Ecwid_Forms::get_delete_feedback();

		// Draw the page content.
	    echo "
	    	<div class='wrap'>
	    		<header class='$namespace-page-header'>
		 			<h2>$title $new_link $back_link</h2>
					$crud_links
					$delete_feedback
				</header>
				$content
			</div>
		";

	}

	function get_products() {

		$out = '';

		$ecwid = new SJF_Ecwid;
		$route = 'products';

		if( isset( $_REQUEST['limit'] ) ) {
			$limit = $_REQUEST['limit'];
			$route = add_query_arg( array( 'limit' => $limit ), $route );
		}

		$sortBy = 'ADDED_TIME_DESC';
		if( isset( $_REQUEST['sortBy'] ) ) {
			$sortBy = $_REQUEST['sortBy'];
		}
		$route = add_query_arg( array( 'sortBy' => $sortBy ), $route );

		$offset = SJF_Ecwid_Pagination::get_offset();
		$route = add_query_arg( array( 'offset' => $offset ), $route );

		$data = $ecwid -> call( $route );

		$body = json_decode( $data['body'], TRUE );

		$browse_form = SJF_Ecwid_Browse::get_browse_form();

		$out .= $browse_form;

		$pagination = SJF_Ecwid_Pagination::get_pagination( $body );
		$out .= $pagination;

		$products = $body['items'];

		$rows = '';
		if( ! is_array( $products ) ) {
			$error = esc_html( 'Error: No products found.', 'sjf_et' );

			return $error;
		}

		$table = new SJF_Ecwid_Admin_List_Tables();

		$out .= $table -> get_table( self::get_format(), $products );

		$items_count = absint( $body['count'] );
		if( $items_count > 9 ) { $out .= $pagination; }

		return $out;

	}

	function get_product( $id, $action = 'review', $template = 'archive' ) {

		// Fire up our class for calling Ecwid.
		$ecwid = new SJF_Ecwid;

		$item_type = $this -> get_item_type();

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$out = '';
			

		if( $action == 'create' ) {
	
			// Grab the fields for our form.
			$format = $this -> get_format();
			
			// Parse any form submission that may have occurred.
			$handler = SJF_Ecwid_Forms::handler( $format );

			// Gather any feedback from our form submission.
			$feedback = SJF_Ecwid_Forms::get_feedback( $handler );
			$out .= $feedback;

			/**
			 * We have to wait until we handle the form submit
			 * before gathering the new data from ecwid,
			 * otherwise we'll show outdated data to the user.
			 */
			$out .= SJF_Ecwid_Forms::form( $format, false, 'POST', 'products' );

		} elseif( $action == 'update' ) {
			
			// Grab the fields for our form.
			$format = $this -> get_format();
			
			// Parse any form submission that may have occurred.
			$handler = SJF_Ecwid_Forms::handler( $format );

			// Gather any feedback from our form submission.
			$feedback = SJF_Ecwid_Forms::get_feedback( $handler );
			$out .= $feedback;

			/**
			 * We have to wait until we handle the form submit
			 * before gathering the new data from ecwid,
			 * otherwise we'll show outdated data to the user.
			 */
			$data = $ecwid -> call( "products/$id" );
			$body = json_decode( $data['body'], TRUE );

			$out .= SJF_Ecwid_Forms::form( $format, $body, 'PUT', "products/$id" );

		} elseif( $action == 'delete' ) {

			$handler = SJF_Ecwid_Forms::delete_handler(  $id, $item_type );

			$out .= $handler;

			$delete_link = SJF_Ecwid_Forms::get_delete_link( $id, $item_type );

			$out .= $delete_link;

		} elseif( $id ) {

			$body = SJF_Ecwid_Admin_Helpers::get_item_fields( $id, $item_type );	
			$out = SJF_Ecwid_Formatting::array_dig( $body );
		}

		return $out;
	}

	function get_format() {
		
		$out = array(
			array(
				'name'          => 'sku',
				'label'         => esc_html__( 'SKU', 'sjf_et' ),
				'sanitize'      => 'string',
				'show_on_index' => true,
				'show_on_create' => true,
				'show_on_update' => true,
			),
			array(
				'name'           => 'name',
				'label'          => esc_html__( 'Product Name', 'sjf_et' ),
				'sanitize'       => 'string',
				'show_on_index'  => true,
				'show_on_create' => true,
				'show_on_update' => true,
			),
			/*
			array(
				'name'     => 'quantity',
				'label'    => esc_html__( 'Quantity', 'sjf_et' ),
				'sanitize' => 'int',
				'show_on_index' => true,
			),
			*/
			array(
				'name'     => 'weight',
				'label'    => esc_html__( 'Weight', 'sjf_et' ),
				'sanitize' => 'int',
				'show_on_create' => true,
				'show_on_update' => true,				
			),
			array(
				'name'     => 'created',
				'label'    => esc_html__( 'Created', 'sjf_et' ),
				'sanitize' => 'date',
				'show_on_index' => true,
			),

		);
		
		return $out;
	}

}