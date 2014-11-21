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

	public static function get_pagination( $body ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$links = self::get_pagination_links( $body );

		$item_count = self::get_item_count( $body['total'], $body['count'] );

		$out = "
			<div class='$namespace-pagination tablenav'>
				$item_count $links
			</div>
		";		

		return $out;

	}

	public static function get_pagination_links( $body ) {
	
		$out = '';

		$namespace  = SJF_Ecwid_Helpers::get_namespace();
		$paged_name = self::get_paged_name();

 		$total  = absint( $body['total'] );
 		$count  = absint( $body['count'] );
 		$offset = absint( $body['offset'] );
  		$limit  = absint( $body['limit'] );

  		if( empty( $total ) ) {
  			return false;
  		}

  		$num_pages = ceil( $total / $limit );

  		$base = remove_query_arg( $paged_name );

  		$current = 1;
  		if ( isset( $_GET[ $paged_name ] ) ) {
  			$current = absint( $_GET[ $paged_name ] );
		}

  		$args = array(
			'base'         => str_replace( $num_pages, '%#%', esc_url( get_pagenum_link( $num_pages ) ) ),
			'format'       => "?$paged_name=%#%",
			'total'        => $num_pages,
			'current'      => $current,
			'show_all'     => False,
			'end_size'     => 3,
			'mid_size'     => 2,
			'prev_next'    => FALSE,
			'type'         => 'plain',
			'add_args'     => TRUE,
			'add_fragment' => '',
			'before_page_number' => '',
			'after_page_number' => ''
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

	public static function get_offset() {

		$paged = 1;
		if( isset( $_GET[self::get_paged_name()] ) ) {
			$paged = $_GET[self::get_paged_name()];
		}

		$limit = SJF_Ecwid_Browse::get_limit();

		$out = ( ( $limit * $paged ) - $limit );

		return $out;
	}

	public static function get_paged_name() {
		return 'paged';
	}

	public static function get_item_count( $count, $limit ) {

		$offset = self::get_offset();
		$end = $offset + $limit;


		$items_label = sprintf( esc_html__( 'Showing %s - %s of %s items', 'sjf_et' ), $offset, $end, $count );
		$out = "<span class='displaying-num'>$items_label</span>";
		return $out;

	}

}