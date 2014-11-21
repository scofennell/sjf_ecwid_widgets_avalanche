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

		$self = esc_url( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

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

	public static function handler( $format ) {
		
		if( ! isset( $_POST[self::get_submit_name()] ) ) { return false; }

		if( ! isset( $_POST[self::get_request_type_name()] ) ) { return false; }
		$request_type = $_POST[self::get_request_type_name()];

		if( ! isset( $_POST[self::get_route_name()] ) ) { return false; }
		$route = $_POST[self::get_route_name()];

		if( ! isset( $_POST[self::get_nonce_name()] ) ) { return false; }

		if( ! check_admin_referer( self::get_nonce_action(), self::get_nonce_name() ) ) { return false; }

		foreach( $_POST as $k => $v ) {
			if( $k[0] == '_' ) {
				continue;
			}
			$v = self::sanitize_value( $k, $v );
			$args [$k]= $v;		
		}

		$args = self::sanitize_keys( $args );
		
		$ecwid = new SJF_Ecwid();

		$out ['body']= $ecwid -> call( $route, $args, $request_type );

		$out ['files']= array();
		foreach( $_FILES as $route => $file ) {

			if( empty( $file['tmp_name'] ) ) { continue; }

			$args = file_get_contents( $file['tmp_name'] );

			$request = $ecwid -> call( $route, $args, 'POST', 'no_encoding' );

			$out ['files'][]= $request;

		}

		return $out;

	}

	public static function sanitize_value( $k, $v ) {

		if ( is_scalar( $v ) ) {
			
			if( stristr( $k, '__bool' ) ) {
				$v = (bool)$v;
			} elseif( stristr( $k, '__int' ) ) {
				$v = (int)$v;
			}
			return $v;

		} elseif ( is_array( $v ) ) {

			foreach( $v as $kk => $vv ) {
				$out [$kk]= self::sanitize_value( $kk, $vv );
			}
			return $out;
		}

	}

	public static function sanitize_keys( $associative_array ) {

		foreach ( $associative_array as $k => $v ) {
			if( is_scalar( $v ) ) {

				if( stristr( $k, '__bool') ) {
					unset( $associative_array[$k] );
					$k = str_replace( '__bool', '', $k );
					$associative_array [ $k ]= $v;
				} elseif( stristr( $k, '__int') ) {
					unset( $associative_array[$k] );
					$k = str_replace( '__int', '', $k );
					$associative_array [ $k ]= $v;
				}

			} elseif( is_array( $v ) ) {
				$associative_array[$k] = self::sanitize_keys( $v );
			}
		}
		return $associative_array;
	}

	public static function get_feedback( $response ) {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$out = '';

		if( ! isset( $_POST[ self::get_submit_name() ] ) ) { return false; }

		$body = json_decode( $response['body']['body'], TRUE );

		//$update_count = 0;
		$updated_or_error = 'error';
		$success_or_fail = esc_html__( 'Failure.', 'sjf_et' );
		if( isset ( $body['success'] ) ) {
			if( $body['success'] ) {
				$success_or_fail = esc_html__( 'Success!', 'sjf_et'  );
				$updated_or_error = 'updated';
				//$update_count = $body['updateCount'];
			}
		}

		$edit_link = '';
		if( isset ( $body['id'] ) ) {

			$ecwid = new SJF_Ecwid;

			$id    = urlencode( $body['id'] );
			$route = $_POST[self::get_route_name()];
			$data = $ecwid -> call( "$route/$id" );
			$created = json_decode( $data['body'], TRUE );
			$name = esc_html( $created['name'] );
			$name = "<em>$name</em>";
			$edit = sprintf( esc_html__( 'Edit %s', 'sjf_et' ), $name );
			

			$href = SJF_Ecwid_Admin_Helpers::remove_crud_args();
			$href = add_query_arg( array( 'action' => 'update' ), $href );
			$href = add_query_arg( array( 'id' => $id ), $href );

			$edit_link = "<a href='$href'>$edit</a>";
			//$update_count = $body['updateCount'];
		}
		
		//$number = esc_html__( 'Number of fields updated:', 'sjf_et'  );
		$out.="<h3>$success_or_fail $edit_link</h3>";
		
		$more_info = esc_html__( 'More Info', 'sjf_et' );
		$toggle_button = SJF_Ecwid_Admin_Helpers::get_toggle_button( $more_info );
		$out .= $toggle_button;

		$preamble_text = esc_html__( 'This information can help diagnose problems.', 'sjf_et' );
		$preamble = "<p class='$namespace-preamble'><em>$preamble_text</em></p>";

		$response_label = esc_html__( 'Response from Ecwid', 'sjf_et' );
		$extra_info = SJF_Ecwid_Formatting::array_dig( $response, null, $response_label );

		$post_label = esc_html__( 'Data posted to Ecwid', 'sjf_et' );
		$extra_info .= SJF_Ecwid_Formatting::array_dig( $_POST, null, $post_label );
		
		$files_label = esc_html__( 'Files posted to Ecwid', 'sjf_et' );
		$extra_info .= SJF_Ecwid_Formatting::array_dig( $_FILES, null, $files_label );
		

		$out .= "<div id='$namespace-extra-info' class='$namespace-extra-info'>$preamble$extra_info</div>";

		$out = "
			<div id='message' class='$updated_or_error $namespace-$updated_or_error $namespace-admin_notice below-h2'>$out</div>
		";

		return $out;

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

	public static function delete_handler( $id, $item_type ) {
		
		if( ! isset( $_GET['id'] ) ) { return FALSE; }
		if( empty( $_GET['id'] ) ) { return FALSE; }
		if( $_GET['id'] != $id ) { return FALSE; }

		if( ! isset( $_GET['confirmed'] ) ) { return FALSE; }
		if( empty( $_GET['confirmed'] ) ) { return FALSE; }

		if( ! isset( $_GET['action'] ) ) { return FALSE; }
		if( $_GET['action'] != 'delete' ) { return FALSE; }

		$action = self::get_delete_nonce_action( $id, $item_type );
		$name = self::get_delete_nonce_name( $id, $item_type );
		if( ! check_admin_referer( $action, $name ) ) { return FALSE; }

		$ecwid = new SJF_Ecwid();

		$route = "$item_type/$id";

		$data = $ecwid -> call( $route, array(), 'DELETE' );
		$deleted = json_decode( $data['body'], TRUE );
		if( $deleted['deleteCount'] == 1 ) {
			return self::redirect_for_feedback( $id, $item_type );
		} else {
			$out = "
				<div id='message' class='error below-h2'>
					<p>Error.</p>
				</div>
			";

			return $out;
		}

	}

	public static function redirect_for_feedback( $id, $item_type ) {
		$url = SJF_Ecwid_Admin_Helpers::remove_crud_args();
		$url = SJF_Ecwid_Admin_Helpers::remove_nonce_args( $url );
		$url = SJF_Ecwid_Admin_Helpers::remove_feedback_args( $url );
		$url = add_query_arg( array( 'success' => 'delete' ), $url );
		return "
			<script>
				window.location.replace( '$url' );
			</script>
		";
	}

	public static function get_delete_feedback() {
		if( ! isset( $_GET['success'] ) ) { return false; }
		if( $_GET['success'] != 'delete') { return false; }
		
		$deleted = esc_html__( 'Successfully deleted.', 'sjf_et' );

		return "
			<div id='message' class='updated below-h2'>
				<p>$deleted</p>
			</div>
		";
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