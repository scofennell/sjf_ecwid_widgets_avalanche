<?php

/**
 * A wrapper for the WordPress Transients API.
 *
 * Provides public methods for getting and setting WP Transients.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

/**
 * Abstracts the WP Transients API, with some addiitonal functionality.
 */
class SJF_Ecwid_Transients {

	/**
	 * Any time we do a transient, we check the request type and maybe flush transients.
	 * 
	 * @param string $request_type GET, POST, DELETE, PUT, etc.
	 * @param bool   $flush_rewrite_rules Do we want to also flush rewrite rules?
	 */
	public function __construct( $request_type = 'GET', $flush_rewrite_rules = TRUE ) {

		// Unless it's a GET, flush transients and maybe flush rewrites..
		if( $request_type != 'GET' ) {
			$this -> flush_transients( $flush_rewrite_rules );
			
			if( $flush_rewrite_rules ) { flush_rewrite_rules( TRUE ); }
		}

	}

	/**
	 * Performs an SQL DELETE query to remove transients.  Also flushed rewrite rules.
	 */
	private function flush_transients() {

		// This is pretty rare, but we actually are writing SQL here!  Grab the wpdb class.
		global $wpdb;

		// Basically just a short version of the plugin namespace.
		$transient_prefix = esc_sql( $this -> get_transient_prefix() );

		// The name of the options table for this install.
		$options = esc_sql( $wpdb -> options );

		// The names of the transients for our plugins will fall into two categories.
		$trans         = esc_sql( "_transient_$transient_prefix%" );
		$trans_timeout = esc_sql( "_transient_timeout_$transient_prefix%" );

		$sql = "
    		DELETE from $options
			WHERE option_name LIKE %s
			OR option_name LIKE %s
		";
		
		// Delete the transients.
		$q = $wpdb -> query(
			$wpdb -> prepare( $sql, $trans, $trans_timeout )
		);

	}

	/**
	 * Grab the transient key for a request.
	 * 
	 * @param  string $request_url The url for an http request to ecwid.
	 * @return string The transient key for an http request to ecwid.
	 */
	public function get_transient_key( $request_url ) {

		// Grab the transient prefix to keep things namespaced to our plugin.
		$transient_prefix = $this -> get_transient_prefix();

		// We md5 the request url to keep it down to 32 chars.
		$out = $transient_prefix . md5( $request_url );
	
		return $out;

	}

	/**
	 * Get the transient prefix for our plugin.
	 * 
	 * @return string The transient prefix for our plugin.
	 */
	private function get_transient_prefix() {

		/**
		 * WordPress limits transient keys to as little as 40 chars in some situations,
		 * and we're going to add a 32-char md5 hash, so limit the prefix to 6 chars.
		 */
		$out = substr( SJF_Ecwid_Helpers::get_namespace() , 0, 7 );
		return $out;

	}

	/**
	 * A wrapper for the wordpress set_transient function.  Useful becuase it keeps us consistent on expiration length.
	 * 
	 * @param string $transient_key The key for this transient.
	 * @param string $content The blob of HTML that we are saving -- probably the result of a remote call.
	 */
	function set_transient( $transient_key, $content ) {

		// No matter what, this result will expire in one day.
		return set_transient( $transient_key, $content, DAY_IN_SECONDS );
	}

	/**
	 * A wrapper for the wordpress get_transient function. This is pretty pointless.
	 */
	public function get_transient( $transient_key ) {
		return get_transient( $transient_key );
	}

}