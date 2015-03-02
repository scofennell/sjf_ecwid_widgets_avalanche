<?php

/**
 * The admin settings screen.
 *
 * Provides a UI to enter plugin settings, dump plugin cache, authorize the plugin to ecwid.com.
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
		
		// Add our menu item.
		add_action( 'admin_menu', array( $this, 'admin_menu_tab' ) );
	
		// Check to see if the user is trying to dump the cache.
		add_action( 'admin_init', array( $this, 'dump_cache' ) );

		// Register our admin notice.
		add_action( 'sjf_et_admin_notices', array( $this, 'get_notice' ) );

    }

	/**
	 * Add a menu item for our plugin.
	 */
	function admin_menu_tab() {
	    
		// Add a primary menu item.
	    add_menu_page(
	    	SJF_Ecwid_Helpers::get_plugin_title(),
	    	SJF_Ecwid_Helpers::get_plugin_title(),
	    	SJF_Ecwid_Helpers::get_capability(),
	    	SJF_Ecwid_Admin_Helpers::get_menu_slug(),
	    	array( $this, 'the_admin_page' ),
	    	$this -> get_dashicon_class()
	    );

	}

	/**
	 * A page for used for help / faq  / clearing transients, etc.
	 */
	function the_admin_page() {
	    
		// Check capability.
		if( ! current_user_can( SJF_Ecwid_Helpers::get_capability() ) ) { return false; }

	    // Seems like a good time to flush rules to make sure our feed works.
		$this -> maybe_flush_rules();

		/**
		 * Grab the auth class, which we'll need for some template tags and also because it handles deauth requests.
		 * The constructor adds an action to admin_footer to check the url for a request to deauth.
		 */
		$auth = new SJF_Ecwid_Auth;

		$namespace = SJF_Ecwid_Helpers::get_namespace();

	    $title = SJF_Ecwid_Helpers::get_plugin_title();

		// Draw the page content.
	    echo "
	    	<div class='wrap'>
	 			<h2>$title</h2>

	 			<div class='$namespace-settings'>
	 	";

			// If the user has just tried to auth, this will reload the page to reflect auth status.
			echo $auth -> get_auth_section();

			echo $this -> get_dump_cache_section();

			echo $this -> get_documentation_section();

			echo $this -> get_debug_section();

		echo "
				</div>
			</div>
		";

	}

	/**
	 * Get a settings section to allow the user to control plugin transients.
	 * 
	 * @return String A settings section to allow the user to control plugin transients.
	 */
	function get_dump_cache_section() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$link = $this -> get_dump_cache_link();
		$header = "<h3 class='$namespace-settings-section-header'>" . esc_html__( 'Caching', 'sjf-et' ) . '</h3>';
		$cache_notes_1 = '<p>' . esc_html__( 'When this plugins grabs information from your store, it saves it to the WordPress transients cache. This cache is automatically cleared each day and when saving any Ecwid widget, but you can clear it now as well.', 'sjf-et' ) . '</p>';
		$cache_notes_2 = '<p>' . esc_html__( ' If you are logged in and WordPress debug mode is on, caching will not occur.', 'sjf-et' ) . '</p>';

		return "
			<div class='$namespace-settings-section'>
				$header
				$cache_notes_1
				$cache_notes_2
				<p class='$namespace-settings-section-submit submit'>
					$link
				</p>
			</div>
		";

	}

	/**
	 * Get a link for dumping all the transients for this plugin.
	 * 
	 * @return string A link for dumping all the transients for this plugin.
	 */
	function get_dump_cache_link() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$url = add_query_arg( array( 'dump_cache' => 1 ) );

		$click_here = esc_html__( 'Clear Caches', 'sjf-et' );

		$link = "<a class='button button-primary $namespace-dump-link' href='$url'>$click_here</a>";

		return $link;
	
	}

	/**
	 * Call our transients cache, with an arg to denote that it should dump caches.
	 */
	function dump_cache() {

		if( ! isset( $_GET['dump_cache'] ) ) { return FALSE; }

		// When the transients class is initiated with a value of FALSE, it dumps caches.
		$trans = new SJF_Ecwid_Transients( FALSE );

	}

	/**
	 * Get a settings section to print helpful debug info.
	 * 
	 * @return String A settings section to print helpful debug info.
	 */
	function get_debug_section() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$help_href = SJF_Ecwid_Helpers::get_help_href();

		$debug      = new SJF_Ecwid_Admin_Debug;
		$debug_info = $debug -> get_reports();

		$header = "<h3 class='$namespace-settings-section-header'>" . esc_html__( 'Debug Info', 'sjf-et' ) . '</h3>';
		$debug_notes_1 = '<p>' . esc_html__( 'This information will be important if you need to ask for help.', 'sjf-et' ) . '</p>';
		$debug_notes_2 = "<p><a class='button-primary' href='$help_href' target='_blank'>" . esc_html__( 'WordPress.org support forum for this plugin', 'sjf-et' ) . '</a></p>';

		return "
			<div class='$namespace-settings-section'>
				$header
				$debug_notes_1
				$debug_notes_2
				$debug_info
			</div>
		";

	}

	/**
	 * Get a settings section to print helpful debug info.
	 * 
	 * @return String A settings section to print helpful debug info.
	 */
	function get_documentation_section() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$documentation      = new SJF_Ecwid_Admin_Documentation;
		$documentation_info = $documentation -> get_docs();

		$header = "<h3 class='$namespace-settings-section-header'>" . esc_html__( 'Documentation', 'sjf-et' ) . '</h3>';
		$documentation_notes_1 = '<p>' . esc_html__( 'Here you can find information on how to use this plugin.', 'sjf-et' ) . '</p>';
		
		return "
			<div class='$namespace-settings-section'>
				$header
				$documentation_notes_1
				$documentation_info
			</div>
		";

	}

	/**
	 * The dashicon slug for our plugin.
	 * 
	 * @return string The slug for the dashicon associated with our plugin.
	 */
	function get_dashicon_slug() {
		return 'cart';
	}

	/**
	 * The dashicon class for our plugin.
	 * 
	 * @return string The class for the dashicon associated with our plugin.
	 */
	function get_dashicon_class() {
		$slug = $this -> get_dashicon_slug();
		return "dashicons-$slug";
	}

	/**
	 * Get an admin notice prompting the user to authorize the app.
	 * 
	 * @return An admin notice prompting the user to authorize the app.
	 */
	function get_notice() {
		
		// No need to show this if we are already on the settings page.
		if ( SJF_Ecwid_Conditional_Tags::is_settings_page() ) { return FALSE; }

		// The user needs a paid account.  If he already has one, don't nag him.
		$response = SJF_Ecwid_Helpers::get_ecwid_response();
		if( $response == '200' ) { return FALSE; }

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Build the title to the admin notice, using a link to the settings page.
		$plugin_title = SJF_Ecwid_Helpers::get_plugin_title();
		$plugin_href  = SJF_Ecwid_Admin_Helpers::get_main_menu_url();
		$plugin_link  = "<a href='$plugin_href'>$plugin_title</a>";
		$title        = sprintf( esc_html__( 'Thanks for installing %s!', 'sjf-et' ), $plugin_link );

		// Build the content of the admin notice.
		$content = SJF_Ecwid_Helpers::get_nag();

		$out = SJF_Ecwid_Admin_Notices::the_notice( $title, $content );

		return $out;

	}

	/**
	 * Check the feed for a response.  If 404, flush rules.
	 */
	function maybe_flush_rules() {

		// Grab the feed url.
		$feed = new SJF_Ecwid_Feed;
		$feed_url = $feed -> get_channel_url();

		// Check the feed.
		$check_feed = wp_remote_request( $feed_url, $args = array() );
		if( is_wp_error( $check_feed ) ) { return FALSE; }
		$code = $check_feed['response']['code'];
		
		// Maybe flush rules.
		if( $code == '404' ) {
			flush_rewrite_rules();
		}

	}

}