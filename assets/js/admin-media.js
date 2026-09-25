/**
 * Implements SPEC.md §6.7: the publication edit screen's upload buttons and
 * the alternates table's Add Row/Delete Row controls. D13 (P2-07) replaces
 * 3.0.1's Thickbox and its legacy media-frame callback with a wp.media
 * frame; the only jQuery here is jQuery( document ).on() delegation, and
 * this file never assigns to that legacy callback.
 *
 * Enqueued only on the publication edit screens (Assets::enqueue_admin()),
 * with 'media-editor' as its only dependency and the localized
 * window.wppaAdminMedia object (Keys::ADMIN_SCRIPT_OBJECT) for its frame
 * titles.
 *
 * @author Eric Mann <eric@eamann.com>
 */

( function ( window, wp, $ ) {
	'use strict';

	if ( ! wp || ! wp.media ) {
		return;
	}

	var strings = window.wppaAdminMedia || {};

	/**
	 * Opens a single-selection media frame and passes the chosen
	 * attachment's url to onSelect.
	 *
	 * @param {string}   title
	 * @param {Function} onSelect
	 */
	function openMediaFrame( title, onSelect ) {
		var frame = wp.media( {
			title: title,
			multiple: false
		} );

		frame.on( 'select', function () {
			var selection = frame.state().get( 'selection' ).first();

			if ( ! selection ) {
				return;
			}

			onSelect( selection.toJSON().url );
		} );

		frame.open();
	}

	$( document ).on( 'click', '#upload_doc_button', function ( event ) {
		event.preventDefault();

		openMediaFrame( strings.docTitle, function ( url ) {
			document.getElementById( 'wpa_upload_doc' ).value = url;
		} );
	} );

	$( document ).on( 'click', '#wpa-upload_image_button', function ( event ) {
		event.preventDefault();

		openMediaFrame( strings.imageTitle, function ( url ) {
			document.getElementById( 'wpa-upload_image' ).value = url;
		} );
	} );

	$( document ).on( 'click', '.wpa-upload-row', function ( event ) {
		event.preventDefault();

		var row = this.closest( 'tr' );

		openMediaFrame( strings.alternateTitle, function ( url ) {
			row.querySelector( 'input[name$="[url][]"]' ).value = url;
		} );
	} );

	$( document ).on( 'click', '#wpa-alternates-button', function ( event ) {
		event.preventDefault();

		var template = document.getElementById( 'wpa-alternate-row-template' );
		var table = document.getElementById( 'wpa-alternate-table' );

		if ( ! template || ! table || ! template.content ) {
			return;
		}

		table.querySelector( 'tbody' ).appendChild( template.content.firstElementChild.cloneNode( true ) );
	} );

	$( document ).on( 'click', '.wpa-delete-row', function ( event ) {
		event.preventDefault();

		this.closest( 'tr' ).remove();
	} );
} )( window, window.wp, window.jQuery );
