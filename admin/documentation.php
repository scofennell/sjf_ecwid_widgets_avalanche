<?php

/**
 * Register documentation class for our plugin.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 1.4
 */

Class SJF_Ecwid_Admin_Documentation {

	function __construct() {
		add_filter( __CLASS__ . '_' . 'get_docs', array( $this, 'get_shortcode_info' ), 10 );
	}

	/**
	 * Template tag (of sorts) to display doc info.
	 */
	function get_docs() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		$out = '';
	
		$out = "<div class='$base_class'>";

		$in = '';

		$out .= apply_filters( __CLASS__ . '_' . __FUNCTION__, $in );

		$out .= "</div>";

		return $out;

	}

	/**
	 * Wrap a doc section in expected HTML for accordion goodness.
	 * 
	 * @param  string $label   The label for this block.
	 * @param  string $slug    The slug for this block, used in css classes and the like.
	 * @param  mixed $content  A string, array, or object of doc data.
	 * @return string          A wrapped doc section.
	 */
	function get_doc( $label, $slug, $content ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_' . __FUNCTION__ );

		// This is unused for now but it's helpful for debugging the debugging.
		$slug = sanitize_html_class( $slug );

		// A toggle link to reveal this section.
		$toggle = SJF_Ecwid_Helpers::get_toggle();

		// The label for this section.
		$label = "<h4 class='$base_class-label'>$label</h4>";

		$content = "<div class='$base_class-accordion $namespace" . "_accordion'>$content</div>";

		$out = "
			<div class='$base_class $namespace-toggle-parent'>
				$label
				$toggle
				$content
			</div>
		";
		
		return $out;

	}

	/**
	 * Get info about the shortcodes.
	 * 
	 * @return string Info about the shotcodes.
	 */
	function get_shortcode_info( $in ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'Shortcode Basics', 'sjf-et' );
		
		$content_1 = '<p>' . esc_html__( 'All of the widgets that ship with this plugin have a correspondong shortcode version that is very similar in functionality.', 'sjf-et') . '</p>'; 
		$content_2 = '<p>' . sprintf( esc_html__( 'The shortcoes all start with the prefix %s, because that is the namespace for this plugin.' ), '<code>sjf_et</code>' ) . '</p>';
		$content_3 = '<p>' . esc_html__( 'Several of the shortcodes feature options for specific product or category ID numbers, or multiple ID numbers.  Shortcodes that accept multiple ID numbers always require them to be a comma-seperated list.', 'sjf-et') . '</p>'; 
		
		$content = $content_1 . $content_2 . $content_3;

		$out = $this -> get_doc( $label, __FUNCTION__, $content );

		return $in . $out;

	}

}