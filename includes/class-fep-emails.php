<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Emails {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function actions_filters() {
		if ( isset( $_POST['action'] ) && 'fep_update_ajax' == $_POST['action'] ) {
			return;
		}
		if ( true != apply_filters( 'fep_enable_email_send', true ) ) {
			return;
		}
		add_action( 'fep_status_to_publish', array( $this, 'send_email' ), 99, 2 );
	}

	function send_email( $mgs, $prev_status ) {
		if ( 'message' != $mgs->mgs_type ) {
			return;
		}
		if ( fep_get_meta( $mgs->mgs_id, '_fep_email_sent', true ) ) {
			return;
		}

		$participants = fep_get_participants( $mgs->mgs_id );
		$participants = apply_filters( 'fep_filter_send_email_participants', $participants, $mgs->mgs_id );
		if ( $participants && is_array( $participants ) ) {
			$participants = array_unique( array_filter( $participants ) );
			$subject  = get_bloginfo( 'name' ) . ': ' . __( 'New Message', 'front-end-pm' );
			$message  = __( 'You have received a new message in', 'front-end-pm' ) . "\r\n";
			$message .= get_bloginfo( 'name' ) . "\r\n";
			$message .= sprintf( __( 'From: %s', 'front-end-pm' ), fep_user_name( $mgs->mgs_author ) ) . "\r\n";
			$message .= sprintf( __( 'Subject: %s', 'front-end-pm' ), $mgs->mgs_title ) . "\r\n";
			$message .= 'Please follow the link to see the full message:' . "\r\n";
			$message .= fep_query_url( 'messagebox' ) . "\r\n";
			if ( 'html' == fep_get_option( 'email_content_type', 'plain_text' ) ) {
				$message      = nl2br( $message );
				$content_type = 'text/html';
			} else {
				$content_type = 'text/plain';
			}
			$attachments             = array();
			$headers                 = array();
			$headers['from']         = 'From: ' . stripslashes( fep_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) ) . ' <' . fep_get_option( 'from_email', get_bloginfo( 'admin_email' ) ) . '>';
			$headers['content_type'] = "Content-Type: $content_type";
			fep_add_email_filters();

			foreach ( $participants as $participant ) {
				if ( $participant == $mgs->mgs_author ) {
					continue;
				}

				if ( ! fep_get_user_option( 'allow_emails', 1, $participant ) ) {
					continue;
				}
				$to = fep_get_userdata( $participant, 'user_email', 'id' );
				if ( ! $to ) {
					continue;
				}
				$content = apply_filters( 'fep_filter_before_email_send', compact( 'subject', 'message', 'headers', 'attachments' ), $mgs, $to );

				if ( empty( $content['subject'] ) || empty( $content['message'] ) ) {
					continue;
				}
				wp_mail( $to, $content['subject'], $content['message'], $content['headers'], $content['attachments'] );
			} //End foreach
			fep_remove_email_filters();
			fep_update_meta( $mgs->mgs_id, '_fep_email_sent', time() );
		}
	}
} //END CLASS

add_action( 'wp_loaded', array( Fep_Emails::init(), 'actions_filters' ) );

