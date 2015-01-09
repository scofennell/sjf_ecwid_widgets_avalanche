<?php

/**
 * Plugin Name: Ecwid Widgets Avalanche
 * Plugin URI:  http://www.scottfennell.org/ecwid
 * Description: A host of widgets for integrating Ecwid with WordPress.
 * Version:     1.0
 * Author:      Scott Fennell
 * Author URI:  http://www.scottfennell.org/
 * Text Domain: sjf-et
 * Domain Path: /lang
 *
 * @todo get ecwid to fix my app URL & intro paragraph
 * @todo grab screenshots from widget settings screen
 * @todo test doing_transients function more rigorously in ecwid class
 */

/*  Copyright 2014  Scott Fennell  (email : scofennell@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

// A constant to define the paths to our plugin folders.
define( 'SJF_ET_FILE', __FILE__ );
define( 'SJF_ET_PATH', trailingslashit( plugin_dir_path( SJF_ET_FILE ) ) );
define( 'SJF_ET_ADMIN_PATH', SJF_ET_PATH . 'admin/' );
define( 'SJF_ET_INC_PATH', SJF_ET_PATH . 'inc/' );

// A constant to define the urls to our plugin folders.
define( 'SJF_ET_URL', trailingslashit( plugin_dir_url( SJF_ET_FILE ) ) );
define( 'SJF_ET_ADMIN_URL', SJF_ET_URL . 'admin/' );
define( 'SJF_ET_INC_URL', SJF_ET_URL . 'inc/' );

// Get the Ecwid api wrapper.  Prepares and sends calls to ecwid.com.
require_once( SJF_ET_INC_PATH . 'ecwid.php' );

// WP Transients API.
require_once( SJF_ET_INC_PATH . 'transients.php' );

// Get the helper functions (get store id, etc).
require_once( SJF_ET_INC_PATH . 'helpers.php' );

// Get the enqueues.
require_once( SJF_ET_INC_PATH . 'enqueue.php' );

// Get the error functions (handle wp error, parse it for display).
require_once( SJF_ET_INC_PATH . 'errors.php' );

// Get a collection of items from Ecwid.
require_once( SJF_ET_INC_PATH . 'collection.php' );

// Get the formatting functions (sanitization, array digs, etc).
require_once( SJF_ET_INC_PATH . 'formatting.php' );

// Get the widgets.
require_once( SJF_ET_INC_PATH . 'widgets/accordion.php' );
require_once( SJF_ET_INC_PATH . 'widgets/slider.php' );
require_once( SJF_ET_INC_PATH . 'widgets/popup.php' );

// If the user is in wp-admin, load the admin files.
if( is_admin() ) {
	require_once( SJF_ET_ADMIN_PATH . 'admin.php' );
}