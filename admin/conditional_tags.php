<?php

/**
 * Conditional tags for detecting which template we're on.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

Class SJF_Ecwid_Conditional_Tags {

	/**
	 * Determine if we are on the main plugin settings page.
	 * 
	 * @return boolean Returns true if we are on the plugin settings page, else false.
	 */
	public static function is_settings_page() {
		
		// Obviously, if it's not wp-admin, it can't be our plugin admin page.
		if( ! is_admin() ) { return FALSE; }

		// Our page will always have a page value in the url.
		if( ! isset( $_GET['page'] ) ) { return FALSE; }

		// Grab the menu slug, check the url for it.
		if( $_GET['page'] != SJF_Ecwid_Admin_Helpers::get_menu_slug() ) { return FALSE; }
		
		return TRUE;
	}

}