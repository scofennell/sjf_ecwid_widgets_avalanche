<?php

function sjf_et_admin_helpers_init() {
	new SJF_Ecwid_Admin_Helpers();
}
add_action( 'init', 'sjf_et_admin_helpers_init' );
add_action( 'admin_init', 'sjf_et_admin_helpers_init' );

class SJF_Ecwid_Admin_Helpers {
	
	/**
     * Adds actions for our class methods.
     */
    function __construct() {
            
		// add_action( 'admin_init', array( $this, 'handler' ) );

    }

	public static function get_token() {
		return 'BeQG4WeaHZAdYi758DBzErsiYkkkGaPD';
	}

	public static function get_client_id() {
		return 'AkKF4tAF8UrPMWsr';
	}

	public static function get_client_secret() {
		return 'YJNkJiywU2yfYBEBqE3vKMbar3XjzaL8';
	}

	public static function get_api_endpoint() {
		return 'https://app.ecwid.com/api/v3/';
	}

	/**
	 * The url to which we post in hopes of getting an auth token.
	 * @return string The url to which we post in hopes of getting an auth token.
	 */
	public static function get_token_url() {
		return 'https://my.ecwid.com/api/oauth/token';
	}

	public static function get_ecwid_cp_url( $id ) {
		$item_type = urlencode( $_GET['page'] );
		$id = absint( $id );
		return "https://my.ecwid.com/cp/CP.html#$item_type:mode=edit&id=$id";
	}

	/**
	 * The url to which we send the user in hopes that they will authorize our app.
	 * @return string The url to which we send the user in hopes that they will authorize our app.
	 */
	public static function get_authorization_url() {
		return 'https://app.ecwid.com/api/oauth/authorize';
	}

	public static function get_dashicon_slug() {
		return 'cart';
	}

	public static function get_dashicon_class() {
		$slug = self::get_dashicon_slug();
		return "dashicons-$slug";
	}

	public static function get_dashicon() {
		$class = self::get_dashicon_class();
		return "<span class='dashicons $class'></span>";
	}

	public static function get_arrow() {
		return "<span class='dashicons dashicons-arrow-down-alt'></span>";
	}

	public static function get_toggle_button( $label ) {
		$namespace = SJF_Ecwid_Helpers::get_namespace();
		$arrow = self::get_arrow();
		$label = esc_html( $label );
		
		return "<a class='$namespace-toggle' href='#$namespace-extra-info'>$label $arrow</a>";
	}

	public static function get_crud_links( $item, $item_type = FALSE, $classes = array() ) {

		if( is_int( $item ) ) {
			$item = self::get_item_fields( $item, $item_type );
		}

		$out = '';

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$id   = absint( $item['id'] );
		$name = esc_html( $item['name'] );
		$url  = esc_url( $item['url'] );

		$current_url_sans_args = self::remove_feedback_args();
		$current_url_sans_args = self::remove_crud_args( $current_url_sans_args );
		$current_url_sans_args = self::remove_nonce_args( $current_url_sans_args );

		$links = array(
			'details' => array(
				'href'  => add_query_arg( array( 'id' => $id ), $current_url_sans_args ),
				'label' => esc_html__( 'Details', 'sjf_et' ),
			),
			'edit' => array(
				'href'  => add_query_arg( array( 'id' => $id, 'action' => 'update' ), $current_url_sans_args ),
				'label' => esc_html__( 'Edit', 'sjf_et' ),
			),
			'in_store' => array(
				'href'     => esc_url( $url ),
				'label'    => esc_html__( 'In Store', 'sjf_et' ),
				'external' => true,
			),
			'in_ecwid' => array(
				'href'     => self::get_ecwid_cp_url( $id ),
				'label'    => esc_html__( 'In Ecwid', 'sjf_et' ),
				'external' => true,
			),
			'delete' => array(
				'href'          => add_query_arg( array( 'id' => $id, 'action' => 'delete' ), $current_url_sans_args ),
				'label'         => esc_html__( 'Delete', 'sjf_et' ),
				'class'         => 'delete',
			),
		);

		$count = count( $links );

		$current_url = self::get_current_url();

		$i = 0;
		foreach( $links as $key => $fields ) {
			$i++;
			$href = self::remove_browse_args( $fields['href'] );
			$label = $fields['label'];
			
			$class = '';
			if( isset( $fields['class'] ) ) {
				$class = sanitize_html_class( $fields['class'] );
			}

			$external = '';
			$target = '';
			if( isset( $fields['external'] ) ) {
				$external = "<span class='dashicons dashicons-admin-links'></span>";
				$target = " target='_blank' ";
			}

			if( self::compare_urls( $href, $current_url ) ) {
				$out .= "<span class='$namespace-crud-inactive $class'>$label</span>";
			} else {
				$out .= "<a $target class='$namespace-crud-link $namespace-crud-link-$class' href='$href'>$label $external</a>";
			}

			if( $i < $count ) { $out .= " | "; }
		}

		if( ! empty( $out ) ) {

			$classes = array_map( 'sanitize_html_class', $classes );
			$classes = implode( ' ', $classes );

			$out = "<nav class='$classes $namespace-crud-links' >$out</nav>";
		}

		return $out;

	}

	public static function remove_browse_args( $url = FALSE ) {
		return remove_query_arg( array( 'paged', 'limit', 'sortBy' ), $url );
	}

	public static function remove_crud_args( $url = FALSE ) {
		return remove_query_arg( array( 'action', 'id', 'confirm' ), $url );
	}

	public static function remove_feedback_args( $url = FALSE ) {
		return remove_query_arg( array( 'confirmed', 'success' ), $url );
	}

	public static function remove_nonce_args( $url = FALSE ) {

		foreach ( $_GET as $k => $v ) {
			if( stristr( $k, 'nonce' ) ) {
				$url = remove_query_arg( array( $k ), $url );
			}
		}

		return $url;
	}

	public static function get_clean_href( $url = FALSE ) {
		$url = self::remove_nonce_args( $url );
		$url = self::remove_crud_args( $url );
		$url = self::remove_browse_args( $url );
		$url = self::remove_feedback_args( $url );
		return $url;
	}

	public static function get_item_title( $item_id, $item_type ) {
		return self::get_item_field( $item_id, $item_type, 'name' );
	}

	public static function get_item_fields( $item_id, $item_type ) {
		$ecwid = new SJF_Ecwid;
		$data = $ecwid -> call( "$item_type/$item_id" );
		$body = json_decode( $data['body'], TRUE );
		return $body;
	}

	public static function get_item_field( $item_id, $item_type, $field_name ) {
		$fields = self::get_item_fields( $item_id, $item_type );
		return $fields[ $field_name ];
	}

	public static function get_back_to_browse_link() {
		$back_href  = self::get_clean_href();
		$back_label = esc_html__( 'Back to Browse', 'sjf_et' );
		$back_link  = "<a href='$back_href' class='add-new-h2'>$back_label</a>";
		return $back_link;
	}

	public static function get_add_new_link( $item_type ) {
		$href = self::get_clean_href();
		$new_href  = add_query_arg( 'action', 'create', $href );
		$new_label = sprintf( esc_html__( 'Add New %s', 'sjf_et' ), ucfirst( $_GET['page'] ) );
		$new_link  = "<a href='$new_href' class='add-new-h2'>$new_label</a>";
		return $new_link;
	}

	public static function compare_urls( $a, $b ) {
		$a_args = parse_url( $a, PHP_URL_QUERY );
		parse_str( $a_args, $a_array );
		ksort( $a_array );
		$a_count = count( $a_array );
		$a_string = implode( '&', $a_array );

		$b_args = parse_url( $b, PHP_URL_QUERY );
		parse_str( $b_args, $b_array );
		ksort( $b_array );
		$b_count = count( $b_array );
		$b_string = implode( '&', $b_array );

		if( $a_string == $b_string ) {
			return TRUE;
		}

		return FALSE;

	}

	public static function get_current_url() {
		$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
		$url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
		$url .= $_SERVER["REQUEST_URI"];
		return $url;
	}

}