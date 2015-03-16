<?php

/**
 * Enqueue the assets needed for our plugin.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_enqueue_init() {
	new SJF_Ecwid_Enqueue();
}
add_action( 'wp_enqueue_scripts', 'sjf_et_enqueue_init' );

Class SJF_Ecwid_Enqueue {

	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Grab our plugin stylesheet.
		wp_enqueue_style( $namespace . '_styles', SJF_ET_INC_URL . 'css/styles.css', FALSE, SJF_ET_VERSION );
		
		// Powers the slider widget.
		wp_register_script( 'bxslider', SJF_ET_INC_URL . 'js/jquery.bxslider.min.js', array( 'jquery' ), SJF_ET_VERSION );

		// Powers the popup widget (you get a cookie when you see the popup; if you have the cookie, you don't see the popup again).
		wp_register_script( 'cookie', SJF_ET_INC_URL . 'js/jquery.cookie.min.js', array( 'jquery' ), SJF_ET_VERSION );

		// Powers the sortable widget.
		wp_register_script( 'tablesorter', SJF_ET_INC_URL . 'js/jquery.tablesorter.min.js', array( 'jquery' ), SJF_ET_VERSION );
	
		// Powers any call to the Ecwid API ... though the store front calls this anyways.
		// wp_register_script( 'ecwid-js-api', $this -> get_ecwid_js_api_url(), array( 'jquery' ), SJF_ET_VERSION  );
		
		// Grab our base scripts.
		wp_register_script( $namespace . '_scripts', SJF_ET_INC_URL . 'js/scripts.js', array( 'jquery' ), SJF_ET_VERSION );
		wp_enqueue_script( $namespace . '_scripts' );

	}


	/**
	 * Enqueue the ecwid.com JS API.
	 */
	public function get_ecwid_js_api_url() {
		
		$id = SJF_Ecwid_Helpers::get_store_id();

		return esc_url( "//app.ecwid.com/script.js?$id" );

	}


}
