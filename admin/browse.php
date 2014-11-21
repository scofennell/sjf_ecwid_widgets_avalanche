<?php

/**
 * A class for sending parameters when browsing an ecwid collection.
 */

function sjf_et_browse_init() {
	new SJF_Ecwid_Browse();
}
add_action( 'admin_init', 'sjf_et_browse_init' );

class SJF_Ecwid_Browse {

	public function __construct() {
		add_action( 'init', self::redirect() );
	}

	public static function get_browse_form() {

		$nonce = wp_nonce_field(
			self::get_nonce_action(),
			self::get_nonce_name(),
			TRUE,
			FALSE
		);

		$self = SJF_Ecwid_Admin_Helpers::get_clean_href();
		$limit = self::get_limit();
		
		$limit_input = self::get_limit_input( $limit );

		$sort = self::get_sort();
		$sort_menu = self::get_sort_menu( $sort );
		
		$go = esc_html__( 'Go', 'sjf_et' );
		$submit_name = esc_attr( self::get_submit_name() );
		
		$self = add_query_arg( array( 'limit' => $limit ), $self );
		$self = add_query_arg( array( 'sortBy' => $sort ), $self );

		$out = "
			<form method='post' action='$self' class='wp-filter'>
				$nonce
				$sort_menu
				$limit_input
				<p><input type='submit' class='button action' name='$submit_name' value='$go'></p>
			</form>
		";		

		return $out;

	}

	public static function get_sort_menu( $sort ) {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$options_array = array(
			'ADDED_TIME_DESC' => esc_html__( 'Date added, newest first', 'sjf_et' ),
			//'ADDED_TIME_ASC'  => esc_html__( 'Date added, oldest first', 'sjf_et' ),
			'NAME_ASC'        => esc_html__( 'Name, A to Z', 'sjf_et' ),
			'NAME_DESC'       => esc_html__( 'Name, Z to A', 'sjf_et' ),
			'PRICE_ASC'       => esc_html__( 'Price, lowest first', 'sjf_et' ),
			'PRICE_DESC'       => esc_html__( 'Price, highest first', 'sjf_et' ),
		);			

		$options = '';
		foreach( $options_array as $k => $v ) {
			$selected = selected( $k, $sort, false );
			$options .= "<option $selected value='$k'>$v</option>";
		}

		$label = esc_html( 'Sort By', 'sjf_et' );

		$sort_name = self::get_sort_name();
		
		$out = "<p><label class='$namespace-browse-label'>$label</label><select name='$sort_name'>$options</select></p>";
		return $out;
	}

	public static function get_limit_input( $limit ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html( 'Results per page', 'sjf_et' );

		$limit_name = self::get_limit_name();

		$out = "<p><label class='$namespace-browse-label'>$label</label><input type='number' min='1' max='1000' name='$limit_name' value='$limit'></p>";

		return $out;

	}

	public static function handler() {
		
		$out = array();

		if( isset($_REQUEST[ self::get_sort_name() ]) ) {
			$sort = $_REQUEST[ self::get_sort_name() ];
			$out ['sortBy']= $sort;
		}

		if( isset($_REQUEST[ self::get_limit_name() ]) ) {
			$limit = $_REQUEST[ self::get_limit_name() ];
			$out ['limit']= $limit;
		}

		return $out;

	}

	public static function redirect() {
		
		if( ! isset( $_POST[self::get_submit_name()] ) ) { return false; }

		if( ! check_admin_referer( self::get_nonce_action(), self::get_nonce_name() ) ) { return false; }

		$values = self::handler();

		if( ! is_array( $values ) ) { return false; }

		$host = esc_url( $_SERVER['HTTP_HOST'] );
		$self = $host . $_SERVER['REQUEST_URI'];

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

	public static function get_sort_name() {
		return 'sortBy';
	}

	public static function get_limit_name() {
		return 'limit';
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