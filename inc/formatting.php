<?php

/**
 * Functions for cleaning and formatting strings.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

class SJF_Ecwid_Formatting {

	/**
	 * Strip all non-alphanum chars from a string.
	 * 
	 * @param  string $string A string, unclean.
	 * @return string The string, with non-alphanum stripped.
	 */
	public static function alphanum( $string ) {
		return preg_replace( '/[^a-zA-Z0-9-]/', '', $string );
	}

	/**
	 * Strip all non-alphanum & underscore & hyphen chars from a string.
	 * 
	 * @param  string $string A string, unclean.
	 * @return string The string, with non-alphanum  & underscore & hyphen stripped.
	 */
	public static function alphanum_underscore_hyphen( $string ) {
		return preg_replace( '/[^a-zA-Z0-9-_]/', '', $string );
	}

	/**
	 * Grab the first x words from a string, sans HTML.
	 * 
	 * @param  string $string Any block of text.
	 * @param  int $num_words The number of words to return.
	 * @return string The first $num_words words from $string.
	 */
	public static function get_words( $string, $num_words ) {
		
		// HTML is a pain when it comes to this stuff.  Let's just strip it.
		$string = strip_tags( $string );

		// Grab the first x words.
		$array = explode( ' ', $string );
		$count = count( $array );
		$words = array_slice( $array, 0, $num_words );
		$out   = implode( ' ', $words );

		// Append an ellipse if we exceeded the $num_words.
		if( ! empty( $out ) ) {
			if( $count > $num_words ) {
				$out .= '&hellip;';
			}
		}

		return $out;

	}

}