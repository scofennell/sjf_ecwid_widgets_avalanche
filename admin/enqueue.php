<?php

/**
 * Enqueue assets for wp-admin.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_admin_enqueue_init() {
	new SJF_Ecwid_Admin_Enqueue();
}
add_action( 'admin_enqueue_scripts', 'sjf_et_admin_enqueue_init' );

Class SJF_Ecwid_Admin_Enqueue {

	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Grab our base styles.
		wp_enqueue_style( $namespace . '_styles', SJF_ET_INC_URL . 'css/styles.css', FALSE, SJF_ET_VERSION );

		// Grab our admin styles.
		wp_enqueue_style( $namespace . '_admin_styles', SJF_ET_ADMIN_URL . 'css/styles.css', FALSE, SJF_ET_VERSION );

		// Grab our base scripts.
		wp_enqueue_script( $namespace . '_scripts', SJF_ET_INC_URL . 'js/scripts.js', array( 'jquery' ), SJF_ET_VERSION );

	}

}