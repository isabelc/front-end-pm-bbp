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


			$pstyle = " style='width:500px;max-width:100%;font-family:Arial,\"Helvetica Neue\",Helvetica,sans-serif;font-size:16px;color:#444;line-height:1.6'";

			$message  = "<div style='background:#f2f6f7;padding:24px;'><div style='width:600px;max-width:90%;font-family:Arial,\"Helvetica Neue\",Helvetica,sans-serif;font-size:16px;color:#444;border:1px solid #ececec;line-height:1.6;background-color: #ffffff;margin-left:auto;margin-right:auto;padding:0 24px;'><div style='text-align:center'>";


			/****************************************************
			* @todo must update image url:
			* make sure it's the right color (lighter?) and minified
			http://localhost/wp-content/themes/forumpress/img/astrotalk-lighter.svg

			****************************************************/

			// $message .= "<img alt='letterhead' src='http://localhost/wp-content/themes/forumpress/img/astrotalk-lighter.svg' width='160' height='55'>";// @todo do this live!!


			$message .= "</div><p $pstyle>You have received a new message at " . get_bloginfo( 'name' ) . '.</p>';
			$message .= '<p $pstyle>From: <strong>' . fep_user_name( $mgs->mgs_author ) . '</strong><br>';
			$message .= 'Subject: <strong>' . $mgs->mgs_title . '</strong></p>';
			$message .= '<br><a style="font-weight:700;border-radius: 4px;color: #fff;background-color: #7F54B3;text-decoration:none;padding: 10px 15px;white-space: nowrap;" href="' . fep_query_url( 'messagebox' ) . '">See Message</a><br><br></div>';

			// @todo live update localhost url to live one: http://localhost/messages/?fepaction=settings

			$message .= "<div style='border-top: 1px solid #f3f3f3; color: #808080;width:600px;max-width:90%;font-family:Arial,\"Helvetica Neue\",Helvetica,sans-serif;font-size: 13px; margin-left:auto;margin-right:auto;padding:14px 24px;'>You're receiving this email because you allow messages at <a href='" .
				home_url() ."' style='color:#7f54b3;text-decoration: underline;'>" .
				get_bloginfo( 'name' ) .".org</a>." .
				"<br>Change your email preferences at <a style='color:#7f54b3;text-decoration:underline;' href='" . fep_query_url( 'settings' ) . "'>Message Settings</a>.</div></div>";

			if ( 'html' == fep_get_option( 'email_content_type', 'plain_text' ) ) {
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

