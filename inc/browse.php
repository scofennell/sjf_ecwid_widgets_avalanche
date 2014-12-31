<?php

/**
 * A class for sending parameters to refine the browsing of an ecwid collection.
 */

function sjf_et_browse_init() {
	new SJF_Ecwid_Browse();
}
add_action( 'admin_init', 'sjf_et_browse_init' );

class SJF_Ecwid_Browse {

	public function __construct() {

		// Redirect upon submit of the browse form to make form values sticky.
		add_action( 'init', self::redirect() );
	}

	/**
	 * The main template tag used to grab a browse form.
	 * 
	 * @return string An HTML form with inputs for refining an Ecwid collection.
	 */
	public static function get_browse_form() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Grab a nonce field for our form.
		$nonce = wp_nonce_field(
			self::get_nonce_action(),
			self::get_nonce_name(),
			TRUE,
			FALSE
		);

		// Get the current url with no browse args.
		$self = SJF_Ecwid_Admin_Helpers::get_clean_href();
		
		// The current value for the number of results per page.
		$limit = self::get_limit();
		
		// An input to set the number of results per page.
		$limit_input = self::get_limit_input( $limit );

		// The current value for sort.
		$sort = self::get_sort();

		// An input to set the value for sort.
		$sort_menu = self::get_sort_menu( $sort );
		
		// The current value for category.
		$category_id = self::get_category();

		// An input to set the value for category.
		$category_menu = self::get_category_menu( $category_id );

		// The current value for keyword.
		$keyword = self::get_keyword();

		// An input to set the value for keyword.
		$keyword_input = self::get_keyword_input( $keyword );

		// The submit button.
		$go = esc_html__( 'Go', 'sjf_et' );
		$submit_name = esc_attr( self::get_submit_name() );
		$submit = "<div class='$namespace-browse-form-input $namespace-browse-form-input-submit'><input type='submit' class='button button-primary action' name='$submit_name' value='$go'></div>";

		$self = SJF_Ecwid_Admin_Helpers::get_current_url();
		
		$out = "
			<form method='post' action='$self' class='wp-filter $namespace-browse-form'>
				$nonce
				$category_menu
				$keyword_input
				$limit_input
				$sort_menu
				$submit
			</form>
		";		

		return $out;

	}

	/**
	 * Get a menu for setting the sortBy param for an ecwid collection.
	 * 
	 * @param  string $sort The current value, used to make the form sticky.
	 * @return string       A menu for setting the sortBy param for an ecwid collection.
	 */
	public static function get_sort_menu( $sort ) {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		/**
		 * These are the sort options that ecwid ships with.
		 * At some point we may have to sniff for the current item type
		 * and throw the correct options for that item type.
		 */
		$options_array = array(
			'ADDED_TIME_DESC' => esc_html__( 'Date added, newest first', 'sjf_et' ),
			'ADDED_TIME_ASC'  => esc_html__( 'Date added, oldest first', 'sjf_et' ),
			'NAME_ASC'        => esc_html__( 'Name, A to Z', 'sjf_et' ),
			'NAME_DESC'       => esc_html__( 'Name, Z to A', 'sjf_et' ),
			'PRICE_ASC'       => esc_html__( 'Price, lowest first', 'sjf_et' ),
			'PRICE_DESC'      => esc_html__( 'Price, highest first', 'sjf_et' ),
		);			

		// Build the options array into html <options>.
		$options = '';
		foreach( $options_array as $k => $v ) {
			$selected = selected( $k, $sort, false );
			$options .= "<option $selected value='$k'>$v</option>";
		}

		$label = esc_html( 'Sort By', 'sjf_et' );

		// The name of the input.
		$sort_name = self::get_sort_name();
		
		$out = "
			<div class='$namespace-browse-form-input'>
				<label class='$namespace-browse-label'>
					<span class='dashicons dashicons-sort'></span>
					$label
				</label>
				<select name='$sort_name'>
					$options
				</select>
			</div>
		";
		return $out;
	}

	/**
	 * Get a menu for setting the value for category.
	 * @param  int $category The current value for category, used to make the field sticky.
	 * @return string A menu for setting the value for category.
	 */
	public static function get_category_menu( $category_id ) {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$out = '';

		$cats = SJF_Ecwid_Admin_Helpers::get_items( 'categories' );

		// Start a holder for each category, starting with an option for any.
		$any = esc_html__( 'Any', 'sjf_et' );
		$options = "<option value=''>$any</option>";
		
		if ( is_array( $cats ) ) {

			// For each category, create an html <option>.
			foreach ( $cats as $c ) {
			
				$id       = absint( $c['id'] );
				$selected = selected( $id, $category_id, false );
				$name     = esc_html( $c['name'] );

				$options .= "<option $selected value='$id'>$name</option>";
			}

		}

		$label = esc_html( 'Category', 'sjf_et' );

		// The name of the reqest var that holds the value for category.
		$category_name = self::get_category_name();
		
		$out = "
			<div class='$namespace-browse-form-input'>
				<label class='$namespace-browse-label'>
					<span class='dashicons dashicons-category'></span> 
					$label
				</label>
				<select name='$category_name'>
				$options
				</select>
			</div>
		";
		
		return $out;
	}

	/**
	 * Get an input for setting the number of results per page.
	 * 
	 * @param  int $limit The current value, used to make the form sticky.
	 * @return string An input for setting the number of results per page.
	 */
	public static function get_limit_input( $limit ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html( 'Per Page', 'sjf_et' );

		$limit_name = self::get_limit_name();

		$max = absint( SJF_Ecwid_Helpers::max_collection_count() );

		$out = "
			<div class='$namespace-browse-form-input'>
				<label class='$namespace-browse-label'>
					<span class='dashicons dashicons-plus'></span> 
					$label
				</label>
				<input type='number' min='1' max='$max' name='$limit_name' value='$limit'>
			</div>
		";

		return $out;

	}

	/**
	 * Get an input for setting the search keyword.
	 * 
	 * @param  string $keyword The current value for keyword, used to make the form sticky.
	 * @return string An input for setting the search keyword.
	 */
	public static function get_keyword_input( $keyword ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html( 'Keyword', 'sjf_et' );

		// The name of the request var that holds the value for keyword.
		$keyword_name = self::get_keyword_name();

		$placeholder = esc_attr__( 'Search', 'sjf_et' );

		$out = "
			<div class='$namespace-browse-form-input'>
				<label class='$namespace-browse-label'>
					<span class='dashicons dashicons-search'></span> 
					$label
				</label>
				<input type='search' name='$keyword_name' value='$keyword' placeholder='$placeholder'>
			</div>
		";

		return $out;

	}

	/**
	 * Listen for form submit and keep all the values in one place for easy looping-through.
	 * 
	 * @return Returns an array of values from the browse form.
	 */
	public static function handler() {
		
		$out = array();

		// Grab and format the value for sortBy.
		if( isset( $_REQUEST[ self::get_sort_name() ] ) ) {
			$sort = $_REQUEST[ self::get_sort_name() ];
			$out['sortBy']= SJF_Ecwid_Formatting::alphanum_underscore_hyphen( $sort );
		}

		// Grab and format the value for limit.
		if( isset( $_REQUEST[ self::get_limit_name() ] ) ) {
			$limit = $_REQUEST[ self::get_limit_name() ];
			$out['limit']= absint( $limit );
		}

		// Grab and format the value for category.
		if( isset( $_REQUEST[ self::get_category_name() ] ) ) {
			$category_id = $_REQUEST[ self::get_category_name() ];
			if ( ! empty( $category_id ) ) {
				$out['category']= absint( $category_id );
			}
		}

		// Grab and format the value for keyword.
		if( isset( $_REQUEST[ self::get_keyword_name() ] ) ) {
			$keyword = $_REQUEST[ self::get_keyword_name() ];
			$out['keyword']= sanitize_text_field( $keyword );
		} else {
			$out['keyword']= '';
		}

		return $out;

	}

	/**
	 * Listen for form submit, parse the values that were submit, and redir the page, using those values.
	 */
	public static function redirect() {
		
		// Listen for form submit.
		if( ! isset( $_POST[ self::get_submit_name() ] ) ) { return FALSE; }
		if( ! check_admin_referer( self::get_nonce_action(), self::get_nonce_name() ) ) { return FALSE; }

		// Grab the values that were submit.
		$values = self::handler();

		if( ! is_array( $values ) ) { return FALSE; }

		$self = SJF_Ecwid_Admin_Helpers::get_current_url();

		foreach( $values as $k => $v ) {
			$self = remove_query_arg( $k, $self );
			$self = add_query_arg( array( $k => $v ), $self );
		}

		wp_redirect( $self );

	}

	public static function get_limit() {

		$limit = 10;

		$handler = self::handler();
		if( isset( $handler['limit'] ) ) {
			$limit = $handler['limit'];
		}

		return $limit;

	}

	public static function get_sort() {

		$sort = 'RELEVANCE';

		$handler = self::handler();
		if( isset( $handler['sortBy'] ) ) {
			$sort = $handler['sortBy'];
		}

		return $sort;

	}

	public static function get_category() {

		$category = '';

		$handler = self::handler();
		if( isset( $handler['category'] ) ) {
			$category = $handler['category'];
		}

		return $category;

	}

	public static function get_keyword() {

		$keyword = '';

		$handler = self::handler();
		if( isset( $handler['keyword'] ) ) {
			$keyword = $handler['keyword'];
		}

		return $keyword;

	}

	public static function get_sort_name() {
		return 'sortBy';
	}

	public static function get_limit_name() {
		return 'limit';
	}

	public static function get_category_name() {
		return 'category';
	}

	public static function get_keyword_name() {
		return 'keyword';
	}

	public static function get_submit_name() {
		return '_' . SJF_Ecwid_Helpers::get_namespace() . '_browse_submit';
	}

	public static function get_nonce_action() {
		$namespace = SJF_Ecwid_Helpers::get_namespace();
		return '_' . $namespace . '_browse_action';
	}

	public static function get_nonce_name() {
		$namespace = SJF_Ecwid_Helpers::get_namespace();
		return '_' . $namespace . '_browse_name';
	}

}