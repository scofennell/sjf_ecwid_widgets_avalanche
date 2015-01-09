<?php

/**
 * Static methods for grabbing common values for wp-admin.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */


class SJF_Ecwid_Admin_Helpers {

	/**
	 * Get the url to our settings page.
	 *
	 * @return  string The url to our settings page.
	 */
	public static function get_main_menu_url() {
		
		$slug = self::get_menu_slug();

		$proto = 'http';
		if( is_ssl() ) { $proto = 'https'; }
		
		return admin_url( "admin.php?page=$slug", $proto );

	}

	/**
	 * The url to a page in the ecwid.com control panel.
	 * 
	 * @return string The url to a page in the ecwid.com control panel.
	 */
	public static function get_ecwid_cp_url( $store_id, $item_type = '' ) {
		
		// If we are on the products page in our plugin, we'd want the user to go to the products page in ecwid.
		$item_type = urlencode( $item_type );
		$store_id = absint( $store_id );
		return "https://my.ecwid.com/cp/CP.html#$item_type:mode=edit&id=$store_id";
	}

	/**
	 * Get the slug for the main menu for our plugin.
	 * 
	 * @return string The slug for the main menu for our plugin.
	 */
	public static function get_menu_slug() {
		return SJF_Ecwid_Helpers::get_namespace();
	}

	public static function get_current_url( $include_request_uri = TRUE ) {
		$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
		$url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
		
		if( $include_request_uri ) {
			$url .= $_SERVER["REQUEST_URI"];
		}
		
		return $url;
	}

}