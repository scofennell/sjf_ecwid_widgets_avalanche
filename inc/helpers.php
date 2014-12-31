<?php

/**
 * Helper functions to grab common values for our plugin.
 *
 * Functions to grab common things like plugin namespace, plugin capability, store id, etc.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

class SJF_Ecwid_Helpers {

	public static function get_plugin_data() {
		return get_plugin_data( SJF_ET_FILE );
	}

	public static function get_plugin_title() {
		$plugin_data = self::get_plugin_data();
		return $plugin_data['Name'];
	}

	/**
	 * [max_collection_count description]
	 * @see http://api.ecwid.com/#search-products
	 * @return [type] [description]
	 */
	public static function max_collection_count() {
		return 100;
	}

	/**
	 * Get the namespace for our plugin.
	 * 
	 * @return string The namespace for our plugin.
	 */
	public static function get_namespace() {
		return 'sjf_et';
	}

	/**
	 * Grab the auth token for this store and our plugin.
	 * 
	 * @return string A 32-char string that gets generated when the user authorizes our plugin for their store.
	 */
	

	public static function get_token_name() {
		$namespace = self::get_namespace();
	
		return "$namespace-token";

	}

	public static function get_token() {
		return get_option( self::get_token_name() );
	}

	public static function set_token( $new_value ) {
		$new_value = sanitize_text_field( $new_value );
		return update_option( self::get_token_name(), $new_value );
	}

	public static function is_authorized() {
		
		// If the token is empty, we know they are not authorized.
		$token = self::get_token();
		if( empty( $token ) ) {
			return FALSE;
		}
		
		// Test the response code from ecwid.  If it's 403 or 402, we know there is something wrong with their account.
		$response = self::get_ecwid_response();
		if( ( $response == '403' ) || ( $response == '402' ) ) {
			return FALSE;
		}

		return TRUE;
	}

	public static function get_ecwid_response() {

		$ecwid = new SJF_Ecwid();
		$test_ping = $ecwid -> call( 'profile' );
		$response_code = $test_ping['response']['code'];
		return $response_code;
	
	} 

	/**
	 * Get the base url for the ecwid api.
	 * 
	 * @return string The base url for the ecwid api.
	 */
	public static function get_api_endpoint() {
		return 'https://app.ecwid.com/api/v3/';
	}

	/**
	 * Get the capability for using our plugin.
	 * 
	 * @return string The capability for using our plugin.
	 */
	public static function get_capability() {
		return 'edit_posts';
	}

	/**
	 * Get the ecwid ID for the store.
	 * 
	 * @return string The ecwid ID for the store.
	 */
	public static function get_store_id() {
		return SJF_Ecwid_Formatting::alphanum( self::get_setting( 'store_id' ) );
	}

	/**
	 * Get the prefix for the plugin options.
	 * 
	 * @return string The prefix for the plugin options.
	 */
	public static function get_settings_prefix() {
		$namespace = self::get_namespace();
		return $namespace . '_setup_options';
	}

	/**
	 * A wrapper for the WP function, get_option.
	 * 
	 * @param  string $slug The slug of the setting we want to get.
	 * @return mixed The value of the option we want to get.
	 */
	public static function get_setting( $slug ) {
		$settings = get_option( self::get_settings_prefix() );
		return $settings[ $slug ];
	}

	/**
	 * Get the parts of Ecwid that our app wants permission to touch.
	 *
	 * This is presented to the user when they authorize the app.
	 * 
	 * @return array The parts of Ecwid that our app wants permission to touch.
	 */
	public static function get_scopes() {
		return array(
			'read_store_profile',
			'update_store_profile',
			'read_catalog',
			'update_catalog',
			'create_catalog',
			'read_orders',
			'update_orders',
			'create_orders',
			'read_customers',
			'update_customers',
			'create_customers',
		);
	}

}