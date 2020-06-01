jQuery( document ).ready( function($) {
	var fep_notification_block_count = 0,
		fep_interval_ms = 60000;// @test now now


	function fep_notification_ajax_call( bypass_local ) {
		bypass_local = typeof bypass_local === 'undefined' ? false : bypass_local;
		if( fep_is_storage_available('localStorage') ){

			if ( ! bypass_local
				&& localStorage.getItem('fep_notification_time') !== null
				&& localStorage.getItem('fep_notification_response') !== null
				&& ( new Date().getTime() -  localStorage.getItem('fep_notification_time') ) < fep_interval_ms ) {

				fep_update_notification( JSON.parse( localStorage.getItem( 'fep_notification_response' ) ) );
				return;
			}
		}


		if ( document.hidden || document.msHidden || document.mozHidden || document.webkitHidden ) {
			
			// How many times notification ajax call will be skipped if browser tab not opened
			if ( fep_notification_block_count < 2 ) {

				fep_notification_block_count++;
				return;
			
			}
		}

		fep_notification_block_count = 0;

		$.ajax({
			url: fep_notification_script.root +'/notification',
			method: 'GET',
			dataType: 'json',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', fep_notification_script.nonce );
			}
		}).done( function( response ) {

			if ( fep_is_storage_available('localStorage') ) {

				localStorage.setItem( 'fep_notification_time', new Date().getTime() );
				localStorage.setItem( 'fep_notification_response', JSON.stringify( response ) );
			}
			fep_update_notification( response );
		}).fail( function() {
			fep_update_notification( [] );
		});
	



	}
	function fep_update_notification( response ){
		$( '.fep_unread_message_count' ).html( response['message_unread_count'] );
		$( '.fep_total_message_count' ).html( response['message_total_count'] );
		$( '.fep_unread_message_count_text' ).html( response['message_unread_count_text'] );
		

		if ( response['message_unread_count'] ) {
			$( '.fep_unread_message_count_hide_if_zero' ).show();
			$( '.fep_hide_if_anyone_zero' ).show();
		} else {
			$( '.fep_unread_message_count_hide_if_zero' ).hide();
			$( '.fep_hide_if_anyone_zero' ).hide();
		}

		$( document ).trigger( 'fep_notification', response );
	}
	function fep_is_storage_available(type) {
		var storage;
		try {
			storage = window[type];
			var x = '__storage_test__';
			storage.setItem(x, x);
			storage.removeItem(x);
			return true;
		}
		catch(e) {
			return e instanceof DOMException && (
				// everything except Firefox
				e.code === 22 ||
				// Firefox
				e.code === 1014 ||
				// test name field too, because code might not be present
				// everything except Firefox
				e.name === 'QuotaExceededError' ||
				// Firefox
				e.name === 'NS_ERROR_DOM_QUOTA_REACHED') &&
				// acknowledge QuotaExceededError only if there's something already stored
				(storage && storage.length !== 0);
		}
	}
	window.addEventListener('storage', function (e) {
		if ( 'fep_notification_response' == e.key ) {
			fep_update_notification( JSON.parse( e.newValue ) );
		}
	}, false );

	if ( fep_notification_script.call_on_ready ) {
		fep_notification_ajax_call( true );

		setInterval( fep_notification_ajax_call, parseInt( fep_interval_ms, 10 ) );
	}
});
