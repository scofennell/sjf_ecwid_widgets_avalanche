<?php

/**
 * Establish a feed of all the products as JSON.
 *
 * Parse ecwid products into a JSON feed.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 1.5.4
 */

function sjf_et_json_feed_init() {
	new SJF_Ecwid_JSON_Feed();
}
add_action( 'plugins_loaded', 'sjf_et_json_feed_init' );

class SJF_Ecwid_JSON_Feed {

	/**
	 * The slug for our feed.
	 * 
	 * @var string
	 */
	public $feed_url = 'products.json';

	/**
	 * The number of posts for our feed.
	 * @var integer
	 */
	public $num_posts = -1;

	/**
	 * The sort method for our feed.
	 * @var string
	 */
	public $sort_by = 'ADDED_TIME_DESC';

	/**
	 * Add actions for our class.
	 */
	public function __construct() {

		// Set the content type for JSON.
		add_action( 'wp_headers', array( $this, 'headers' ), 999 );

		// We need to wait until init before we add a feed.
		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Register our feed with WordPress.
	 */
	public function init() {
		add_feed( $this -> feed_url, array( $this, 'the_json' ) );
	}

	/**
	 * Determine if we are viewing the products feed.
	 * 
	 * @return boolean If we are viewing the product feed, TRUE, else FALSE.
	 */
	public function is_feed() {

		// Just to be safe, make really sure we don't mess up the rest of the site with the wrong content type.
		if(
			is_404()            ||
			is_admin()          ||
			is_archive()        ||
			is_attachment()     ||
			is_author()         ||
			is_comments_popup() ||
			is_date()           ||
			is_front_page()     ||
			is_home()           ||
			is_page()           ||
			is_search()         ||
			is_single()         ||
			is_singular()       
		) {
			return FALSE;
		}

		// Grab the feed url.
		$feed_url = $this -> feed_url;

		// Grab the last portion of the current url.
		$url              = untrailingslashit( remove_query_arg( '' ) );
		$url_arr          = explode( '/', $url );
		$last_part_of_url = array_pop( $url_arr );

		// Watch our for fragments.
		$frags = explode( '#', $last_part_of_url );

		$last_part_of_url = $frags[0];

		// Compare the current url in either pretty or non-pretty permalink format.
		if( $last_part_of_url == $feed_url ) { return TRUE; }
		if( $last_part_of_url == "?feed=$feed_url" ) { return TRUE; }

		return FALSE;

	}

	/**
	 * If we are viewing the ecwid feed, set the headers for RSS content type.
	 */
	public function headers() {
	
		// Are we viewing the feed?
		if( ! $this -> is_feed() ) {
			return FALSE;
		}

		header( 'Content-Type: application/json', TRUE );

	}

	/**
	 * Get the products for our feed.
	 * 
	 * @return array An array of products.
	 */
	function get_products() {

		if( ! isset( $_GET['term'] ) ) { return FALSE; }

		$term = trim( wp_strip_all_tags( $_GET['term'], TRUE ) );

		// Args for our product query.
		$args = array(
			'keyword' => $term,
			'limit'   => $this -> num_posts,
			'sortBy'  => $this -> sort_by,
		);
		
		// Grab the products.
		$collection = new SJF_Ecwid_Collection( 'products', $args );
		$result = $collection -> get_collection();
		
		// Make sure the result isn't weird.
		if( ! is_array( $result ) ) { return FALSE; }
		if( ! isset( $result['items'] ) ) { return FALSE; }
		
		$items = $result['items'];
		return $items;

	}

	/**
	 * The main template tag for drawing our feed.
	 */
	public function the_json() {
		
		// Grab the products.
		$items = $this -> get_products();
		if( ! is_array( $items ) ) { return FALSE; }

		$out = '';

		// For each product...
		$out = array();
		foreach( $items as $item ) {

			// Grab the product title and url.
			$title = $item['name'];
			$url   = $item['url'];

			// The source for this product in the autosuggest.
			$out[]= array(
				'label' => wp_strip_all_tags( $title ),
				'value' => esc_attr( $url ),
			);

		}

		echo json_encode( $out );

	}

}