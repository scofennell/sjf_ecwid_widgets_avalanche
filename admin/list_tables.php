<?php

Class SJF_Ecwid_Admin_List_Tables {

	function get_table( $format, $items ) {

		$out = '';

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
	
		if( ! is_array( $items ) ) {
			return false;
		}

		asort( $format );

		$i = 0;
		foreach( $items as $item_fields ) {
			
			ksort( $item_fields );

			$this_out = '';

			foreach( $format as $field ) {
					
				if( ! isset( $field['show_on_index'] ) ) { continue; }

				if( array_key_exists( $field['name'], $item_fields ) ) {
					
					$v = $item_fields[ $field['name'] ];

					if( $field['sanitize'] == 'date' ) {
						$date = "<date class='$namespace-created-date'>" . date( $date_format, strtotime( $v ) ) . '</date>'; 
						$time = "<time class='$namespace-created-time'>" . date( $time_format, strtotime( $v ) ) . '</time>'; 
						$v = "$date<br>$time";
					} elseif( $field['name'] == 'name' ) {
						$item_name = esc_html( $item_fields['name'] );
						$item_href = add_query_arg( array( 'id' => $item_fields['id'], 'action' => 'update' ) );
						$item_href = SJF_Ecwid_Admin_Helpers::remove_browse_args( $item_href );
						$item_href = esc_url( $item_href );
						$item_link = "<a class='row-title' href='$item_href'>$item_name</a>";
						$v = $item_link . SJF_Ecwid_Admin_Helpers::get_crud_links( $item_fields, FALSE, array( 'row-actions' ) );
					}

					$v = wp_kses_post( $v );

					$this_out .= "<td>$v</td>";
				} else {
					$this_out .= "<td>$nbsp;</td>";	
				} 
			}

			if( ! empty( $this_out ) ) {
				
				$i++;
				$maybe_alt = '';
				if( $i % 2 == 0 ) {
					$maybe_alt = 'alt';
				}

				$out .= "<tr class='$maybe_alt'>$this_out</tr>";
			}
		}

		if( ! empty( $out ) ) {

			$head = $this -> get_table_head( $format );
			$out = "<table class='$namespace-table wp-list-table widefat sortable'>$head$out</table>";
			wp_enqueue_script( 'tablesorter' );
			$out .= $this -> table_sorter_js();

		}

		return $out;

	}

	function table_sorter_js() {
		return "
			<script>
				jQuery( window ).load( function() { 
					var sortable = jQuery( 'table.sortable' );
					jQuery( sortable ).tablesorter();
					
					//assign the sortStart event 
   					jQuery( sortable ).bind( 'sortStart', function() { 
        				jQuery( this ).find( 'tr' ).removeClass( 'alt' );
        			}).bind( 'sortEnd',function() {
        				jQuery( this ).find( 'tr:nth-child(2n)' ).addClass( 'alt' );
				    }); 
				}); 
			</script>
		";
	}

	function get_table_head( $format ) {
		$out = '';
		asort( $format );
		foreach( $format as $field ) {

			if( ! isset( $field['show_on_index'] ) ) { continue; }

			$field_name = esc_html( $field['label'] );

			$out .= "<th><a href='#'>$field_name <span class='dashicons dashicons-sort'></span></a></th>";

		}

		if( ! empty( $out ) ) {
			$out = "<thead><tr>$out</tr></thead>";
		}

		return $out;

	}

}