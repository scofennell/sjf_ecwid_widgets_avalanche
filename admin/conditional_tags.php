<?php

Class SJF_Ecwid_Conditional_Tags {

	public static function is_editing() {
		if( ! isset( $_GET['action'] ) ) { return FALSE; }
		if( $_GET['action'] != 'update' ) { return FALSE; }
		return TRUE;
	}

	public static function is_deleting() {
		if( ! isset( $_GET['action'] ) ) { return FALSE; }
		if( $_GET['action'] != 'delete' ) { return FALSE; }
		return TRUE;
	}

	public static function is_creating() {
		if( ! isset( $_GET['action'] ) ) { return FALSE; }
		if( $_GET['action'] != 'create' ) { return FALSE; }
		return TRUE;
	}

	public static function is_settings_page() {
		if( ! is_admin() ) { return FALSE; }
		if( ! isset( $_GET['page'] ) ) { return FALSE; }
		if( $_GET['page'] != SJF_Ecwid_Admin_Helpers::get_menu_slug() ) { return FALSE; }
		return TRUE;
	}

	public static function is_reviewing( $template = 'archive' ) {

		if( self::is_editing() ) { return FALSE; }

		if( self::is_creating() ) { return FALSE; }

		if( self::is_deleting() ) { return FALSE; }

		if( $template == 'single' ) {
			if( ! isset( $_GET['id'] ) ) {
				return FALSE;
			} else {
				return TRUE;
			}
		}

		return TRUE;
	}

}