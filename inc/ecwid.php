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
	 * Determine if we are going to use the WP transients API for this request.
	 * 
	 * @param  string $request_type Any REST action.
	 * @return boolean If it's a get request and we are not in debug mode, return true. Else, false.
	 */
	private function doing_transients( $request_type ) {
		if ( ( $request_type == 'GET' ) && ( ! WP_DEBUG || ! is_user_logged_in() ) ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Grab an array of response codes that represent data that we probably don't want in our widgets.
	 * 
	 * @return int A list of bad HTTP response codes.
	 */
	private function bad_response_codes() {
		return array( 400, 402, 403, 404, 409, 413, 422, 449, 500 );
	}

	/**
	 * Determine if an HTTP response is suitable for populating a widget.
	 * 
	 * @param  array $response An HTTP response.
	 * @return boolean Returns TRUE if an HTTP response is suited for widgetry, else FALSE.
	 */
	private function check_response_code( $response ) {

		if( ! is_array( $response ) ) { return FALSE; }
		
		if( ! isset( $response['response'] ) ) { return FALSE; }
		
		if( ! isset( $response['response']['code'] ) ) { return FALSE; }
		
		if( in_array( $response['response']['code'], $this -> bad_response_codes() ) ) { return FALSE; }

		if( is_wp_error( $response ) ) { return FALSE; }

		return TRUE;

	}

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
		
		// Build the url to which our request will be sent.
		$endpoint = trailingslashit( esc_url( SJF_Ecwid_Helpers::get_api_endpoint() ) );
		$store_id = trailingslashit( urlencode( SJF_Ecwid_Helpers::get_store_id() ) );
		$token    = urlencode( SJF_Ecwid_Helpers::get_token() );
		$url      = $endpoint . $store_id . $route;
		$url      = add_query_arg( array( 'token' => $token ), $url );

		// Fire up our transients class.  It flushes transients for any request other than a GET.
		$transients = new SJF_Ecwid_Transients( $request_type );
		
		// Get the transient key for this url (it gets compressed to 32 chars via MD5).
		$transient_key = $transients -> get_transient_key( $url );

		// But do we actually want to read and save transients?
		$doing_transients = $this -> doing_transients( $request_type );

		// If it's a get request, see if there's a transient for it.
		if( $doing_transients ) {

			// If there is a transient for this request, bail.
			$transient = $transients -> get_transient( $transient_key );

			if( ! empty( $transient ) ) {
				return $transient;
			}

		}

		// If we made it this far, there is no transient for this url.  Build our request args.
		$request_args = array();
		$request_args['method'] = $request_type;
		
		// Unless it's a GET or a DELETE, we may need to send our request as JSON.
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

		// Call ecwid.
		$out = wp_remote_request( $url, $request_args );
			
		// If it's an error, and we are in debug mode, echo an error message.
		if( is_wp_error( $out ) && WP_DEBUG ) {
			$error = new SJF_Ecwid_Errors;
			echo( $error -> get_error_message( $out ) );
		}

		// We don't want to save an error as a transient.
		$doing_transients = $this -> check_response_code( $out );

		if( $doing_transients ) {
			$transients -> set_transient( $transient_key, $out );
		}

		return $out;

	}

}