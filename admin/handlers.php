<?php

Class SJF_Ecwid_Handler {
	
	public static function handler( $format ) {

		if( ! isset( $_POST[SJF_Ecwid_Forms::get_submit_name()] ) ) { return false; }

		if( ! isset( $_POST[SJF_Ecwid_Forms::get_request_type_name()] ) ) { return false; }
		$request_type = $_POST[SJF_Ecwid_Forms::get_request_type_name()];

		if( ! isset( $_POST[SJF_Ecwid_Forms::get_route_name()] ) ) { return false; }
		$route = $_POST[SJF_Ecwid_Forms::get_route_name()];

		if( ! isset( $_POST[SJF_Ecwid_Forms::get_nonce_name()] ) ) { return false; }

		if( ! check_admin_referer( SJF_Ecwid_Forms::get_nonce_action(), SJF_Ecwid_Forms::get_nonce_name() ) ) { return false; }

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

		$files_count = count( $_FILES );

		if( ! empty( $files_count ) ) {

			foreach( $_FILES as $route => $file ) {

				if( empty( $file['tmp_name'] ) ) { continue; }

				$args = file_get_contents( $file['tmp_name'] );

				$request = $ecwid -> call( $route, $args, 'POST', 'no_encoding' );

				$out ['files'][]= $request;

			}

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

		if( ! isset( $_POST[ SJF_Ecwid_Forms::get_submit_name() ] ) ) { return false; }

		$body = json_decode( $response['body']['body'], TRUE );

		//$update_count = 0;
		$updated_or_error = 'error';
		$success_or_fail = esc_html__( 'Failure.', 'sjf_et' );
		if( isset ( $body['success'] ) || isset( $body['updateCount'] ) || isset( $body['id'] ) ) {
			$success_or_fail = esc_html__( 'Success!', 'sjf_et'  );
			$updated_or_error = 'updated';
			//$update_count = $body['updateCount'];
		}

		$edit_link = '';
		if( isset ( $body['id'] ) ) {

			$ecwid = new SJF_Ecwid;

			$id    = urlencode( $body['id'] );
			$route = $_POST[SJF_Ecwid_Forms::get_route_name()];
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

	public static function delete_handler( $id, $item_type ) {
		
		if( ! isset( $_GET['id'] ) ) { return FALSE; }
		if( empty( $_GET['id'] ) ) { return FALSE; }
		if( $_GET['id'] != $id ) { return FALSE; }

		if( ! isset( $_GET['confirmed'] ) ) { return FALSE; }
		if( empty( $_GET['confirmed'] ) ) { return FALSE; }

		if( ! isset( $_GET['action'] ) ) { return FALSE; }
		if( $_GET['action'] != 'delete' ) { return FALSE; }

		$action = SJF_Ecwid_Forms::get_delete_nonce_action( $id, $item_type );
		$name = SJF_Ecwid_Forms::get_delete_nonce_name( $id, $item_type );
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

}