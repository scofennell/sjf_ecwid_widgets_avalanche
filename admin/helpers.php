<?php

/**
 * Static methods for grabbing common values for wp-admin.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */


class SJF_Ecwid_Admin_Helpers {

	/**
	 * Get the url to our settings page.
	 *
	 * @return  string The url to our settings page.
	 */
	public static function get_main_menu_url() {
		
		$slug = self::get_menu_slug();

		$proto = 'http';
		if( is_ssl() ) { $proto = 'https'; }
		
		return admin_url( "admin.php?page=$slug", $proto );

	}

	/**
	 * The url to a page in the ecwid.com control panel.
	 * 
	 * @return string The url to a page in the ecwid.com control panel.
	 */
	public static function get_ecwid_cp_url( $store_id, $item_type = '' ) {
		
		// If we are on the products page in our plugin, we'd want the user to go to the products page in ecwid.
		$item_type = urlencode( $item_type );
		$store_id = absint( $store_id );
		return "https://my.ecwid.com/cp/CP.html#$item_type:mode=edit&id=$store_id";
	}

	/**
	 * Get the slug for the main menu for our plugin.
	 * 
	 * @return string The slug for the main menu for our plugin.
	 */
	public static function get_menu_slug() {
		return SJF_Ecwid_Helpers::get_namespace();
	}

	public static function get_current_url( $include_request_uri = TRUE ) {
		$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
		$url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
		
		if( $include_request_uri ) {
			$url .= $_SERVER["REQUEST_URI"];
		}
		
		return $url;
	}

	/**
	 * Get categories as HTML checkbox inputs.
	 * 
	 * @param  string $route The route to the collection.
	 * @param  array $which_members An array of member ID's to power checked().
	 * @param  string The name of the checkbox group.
	 * @param  boolean Offer a value to react to the current ecwid store category as the user navigates his store.
	 * @return string Products as HTML checkbox inputs.
	 */
	public static function get_collection_as_checkboxes( $route, $which_members, $name, $offer_current ) {
		
		$out = '';

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// Get all products.
		$collection = new SJF_Ecwid_Collection( $route );
		$result = $collection -> get_collection();
		if( ! isset( $result['items'] ) ) {
			return FALSE;
		}
		$items = $result['items'];
		if( ! is_array( $items ) ) {
			return FALSE;
		}

		$route_class = sanitize_html_class( $route );

		// Do we want to allow the user to choose to track the current category?
		if( $offer_current ) {

			// The input name for this checkbox.
			$this_name = $name . "[current]";

			$checked = '';
			if( isset( $which_members['current'] ) ) {
				$checked = checked( $which_members['current'], 1, FALSE );
			}
			$title = esc_html__( '(The current category)', 'sjf-et' );
			$out .= "
				<li class='$namespace-checkbox-$route_class'>
					<label>
						<input $checked name='$this_name' value='1' type='checkbox'>
						<strong>$title</strong>
					</label>
				</li>
			";
		}

		// For each product...
		foreach( $items as $item ) {

			$title = esc_html( $item['name'] );
			$id    = esc_attr( $item['id'] );

			// The input name for this checkbox.
			$this_name = $name . "[$id]";

			// Determine if this checkbox should be pre-checked.
			$checked = '';
			if( isset( $which_members[ $id ] ) ) {
				$checked = checked( $which_members[ $id ], 1, FALSE );
			}

			// Wrap each input in a label and a list item.
			$out .= "
				<li class='$namespace-checkbox-$route_class'>
					<label>
						<input $checked name='$this_name' value='1' type='checkbox'>
						$title
					</label>
				</li>
			";

		}

		// If there were products, wrap them in a list.
		if( ! empty( $out ) ) {
			$out = "<ul class='$namespace-checkboxes-$route_class'>$out</ul>";
		}

		return $out;
	}

}