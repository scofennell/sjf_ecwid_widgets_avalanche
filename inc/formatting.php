<?php

function sjf_et_formatting_init() {
	new SJF_Ecwid_Formatting();
}
add_action( 'init', 'sjf_et_formatting_init' );
add_action( 'admin_init', 'sjf_et_formatting_init' );

class SJF_Ecwid_Formatting {

	public static function alphanum( $string ) {
		return preg_replace( '/[^a-zA-Z0-9-]/', '', $string );
	}

	public static function alphanum_underscore_hyphen( $string ) {
		return preg_replace( '/[^a-zA-Z0-9-_]/', '', $string );
	}

	public static function array_dig( $v, $k = null, $title = '' ) {
		
		$namespace   = SJF_Ecwid_Helpers::get_namespace();
		$value_class = $namespace . '-value';
		$key_class   = $namespace . '-key';
		$array_class = $namespace . '-array';

		$out = '';

		if( ! empty ( $title ) ) {
			$out .= "<h2>$title</h2>";
		}

	    if ( ! is_array( $v ) ) {
			
			$v = stripslashes( $v );
			
			$v = self::get_value_as_image_maybe( $v );
	        
	        $out .= "<div class='$value_class'>";
	        $out .= "$v";
   	        $out .= "</div>";

	    } else {

	    	foreach ( $v as $k => $v ) {
	        	$out .= "<div class='$array_class'>";
	        	$out .= "<h3 class='$key_class'>$k</h3>";
	        	$out .= self::array_dig( $v, $k );
	        	$out .= "</div>";
	    	}
	    }
	

    	return $out;
	}

	public static function get_value_as_image_maybe( $v ) {
		
		if( $v != @esc_url( $v ) ) { return $v; }

		$head = wp_remote_head( $v );
		
		if( ! is_array( $head ) ) { return $v; }
		
		if( ! isset( $head['headers'] ) ) { return $v; }

		if( ! isset( $head['headers']['content-type'] ) ) { return $v; }

		$content_type = $head['headers']['content-type'];
		
		if( ! stristr( $content_type, 'image' ) ) {  return $v; }

		return "<img src='$v'>";

	}

}