<?php

/**
 * Plugin Name: SJF Ecwid Tools
 * Plugin URI:  http://www.scottfennell.org/
 * Description: A host of widgets, menu pages, and functions for integrating Ecwid with WordPress.
 * Version:     0.1
 * Author:      Scott Fennell
 * Author URI:  http://www.scottfennell.org/
 * Text Domain: sjf-et
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

// A constant to define the path to this plugin file.
define( 'SJF_ET_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'SJF_ET_ADMIN_PATH', SJF_ET_PATH . 'admin/' );
define( 'SJF_ET_INC_PATH', SJF_ET_PATH . 'inc/' );

// A constant to define the url to this plugin file.
define( 'SJF_ET_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SJF_ET_ADMIN_URL', SJF_ET_URL . 'admin/' );

// Get the Ecwid api wrapper.  Prepares and sends calls to ecwid.com.
require_once( SJF_ET_INC_PATH . 'ecwid.php' );

// WP Transients API.
require_once( SJF_ET_INC_PATH . 'transients.php' );

// Get the helper functions (get store id, etc).
require_once( SJF_ET_INC_PATH . 'helpers.php' );

// Get the formatting functions (sanitization, array digs, etc).
require_once( SJF_ET_INC_PATH . 'formatting.php' );

// If the user is in wp-admin, load the admin files.
if( is_admin() ) {
	require_once( SJF_ET_ADMIN_PATH . 'admin.php' );
}

?>