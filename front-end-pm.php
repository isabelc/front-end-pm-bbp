<?php
/*
Plugin Name:	Front End PM Mod
Plugin URI:		https://www.shamimsplugins.com/contact-us/
Description:	Front End PM is a Private Messaging system and a secure contact form to your WordPress site.This is full functioning messaging system fromfront end. The messaging is done entirely through the front-end of your site rather than the Dashboard. This is very helpful if you want to keep your users out of the Dashboard area.
Version:		13.0.alpha-1
Author:			Shamim Hasan
Author URI:		https://www.shamimsplugins.com/contact-us/
License:		GPLv2 or later
License URI:	https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Front_End_Pm {
	private static $instance;

	private function __construct() {
		$this->constants();
		$this->includes();
	}

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function constants() {
		global $wpdb;
		define( 'FEP_PLUGIN_VERSION', '11.2.3' );
		define( 'FEP_DB_VERSION', '1121' );
		define( 'FEP_PLUGIN_FILE', __FILE__ );
		define( 'FEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'FEP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

		if ( ! defined ('FEP_MESSAGE_TABLE' ) ) {
			define( 'FEP_MESSAGE_TABLE', $wpdb->base_prefix . 'fep_messages' );
		}
		if ( ! defined ('FEP_META_TABLE' ) ) {
			define( 'FEP_META_TABLE', $wpdb->base_prefix . 'fep_messagemeta' );
		}
		if ( ! defined ('FEP_PARTICIPANT_TABLE' ) ) {
			define( 'FEP_PARTICIPANT_TABLE', $wpdb->base_prefix . 'fep_participants' );
		}
		if ( ! defined ('FEP_ATTACHMENT_TABLE' ) ) {
			define( 'FEP_ATTACHMENT_TABLE', $wpdb->base_prefix . 'fep_attachments' );
		}
	}

	function includes() {
		require_once( FEP_PLUGIN_DIR . 'functions.php' );
		require_once( FEP_PLUGIN_DIR . 'default-hooks.php' );
	}

} //END Class
Front_End_Pm::init();
