/**
 * Mm Ajax Login JS
 */

( function( $ ) {

	var selector = mm_ajax_login_vars.selector;

	// Open the ajax modal when a trigger link is clicked.
	$( selector ).on( 'click', function( event ) {
		event.preventDefault();

		// Store the url to redirect to after login.
		var redirectUrl = ( $( this ).attr( 'href' ) ) ? $( this ).attr( 'href' ) : '';

		// Check whether the user is logged in and proceed from there.
		mmAjaxLoginCheckIsUserLoggedIn( redirectUrl );
	});

	// Close the ajax modal when the close button or overlay is clicked or when the user hits esc.
	$( '.mm-ajax-login, .mm-ajax-login .close-button' ).on( 'click keyup', function( event ) {
		if ( event.target == this || event.target.className == 'close-button' || event.keyCode == 27 ) {
			$( this ).removeClass( 'open visible' );
			$( '.mm-ajax-login-inner' ).removeClass( 'visible' );
		}
	});

	// Call our form trigger function when the login button is clicked or when the user hits enter.
	// We need to do this manually rather than use input type="submit" in the form because
	// we want to allow extra inputs to be added that might inject custom data into the form
	// before submitting.
	$( '#mm-ajax-login-submit-button' ).on( 'click', mmAjaxLoginSubmitForm );
	$( '#mm-ajax-login-form input' ).on( 'keypress', function( e ) {
		if ( e.keyCode == 13 ) {
			mmAjaxLoginSubmitForm();
		}
	});

	// Trigger the form submission.
	function mmAjaxLoginSubmitForm() {
		$( '#mm-ajax-login-form' ).trigger( 'submit' );
	}

	// Function to do the vertical centering.
	function mmAjaxLoginVerticalCenter( selector, offset ) {
		var $this, parentHeight, marginTop;

		$this = $( selector );

		// Grab the wrapper's height.
		parentHeight = $this.parent().height();

		// Calculate and add the margin-top to center the element if it is a positive value.
		marginTop = ( ( parentHeight - $this.outerHeight() ) / 2 ) + parseInt( offset );
		if ( 0 > marginTop ) {
			marginTop = 15;
		}
		$this.css( 'margin-top', marginTop ).addClass( 'visible' );
	}

	// Check whether the user is logged in.
	function mmAjaxLoginCheckIsUserLoggedIn( redirectUrl ) {
		var nonce, data;

		// Grab the nonce value from the form.
		nonce = $( '#mm-ajax-login-form #_wpnonce' ).attr( 'value' );

		// Build the AJAX data.
		data = {
			'action': 'mm_is_user_logged_in',
			'nonce' : nonce
		};

		// Make the AJAX request.
		$.post( mm_ajax_login_vars.ajax_url, data, function( response ) {

			// If the user is already logged in, follow the clicked link,
			// otherwise show the login form.
			if ( response == 'yes' ) {

				// Only do it if the URL is legit.
				if ( 0 === redirectUrl.lastIndexOf( 'http', 0 ) ||
					 0 === redirectUrl.lastIndexOf( '/', 0 ) ||
					 0 === redirectUrl.lastIndexOf( '#', 0 ) ) {
					window.location.href = redirectUrl;
				} else {
					window.location.reload();
				}
			} else {
				mmAjaxLoginShowLoginForm( redirectUrl );
			}
		});
	}

	// Show the login form.
	function mmAjaxLoginShowLoginForm( redirectUrl ) {
		var $modal = $( '.mm-ajax-login' );

		$modal.addClass( 'open' );
		mmAjaxLoginVerticalCenter( '.mm-ajax-login-inner', 0 );
		$modal.addClass( 'visible' );
		$modal.find( 'input[type="text"]' ).focus();

		// Store the redirect URL in the form.
		$( '#mm-ajax-login-form' ).append( '<input id="mm-ajax-login-redirect-url" type="hidden" value="' + redirectUrl + '" />' );
	}

	// Attempt to sign the user in.
	function mmAjaxLoginDoAjax( event ) {
		event.preventDefault();

		// Update the status message.
		$( '#mm-ajax-login-status' ).text( mm_ajax_login_vars.status_message ).addClass( 'visible' );

		// Grab the redirect URL.
		var redirectUrl = $( '#mm-ajax-login-redirect-url' ).attr( 'value' );

		// Build our AJAX data.
		var data = {
			'action' : 'mm_do_ajax_login',
			'data'   : $( '#mm-ajax-login-form' ).serialize(),
			'nonce'  : $( '#mm-ajax-login-form #_wpnonce' ).attr( 'value' )
		}

		// Make the AJAX request.
		$.post( mm_ajax_login_vars.ajax_url, data, function( response ) {

			// Convert the response into an object.
			var responseObj = $.parseJSON( response );

			// Update the status message.
			$( '#mm-ajax-login-status' ).text( responseObj.message );

			// Redirect if login was successful.
			if ( true === responseObj.logged_in ) {

				// Only do it if the URL is legit.
				if ( 0 === redirectUrl.lastIndexOf( 'http', 0 ) ||
					 0 === redirectUrl.lastIndexOf( '/', 0 ) ||
					 0 === redirectUrl.lastIndexOf( '#', 0 ) ) {
					window.location.href = redirectUrl;
				} else {
					window.location.reload();
				}
			}
		});
	}

	// Submit the form via AJAX.
	$( '#mm-ajax-login-form' ).on( 'submit', mmAjaxLoginDoAjax );
})( jQuery );
