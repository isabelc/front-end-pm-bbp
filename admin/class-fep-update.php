<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Update {
	private static $instance;

	public static function init() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function actions_filters() {
		if( ! is_admin() ){
			return;
		}
		
		add_action( 'admin_init', array( $this, 'install' ), 5 );
	}
	function install() {
		if ( false !== get_option( 'FEP_admin_options' ) ) {
			return;
		}
		global $wpdb;
		$roles = array_keys( get_editable_roles() );
		$id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[front-end-pm%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1" );
		$options = array();
		$options['userrole_access'] = $roles;
		$options['userrole_new_message'] = $roles;
		$options['userrole_reply'] = $roles;
		$options['page_id'] = $id;
		fep_update_option( $options );
		$this->create_htaccess();
	}
	function create_htaccess() {
		add_filter( 'upload_dir', array( Fep_Attachment::init(), 'upload_dir' ), 99 );
		$wp_upload_dir = wp_upload_dir();
		remove_filter( 'upload_dir', array( Fep_Attachment::init(), 'upload_dir' ), 99 );
		
		$upload_path = $wp_upload_dir['basedir'] . '/front-end-pm';
		$htaccess_path = $upload_path . '/.htaccess';

		// Make sure the /front-end-pm folder is created
		wp_mkdir_p( $upload_path );

		//.htaccess file content
		$htaccess = "Options -Indexes\ndeny from all\n";
		if ( ! file_exists( $htaccess_path ) && wp_is_writable( $upload_path ) ) {
			// Create the file if it doesn't exist
			@file_put_contents( $htaccess_path, $htaccess );
		}
	}
} //END CLASS

add_action( 'init', array( Fep_Update::init(), 'actions_filters' ) );

