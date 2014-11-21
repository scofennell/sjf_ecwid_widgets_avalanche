<?php

function sjf_et_helpers_init() {
	new SJF_Ecwid_Helpers();
}
add_action( 'init', 'sjf_et_helpers_init' );
add_action( 'admin_init', 'sjf_et_helpers_init' );

class SJF_Ecwid_Helpers {

	public static function get_capability() {
		return 'edit_posts';
	}

	public static function get_store_id() {
		return SJF_Ecwid_Formatting::alphanum( self::get_setting( 'store_id' ) );
	}
	
	public static function get_namespace() {
		return 'sjf_et';
	}

	public static function get_settings_prefix() {
		$namespace = self::get_namespace();
		return $namespace . '_setup_options';
	}

	public static function get_setting( $slug ) {
		$settings = get_option( self::get_settings_prefix() );
		return $settings[ $slug ];
	}

	public static function get_scopes() {
		return array(
			'read_store_profile',
			'update_store_profile',
			'read_catalog',
			'update_catalog',
			'create_catalog',
			'read_orders',
			'update_orders',
			'create_orders',
			'read_customers',
			'update_customers',
			'create_customers',
		);
	}

}