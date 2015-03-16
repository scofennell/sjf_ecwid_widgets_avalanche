<?php

/**
 * Get a collection from Ecwid.
 *
 * Incorporates request params, passes them to Ecwid, returns the result.
 * Although we already have a wrapper class for calling ecwid, which this class uses,
 * this class expedites a certain type of ecwid call, which is to parse args for a collection of items,
 * such as products or categories, form the request, and format the response 
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

	public $args = array();

	/**
     * Adds actions for our class methods and parses instantiation args.
     *
     * When this class is instantiated, it expects a value for route.
     */
    function __construct( $route, $args = array() ) {
        
        // Set the value for route.
        $this -> route = $route;

        // Set the args for our request.
        $this -> args = $args;

    }

    /**
     * Get a collection of items from ecwid.
     * 
     * @return mixed An array of items, or an error message.
     */
    function get_collection() {

    	$route = $this -> route;
		$args = $this -> args;

    	// Our wrapper class for calling ecwid.
		$ecwid = new SJF_Ecwid;

		// Parse the arg for limiting the number of results.
		if( isset( $args['limit'] ) ) {
			$limit = SJF_Ecwid_Formatting::alphanum_underscore_hyphen( $args['limit'] );
			$route = add_query_arg( array( 'limit' => $limit ), $route );
		}

		// Parse the arg for ordering the results.
		$sortBy = 'ADDED_TIME_DESC';
		if( isset( $args['sortBy'] ) ) {
			$sortBy = SJF_Ecwid_Formatting::alphanum_underscore_hyphen( $args['sortBy'] );
		}
		$route = add_query_arg( array( 'sortBy' => $sortBy ), $route );

		// Parse the arg for limiting results to a category.
		$category = '';
		if( isset( $args['category'] ) ) {
			$category = SJF_Ecwid_Formatting::alphanum_underscore_hyphen( $args['category'] );
			if( ! empty( $category ) ) {
				$route = add_query_arg( array( 'category' => $category ), $route );
			}
		}

		// Parse the arg for limiting results to those that contain a given keyword.
		$keyword = '';
		if( isset( $args['keyword'] ) ) {
			$keyword = rawurldecode( $args['keyword'] );
			if( ! empty( $keyword ) ) {
				$route = add_query_arg( array( 'keyword' => $keyword ), $route );
			}
		}

		// Parse the arg for limiting results to those from a parent ID.  Allow '0' to be passed, since it means all root items.
		$parent = '';
		if( isset( $args['parent'] ) ) {
			$parent = rawurldecode( $args['parent'] );
			$route = add_query_arg( array( 'parent' => $parent ), $route );
		}

		// Parse the arg for including subCategories.  Expects Boolean.
		$with_subcategories = FALSE;
		if( isset( $args['with_subcategories'] ) ) {
			$with_subcategories = rawurldecode( $args['with_subcategories'] );
			if( ! empty( $with_subcategories ) ) {
				$route = add_query_arg( array( 'withSubcategories' => $with_subcategories ), $route );
			}
		}

		$result = $ecwid -> call( $route );

		if( is_wp_error( $result ) ) {
			$error = new SJF_Ecwid_Errors;
			return $error -> get_error_message( $result );
		}

		$out = json_decode( $result['body'], TRUE );

		return $out;

	}

}