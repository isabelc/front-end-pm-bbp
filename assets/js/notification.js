jQuery( document ).ready( function($) {
	var fep_notification_block_count = 0;
	var fep_sound = new Audio( fep_notification_script.sound_url );
	function fep_notification_ajax_call( bypass_local ) {
		bypass_local = typeof bypass_local === 'undefined' ? false : bypass_local;
		if( fep_is_storage_available('localStorage') ){
			if ( ! bypass_local
				&& localStorage.getItem('fep_notification_time') !== null
				&& localStorage.getItem('fep_notification_response') !== null
				&& ( new Date().getTime() -  localStorage.getItem('fep_notification_time') ) < fep_notification_script.interval ) {
				fep_update_notification( JSON.parse( localStorage.getItem( 'fep_notification_response' ) ) );
				return;
			}
		}
		if ( document.hidden || document.msHidden || document.mozHidden || document.webkitHidden ) {
			if ( fep_notification_block_count < fep_notification_script.skip ) {
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
			if ( '1' == fep_notification_script.play_sound
			&& ( response['message_unread_count'] )
			&& ( response['message_unread_count'] > response['message_unread_count_prev'] ) ) {
				fep_sound.play();
			}
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
		//console.log(  response );
		$( '.fep_unread_message_count' ).html( response['message_unread_count_i18n'] );
		$( '.fep_total_message_count' ).html( response['message_total_count_i18n'] );
		$( '.fep_unread_message_count_text' ).html( response['message_unread_count_text'] );
		

		if ( response['message_unread_count'] ) {
			$( '.fep_unread_message_count_hide_if_zero' ).show();
			$( '.fep_hide_if_anyone_zero' ).show();
		} else {
			$( '.fep_unread_message_count_hide_if_zero' ).hide();
			$( '.fep_hide_if_anyone_zero' ).hide();
		}

		if ( response['notification_bar'] ) {
			$( '.fep-notification-bar' ).show();
		} else{
			$( '.fep-notification-bar' ).hide();
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
	}
	setInterval( fep_notification_ajax_call, parseInt( fep_notification_script.interval, 10 ) );

	$( '.fep-notification-bar .fep-notice-dismiss' ).on( 'click', function() {
		$( this ).parent().hide( 'slow' );
		$.ajax({
			url: fep_notification_script.root +'/notification/dismiss',
			method: 'GET',
			dataType: 'json',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', fep_notification_script.nonce );
			}
		})
	});
});
