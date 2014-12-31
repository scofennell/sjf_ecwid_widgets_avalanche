<?php

/**
 * Register admin notifications for our plugin.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_admin_notices_init() {
	new SJF_Ecwid_Admin_Notices();
}
add_action( 'init', 'sjf_et_admin_notices_init' );

Class SJF_Ecwid_Admin_Notices {

	function __construct() {
		add_action( 'admin_notices', array( $this, 'the_notices' ) );
	}

	function the_notices() {

		do_action( 'sjf_et_admin_notices' );

	}

	public static function the_notice( $title = '', $content = '', $classes = array() ) {
	
		$namespace = SJF_Ecwid_Helpers::get_namespace();
	
		$title   = "<h3 class='$namespace-admin-notice-title'>$title</h3>";
		$content = "<div class='$namespace-admin-notice-content'>$content</div>";
	
		$classes = array_map( 'sanitize_html_class', $classes );
		$classes = implode( ' ', $classes );

		$out = "
			<div class='$namespace-admin-notice error fade $classes'>
				$title
				$content
			</div>
		";

		echo $out;

	}

}