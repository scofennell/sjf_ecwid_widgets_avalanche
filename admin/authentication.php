<?php

/**
 * Get an authentication token from ecwid.com.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

class SJF_Ecwid_Auth {

	/**
     * Adds actions for our class methods.
     */
    function __construct() {    
		
		// Check to see if the user is trying to deauth.
		add_action( 'admin_footer', array( $this, 'check_deauth' ) );

    }


	/**
	 * The url to which we send the user in hopes that they will authorize our app.
	 * 
	 * @return string The url to which we send the user in hopes that they will authorize our app.
	 */
	function get_authorization_url() {
		return 'https://my.ecwid.com/api/oauth/authorize';
	}

	/**
	 * Get url from which we will requst an auth token.
	 * 
	 * @return string Url from which we will requst an auth token.
	 */
	function get_token_url() {
		return 'https://my.ecwid.com/api/oauth/token';
	}

	/**
	 * The ID issued to me, Scott, by Ecwid, for this plugin.
	 * 
	 * @return string The ID issued to me, Scott, by Ecwid, for this plugin.
	 */
	function get_client_id() {
		return 'AkKF4tAF8UrPMWsr';
	}

	/**
	 * The secret issued to me, Scott, by Ecwid, for this plugin.
	 * 
	 * @return string The secret issued to me, Scott, by Ecwid, for this plugin.
	 */
	function get_client_secret() {
		return 'YJNkJiywU2yfYBEBqE3vKMbar3XjzaL8';
	}

	/**
	 * Get the parts of Ecwid that our app wants permission to touch.
	 *
	 * This is presented to the user when they authorize the app.
	 * 
	 * @return array The parts of Ecwid that our app wants permission to touch.
	 */
	function get_scopes() {
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

	/**
	 * Get the url to our settings page, uri sanitized.
	 *
	 * @return  string The url to our settings page, uri sanitized.
	 */
	function get_redir_uri_clean() {
		
		$redirect_uri_dirty = esc_url_raw( SJF_Ecwid_Admin_Helpers::get_main_menu_url() );
		$redirect_uri = urlencode( $redirect_uri_dirty );

		return $redirect_uri;

	}

	/**
	 * Get the settings section for authorizing the plugin.
	 * 
	 * @return string The settings section for authorizing the plugin.
	 */
	function get_auth_section() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// We do a redir if the user clicks on the deauth link, so the UI updates as such.
		add_action( 'admin_footer', array( $this, 'redir_script' ) );

		// If the user is authorized, thank them and give them a deauth link.
		if ( SJF_Ecwid_Helpers::is_authorized() ) {
			
			$connect_notes = '<p>' . esc_html__( 'This plugin needs to be connected to your store in order to work.  Right now, it is connected.', 'sjf-et' ) . '</p>';
			$link = $this -> deauth_link();
			
		// If the user is not authorized, try to authorize them, or prompt them to authorize.
		} else {

			// Try to authorize.  This will reload the page if successful.
			$request_token = $this -> request_token();

			$connect_notes = '<p>' . esc_html__( 'This plugin needs to be connected to your store in order to work.  Right now, it is not connected.', 'sjf-et' ) . '</p>';

			$link = $this -> auth_link();

		}

		// Section header.
		$header = '<h3 class="$namespace-settings-section-header">' . esc_html__( 'Connecting Your Store', 'sjf-et' ) . '</h3>';

		// A link to de/auth.
		$link = "
			<p class='$namespace-settings-section-submit submit'>
				$link
			</p>
		";

		// Maybe thank the user for being authorized.
		$thank  = $this -> thank();

		$out = "
			<div class='$namespace-settings-section'>
				$header
				$link
				$thank
			</div>
		";

		return $out;

	}

	/**
	 * Get a thank you message for users who are auth'd.
	 *
	 * @return string A thank you message for users who are auth'd.
	 */
	function thank() {

		$token = SJF_Ecwid_Helpers::get_token();
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		if( empty( $token ) ) { return FALSE; }

		$content = sprintf( esc_html__( 'Your Ecwid API token: %s', 'sjf-et' ), "<code>$token</code>" );
		
		$out = "<p class='$namespace-thank'>$content</p>";
		
		return $out;

	}

	/**
	 * Ping ecwid.com for an auth token for our app.
	 *
	 * @return string JS to redir the page on success, or a failure message on failure.
	 */
	function request_token() {
		
		// Grab the redir code from ecwid.
		if( ! isset( $_GET['code'] ) ) { return FALSE; }
		$code = sanitize_text_field( $_GET['code'] );

		// Build args for requesting a token.
		$request_body = array(

			// Ecwid adds this to the url when we authorize on ecwid.com
			'code'          => $code,

			// Ecwid guys gave this to me, it's hard-coded in the plugin.
			'client_id'     => $this -> get_client_id(),

			// Ecwid guys gave this to me, it's hard-coded in the plugin.
			'client_secret' => $this -> get_client_secret(),
			
			// We want users to return to the plugin settings page upon auth.
			'redirect_uri'  => SJF_Ecwid_Admin_Helpers::get_main_menu_url(),

			// Ecwid says to do this, I don't know why.
			'grant_type'    => 'authorization_code',

		);
		$args = array( 'body' => $request_body );

		// The url from which we wil request a token.
		$url = $this -> get_token_url();
		
		// Parse the response.
		$post = wp_remote_post( $url, $args );
		$response = json_decode( $post['body'], TRUE );

		// If successful, save the token and reload the page so as to update the menu UI.
		if( isset( $response['access_token'] ) && isset( $response['store_id'] ) ) {
			
			// Save the token to the db.
			$token = $response['access_token'];
			SJF_Ecwid_Helpers::set_token( $token );

			// Save the store_id to the db.
			$store_id = $response['store_id'];
			SJF_Ecwid_Helpers::set_store_id( $store_id );


			// Redir the page so that the UI is updated to reflect the fact that the user is auth'd.
			$url = remove_query_arg( 'code' );
			echo "
				<script>
					window.location.replace( '$url' );
				</script>
			";

			return TRUE;

		} else {
			return FALSE;
		}

	}

	/**
	 * Get a link for authorizing our app.
	 * 
	 * @return string A link for authorizing our app.
	 */
	function auth_link() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Grab the store ID.  If none, bail.
		// $store_id = SJF_Ecwid_Helpers::get_store_id();
		// if( empty( $store_id ) ) { return false; }

		// The ecwid.com base url for requesting auth.
		$base = $this -> get_authorization_url();

		// Ecwid guys gave this to me, it's hard-coded in the plugin.
		$client_id = $this -> get_client_id();
		
		// Ecwid guys gave this to me, it's hard-coded in the plugin.
		$client_secret = $this -> get_client_secret();
		
		// Ecwid says to do this, I don't know why.
		$response_type = 'code';
		
		// We want users to return to the plugin settings page upon auth.
		$redirect_uri = $this -> get_redir_uri_clean();
		
		// Convert the scope to a url var.
		$scope_array = $this -> get_scopes();
		$scope = '';
		foreach( $scope_array as $s ) {
			$scope .= "$s+";
		}
		$scope = rtrim( $scope, '+' );
		
		$url = $base . "?client_id=$client_id&redirect_uri=$redirect_uri&response_type=$response_type&scope=$scope";

		$click_here = esc_html__( 'Connect this plugin to your store.', 'sjf-et' );

		$link = "<a class='button button-primary $namespace-auth-link' href='$url'>$click_here</a>";

		return $link;
	
	}

	/**
	 * Get a link for deauthorizing our app.
	 * 
	 * @return string A link for deauthorizing our app.
	 */
	function deauth_link() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Grab the store ID.  If none, bail.
		$store_id = SJF_Ecwid_Helpers::get_store_id();
		if( empty( $store_id ) ) { return false; }

		// If the user is not logged in, ecwid will "remember" that he is trying to get to this page once logged in.
		$url = 'https://my.ecwid.com/cp/?place=apps:view=authorized';

		$click_here = esc_html__( 'Disconnect this plugin from your store.', 'sjf-et' );

		$link = "
			<a class='button $namespace-deauth-link' target='_blank' href='$url'>
				<span class='dashicons dashicons-dismiss'></span>
				$click_here
			</a>";

		return $link;

	}

	/**
	 * Sniff the url to see if the user wants to deauth.  If so, erase the token and reload the page.
	 */
	function check_deauth() {

		// Only do this if we are trying to deauth.
		if( ! isset( $_GET['deauth'] ) ) { return FALSE; }
			
		// Erase the token.
		SJF_Ecwid_Helpers::set_token( '' );

		// Erase the store id.
		SJF_Ecwid_Helpers::set_store_id( '' );

		// Redir the page.
		$url = remove_query_arg( 'deauth' );
		echo "
			<script>
				window.location.replace( '$url' );
			</script>
		";
			
	}

	/**
	 * A click handler for the deauth button: When that button is clicked, redir the page.
	 */
	function redir_script() {
		
		if( ! SJF_Ecwid_Conditional_Tags::is_settings_page() ) { return FALSE; }

		$namespace = SJF_Ecwid_Helpers::get_namespace();
		$link = "$namespace-deauth-link";
		$redir = add_query_arg( array( 'deauth' => 1 ) );
			
		$out = <<<EOT
			<script>
				jQuery( document ).ready( function( $ ) {
					
					var link = $( ".$link " );

					$( link ).click( function( event ) {
						window.location.replace( '$redir' );
					});

					
				});
			</script>
EOT;

		echo $out;
	}

}