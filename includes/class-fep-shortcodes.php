<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Shortcodes {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		//ADD SHORTCODES
		add_shortcode( 'front-end-pm', array( fep_main_class::init(), 'main_shortcode_output' ) ); //for FRONT END PM
		add_shortcode( 'fep_shortcode_new_message_form', array( $this, 'new_message_form' ) );
	}
	function new_message_form( $atts, $content = null, $tag = '' ) {
		$atts = shortcode_atts( array(
			'to'		=> '{current-post-author}',
			'subject'	=> '',
			'heading'	=> __( 'Contact','front-end-pm' ),
		), $atts, $tag );
		if ( '{current-post-author}' == $atts['to'] ) {
			$atts['to'] = get_the_author_meta( 'user_nicename' );
		} elseif ( '{current-author}' == $atts['to'] ) {
			if ( $nicename = fep_get_userdata( get_query_var( 'author_name' ), 'user_nicename' ) ) {
				$atts['to'] = $nicename;
			} elseif ( $nicename = fep_get_userdata( get_query_var( 'author' ), 'user_nicename', 'id' ) ) {
				$atts['to'] = $nicename;
			}
			unset( $nicename );
		} elseif ( '{um-current-author}' == $atts['to'] && function_exists( 'um_profile_id' ) ) {
			$atts['to'] = fep_get_userdata( um_profile_id(), 'user_nicename', 'id' );
		} else {
			$atts['to'] = esc_html( $atts['to'] );
		}
		if ( false !== strpos( $atts['subject'], '{current-post-title}' ) ) {
			$atts['subject'] = str_replace( '{current-post-title}', get_the_title(), $atts['subject'] );
		}
		extract( $atts );
		$to_id = fep_get_userdata( $to );
		if ( ! is_user_logged_in() ) {
			return apply_filters( 'fep_filter_shortcode_new_message_form', '<div class="fep-error">' . sprintf( __( 'You must <a href="%s">login</a> to contact', 'front-end-pm' ), wp_login_url( get_permalink() ) ) . '</div>', $atts );
		} elseif ( ! fep_current_user_can( 'send_new_message_to', $to_id ) ) {
			return apply_filters( 'fep_filter_shortcode_new_message_form', '<div class="fep-error">' . sprintf( __( 'You cannot send message to %s', 'front-end-pm' ), fep_user_name( $to_id ) ) . '</div>', $atts );
		}
		$template = fep_locate_template( 'form-shortcode-message.php' );
		ob_start();
		include( $template );
		return apply_filters( 'fep_filter_shortcode_new_message_form', ob_get_clean(), $atts );
	}
} //END CLASS
add_action( 'init', array( Fep_Shortcodes::init(), 'actions_filters' ) );
