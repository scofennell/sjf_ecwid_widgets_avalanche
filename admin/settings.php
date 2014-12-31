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
		add_action( 'sjf_et_admin_notices', array( $this, 'get_notice' ) );
		add_action( 'admin_init', array( $this, 'check_deauth' ) );
		add_action( 'admin_init', array( $this, 'dump_cache' ) );
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
	    	SJF_Ecwid_Admin_Helpers::get_menu_slug(),
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

		$namespace = SJF_Ecwid_Helpers::get_namespace();

	    $title = esc_html__( 'Ecwid Tools for WordPress', 'sjf_et' );

		// Draw the page content.
	    echo "
	    	<div class='wrap'>
	 			<h2>$title</h2>

	 			<div class='$namespace-settings'>

	 				<form action='options.php' method='post' class='$namespace-settings-section'>
			";		
						settings_fields( SJF_Ecwid_Helpers::get_namespace() . '_setup_options' );
						do_settings_sections( SJF_Ecwid_Helpers::get_namespace() . '_setup' );
			
						$val = esc_attr__( 'Save Settings', 'sjf_et' );
			echo "		
						<p class='submit'>
							<input class='button button-primary' name='Submit' type='submit' value='$val' />
						</p>
					</form>
			";

			$store_id = SJF_Ecwid_Helpers::get_store_id();
			if( ! empty( $store_id ) ) {

				// If the user already has a token, thank.
				$connect = '<h3>' . esc_html__( 'Connecting Your Store', 'sjf_et' ) . '</h3>';
				$connect_notes = '<p>' . esc_html__( 'This plugin needs to be connected to your store in order to work.', 'sjf_et' ) . '</p>';
				
				if ( SJF_Ecwid_Helpers::is_authorized() ) {

					$thank  = $this -> thank();
					$deauth = $this -> deauth_link();
					$connect_notes = '<p>' . esc_html__( 'This plugin needs to be connected to your store in order to work.  Right now, it is connected.', 'sjf_et' ) . '</p>';
				
					echo "
						<div class='$namespace-settings-section'>
							$connect
							$connect_notes
							$thank
							<p class='submit'>
								$deauth
							</p>
						</div>";
				} else {

					$authorize = $this -> authorize();
					$connect_notes = '<p>' . esc_html__( 'This plugin needs to be connected to your store in order to work.  Right now, it is not connected.', 'sjf_et' ) . '</p>';
				
					echo "
						<div class='$namespace-settings-section'>
							$connect
							$connect_notes
							<p class='submit'>
								$authorize
							</p>
						</div>
					";

				}

				$dump_cache = $this -> dump_cache_link();
				$cache_control = '<h3>' . esc_html__( 'Caching', 'sjf_et' ) . '</h3>';
				$cache_notes = '<p>' . esc_html__( 'When this plugins grabs information from your store, it saves it to the WordPress transients cache. This cache is automatically cleared each day, but you can clear it now as well.', 'sjf_et' ) . '</p>';
				echo "
					<div class='$namespace-settings-section'>
						$cache_control
						$cache_notes
						<p class='submit'>
							$dump_cache
						</p>
					</div>
				";

			}		

		echo "</div></div>";
		

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
		$section_header = esc_html__( 'Settings', 'sjf_et' );
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

	function get_main_menu_url() {
		
		$slug = SJF_Ecwid_Admin_Helpers::get_menu_slug();

		$proto = 'http';
		if( is_ssl() ) { $proto = 'https'; }
		return admin_url( "admin.php?page=$slug", $proto );

	}

	function get_redir_uri_clean() {
		
		$redirect_uri_dirty = esc_url_raw( $this -> get_main_menu_url() );
		$redirect_uri = urlencode( $redirect_uri_dirty );

		return $redirect_uri;

	}

	function thank() {

		$token = SJF_Ecwid_Helpers::get_token();
		if( ! empty( $token ) ) {
			$your = esc_html__( 'Your Ecwid API token:', 'sjf_et' );
			$out = "<p>$your <code>$token</code></p>";
			return $out;
			
		}

		return FALSE;
	}

	/**
	 * Prompt the user to authorize the app.
	 */
	function authorize() {
		
		if ( SJF_Ecwid_Helpers::is_authorized() ) {
			return TRUE;
		}

		// Try to authorize.
		$auth = $this -> request_token();

		if ( SJF_Ecwid_Helpers::is_authorized() ) {
		
			$thank  = $this -> thank();
			$deauth = $this -> deauth_link();

			return $thank . $deauth;

		} else {
			
			$auth_link = $this -> auth_link();

			return $auth . $auth_link;

		}

	}

	function request_token() {
		
		// Grab the store ID.  If none, bail.
		$store_id = SJF_Ecwid_Helpers::get_store_id();
		if( empty( $store_id ) ) { return FALSE; }

		// Grab the redir code from ecwid.
		if( ! isset( $_GET['code'] ) ) { return FALSE; }
		$code = sanitize_text_field( $_GET['code'] );

		$client_id     = SJF_Ecwid_Admin_Helpers::get_client_id();
		$client_secret = SJF_Ecwid_Admin_Helpers::get_client_secret();
		$scope_array   = SJF_Ecwid_Helpers::get_scopes();

		$url = SJF_Ecwid_Admin_Helpers::get_token_url();
		
		$redirect_uri = $this -> get_main_menu_url();

		$grant_type = 'authorization_code';

		$request_body = array(
			'code'          => $code,
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'redirect_uri'  => $redirect_uri,
			'grant_type'    => $grant_type,
		);

		$args = array( 'body' => $request_body );

		$post = wp_remote_post( $url, $args );

		$response = json_decode( $post['body'], TRUE );

		if( isset( $response['error'] ) ) {

			$error = '<strong>' . $response['error'] . '</strong>';

			$problem = sprintf( esc_html__( 'There has been a problem: %s', 'sjf-et' ), $error );

			return "<p>$problem</p>";

		} elseif( isset( $response['access_token'] ) ) {
			
			$token = $response['access_token'];

			SJF_Ecwid_Helpers::set_token( $token );

			$url = remove_query_arg( 'code' );

			echo "
			<script>
				window.location.replace( '$url' );
			</script>
			";
			
			exit;

		} else {

			$problem = esc_html__( 'There has been a problem. 262', 'sjf-et' );
		
			return "<p>$problem</p>";

		}
	}

	function dump_cache_link() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$url = add_query_arg( array( 'dump_cache' => 1 ) );

		$click_here = esc_html__( 'Clear Caches', 'sjf_et' );

		$link = "<a class='button button-primary $namespace-dump-link' href='$url'>$click_here</a>";

		return $link;
	
	}

	function dump_cache() {

		if( ! isset( $_GET['dump_cache'] ) ) { return FALSE; }

		$trans = new SJF_Ecwid_Transients( FALSE );

	}

	function auth_link() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Grab the store ID.  If none, bail.
		$store_id = SJF_Ecwid_Helpers::get_store_id();
		if( empty( $store_id ) ) { return false; }

		$scope_array   = SJF_Ecwid_Helpers::get_scopes();
		$client_id     = SJF_Ecwid_Admin_Helpers::get_client_id();
		$client_secret = SJF_Ecwid_Admin_Helpers::get_client_secret();
		$scope_array   = SJF_Ecwid_Helpers::get_scopes();

		$scope = '';
		foreach( $scope_array as $s ) {
			$scope .= "$s+";
		}

		$scope         = rtrim( $scope, '+' );
		$response_type = 'code';
		$redirect_uri  = $this -> get_redir_uri_clean();
		$base          = SJF_Ecwid_Admin_Helpers::get_authorization_url();

		$url = $base . "?client_id=$client_id&redirect_uri=$redirect_uri&response_type=$response_type&scope=$scope";

		$click_here = esc_html__( 'Connect this plugin to your store.', 'sjf_et' );

		$link = "<a class='button button-primary $namespace-auth-link' href='$url'>$click_here</a>";

		return $link;
	
	}

	function deauth_link() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Grab the store ID.  If none, bail.
		$store_id = SJF_Ecwid_Helpers::get_store_id();
		if( empty( $store_id ) ) { return false; }

		$url = 'https://my.ecwid.com/cp/?place=apps:view=authorized';

		$click_here = esc_html__( 'Disconnect this plugin from your store.', 'sjf_et' );

		$link = "<a class='button $namespace-deauth-link' target='_blank' href='$url'><span class='dashicons dashicons-dismiss'></span> $click_here</a>";

		return $link;

	}

	function get_notice() {
		
		if ( SJF_Ecwid_Helpers::is_authorized() ) { return FALSE; }

		if ( SJF_Ecwid_Conditional_Tags::is_settings_page() ) { return FALSE; }

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$plugin_title = SJF_Ecwid_Helpers::get_plugin_title();
		$plugin_href  = $this -> get_main_menu_url();
		$plugin_link  = "<a href='$plugin_href'>$plugin_title</a>";

		$title = sprintf( esc_html__( 'Thanks for installing %s!', 'sjf_et' ), $plugin_link );

		$content = '';
		
		$response = SJF_Ecwid_Helpers::get_ecwid_response();
	
		if( $response == '402' ) {

			$content .= '<p>' . esc_html__( "You need a paid Ecwid account in order to use this plugin!", 'sjf_et' ) . '</p>';

		} else {

			$content = '<p>' . esc_html__( "Just authorize the plugin and you'll be all set!", 'sjf_et' ) . '</p>';

		}

		$content .= '<p>' .  $this -> auth_link() . '</p>';

		$out = SJF_Ecwid_Admin_Notices::the_notice( $title, $content );

		return $out;

	}

	function check_deauth() {

		if( isset( $_GET['deauth'] ) ) {
			SJF_Ecwid_Helpers::set_token( '' );

			$url = remove_query_arg( 'deauth' );

			echo "
			<script>
				window.location.replace( '$url' );
			</script>
			";
			
		}

	}

}