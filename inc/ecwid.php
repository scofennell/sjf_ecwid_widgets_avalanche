<?php

/**
 * A wrapper for the Ecwid API.
 *
 * Provides a public method for calling Ecwid.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

/**
 * A class for preparing and sending requests to ecwid.com.
 */
class SJF_Ecwid {

	/**
	 * Prepare and send a request to Ecwid.com using wp_remote_request() or a transient if present.
	 * 
	 * @param  string $route 		The route for this request.  Examples:  'profile', 'profile/logo'. 
	 * @param  array  $args         The product, store, customer, etc data that we want to send.
	 * @param  string $request_type A REST request type.
	 * @param  string $encode       How to encode the request.
	 * @return array                A wp_remote_request() result.
	 */
	public function call( $route, $args = array(), $request_type = 'GET', $encode = 'json' ) {

		// Fire up our transients class.  It flushes transients for any request other than GET.
		$transients = new SJF_Ecwid_Transients( $request_type );
		
		$endpoint = trailingslashit( esc_url( SJF_Ecwid_Admin_Helpers::get_api_endpoint() ) );
		$store_id = trailingslashit( urlencode( SJF_Ecwid_Helpers::get_store_id() ) );
		$token    = urlencode( SJF_Ecwid_Admin_Helpers::get_token() );
		
		$url = $endpoint . $store_id . $route;

		$url = add_query_arg( array( 'token' => $token ), $url );

		// If it's a get request, see if there's a transient for it.
		if( $request_type == 'GET' ) {

			// Get the transient key for this url (it gets compressed to 32 chars via MD5).
			$transient_key = $transients -> get_transient_key( $url );

			// If there is a transient for this request, bail.
			$transient = $transients -> get_transient( $transient_key );

			if( ! empty( $transient ) ) {
				return $transient;
			}

		}

		$request_args = array();
		$request_args['method'] = $request_type;
		
		if( ( $request_type != 'GET' ) && ( $request_type != 'DELETE' ) ) {
			
			if( $encode == 'json' ) {
				$body = json_encode( $args );
			} else {
				$body = $args;
			}
			$request_args['body']= $body;

			$headers = array( 'content-type' => 'application/json' );
			$request_args['headers']= $headers;

		}

		$out = wp_remote_request( $url, $request_args );

		// If it's a get request, save the output as a transient.
		if( $request_type == 'GET' ) {
			$transients -> set_transient( $transient_key, $out );
		}

		return $out;

	}

}