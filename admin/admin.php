<?php

/**
 * Settings.
 */
require_once( SJF_ET_ADMIN_PATH . 'settings.php' );

/**
 * Helper functions used throughout wp-admin.
 */
require_once( SJF_ET_ADMIN_PATH . 'admin_helpers.php' );

/**
 * Conditional tags used throughout wp-admin.
 */
require_once( SJF_ET_ADMIN_PATH . 'conditional_tags.php' );

/**
 * Functions for creatingforms.
 */
require_once( SJF_ET_ADMIN_PATH . 'forms.php' );

/**
 * Functions for handling forms.
 */
require_once( SJF_ET_ADMIN_PATH . 'handlers.php' );

/**
 * Functions for creating list tables.
 */
require_once( SJF_ET_ADMIN_PATH . 'list_tables.php' );

/**
 * Enqueue admin scripts.
 */
require_once( SJF_ET_ADMIN_PATH . 'admin_enqueue.php' );

/**
 * Grab our admin notices.
 */
require_once( SJF_ET_ADMIN_PATH . 'admin_notices.php' );

/**
 * Echo admin inline scripts.
 */
require_once( SJF_ET_ADMIN_PATH . 'admin_inline_scripts.php' );

if( SJF_Ecwid_Helpers::is_authorized() ) {

	/**
	 * Profile.
	 */
	require_once( SJF_ET_ADMIN_PATH . 'profile.php' );

	/**
	 * Products.
	 */
	require_once( SJF_ET_ADMIN_PATH . 'products.php' );

	/**
	 * Products.
	 */
	require_once( SJF_ET_ADMIN_PATH . 'categories.php' );

	/**
	 * Dashboard widgets.
	 */
	require_once( SJF_ET_ADMIN_PATH . 'dashboard_widgets.php' );

}