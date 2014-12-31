<?php

function sjf_et_admin_inline_scripts_init() {
	new SJF_Ecwid_Admin_Inline_Scripts();
}
add_action( 'init', 'sjf_et_admin_inline_scripts_init' );

class SJF_Ecwid_Admin_Inline_Scripts {

	/**
     * Adds actions for our class methods.
     */
    function __construct() {
            
		add_action( 'admin_footer', array( $this, 'show_hide' ) );

		add_action( 'admin_footer', array( $this, 'deauth' ) );

    }

	function show_hide() {

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$out = <<<EOT
			<script>
				jQuery( document ).ready( function( $ ) {
					
					var toggle = $( ".$namespace-toggle" );

					var extraInfo = $( ".$namespace-extra-info" );

					$( extraInfo ).hide();

					$( toggle ).click( function( event ) {
						event.preventDefault();
						$( this ).next( extraInfo ).slideToggle();
						$( this ).find(".dashicons").toggleClass( "dashicons-arrow-down-alt dashicons-arrow-up-alt" );
					});

					
				});
			</script>
EOT;

		echo $out;

	}

	function deauth() {
		
		if( ! SJF_Ecwid_Conditional_Tags::is_settings_page() ) { return FALSE; }

		$namespace = SJF_Ecwid_Helpers::get_namespace();
		$link = "$namespace-deauth-link";
		$redir = add_query_arg( array( 'deauth' => 1 ) );
			
		$out = <<<EOT
			<script>
				jQuery( document ).ready( function( $ ) {
					
					var link = $( ".$link " );

					$( link ).click( function( event ) {
						window.location.replace( '$redir' );
					});

					
				});
			</script>
EOT;

		echo $out;
	}

}