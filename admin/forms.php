<?php

Class SJF_Ecwid_Forms {

	public static function get_nonce_action() {
		$namespace = SJF_Ecwid_Helpers::get_namespace();
		return '_' . $namespace . '_nonce_action';
	}

	public static function get_nonce_name() {
		$namespace = SJF_Ecwid_Helpers::get_namespace();
		return '_' . $namespace . '_nonce_name';
	}

	public static function get_delete_nonce_action( $id, $item_type ) {
		$namespace = SJF_Ecwid_Helpers::get_namespace();
		$item_type = SJF_Ecwid_Formatting::alphanum( $item_type );
		return '_' . $namespace . $id . $item_type . '_nonce_delete_action';
	}

	public static function get_delete_nonce_name( $id, $item_type ) {
		$namespace = SJF_Ecwid_Helpers::get_namespace();
		$item_type = SJF_Ecwid_Formatting::alphanum( $item_type );
		return '_' . $namespace . $id . $item_type . '_nonce_delete_name';
	}

	public static function get_submit_name() {
		return '_' . SJF_Ecwid_Helpers::get_namespace() . '_submit';
	}

	public static function get_request_type_name() {
		return '_' . SJF_Ecwid_Helpers::get_namespace() . '_request_type';
	}

	public static function get_route_name() {
		return '_' . SJF_Ecwid_Helpers::get_namespace() . '_route';
	}

	public static function form( $format, $body, $request_type, $route ) {

		$out = '';

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// $format is an array of fields that our app exposes to editing.
		foreach( $format as $fieldset ) {
			$out .= self::get_format_as_fieldsets( $fieldset, $body );
		}
		
		if( empty( $out ) ) { return false; }

		$route_name = esc_attr( self::get_route_name() );
		
		$request_type_name = esc_attr( self::get_request_type_name() );
		
		$submit = esc_html__( 'Submit', 'sjf_et' );
		$submit_name = esc_attr( self::get_submit_name() );

		$nonce = wp_nonce_field(
			self::get_nonce_action(),
			self::get_nonce_name(),
			TRUE,
			FALSE
		);

		$self = SJF_Ecwid_Admin_Helpers::get_current_url();

		$out = "
			<form method='post' action='$self'  enctype='multipart/form-data'>
				$nonce
				$out
				<input type='hidden' name='$request_type_name' value='$request_type'>
				<input type='hidden' name='$route_name' value='$route'>
				<p><input type='submit' class='button button-primary' name='$submit_name' value='$submit'></p>
			</form>
		";		

		return $out;

	}

	public static function get_format_as_fieldsets( $fieldset, $body, $path = '' ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$out = '';

		$fieldset_name = $fieldset['name'];
		$legend = $fieldset['label'];

		if( empty( $path ) ) {
			$path .= $fieldset['name'];
		} elseif( ! stristr( $path, $fieldset['name'] ) ) {
			$path .= '['.$fieldset['name'].']';
		}

		if( isset( $fieldset['fields'] ) ) {

			$out .= "
				<fieldset class='$namespace-fieldset'>
					<legend class='$namespace-legend'>$legend</legend>
			";


			$fields = $fieldset['fields'];

			// For each field, see if the key for that field exists. 
			foreach( $fields as $field ) {

				if( isset( $field['fields'] ) ) {
					
					$fields = $field['fields'];

					$out .= self::get_format_as_fieldsets( $field, $body, $path );

				} else {

					$out .=  self::get_field_as_input( $field, $fieldset_name, $body, $path );

				}

			}

			$out .= "</fieldset>";
		
		} else {
			$out .=  self::get_field_as_input( $fieldset, $fieldset_name, $body, false );
		}

		return $out;

	}

	public static function get_field_as_input( $field, $fieldset_name, $body, $path ) {

		$out = '';

		if( ! isset( $field['show_on_update'] ) ) {
			return false;
		}

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		// The label for the field.
		$label = $field['label'];
		
		// The ecwid name for this setting.
		$setting_name = $field['name'];
		
		// The type of form input.
		$type ='';
		if( isset( $field['type'] ) ) {
			$type = $field['type'];
		}

		// The sanitization routine for this field.
		$sanitize = $field['sanitize'];
		
		// Append the setting name to the path, wrapping it for array use.
		if( $path ) {
			$path .= "[$setting_name]";
		} else {
			$path = "$setting_name";
		}

		// $body value comes from the ecwid remote $_GET call, for PUT requests.  Dig into it, getting the value at $path.
		$value = '';
		if( $body ) {
			$value = self::body_dig( $body, $path );
		}

		// The name used for this input in the form.
		$field_name = $path;

		// If it's boolean, flag it as such.
		$field_name = self::append_sanitization( $field_name, $sanitize );
		
		// If $type is an array, that means this field is an HTML select.
		if( is_array( $type ) ) {

			$options = '';
			foreach( $type as $k => $v ) {
				$selected = selected( $value, $k, false );
				$options .= "<option $selected value='$k'>$v</option>";
			}

			$input = "
				<select class='$namespace-input $namespace-input-select' name='$field_name'>
					$options
				</select>
			";

		// If $type is 'file', then that means this is an image upload.
		} elseif( $type == 'file' ) {
			
			// The Ecwid API route to which we'll send the binary data for this image.
			$image_route = esc_attr( $field['route'] );
			
			$input  = "<input class='$namespace-input' type='file' name='$image_route' value='$value'>";
			
			if( ! empty( $value ) ) {
				$image = "<img src='$value'>";
				$input .= $image;
			}
		
		} else {
			$input = "<input class='$namespace-input' type='text' name='$field_name' value='$value'>";					
		}
		
		$out .= "
			<label class='$namespace-label'>$label</label>
			$input
		";

		return $out;
	}

	/**
	 * Given a JSON response body from Ecwid, dig through it and find the value for a field.
	 * 
	 * @param  array  $body JSON response body from Ecwid.
	 * @param  string $path The path to a form field ( example: settings[closed__bool] ).
	 * @return mixed  The value of the form field at $path.
	 */
	public static function body_dig( $body, $path ) {

		// Get rid of the closing brackets at the end of field names.
		$path = str_replace( ']', '', $path );

		// Break the path into an array at every opening bracket.
		$path_array = explode( '[', $path );
		
		// How deep is the path to this field?
		$depth = count( $path_array );

		$i=0;
		foreach( $path_array as $p ) {
			
			// If $p is a key in body...
			if( array_key_exists( $p, $body ) ) {
				
				// Step down to that level of $body.
				$body = $body[$p];

			}

			$i++;
		
			// If we are at the bottom of the array, return.
			if( $depth == $i ) {
				if( is_scalar( $body ) ) {
					return $body;
				} else {
					return false;
				}
			}
		}
	}

	public static function append_sanitization( $field_name, $sanitize ) {
		
		$arrayify = false;
		if( substr( $field_name, -1, 1 ) == ']' ) {
			$arrayify = true;
			$field_name = substr( $field_name, 0, -1 );
		}

		if( $sanitize == 'boolean' ) {
			$field_name .= '__bool'; 
		} elseif( $sanitize == 'int' ) {
			$field_name .= '__int'; 
		}

		if( $arrayify ) {
			$field_name .= ']';
		}

		return $field_name;

	}

	public static function get_delete_link( $item_id, $item_type ) {
		
		$item_title = SJF_Ecwid_Admin_Helpers::get_item_title( $item_id, $item_type );

		$confirm_label = sprintf( esc_html__( 'Really delete %s?  This cannot be undone.', 'sjf_et' ), $item_title );

		$current       = add_query_arg( array( 'confirmed' => 1 ) );
		$delete_action = self::get_delete_nonce_action( $item_id, $item_type );
		$delete_name   = self::get_delete_nonce_name( $item_id, $item_type );
		$delete_nonce  = esc_url( wp_nonce_url( $current, $delete_action, $delete_name ) );

		$out = "<a href='$delete_nonce'>$confirm_label</a>";

		return $out;
	}
}