/**
 * Scripts for our plugin widgets.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 1.3
 */

/**
 * Helpers
 */

var SJFETHelpers = {

	catSep: function() {
		return 'which_categories=';
	},

	getUrlBase: function( url ) {
		var urlArr = [];
		if( typeof url !== 'string' ) { return; }
		urlArr = url.split( this.catSep() );
		return urlArr[0];
	},

	getQueryString: function( urlToSplit ) {

		var urlArr = [];
		var count = 0;
		if( typeof urlToSplit !== 'string' ) { return; }


		urlArr = urlToSplit.split( this.catSep() );
		
		if( typeof urlArr !== 'object' ) { return; }

		count = urlArr.length;

		if( count > 1 ) {
			return urlArr[1];
		}

	},

	getCatArray: function( url ) {

		var queryStr = this.getQueryString( url );

		var catArr = [];
		if( typeof queryStr !== 'string' ) { return catArr; }
		catArr = queryStr.split( ',' );
		return catArr;

	},

	removeCat: function( url, catToRemove ) {

		var out  = '';
		var cats = [];
		var base = this.getUrlBase( url );
		var queryArr = this.getCatArray( url );

		if( typeof queryArr !== 'object' ) { return; }

		var count = queryArr.length;

		for ( var i = 0; i < count; i++ ) {
			if( queryArr[i] != catToRemove ) {
				cats.push( queryArr[i] );
			}
		}

		if( typeof cats !== 'object' ) { return; }
		var catCount = cats.length;

		cats.join( ',' );

		out = base;
		if( catCount > 0 ) {
			out = out + this.catSep() + cats;
		}

		return out;

	},

	addCat: function( url, catToAdd ) {
		var out  = ''; 
		var base = this.getUrlBase( url );
		var cats = this.getCatArray( url );
		if( typeof cats !== 'object' ) { return; }

		if( jQuery.inArray( catToAdd, cats ) !== -1 ) {
			return url;
		}

		
		cats.push( catToAdd );
		cats.join( ',' );
		out = base + this.catSep() + cats;
		return out;	
	},

	replaceCat: function( url, catToRemove, catToAdd ) {
		var url = this.removeCat( url, catToRemove );

		url = this.addCat( url, catToAdd );

		if( typeof url === 'string' ) {
			url = url.replace( '=,', '=' );
		}

		return url;
	},

	getNumericCats: function( url ) {
		var out = [];

		var cats = this.getCatArray( url );

		if ( typeof cats !== 'object' ) { return; }

		var count = cats.length;

		for ( var i = 0; i < count; i++ ) {
			if( jQuery.isNumeric( cats[i] ) ) {
				var cat = parseInt( cats[i] );
				out.push( cat );
			}
		}

		return out;
	}

};

/**
 * Autosuggest
 *
 * The args were localized in the Autosuggest widget file.
 */
jQuery( document ).ready( function( $ ) {

	if( typeof SJF_ET_Autosuggest === "undefined" ) { return false; }
	if( ! jQuery().autocomplete ) { return false; }

	// The configuration for the autosuggest.
	var source           = SJF_ET_Autosuggest.source;
	var error            = SJF_ET_Autosuggest.error;
	var autosuggestClass = '.' + SJF_ET_Autosuggest.class;

	// Instantiate the autocomplete.
	$( autosuggestClass + '-input' ).autocomplete({

		// No min length.
		minLength: 0,

		// All the products.
		source: source,

		// Show the results in our widget.
		appendTo: autosuggestClass + '-suggestions',
		
		// Position them at the left bottom of the widget.
		position: { my : "left top", at: "left bottom" },

		// When an item is focused, don't do anything just yet.
		focus: function( event, ui ) {
			return false;
		},

		// When an item is selected, use the label for the input and the href for the form action.  And submit the form!
		select: function( event, ui ) {

			// Grab the label for this item and toss it in the search box.
			$( autosuggestClass + '-input' ).val( ui.item.label );

			// Grab the href and point the form to it.
			$( autosuggestClass ).attr({ action: ui.item.value });
			
			// Submit the form.
			$( autosuggestClass ).submit();
			
			// Don't do anything else.
			return false;
		}

	// Output each item as a link instead of just a list item.
	}).autocomplete( "instance" )._renderItem = function( ul, item ) {
		return $( "<li>" )
		.append( "<a class='sjf_et_autosuggest-link' href='" + item.value + "'>" + item.label + "</a>" )
		.appendTo( ul );
	};

	// Use the source to build an array of valid labels for pointing the form to.
	haystack = [];
	$( source ).each( function( index, value ) {
		haystack.push( value.label );
	});

	// When the form is submit, make sure it is pointing to a valid item.
	$( autosuggestClass ).submit( function( event ) {
		
		// Remove the error text when the user attempts to submit, so we don't end up with lots of them.
		$( autosuggestClass ).find( '.sjf_et-error' ).remove();

		// Grab the text in the search box.
		needle = $( this ).find( autosuggestClass + '-input' ).val();

		// See if it's in the array of good values.
		inArray = $.inArray( needle, haystack );
		
		// Check for an empty for action.
		formAction = $( autosuggestClass ).attr( 'action' );

		// If something is amiss, either an empty form action or a bad spelling, warn the user.
		if( ( inArray == -1 ) || ( typeof formAction === "undefined" ) ) {

			$( this ).addClass( 'sjf_et-error' );
			$( error ).hide().insertAfter( autosuggestClass ).fadeIn();
			return false;
		}

	});

	// Upon any change to the search box, remove the warning class.
	$( autosuggestClass + '-input' ).on( 'input propertychange paste', function() {

		$( autosuggestClass ).removeClass( 'sjf_et-error' );
		$( '.sjf_et_autosuggest-error' ).fadeOut();

	});

});

/**
 * Slider
 *
 * The args were localized in the slider widget file.
 */
jQuery( window ).load( function() {

	if( typeof SJF_ET_Slider === "undefined" ) { return false; }

	if( ! jQuery().bxSlider ) { return false; }

	// The configuration for the BX Slider.
	var args       = SJF_ET_Slider.args;
	var sliderArgs = jQuery.parseJSON( args );

	// The CSS class for our slider.
	var sliderClass = '.' + SJF_ET_Slider.class;
	
	// Find the slider by class, instantiate it with the args from our php file.
	jQuery( sliderClass ).bxSlider( sliderArgs );

});

/**
 * Accordion
 */
jQuery( document ).ready( function( $ ) {
	
	// Hide all elements that carry our plugin hide class.
	var hide = $( '.sjf_et_accordion' );
	$( hide ).hide();

	// Our plugin toggler class.  When it's clicked, it's hidden slblings are revealed.
	var toggle = $( '.sjf_et-toggle' );
	$( toggle ).click( function( event ) {
		event.preventDefault();
		$( this ).siblings( '.sjf_et_accordion' ).slideToggle();

		// We typically have a toggle arrow, which we'll invert on open/close.
		$( this ).find( '.dashicons' ).toggleClass( 'dashicons-arrow-down-alt dashicons-arrow-up-alt' );
	});

});

/**
 * Popup
 */
jQuery( document ).ready( function( $ ) {
	 
	 // We need to make sure cookie plugin is defined, since the popup saves a cookie when closed.
	if( ! $.cookie ) { return false; }

	// The actual popup dialogue thing.
	var popup = $( '.sjf_et_popup_get_popup' );

	// The button to close the popup.
	var close = $( '.sjf_et_popup-close' );

	// The name of the cookie associated with this popup.
	var cookie = $( popup ).data( 'cookie' );

	// Move the popup to the end of the body so it stacks on top.
	$( popup ).appendTo( 'body' );

	// When we click the close button or the overlay BG, close the popup and save a cookie.
	$( [close, popup] ).each( function() {
		$( this ).click( function( event ) {
			event.preventDefault();
			$( popup ).fadeOut();
			$.cookie( cookie, '1', { expires: 1 } );
		});
	});

	// We want to be able to click the popup without triggering the call to fade it out.
	$( '.sjf_et_popup-inner' ).click( function( event ) {
		event.stopPropagation();
	});

});

/**
 * Sortable
 */
jQuery( document ).ready( function( $ ) { 

	if( ! $.tablesorter ) { return false; }
	if( typeof SJF_ET_Sortable === "undefined" ) { return false; }
	
	// After the table loads, make it sortable.
	function loadTable( el, child, url ) {

		$( el ).load( url, function() {
			var loaded = $( el ).find( child );
			loaded.tablesorter();
		});
	}

	function gettingCurrent( whichCatsArr ) {

		var currentInArray = jQuery.inArray( 'current', whichCatsArr );
		
		if( currentInArray === -1 ) { return -1; }

		if( typeof Ecwid !== 'object' ) { return -1; }
		
		return 1;
			
	}

	function findCurrent( page ) {
		var ID = 0;
		if( ( typeof page.categoryId ) !== 'number' ) { return ID; }
							
		if( page.categoryId < 1 ) { return ID; }
							
		ID = page.categoryId;

		return ID;

	}

	var baseUrl     = SJF_ET_Sortable.base_url;
	var loaderClass = '.' + SJF_ET_Sortable.loader_class;
	var sortables   = $( loaderClass );
	$( sortables ).each( function( index, value ) {
		
		var sortable = this;

		var whichCats = $( this ).data( 'which_categories' );
		var url       = baseUrl + '?' + SJFETHelpers.catSep() + whichCats;
		var whichCatsArr       = SJFETHelpers.getCatArray( url );	
		var specified          = SJFETHelpers.getNumericCats( url );
		var ID                 = 0;
		var getCurrent         = -1;
		var gotCurrent         = 0;
		var currentIsSpecified = -1;

		getCurrent = gettingCurrent( whichCatsArr );

		if( getCurrent > -1 ) {
			
			Ecwid.OnPageLoad.add( function( page ) {

				ID = findCurrent( page );

				if( ID > 0 ) {
					
					currentIsSpecified = jQuery.inArray( ID, specified );
					if( currentIsSpecified == -1 ) {
							
						url = SJFETHelpers.replaceCat( url, 'current', ID );
						gotCurrent = 1;

					}

					loadTable( sortable, '.sjf_et_sortable_get_sortable', url );

					if( currentIsSpecified == -1 ) {
						url = SJFETHelpers.replaceCat( url, ID, 'current' );
					}

				} else {
					loadTable( sortable, '.sjf_et_sortable_get_sortable', url );
				}
					
			});

		}

		if( gotCurrent < 1 ) {

			url = SJFETHelpers.removeCat( url, 'current' );

			loadTable( sortable, '.sjf_et_sortable_get_sortable', url );
		
		}

		
	// End each sortable.
	});

	$( '.sjf_et_sortable_get_table_head-th-link' ).click( function( event ) {
		event.preventDefault();
	});

});