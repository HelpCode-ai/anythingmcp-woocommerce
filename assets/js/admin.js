/**
 * AnythingMCP for WooCommerce: create an API key and show it once.
 *
 * The key and secret come back from admin-ajax.php and only ever live in the
 * two read-only fields of this page. They have no name attribute, so the
 * settings form never submits them.
 */
( function () {
	'use strict';

	var config = window.amcpWcAdmin;
	if ( ! config ) {
		return;
	}

	var createBox = document.getElementById( 'amcp-wc-create' );
	var createButton = document.getElementById( 'amcp-wc-create-key' );
	var permissions = document.getElementById( 'amcp-wc-permissions' );
	var spinner = document.getElementById( 'amcp-wc-spinner' );
	var errorBox = document.getElementById( 'amcp-wc-error' );
	var keyBox = document.getElementById( 'amcp-wc-key' );
	if ( ! createButton || ! keyBox ) {
		return;
	}

	function showError( message ) {
		errorBox.querySelector( 'p' ).textContent = message || config.i18n.error;
		errorBox.hidden = false;
	}

	function setBusy( busy ) {
		createButton.disabled = busy;
		spinner.classList.toggle( 'is-active', busy );
	}

	createButton.addEventListener( 'click', function () {
		var body = new URLSearchParams();
		body.append( 'action', 'amcp_wc_create_key' );
		body.append( 'nonce', config.nonce );
		body.append( 'permissions', permissions.value );

		errorBox.hidden = true;
		setBusy( true );

		window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} )
			.then( function ( response ) {
				return response.json().catch( function () {
					return null;
				} );
			} )
			.then( function ( result ) {
				setBusy( false );
				if ( ! result || ! result.success || ! result.data ) {
					showError( result && result.data && result.data.message );
					return;
				}
				document.getElementById( 'amcp-wc-consumer-key' ).value = result.data.consumer_key;
				document.getElementById( 'amcp-wc-consumer-secret' ).value = result.data.consumer_secret;
				document.getElementById( 'amcp-wc-key-description' ).textContent = result.data.description;
				document.getElementById( 'amcp-wc-key-permissions' ).textContent = result.data.permissions;
				createBox.hidden = true;
				keyBox.hidden = false;
				document.getElementById( 'amcp-wc-consumer-key' ).focus();
			} )
			.catch( function () {
				setBusy( false );
				showError();
			} );
	} );

	function copyFallback( input ) {
		input.focus();
		input.select();
		try {
			return document.execCommand( 'copy' );
		} catch ( e ) {
			return false;
		}
	}

	Array.prototype.forEach.call( document.querySelectorAll( '.amcp-wc-copy' ), function ( button ) {
		button.addEventListener( 'click', function () {
			var input = document.getElementById( button.getAttribute( 'data-target' ) );
			var status = button.parentNode.querySelector( '.amcp-wc-copied' );
			function done( ok ) {
				status.textContent = ok ? config.i18n.copied : config.i18n.copyFailed;
				window.setTimeout( function () {
					status.textContent = '';
				}, 3000 );
			}
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( input.value ).then(
					function () {
						done( true );
					},
					function () {
						done( copyFallback( input ) );
					}
				);
			} else {
				done( copyFallback( input ) );
			}
		} );
	} );
}() );
