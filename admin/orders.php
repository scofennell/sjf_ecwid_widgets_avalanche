<?php

/**
 * Display the orders.
 *
 * Draws the orders screen.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_admin_orders_init() {
	new SJF_Ecwid_Admin_Orders();
}
add_action( 'init', 'sjf_et_admin_orders_init' );

class SJF_Ecwid_Admin_Orders {

	/**
     * Adds actions for our class methods.
     */
    function __construct() {
            
		add_action( 'admin_menu', array( $this, 'orders_menu_tab' ) );

    }

	/**
	 * Add a menu item for our plugin.
	 */
	function orders_menu_tab() {
	    
		// Add a primary menu item.
	    add_submenu_page(
	    	'sjf-et',
	    	esc_html__( 'Orders', 'sjf_et' ),
	    	esc_html__( 'Orders', 'sjf_et' ),
	    	SJF_Ecwid_Helpers::get_capability(),
	    	'orders',
	    	array( $this, 'orders_page' ),
	    	SJF_Ecwid_Admin_Helpers::get_dashicon_class(),
	    	6
	    );

	}

	/**
	 * A page for used for help / faq  / clearing transients, etc.
	 */
	function orders_page() {
	    
		 // Check capability.
		if( ! current_user_can( SJF_Ecwid_Helpers::get_capability() ) ) { return false; }

	    $title = esc_html__( 'Orders', 'sjf_et' );
		
		$orders = $this -> get_orders();

		// Draw the page content.
	    echo "
	    	<div class='wrap'>
	 			<h2>$title</h2>
		
				$orders

			</div>
		";

	}

	function get_orders() {

		$out = '';

		$ecwid = new SJF_Ecwid;
		$store_data = $ecwid -> call( 'orders' );

		$body = json_decode( $store_data['body'], TRUE );

		$out = SJF_Ecwid_Formatting::array_dig( $body );

		return $out;

	}

}