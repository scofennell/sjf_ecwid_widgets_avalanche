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

	/**
	 * Get the plugin name from the php docblock, falling back to a hardcoded value here if needed.
	 * 
	 * @return string The plugin name.
	 */
	public static function get_plugin_title() {
		if ( function_exists( 'get_plugin_data' ) ) {
			$plugin_data = get_plugin_data( SJF_ET_FILE );
			return $plugin_data['Name'];
		} else {
			return 'Ecwid Widgets Avalanche';
		}
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
	 * Grab the name for the auth token from ecwid.com.
	 * 
	 * @return string The name for the auth token from ecwid.com.
	 */
	public static function get_store_id_name() {
		$namespace = self::get_namespace();
	
		return "$namespace-store_id";

	}

	/**
	 * Get the ecwid ID for the store.
	 * 
	 * @return string The ecwid ID for the store.
	 */
	public static function get_store_id() {
		return get_option( self::get_store_id_name() );
	}

	/**
	 * Set the ecwid ID for the store.
	 */
	public static function set_store_id( $new_value ) {
		$new_value = sanitize_text_field( $new_value );
		return update_option( self::get_store_id_name(), $new_value );
	}

	/**
	 * Grab the name for the auth token from ecwid.com.
	 * 
	 * @return string The name for the auth token from ecwid.com.
	 */
	public static function get_token_name() {
		$namespace = self::get_namespace();
	
		return "$namespace-token";

	}

	/**
	 * Get the ecwid.com auth token.
	 * 
	 * @return string The ecwid.com auth token.
	 */
	public static function get_token() {
		return get_option( self::get_token_name() );
	}

	/**
	 * Save the ecwid.com auth token to the db.
	 * 
	 * @return boolean Returns a call to update_option.
	 */
	public static function set_token( $new_value ) {
		$new_value = sanitize_text_field( $new_value );
		return update_option( self::get_token_name(), $new_value );
	}

	/**
	 * Determine if the plugin is authorized to ecwid.com.
	 *
	 * @param int $attempt The number of attempts made thus far.
	 * @return boolean If authorized, returns true, else false.
	 */
	public static function is_authorized( $attempt = 1 ) {
		
		$max_attempts = 5;

		// If this call exceeds the limit, bail.
		if( $attempt > $max_attempts ) {
			return FALSE;
		}

		// If the token is empty, we know they are not authorized.
		$token = self::get_token();
		if( empty( $token ) ) {
			return FALSE;
		}
		
		// Get a response from ecwid.
		$response = self::get_ecwid_response();

		// If something weird happened, bail.
		if( ! is_int( $response ) ) {
			return FALSE;
		}

		// Test the response code from ecwid.  If it's 403 or 402, we know there is something wrong with their account.
		if( ( $response == '403' ) || ( $response == '402' ) ) {
			return FALSE;
		}

		// Test the response code from ecwid.  If it's 400 or 500, maybe we just need to try again.
		if( ( $response == '400' ) || ( $response == '500' ) ) {
			$attempt++;
			return self::is_authorized( $attempt );
		}

		return TRUE;
	}

	/**
	 * Ping ecwid.com to determine the response code.
	 * 
	 * @return int an HTTP response code.
	 */
	public static function get_ecwid_response() {

		$ecwid         = new SJF_Ecwid();
		$test_ping     = $ecwid -> call( 'profile' );
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

}