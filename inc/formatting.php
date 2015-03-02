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
	 * Return an html class for a given string.
	 * 
	 * @param  string $string Some string to convert into a class name.
	 * @return string An html class for a given string.
	 */
	public static function get_class_name( $string ) {
		return strtolower( sanitize_html_class( $string ) );
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

	/**
	 * Given a monetary value, convert it to a formatted version for the current locale and store currency.
	 * 
	 * @param  float  $money A monetary value.
	 * @return string The input float, formatted.
	 */
	public static function get_money( $money ) {

		// Grabs the store profile from our local cache.
		$profile = SJF_Ecwid_Helpers::get_store_profile();

		// Grab the formats and units part of the store profile.
		$fau = $profile['formatsAndUnits'];

		// Ask WordPress for the blog locale -- though I feel like I should be getting this from the store profile somewhere.
		$locale   = get_locale();
		
		// The currency for the store.
		$currency = $fau['currency'];
		
		// Create the format for this locale.
		$fmt   = numfmt_create( $locale, NumberFormatter::CURRENCY );
		
		// Convert the input value.
		$money =  esc_html( numfmt_format_currency( $fmt, $money, $currency ) );

		return $money;

	}

	/**
	 * Given a weight, return it with the unit indicator.
	 * 
	 * @param  float $weight A decimal weight.
	 * @return string The weight with a unit label.
	 */
	public static function get_weight( $weight ) {

		// Grabs the store profile from our local cache.
		$profile = SJF_Ecwid_Helpers::get_store_profile();

		// Grab the formats and units part of the store profile.
		$fau = $profile['formatsAndUnits'];

		// Grab the weight unit.
		$weight_unit = $fau['weightUnit'];
		
		// Append the weight unit to the weight.
		$weight .= " $weight_unit";

		return $weight;

	}

}