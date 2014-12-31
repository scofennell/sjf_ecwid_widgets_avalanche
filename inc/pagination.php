<?php

/**
 * A class for paginating collections.
 */

function sjf_et_pagination_init() {
	new SJF_Ecwid_Pagination();
}
add_action( 'admin_init', 'sjf_et_pagination_init' );

class SJF_Ecwid_Pagination {

	public function __construct() {
		//add_action( 'init', self::handler() );
	}

	/**
	 * The main template tag we would use to grab a pagination module.
	 * 
	 * @param  array $body The result of an ecwid call, containing the total, count, offset, and limit.
	 * @return string HTML links to each pagination page.
	 */
	public static function get_pagination( $body ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// The links to each paginated page.
		$links = self::get_pagination_links( $body );

		// A label displaying "Page x of y".
		$item_count = self::get_item_count( $body['total'], $body['count'] );

		$out = "
			<div class='$namespace-pagination tablenav'>
				$item_count $links
			</div>
		";		

		return $out;

	}

	/**
	 * A wrapper for the WP function, paginate_links().
	 * 
	 * @param  array $body The result of an ecwid call, containing the total, count, offset, and limit.
	 * @return string HTML links to each pagination page.
	 */
	public static function get_pagination_links( $body ) {
	
		$out = '';

		$namespace  = SJF_Ecwid_Helpers::get_namespace();
		
		// The url var that tells us what page we are currently on.
		$paged_name = self::get_paged_name();

		// The result of an ecwid call.
 		$total  = absint( $body['total'] );
 		$count  = absint( $body['count'] );
 		$offset = absint( $body['offset'] );
  		$limit  = absint( $body['limit'] );

  		// If there were no items returned, bail.
  		if( empty( $total ) ) {
  			return false;
  		}

  		// Calculate the number of pages, rounding up.
  		$num_pages = ceil( $total / $limit );

  		// Grab the current url, sans pagination args.
  		$base = remove_query_arg( $paged_name );

  		// Determine the number of the page we are on right now.
  		$current = self::get_current_page();

  		// Build args for a call to paginate_links().  I don't really understand this function.
  		$args = array(
			'base'               => str_replace( $num_pages, '%#%', get_pagenum_link( $num_pages ) ),
			'format'             => "?$paged_name=%#%",
			'total'              => $num_pages,
			'current'            => $current,
			'show_all'           => False,
			'end_size'           => 3,
			'mid_size'           => 2,
			'prev_next'          => FALSE,
			'type'               => 'plain',
			'add_args'           => TRUE,
			'add_fragment'       => '',
			'before_page_number' => '',
			'after_page_number'  => ''
		);

  		$out = paginate_links( $args );

  		/*
		$i = 0;
		while( $i < $num_pages ) {
			$i++;
			$out .= self::get_pagination_link( $i );
		}
		*/
	
		if( ! empty( $out ) ) {
			$out = "<nav class='$namespace-pagination-links'>$out</nav>";
		}

		return $out;

	}

	/*
	public static function get_pagination_link( $i ) {

		$paged_name = self::get_paged_name();

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$href = remove_query_arg( $paged_name );
		$href = add_query_arg( array( $paged_name => $i ), $href );
		
		$label = esc_html( number_format_i18n( $i ) );

		$current = "$namespace-pagination-link-current";
		$maybe_current = '';
		if( ! isset( $_GET[ $paged_name ] ) && $i == 1 ) {
			$maybe_current = $current;
		} elseif( isset( $_GET[ $paged_name ] ) ) {
			if( $_GET[ $paged_name ] == $i ) {
				$maybe_current = $current;
			}
		} 

		$out = "<a class='$namespace-pagination-link $maybe_current' href='$href'>$label</a>";

		return $out;

	}
	*/

	/**
	 * Get the name of the url var that tells us the current page number.
	 * 
	 * @return string The name of the url var that tells us the current page number.
	 */
	public static function get_paged_name() {
		return 'paged';
	}

	/**
	 * Get the value of the url var that tells us the current page number.
	 * 
	 * @return int The value of the url var that tells us the current page number.
	 */
	public static function get_current_page() {
		$paged = 1;
		if( isset( $_GET[ self::get_paged_name() ] ) ) {
			$paged = $_GET[ self::get_paged_name() ];
		}
		return absint( $paged );
	}

	/**
	 * Get the number of items to skip, from the beginning of a collection, when browsing one page of a collection.
	 * 
	 * @return int The number of items to skip.
	 */
	public static function get_offset() {

		$paged = self::get_current_page();

		$limit = SJF_Ecwid_Browse::get_limit();

		// Multiply the current page by the # per page, then subtract the number per page.
		$out = ( ( $limit * $paged ) - $limit );

		return $out;
	}

	/**
	 * Get a label telling us, "Showing x - y of z items".
	 * 
	 * @param  int $count The total number of items in a set.
	 * @param  int $limit The number of items per page in a set.
	 * @return string A label telling us, "Showing x - y of z items".
	 */
	public static function get_item_count( $count, $limit ) {

		// The number of items to skip when showing one page of a set.
		$offset = self::get_offset();
		
		// The upper limit of the number of items being viewed.
		$end = $offset + $limit;

		$items_label = sprintf( esc_html__( 'Showing %s - %s of %s items', 'sjf_et' ), $offset, $end, $count );
		$out = "<span class='displaying-num'>$items_label</span>";
		return $out;

	}

}