<?php

/**
 * Register debug class for our plugin.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

Class SJF_Ecwid_Admin_Debug {

	/**
	 * Get a link to emailing debug info.
	 * 
	 * @param  string $body The email body, presumably debug info.
	 * @return string A link to emailing debug info.
	 */
	function get_email_link( $body ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		// Send it to the blog admin.
		$mailto  = rawurlencode( sanitize_email( get_bloginfo( 'admin_email' ) ) );
		
		// Grab a subject line.
		$subject = rawurlencode( esc_attr__( 'Debug Info from Widgets Avalance for Ecwid', 'sjf-et' ) );

		// Sanitize the body.
		$body = rawurlencode( $body );

		// Build the href, along with some extra paranoia escaping.
		$href = "mailto:$mailto?subject=$subject&body=$body";
		$href = str_replace( '&amp;', '%26', $href );
		$href = str_replace( ' ', '%20', $href );

		// Build the link.
		$label = esc_html__( 'Email this information to a trusted friend', 'sjf-et' );
		$icon  = '<i class="dashicons dashicons-email-alt"></i>';
		$out   = "<a class='$base_class button'  target='_top' href='mailto:$mailto?subject=$subject&body=$body'>$icon $label</a>";
	
		return $out;

	}

	/**
	 * Template tag (of sorts) to display debug info.
	 * 
	 * @return string Debug info in nested accordion list.
	 */
	function get_reports() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		// Fire up our transients class.  It flushes transients for any request other than a GET.
		$transients = new SJF_Ecwid_Transients( 'GET' );
		
		// Get the transient key for this url (it gets compressed to 32 chars via MD5).
		$transient_key = $transients -> get_transient_key( 'debug' );

		// If there is a transient for this request, bail.
		$transient = $transients -> get_transient( $transient_key );

		if( ! empty( $transient ) ) {
			return $transient;
		}

		$out = '';
		// Grab all the debuggery sections.
		$out .= $this -> get_blog_info();
		$out .= $this -> get_browser_info();
		$out .= $this -> get_plugin_info();
		$out .= $this -> get_store_info();
		$out .= $this -> get_theme_info();
		$out .= $this -> get_user_info();
		$out .= $this -> get_wpmu_blog_info();
		
		/**
		 * @todo Leaving this commented out due to security concerns.
		 */
		// $out .= $this -> get_server_info();

		// Give a mailto: link to email the debug info.
		$mail_link = $this -> get_email_link( $out );

		// If we're not empty, wrap the output.
		if( ! empty( $out ) ) {
			$out = "
				<output class='$base_class'>
					$out
					$mail_link
				</output>
			";
		}

		// Store this massive chunk so we don't have to worry about grabbing it again.
		$transients -> set_transient( $transient_key, $out );
	
		return $out;

	}

	/**
	 * Wrap a debug section in expected HTML for accordion goodness.
	 * 
	 * @param  string $label   The label for this block.
	 * @param  string $slug    The slug for this block, used in css classes and the like.
	 * @param  mixed $content  A string, array, or object of debug data.
	 * @return string          A wrapped debug section.
	 */
	function get_report( $label, $slug, $content ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		// Dig into the debug content.
		$out = $this -> var_dig( $content, $slug );

		// If there is debug content, wrap it.
		if( ! empty( $out ) ) {

			// This is unused for now but it's helpful for debugging the debugging.
			$slug = sanitize_html_class( $slug );

			// A toggle link to reveal this section.
			$toggle = SJF_Ecwid_Helpers::get_toggle();

			// The label for this section.
			$label = "<h4 class='$base_class-label'>$label</h4>";

			$out = "
				<div class='$base_class $namespace-toggle-parent'>
					$label
					$toggle
					$out
				</div>
			";
		}

		return $out;

	}

	/**
	 * Dig into a variable and output it as a nested list.
	 * 
	 * @param  mixed $content     An object, array, or string to be converted to a nested list.
	 * @param  string  $slug      Not currently used, helpful to debug the debugging.
	 * @param  boolean $accordion Output as accordion?
	 * @return string             Debug information as a nested list.
	 */
	function var_dig( $content, $slug = '', $accordion = TRUE ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		$out = '';

		// Convert the value to an array.
		$content = $this -> arrayify( $content );
		if( ! is_array( $content ) ) {
			return FALSE;
		}

		// For each piece of info...
		foreach( $content as $k => $v ) {

			// Convert it to an array.
			$v = $this -> arrayify( $v );

			// If it's an array, dig into it recursively.
			if( is_array( $v ) ) {

				$out .= "<li><h5 class='$base_class-key'>$k</h5>" . $this -> var_dig( $v, $k, FALSE ) . '</li>';

			// If it's scalar, just output it.
			} elseif( is_scalar( $v ) ) {

				$out .= "
					<li class='$base_class-pair'>
						<h5 class='$base_class-key'>" . esc_html( $k ) . "</h5>
						<code  class='$base_class-value'>" . esc_html( $v ) . "</code>
					</li>
				";
				
			}
		
		}

		// If we're not empty, wrap the output, possibly as an accordion.
		if( ! empty( $out ) ) {

			$maybe_accordion = '';
			if( $accordion ) { $maybe_accordion = $namespace . '_accordion'; }

			$out = "<ul class='$base_class-list $maybe_accordion'>$out</ul>";
		}

		return $out;

	}

	/**
	 * Convert an object to an array.
	 * 
	 * @param  mixed $object Any variable that might be an object.
	 * @return mixed If given an array or object, returns an array.  Else, returns original value. 
	 */
	function arrayify( $object ) {
	
		// Are we actually not an object?  Just bail.
		if( ! is_object( $object ) ) {
			return $object;
		}

		// if we are an object, convert to an array.
		$object = json_encode( $object );
		$out    = json_decode( $object, TRUE );

		return $out;

	}

	/**
	 * Get info about the user browser and OS.
	 * 
	 * @return string Info about the user browser and OS.
	 */
	function get_browser_info() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Lean on this wp-core file to browser sniff for us.
		$dashboard = ABSPATH . ( '/wp-admin/includes/dashboard.php' );
		if( ! @ file_exists( $dashboard ) ) {
    		return FALSE;
		} else {
		   include( $dashboard );
		}

		$label = esc_html__( 'Browser Info', 'sjf-et' );
		
		$array = wp_check_browser_version();

		if( is_array( $array ) ) {
			return $this -> get_report( $label, __FUNCTION__, $array );
		}

	}

	/**
	 * Get info about the Ecwid store to which this blog is authenticated.
	 * 
	 * @return string Info about the Ecwid store to which this blog is authenticated.
	 */
	function get_store_info() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'Store Info', 'sjf-et' );
		
		$array = SJF_Ecwid_Helpers::get_store_profile();

		return $this -> get_report( $label, __FUNCTION__, $array );
	
	}

	/**
	 * Grab info about the current user.
	 * 
	 * @return string info about the current user.
	 */
	function get_user_info() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'User Info', 'sjf-et' );
		
		$content = wp_get_current_user();

		return $this -> get_report( $label, __FUNCTION__, $content );

	}

	/**
	 * Grab info about the blog itself.
	 * 
	 * @return string Info about the blog itself
	 */
	function get_blog_info() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'Blog Info', 'sjf-et' );
		
		/**
		 * @todo There must be a way to do this all in one swoop.
		 */
		$fields = array(
			'url',
			'wpurl',
			'description',
			'rdf_url',
			'rss_url',
			'rss2_url',
			'atom_url',
			'comments_atom_url',
			'comments_rss2_url',
			'pingback_url',
			'stylesheet_url',
			'stylesheet_directory',
			'template_directory',
			'template_url',
			'admin_email',
			'charset',
			'html_type',
			'version',
			'language',
			'text_direction',
			'name',
		);

		$content = array();

		foreach( $fields as $f ) {
			$content[ $f ]= get_bloginfo( $f );	
		}

		return $this -> get_report( $label, __FUNCTION__, $content );
		
	}

	/**
	 * Grab info pertaining to network status.
	 * 
	 * @return string Info pertaining to network status.
	 */
	function get_wpmu_blog_info() {
		
		if( ! function_exists( 'get_blog_details' ) ) { return false; }

		$label = esc_html__( 'WPMU Blog Info', 'sjf-et' );
		
		$content = get_blog_details();

		return $this -> get_report( $label, __FUNCTION__, $content );

	}

	/**
	 * Grab theme info.
	 * 
	 * @return string Theme info.
	 */
	function get_theme_info() {

		$label = esc_html__( 'Theme Info', 'sjf-et' );
		
		$theme = wp_get_theme();

		/**
		 * @todo I'm having trouble unpacking the theme object into an array, so I'm doing it manually.
		 */
		$fields = array(
			'Name',
			'ThemeURI',
			'Description', 
			'Author',
			'AuthorURI', 
			'Version',
			'Template', 
			'Status', 
			'Tags',
			'TextDomain', 
			'DomainPath',
		);

		foreach( $fields as $f ) {
			$content[$f]= $theme -> $f;	
		}

		return $this -> get_report( $label, __FUNCTION__, $content );

	}

	/**
	 * Grab plugin info.
	 * 
	 * @return string Plugin info.
	 */ 
	function get_plugin_info() {

		$label = esc_html__( 'Plugin Info', 'sjf-et' );
		
		// Get all installed plugins.
		$plugins = get_plugins();

		$content = array();

		// Only grab active plugins.
		foreach( $plugins as $k => $v ) {
			if( ! is_plugin_active( $k ) ) {
				continue;
			}

			$content[]= array( $k => $v );

		}

		return $this -> get_report( $label, __FUNCTION__, $content );

	}

	/**
	 * Get info about the server.
	 * 
	 * @return string Info about the server. Currently unused due to security concerns.
	 */
	function get_server_info() {

		if( ! WP_DEBUG ) { return FALSE; }

		if( ! is_admin() ) { return FALSE; }

		if( ! current_user_can( 'update_core' ) ) { return FALSE; }

		$label = esc_html__( 'Server Info', 'sjf-et' );
		
		return $this -> get_report( $label, __FUNCTION__, $_SERVER );

	}

}