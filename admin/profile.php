<?php

/**
 * Display the store profile.
 *
 * Draws the store profile screen.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 0.1
 */

function sjf_et_admin_profile_init() {
	new SJF_Ecwid_Admin_Profile();
}
add_action( 'init', 'sjf_et_admin_profile_init' );

class SJF_Ecwid_Admin_Profile {

	/**
	 * Adds actions for our class methods.
	 */
	function __construct() {
			
		add_action( 'admin_menu', array( $this, 'menu_tab' ) );

	}

	function get_page_label() {
		return esc_html__( 'Store Profile', 'sjf_et' );
	}

	function get_item_type() {
		return 'profile';
	}

	/**
	 * Add a menu item for our plugin.
	 */
	function menu_tab() {
		
		// Add a primary menu item.
		add_submenu_page(
			SJF_Ecwid_Admin_Helpers::get_menu_slug(),
			$this -> get_page_label(),
			$this -> get_page_label(),
			SJF_Ecwid_Helpers::get_capability(),
			$this -> get_item_type(),
			array( $this, 'page' ),
			SJF_Ecwid_Admin_Helpers::get_dashicon_class(),
			6
		);

	}

	/**
	 * A page for used for help / faq  / clearing transients, etc.
	 */
	function page() {
		
		 // Check capability.
		if( ! current_user_can( SJF_Ecwid_Helpers::get_capability() ) ) { return false; }

		$title = $this -> get_page_label();
		
		// If we are editing, give a link to go back to browse.
		if( SJF_Ecwid_Conditional_Tags::is_editing() ) {

			$profile = $this -> get_profile( 'update' );
			$link    = SJF_Ecwid_Admin_Helpers::get_back_to_browse_link();

		// Else if we are browsing, give a link to edit.
		} else {
			$profile = $this -> get_profile();	
			$href    = add_query_arg( 'action', 'update' );
			$label   = esc_html__( 'Edit', 'sjf_et' );
			$link    = "<a href='$href' class='add-new-h2'>$label</a>";
		}

		// Draw the page content.
		echo "
			<div class='wrap'>
				<h2>$title $link</h2>
		
				$profile

			</div>
		";

	}

	/**
	 * Get the store profile.
	 * 
	 * @param  string $action The type of view:  Update or review.
	 * @return string The store profile.
	 */
	function get_profile( $action = '' ) {

		$out = '';

		$item_type = $this -> get_item_type();

		// Fire up our class for calling Ecwid.
		$ecwid = new SJF_Ecwid;
		
		// If we are updating...
		if( $action == 'update' ) {

			// Grab the fields for our form.
			$format = $this -> get_format();
			
			// Parse any form submission that may have occurred.
			$handler = SJF_Ecwid_Handler::handler( $format );

			// Gather any feedback from our form submission.
			$feedback = SJF_Ecwid_Handler::get_feedback( $handler );
			$out .= $feedback;

			/**
			 * We have to wait until we handle the form submit
			 * before gathering the new data from ecwid,
			 * otherwise we'll show outdated data to the user.
			 */
			$data = $ecwid -> call( $item_type );
			
			$body = json_decode( $data['body'], TRUE );

			$out .= SJF_Ecwid_Forms::form( $format, $body, 'PUT', $item_type );

		// Else if we are just reviewing...
		} else {

			$data = $ecwid -> call( $item_type );
			$body = json_decode( $data['body'], TRUE );

			$out = SJF_Ecwid_Formatting::array_dig( $body );

		}

		return $out;

	}

	function get_format() {
		$out = array(
			array(
				'name'   => 'generalInfo',
				'label'  => esc_html__( 'General Info', 'sjf_et' ),
				'show_on_update' => true,
				'fields' => array(
					
					array(
						'name'     => 'storeUrl',
						'label'    => esc_html__( 'Store Url', 'sjf_et' ),
						'sanitize' => 'url',
						'show_on_update' => true,
					),
					
					array(
						'name'     => 'starterSite',
						'label'    => esc_html__( 'Starter Site', 'sjf_et' ),
						'sanitize' => 'array',
						'fields' => array(
							
							array(
								'name'     => 'ecwidSubdomain',
								'label'    => esc_html__( 'Ecwid Subdomain', 'sjf_et' ),
								'sanitize' => 'string',
								'show_on_update' => true,
		
							),
							/*
							array(
								'name'     => 'customDomain',
								'label'    => esc_html__( 'Custom Domain', 'sjf_et' ),
								'sanitize' => 'string',
							),
							*/
							array(
								'name'     => 'storeLogoUrl',
								'label'    => esc_html__( 'Store Logo', 'sjf_et' ),
								'sanitize' => 'image',
								'type'     => 'file',
								'route'    => 'profile/logo',
								'show_on_update' => true,
							),
						),
					),
				),
			),
			
			array(
				'name'   => 'account',
				'label'  => esc_html__( 'Account', 'sjf_et' ),
				'show_on_update' => true,
				'fields' => array(
					array(
						'name'     => 'accountName',
						'label'    => esc_html__( 'Account Name', 'sjf_et' ),
						'sanitize' => 'string',
						'show_on_update' => true,
		
					),
					array(
						'name'     => 'accountNickName',
						'label'    => esc_html__( 'Account Nick Name', 'sjf_et' ),
						'sanitize' => 'string',
						'show_on_update' => true,
		
					),
				),
			),
			array(
				'name'     => 'settings',
				'label'    => esc_html__( 'Settings', 'sjf_et' ),
				'show_on_update' => true,
				'fields'   => array(
					array(
						'name'     => 'closed',
						'label'    => esc_html__( 'Open or Close the Store', 'sjf_et' ),
						'type'  => array(
							''  => esc_html__( 'Open', 'sjf_et' ),
							'1' => esc_html__( 'Closed', 'sjf_et' ),
						),
						'sanitize' => 'boolean',
						'show_on_update' => true,
		
					),
					array(
						'name'     => 'storeName',
						'label'    => esc_html__( 'Store Name', 'sjf_et' ),
						'sanitize' => 'string',
						'show_on_update' => true,
		
					),
					array(
						'name'     => 'invoiceLogoUrl',
						'label'    => esc_html__( 'Invoice Logo', 'sjf_et' ),
						'sanitize' => 'image',
						'type'     => 'file',
						'route'    => 'profile/invoicelogo',
						'show_on_update' => true,
		
					),
				),
			),
			array(
				'name'     => 'company',
				'label'    => esc_html__( 'Company', 'sjf_et' ),
				'show_on_update' => true,
				'fields'   => array(
					array(
						'name'     => 'companyName',
						'label'    => esc_html__( 'Company Name', 'sjf_et' ),
						'sanitize' => 'string',
						'show_on_update' => true,
					
					),
					array(
						'name'     => 'email',
						'label'    => esc_html__( 'Email Address', 'sjf_et' ),
						'sanitize' => 'email',
						'show_on_update' => true,
		
					),
					array(
						'name'     => 'street',
						'label'    => esc_html__( 'Street Address', 'sjf_et' ),
						'sanitize' => 'string',
						'show_on_update' => true,
		
					),
					array(
						'name'     => 'city',
						'label'    => esc_html__( 'City', 'sjf_et' ),
						'sanitize' => 'string',
						'show_on_update' => true,
		
					),
					/*
					array(
						'name'     => 'countryCode',
						'label'    => esc_html__( 'Country Code', 'sjf_et' ),
						'sanitize' => 'string',
					),*/
					array(
						'name'     => 'postalCode',
						'label'    => esc_html__( 'Postal Code', 'sjf_et' ),
						'sanitize' => 'postal_code',
						'show_on_update' => true,
		
					),
					array(
						'name'     => 'stateOrProvinceCode',
						'label'    => esc_html__( 'State or Province Code', 'sjf_et' ),
						'sanitize' => 'string',
						'show_on_update' => true,
		
					),
					array(
						'name'     => 'phone',
						'label'    => esc_html__( 'Phone Number', 'sjf_et' ),
						'sanitize' => 'string',
						'show_on_update' => true,
		
					),
				),
			),
		);
		
		return $out;
	}
}