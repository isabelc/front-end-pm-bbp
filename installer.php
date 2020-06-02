<?php
function fep_create_database(){
	global $wpdb;

	$message_table = $wpdb->base_prefix . 'fep_messages';
	$participant_table = $wpdb->base_prefix . 'fep_participants';
	$messagemeta_table = $wpdb->base_prefix . 'fep_messagemeta';
	$attachment_table = $wpdb->base_prefix . 'fep_attachments';

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$charset_collate = $wpdb->get_charset_collate();

	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$message_table}'" ) != $message_table ) {

		$sql_message = "CREATE TABLE $message_table (
			mgs_id bigint(20) unsigned NOT NULL auto_increment,
			mgs_parent bigint(20) unsigned NOT NULL default '0',
			mgs_author bigint(20) unsigned NOT NULL default '0',
			mgs_created datetime NOT NULL default '0000-00-00 00:00:00',
			mgs_title text NOT NULL,
			mgs_content mediumtext NOT NULL,
			mgs_type varchar(20) NOT NULL DEFAULT 'message',
			mgs_status varchar(20) NOT NULL DEFAULT 'pending',
			mgs_last_reply_by bigint(20) unsigned NOT NULL default '0',
			mgs_last_reply_time datetime NOT NULL default '0000-00-00 00:00:00',
			mgs_last_reply_excerpt varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (mgs_id),
			KEY mgs_parent_last_time (mgs_parent,mgs_last_reply_time),
			KEY mgs_type_created (mgs_type,mgs_created)
		) $charset_collate;";

		dbDelta( $sql_message );

	}
		
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$participant_table}'" ) != $participant_table ) {

		$sql_perticipiants = "CREATE TABLE $participant_table (
			per_id bigint(20) unsigned NOT NULL auto_increment,
			mgs_id bigint(20) unsigned NOT NULL default '0',
			mgs_participant bigint(20) unsigned NOT NULL default '0',
			mgs_read bigint(20) unsigned NOT NULL default '0',
			mgs_parent_read bigint(20) unsigned NOT NULL default '0',
			mgs_deleted bigint(20) unsigned NOT NULL default '0',
			mgs_archived bigint(20) unsigned NOT NULL default '0',
			PRIMARY KEY  (per_id),
			UNIQUE KEY mgs_id_participant (mgs_id,mgs_participant)
		) $charset_collate;";

		dbDelta( $sql_perticipiants );

	}
		

	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$messagemeta_table}'" ) != $messagemeta_table ) {

		$sql_meta = "CREATE TABLE $messagemeta_table (
			meta_id bigint(20) unsigned NOT NULL auto_increment,
			fep_message_id bigint(20) unsigned NOT NULL default '0',
			meta_key varchar(255) default NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY fep_message_id (fep_message_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";

		dbDelta( $sql_meta );

	}
		
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$attachment_table}'" ) != $attachment_table ) {

		$sql_attachments = "CREATE TABLE $attachment_table (
			att_id bigint(20) unsigned NOT NULL auto_increment,
			mgs_id bigint(20) unsigned NOT NULL default '0',
			att_mime varchar(100) NOT NULL default '',
			att_file varchar(255) NOT NULL default '',
			att_status varchar(20) NOT NULL default '',
			PRIMARY KEY  (att_id),
			KEY mgs_id (mgs_id)
		) $charset_collate;";
		dbDelta( $sql_attachments );
	}
}

function fep_upload_dir( $upload ) {
	$upload['subdir']	= '/front-end-pm' . $upload['subdir'];
	$upload['path']		= $upload['basedir'] . $upload['subdir'];
	$upload['url']		= $upload['baseurl'] . $upload['subdir'];
	return $upload;
}
function fep_create_htaccess() {
	add_filter( 'upload_dir', 'fep_upload_dir', 99 );
	$wp_upload_dir = wp_upload_dir();
	remove_filter( 'upload_dir', 'fep_upload_dir', 99 );
		
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

function fep_install() {
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
	update_option( 'FEP_admin_options', $options );
	fep_create_htaccess();
}

fep_create_database();
fep_install();
