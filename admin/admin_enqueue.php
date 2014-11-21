<?php

/**
 * Enqueue scripts used in wp-admin.
 * 
 * @param string $hook The slug for the screen being viewed.
 */
function sjf_et_admin_enqueue( $hook ) {

	wp_register_script( 'tablesorter', SJF_ET_URL . 'js/jquery.tablesorter.min.js', array( 'jquery' ), '', FALSE );

	wp_enqueue_style( SJF_Ecwid_Helpers::get_namespace() . '_admin_styles', SJF_ET_ADMIN_URL . 'css/' . SJF_Ecwid_Helpers::get_namespace() . '_admin_styles.css' );

}
add_action( 'admin_enqueue_scripts', 'SJF_ET_admin_enqueue' );