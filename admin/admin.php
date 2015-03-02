<?php

/**
 * Require our admin files.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

/**
 * Settings.
 */
require_once( SJF_ET_ADMIN_PATH . 'settings.php' );

/**
 * Authenticate with ecwid.com.
 */
require_once( SJF_ET_ADMIN_PATH . 'authentication.php' );

/**
 * Helper functions used throughout wp-admin.
 */
require_once( SJF_ET_ADMIN_PATH . 'helpers.php' );

/**
 * Conditional tags used throughout wp-admin.
 */
require_once( SJF_ET_ADMIN_PATH . 'conditional_tags.php' );

/**
 * Enqueue admin scripts.
 */
require_once( SJF_ET_ADMIN_PATH . 'enqueue.php' );

/**
 * Grab our admin notices.
 */
require_once( SJF_ET_ADMIN_PATH . 'notices.php' );

/**
 * Grab our debug class.
 */
require_once( SJF_ET_ADMIN_PATH . 'debug.php' );

/**
 * Grab our documentation class.
 */
require_once( SJF_ET_ADMIN_PATH . 'documentation.php' );