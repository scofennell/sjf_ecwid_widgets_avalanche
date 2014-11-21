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
	 * @param string $request_type GET, POST, DELETE, PUT, etc
	 */
	public function __construct( $request_type ) {

		// Unless it's a GET, flush transients.
		if( $request_type != 'GET' ) {
			$flush = $this -> flush_transients();
		}

	}

	/**
	 * Performs as SQL DELETE query to remove transients.
	 * @return [type] [description]
	 */
	private function flush_transients() {

		global $wpdb;

		$transient_prefix = esc_sql( $this -> get_transient_prefix() );

		$options = esc_sql( $wpdb -> options );

		$trans = esc_sql( "_transient_$transient_prefix%" );
		$trans_timeout = esc_sql( "_transient_timeout_$transient_prefix%" );

		$sql = "
    		DELETE from $options
			WHERE option_name LIKE %s
			OR option_name LIKE %s
		";
		
		$q = $wpdb -> query(
			$wpdb -> prepare( $sql, $trans, $trans_timeout )
		);

	}

	public function get_transient_key( $request_url ) {

		$transient_prefix = $this -> get_transient_prefix();

		$out = $transient_prefix . md5( $request_url );
	
		return $out;

	}

	private function get_transient_prefix() {

		/**
		 * WordPress limits transient keys to as little as 40 chars in some situations,
		 * and we're going to add a 32-char md5 hash, so limit the prefix to 7 chars.
		 */
		$out = substr( SJF_Ecwid_Helpers::get_namespace() , 0, 7 );
		return $out;

	}

	function set_transient( $transient_key, $out ) {
		return set_transient( $transient_key, $out, DAY_IN_SECONDS );
	}

	public function get_transient( $transient_key ) {
		return get_transient( $transient_key );
	}

}