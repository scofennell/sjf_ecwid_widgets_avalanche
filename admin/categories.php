<?php

/**
 * Display the categories.
 *
 * Draws the categories screen.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_admin_categories_init() {
	new SJF_Ecwid_Admin_Categories();
}
add_action( 'init', 'sjf_et_admin_categories_init' );

class SJF_Ecwid_Admin_Categories {

	/**
	 * Adds actions for our class methods.
	 */
	function __construct() {
			
		add_action( 'admin_menu', array( $this, 'menu_tab' ) );

	}

	function get_page_label() {
		return esc_html__( 'Categories', 'sjf_et' );
	}

	function get_item_type() {
		return 'categories';
	}

	function get_item_type_singular() {
		return 'category';
	}

	/**
	 * Add a menu item for our plugin.
	 */
	function menu_tab() {
		
		// Add a primary menu item.
		add_submenu_page(
			SJF_Ecwid_Admin_Helpers::get_menu_slug(),
			$this -> get_page_label(),
			$this -> get_page_label(),
			SJF_Ecwid_Helpers::get_capability(),
			$this -> get_item_type(),
			array( $this, 'page' ),
			SJF_Ecwid_Admin_Helpers::get_dashicon_class(),
			6
		);

	}

	/**
	 * A page for used for help / faq  / clearing transients, etc.
	 */
	function page() {
		
		 // Check capability.
		if( ! current_user_can( SJF_Ecwid_Helpers::get_capability() ) ) { return false; }

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$title = $this -> get_page_label();
		
		$id = false;
		if( isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
		}

		$crud_links = '';

		$new_link = SJF_Ecwid_Admin_Helpers::get_add_new_link( $this -> get_item_type() );

		$back_link = SJF_Ecwid_Admin_Helpers::get_back_to_browse_link();
		
		// If we are editing, give a link to go back to browse.
		if( SJF_Ecwid_Conditional_Tags::is_editing() ) {
			$content    = $this -> get_item( $id, 'update' );
			$title      = SJF_Ecwid_Admin_Helpers::get_item_title( $id, $this -> get_item_type() );
			$crud_links = SJF_Ecwid_Admin_Helpers::get_crud_links( $id, $this -> get_item_type(), $this -> get_item_type_singular() );

		// Else if we are browsing, give a link to edit.
		} elseif( SJF_Ecwid_Conditional_Tags::is_creating() ) {
			$content = $this -> get_item( false, 'create' );	
			$new_link = '';

		} elseif( SJF_Ecwid_Conditional_Tags::is_reviewing( 'single' ) ) {
			$content    = $this -> get_item( $id );
			$title      = SJF_Ecwid_Admin_Helpers::get_item_title( $id, $this -> get_item_type() );
			$crud_links = SJF_Ecwid_Admin_Helpers::get_crud_links( $id, $this -> get_item_type(), $this -> get_item_type_singular() );

		} elseif( SJF_Ecwid_Conditional_Tags::is_deleting() ) {
			$title   = SJF_Ecwid_Admin_Helpers::get_item_title( $id, $this -> get_item_type() );
			$content = $this -> get_item( $id, 'delete' );

		} elseif( SJF_Ecwid_Conditional_Tags::is_reviewing( 'index' ) ) {
			$content = $this -> get_items();

			$back_link  = '';
		} 
		
		$delete_feedback = SJF_Ecwid_Handler::get_delete_feedback();

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

	function get_items() {

		$out = '';

		$collection = new SJF_Ecwid_Collection( $this -> get_item_type() );

		$body = $collection -> get_collection();

		$browse_form = SJF_Ecwid_Browse::get_browse_form();

		$out .= $browse_form;

		$pagination = SJF_Ecwid_Pagination::get_pagination( $body );
		$out .= $pagination;

		$categories = $body['items'];

		$rows = '';
		if( ! is_array( $categories ) ) {

			var_dump( $categories );

			$error = esc_html( 'Error: No categories found.', 'sjf_et' );

			$out .= $error;
		}

		$table = new SJF_Ecwid_Admin_List_Tables();

		$out .= $table -> get_table( $this -> get_format(), $categories, $this -> get_item_type(), $this -> get_item_type_singular() );

		$items_count = absint( $body['count'] );
		if( $items_count > 9 ) { $out .= $pagination; }

		return $out;

	}

	function get_item( $id, $action = 'review', $template = 'archive' ) {

		// Fire up our class for calling Ecwid.
		$ecwid = new SJF_Ecwid;

		$item_type = $this -> get_item_type();

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$out = '';
			

		if( $action == 'create' ) {
	
			// Grab the fields for our form.
			$format = $this -> get_format();
			
			// Parse any form submission that may have occurred.
			$handler = SJF_Ecwid_Handler::handler( $format );

			// Gather any feedback from our form submission.
			$feedback = SJF_Ecwid_Handler::get_feedback( $handler );
			$out .= $feedback;

			/**
			 * We have to wait until we handle the form submit
			 * before gathering the new data from ecwid,
			 * otherwise we'll show outdated data to the user.
			 */
			$out .= SJF_Ecwid_Forms::form( $format, false, 'POST', $item_type );

		} elseif( $action == 'update' ) {
			
			// Grab the fields for our form.
			$format = $this -> get_format();
			
			// Parse any form submission that may have occurred.
			$handler = SJF_Ecwid_Handler::handler( $format );

			// Gather any feedback from our form submission.
			$feedback = SJF_Ecwid_Handler::get_feedback( $handler );
			$out .= $feedback;

			/**
			 * We have to wait until we handle the form submit
			 * before gathering the new data from ecwid,
			 * otherwise we'll show outdated data to the user.
			 */
			$data = $ecwid -> call( "$item_type/$id" );
			$body = json_decode( $data['body'], TRUE );

			$out .= SJF_Ecwid_Forms::form( $format, $body, 'PUT', "$item_type/$id" );

		} elseif( $action == 'delete' ) {

			$handler = SJF_Ecwid_Handler::delete_handler(  $id, $item_type );

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
				'name'          => 'id',
				'label'         => esc_html__( 'Category ID', 'sjf_et' ),
				'sanitize'      => 'int',
				'show_on_index' => true,	
			),

			array(
				'name'          => 'parentId',
				'label'         => esc_html__( 'Parent ID', 'sjf_et' ),
				'sanitize'      => 'int',
				'show_on_index' => true,	
			),

			array(
				'name'           => 'name',
				'label'          => esc_html__( 'Category Name', 'sjf_et' ),
				'sanitize'       => 'string',
				'show_on_index'  => true,
				'show_on_create' => true,
				'show_on_update' => true,
			),
			
			array(
				'name'     => 'productCount',
				'label'    => esc_html__( 'Product Count', 'sjf_et' ),
				'sanitize' => 'int',
				'show_on_index' => true,
			),

		);
		
		return $out;
	}

}