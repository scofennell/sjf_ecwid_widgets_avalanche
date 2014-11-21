<?php

/**
 * The main administration screen.
 *
 * Draws the main plugin screen and requires files for sub-screens.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_admin_init() {
	new SJF_Ecwid_Admin();
}
add_action( 'init', 'sjf_et_admin_init' );

class SJF_Ecwid_Admin {

	/**
     * Adds actions for our class methods.
     */
    function __construct() {    
		add_action( 'admin_menu', array( $this, 'admin_menu_tab' ) );
		add_action( 'admin_init', array( $this, 'admin_settings' ) );
    }

	/**
	 * Add a menu item for our plugin.
	 */
	function admin_menu_tab() {
	    
		// Add a primary menu item.
	    add_menu_page(
	    	esc_html__( 'Ecwid Tools for WordPress', 'sjf_et' ),
	    	esc_html__( 'Ecwid Tools for WordPress', 'sjf_et' ),
	    	SJF_Ecwid_Helpers::get_capability(),
	    	'sjf-et',
	    	array( $this, 'admin_page' ),
	    	SJF_Ecwid_Admin_Helpers::get_dashicon_class(),
	    	6
	    );

	}

	/**
	 * A page for used for help / faq  / clearing transients, etc.
	 */
	function admin_page() {
	    
		 // Check capability.
		if( ! current_user_can( SJF_Ecwid_Helpers::get_capability() ) ) { return false; }
  
	    $title = esc_html__( 'Ecwid Tools for WordPress', 'sjf_et' );
		$authorize = $this -> authorize();

		// Draw the page content.
	    echo "
	    	<div class='wrap'>
	 			<h2>$title</h2>

	 			<form action='options.php' method='post'>
		";		
					settings_fields( SJF_Ecwid_Helpers::get_namespace() . '_setup_options' );
					do_settings_sections( SJF_Ecwid_Helpers::get_namespace() . '_setup' );
		
					$val = esc_attr__( 'Save Settings', 'sjf_et' );
		echo "		
					<p class='submit'>
						<input class='button button-primary' name='Submit' type='submit' value='$val' />
					</p>
			</form>

			$authorize

			</div>
		";

	}

	/**
	 * Register and define the settings.
	 */
	function admin_settings() {
		
		// Register an array to hold our settings.
		register_setting(
			SJF_Ecwid_Helpers::get_namespace() . '_setup_options',
			SJF_Ecwid_Helpers::get_namespace() . '_setup_options',
			array( $this, 'validate_options' )
		);

		// Register a group of settings fields.
		$section_header = esc_html__( 'Ecwid Tools for WordPress Settings', 'sjf_et' );
		add_settings_section(
			SJF_Ecwid_Helpers::get_namespace() . '_setup_main',
			$section_header,
			false,
			SJF_Ecwid_Helpers::get_namespace() . '_setup'
		);
		
		// Add a settings field for store id.
		$store_id_setting_header = esc_html__( 'Enter Ecwid store ID here.', 'sjf_et' );
		add_settings_field(
			SJF_Ecwid_Helpers::get_namespace() . '_store_id',
			$store_id_setting_header,
			array( $this, 'store_id_input' ),
			SJF_Ecwid_Helpers::get_namespace() . '_setup',
			SJF_Ecwid_Helpers::get_namespace() . '_setup_main'
		);

	}

	/**
	 * Echo an input for API Key.
	 */
	function store_id_input() {

		// Get the current value for api key.
		$store_id        = esc_attr( SJF_Ecwid_Helpers::get_store_id() );
		$placeholder     = esc_attr__( 'Ecwid Store ID', 'sjf_et' );
		$settings_prefix = esc_attr( SJF_Ecwid_Helpers::get_settings_prefix() );

		// echo the field
		echo "<input id='api_key' name='$settings_prefix" . "[store_id]' type='text' placeholder='$placeholder' value='$store_id' />";

	}

	/**
	 * Validate the options on the way to the DB.
	 * 
	 * @param  array $input Untrusted options.
	 * @return array Trusted options.
	 */
	function validate_options( $input ) {

		// Sanitize store_id.
		if(isset( $input[ 'store_id' ] ) ) {
			$valid[ 'store_id' ] = SJF_Ecwid_Formatting::alphanum( $input[ 'store_id' ] );
		}

		return $valid;
	}

	function get_redir_uri() {
		
		/**
		 * @todo  Once I can register the app as being "user installed", use this instead of home url.
  		 * I'm using home url since it was resolving to scottfennell.com/ecwid
		 * which is how I first registered the app with ecwid.
		 */
		
		/*
		$proto = 'http';
		if( is_ssl() ) { $proto = 'https'; }
		$redirect_uri_dirty = esc_url( admin_url( 'admin.php?page=sjf-et', $proto ) );
		$redirect_uri = urlencode( $redirect_uri_dirty );
		*/
	
		return home_url();

	}

	/**
	 * Prompt the user to authorize the app.
	 */
	function authorize() {

		// If the user already has a token, bail.
		$token = SJF_Ecwid_Admin_Helpers::get_token();
		if( ! empty( $token ) ) {
			$your = esc_html__( 'Your Ecwid API token:', 'sjf_et' );
			$note = esc_html__( 'This might come in handy if you need support for this app.', 'sjf_et' );
			$out = "<p>$your <code>$token</code></p><p><em>$note</em></p>";
			return $out;
		
		}

		// Grab the store ID.  If none, bail.
		$store_id = SJF_Ecwid_Helpers::get_store_id();
		if( empty( $store_id ) ) { return false; }

		$client_id     = SJF_Ecwid_Admin_Helpers::get_client_id();
		$client_secret = SJF_Ecwid_Admin_Helpers::get_client_secret();
		$scope_array   = SJF_Ecwid_Admin_Helpers::get_scopes();

		// If the user has just agreed to authorize the app, request a token.
		if( isset( $_GET['code'] ) ) {

			$url = SJF_Ecwid_Admin_Helpers::get_token_url();
			
			$code = sanitize_text_field( $_GET['code'] );

			$redirect_uri = $this -> get_redir_uri();

			$grant_type = 'authorization_code';

			$body = array(
				'code'          => $code,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri'  => $redirect_uri,
				'grant_type'    => $grant_type,
			);

			$args = array( 'body' => $body );

			$post = wp_remote_post( $url, $args );

			$body = json_decode( $post['body'] );

			var_dump( $body );

		// If the user has not yet agreed to authorize, prompt him to do so.
		} else {

			$scope = '';
			foreach( $scope_array as $s ) {
				$scope .= "$s+";
			}

			$scope         = rtrim( $scope, '+' );
			$response_type = 'code';
			$redirect_uri  = $this -> get_redir_uri();
			$base          = SJF_Ecwid_Admin_Helpers::get_authorization_url();

			$url = $base . "?client_id=$client_id&redirect_uri=$redirect_uri&response_type=$response_type&scope=$scope";

			$click_here = esc_html__( 'Thanks for entering a store ID! Click here to connect this plugin to your store.', 'sjf_et' );

			$link = "<a href='$url'>$click_here</a>";

			return $link;

		}
	}
}