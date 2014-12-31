<?php

/**
 * Our error handler class.
 *
 * Parse an error message and return it for display.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

class SJF_Ecwid_Errors {

	/**
	 * Parse a wp error object into something more reaable for users.
	 * 
	 * @param  object $wp_error A WP Error.
	 * @return string An error message, wrapped and classed.
	 */
	function get_error_message( $wp_error ) {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();
		$out = '';

		// Grab the array of errors.
		$errors = $wp_error -> errors;

		// For each error...
		foreach( $errors as $k => $v ) {
			
			// The subtitle for this error.
			$out .= "<h4>$k</h4><ul>";
			
			// Each part of the error is listed out.
			foreach( $v as $kk => $vv ) {
				$out .= "<li>$kk: $vv</li>";
			}

			$out .= "</ul>";
		}

		// If not empty, wrap and class the output.
		if( ! empty( $out ) ) {

			$header_label = esc_html__( 'Sorry, there has been an error.  More details:', 'sjf_et' );
			$header = "<h3>$header_label</h3>";
			$out = "
				<div class='$namespace-wp-error'>$header $out</div>
			";
		
		}
		
		return $out;
	}

}