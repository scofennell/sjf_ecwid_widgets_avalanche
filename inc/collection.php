<?php

/**
 * Get a collection from Ecwid.
 *
 * Incorporates request params, passes them to Ecwid, returns the result.
 * Although we already have a wrapper class for calling ecwid, which this class uses,
 * this class expedites a certain type of ecwid call, which is to parse args for a collection of items,
 * such as products or categories, form the request, and format the response 
 *
 * @todo The request vars are hard coded here but instead they should be grabbed from the methods in browse.php.
 * @todo It's lame to rely on the REQUEST vars.  Should take args.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

class SJF_Ecwid_Collection {

	/**
	 * The route to which we are sending our request.  Could be 'products' or 'categories' or similar.
	 * @var string
	 */
	public $route;

	/**
     * Adds actions for our class methods and parses instantiation args.
     *
     * When this class is instantiated, it expects a value for route.
     */
    function __construct( $route ) {
        
        // Set the value for route.
        $this -> route = $route;

    }

    /**
     * Get a collection of items from ecwid.
     * 
     * @return mixed An array of items, or an error message.
     */
    function get_collection() {

    	$route = $this -> route;

    	// Our wrapper class for calling ecwid.
		$ecwid = new SJF_Ecwid;

		// Parse the arg for limiting the number of results.
		if( isset( $_REQUEST['limit'] ) ) {
			$limit = SJF_Ecwid_Formatting::alphanum_underscore_hyphen( $_REQUEST['limit'] );
			$route = add_query_arg( array( 'limit' => $limit ), $route );
		}

		// Parse the arg for ordering the results.
		$sortBy = 'ADDED_TIME_DESC';
		if( isset( $_REQUEST['sortBy'] ) ) {
			$sortBy = SJF_Ecwid_Formatting::alphanum_underscore_hyphen( $_REQUEST['sortBy'] );
		}
		$route = add_query_arg( array( 'sortBy' => $sortBy ), $route );

		// Parse the arg for limiting results to a category.
		$category = '';
		if( isset( $_REQUEST['category'] ) ) {
			$category = SJF_Ecwid_Formatting::alphanum_underscore_hyphen( $_REQUEST['category'] );
			if( ! empty( $category ) ) {
				$route = add_query_arg( array( 'category' => $category ), $route );
			}
		}

		// Parse the arg for limiting results to those that contain a given keyword.
		$keyword = '';
		if( isset( $_REQUEST['keyword'] ) ) {
			$keyword = rawurldecode( $_REQUEST['keyword'] );
			if( ! empty( $keyword ) ) {
				$route = add_query_arg( array( 'keyword' => $keyword ), $route );
			}
		}

		// Parse the arg for offset (for pagination).
		$offset = SJF_Ecwid_Pagination::get_offset();
		$route = add_query_arg( array( 'offset' => $offset ), $route );

		$result = $ecwid -> call( $route );

		$out = json_decode( $result['body'], TRUE );

		return $out;

	}

}
