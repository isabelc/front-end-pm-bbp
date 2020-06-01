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
		add_action( 'admin_enqueue_scripts', array( $this, 'fep_update_script' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'install' ), 5 );
		add_action( 'admin_init', array( $this, 'message_view_changed' ), 25 );
		add_action( 'admin_init', array( $this, 'auto_update' ), 30 );
		add_action( 'wp_ajax_fep_update_ajax', array( $this, 'ajax' ) );
	}
	
	function fep_update_script() {
		wp_register_script( 'fep_update_script', FEP_PLUGIN_URL . 'assets/js/fep_update_script.js', array( 'jquery' ), FEP_PLUGIN_VERSION, true );
	}
	
	function admin_menu(){
		add_submenu_page( 'fep-non-exist-menu', 'Front End PM - ' . __( 'Update', 'front-end-pm' ), __( 'Update', 'front-end-pm' ), apply_filters( 'fep_admin_cap', 'manage_options' ), 'fep_update', array( $this, 'update_page' ) );
	}
	
	function update_page() {
		wp_enqueue_script( 'fep_update_script' );
		?>
		<div class="wrap">
			<h2><?php printf(__( '%s update', 'front-end-pm' ), fep_is_pro() ? 'Front End PM PRO' : 'Front End PM' ); ?></h2>
			<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'front-end-pm' ) ?></em></p></noscript>
			<div id="fep-update-warning">
				<strong><?php _e( 'DO NOT close this window. This may take a while.', 'front-end-pm' ); ?></strong>
			</div>
			<div>
				<img src="<?php echo FEP_PLUGIN_URL; ?>assets/images/loading.gif" class="fep-ajax-img hidden" />
			</div>
			<div>
				<button class="fep-start-update button-primary hide-if-no-js"><?php _e( 'Start Update', 'front-end-pm' ); ?></button>
			</div>
			<div id="fep-ajax-response"></div>
		</div>
		<?php
	}
	
	function install() {
		if ( false !== fep_get_option( 'plugin_version', false ) ) {
			return;
		}
		global $wpdb;
		$roles = array_keys( get_editable_roles() );
		$id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[front-end-pm%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1" );
		$options = array();
		$options['userrole_access'] = $roles;
		$options['userrole_new_message'] = $roles;
		$options['userrole_reply'] = $roles;
		$options['plugin_version'] = get_option( 'FEP_admin_options' ) ? '4.0' : FEP_PLUGIN_VERSION;
		$options['page_id'] = $id;
		fep_update_option( $options );
		//fep_add_caps_to_roles();
		$this->create_htaccess();
	}
	function message_view_changed() {
		if ( get_option( '_fep_message_view_changed' ) ) {
			add_filter( 'fep_update_enable_version_check', '__return_false' );
			add_filter( 'fep_require_manual_update', '__return_true' );
			add_action( 'fep_plugin_manual_update', array( $this, 'individual_to_threaded' ) );
		}
	}

	function auto_update() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( isset( $_GET['page'] ) && 'fep_update' == $_GET['page'] ) {
			return;
		}
		$prev_ver = fep_get_option( 'plugin_version', '3.3' );
		if ( version_compare( $prev_ver, FEP_PLUGIN_VERSION, '=' ) && apply_filters( 'fep_update_enable_version_check', true ) ) {
			return;
		}
		$require = false;

		if ( version_compare( $prev_ver, '10.1.1', '<' ) ) {
			$require = true;
		}
		if ( apply_filters( 'fep_require_manual_update', $require, $prev_ver ) ) {
			add_action( 'admin_notices', array( $this, 'notice_update' ) );
		} else {
			do_action( 'fep_plugin_auto_update', $prev_ver );
			do_action( 'fep_plugin_update', $prev_ver );
			fep_update_option( 'plugin_version', FEP_PLUGIN_VERSION );
		}
	}
	
	public function notice_update() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['page'] ) && 'fep_update' == $_GET['page'] ) {
			return;
		}
		?>
		<div class="notice notice-info">
			<p><?php printf( __( '%s needs to database update.', 'front-end-pm' ), fep_is_pro() ? 'Front End PM PRO' : 'Front End PM' ); ?></p>
			<p>
				<a href="<?php echo add_query_arg( 'page', 'fep_update', admin_url( 'admin.php' ) ); ?>" class="button button-primary"><?php _e( 'Proceed', 'front-end-pm' ); ?></a>
			</p>
		</div>
		<?php
	}

	function ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$response = array(
				'update'  => 'completed',
				'message' => __( 'You do not have permission to trigger update.', 'front-end-pm' ),
			);
			wp_send_json( $response );
		}
		$prev_ver = fep_get_option( 'plugin_version', '3.3' );
		if ( version_compare( $prev_ver, '4.1', '<' ) ) {
			//$this->update_version_41();
			$response = array(
				'update'	=> 'completed',
				'message'	=> __( 'You cannot update from your current version.', 'front-end-pm' ),
			);
			wp_send_json( $response );
		}
		ignore_user_abort( true );
		if ( ! fep_is_func_disabled( 'set_time_limit' ) ) {
			set_time_limit( 3600 );
		}
		do_action( 'fep_plugin_manual_update', $prev_ver );
		do_action( 'fep_plugin_update', $prev_ver );
		fep_update_option( 'plugin_version', FEP_PLUGIN_VERSION );
		$response = array(
			'update'	=> 'completed',
			'message'	=> __( 'Update completed.', 'front-end-pm' ),
		);
		wp_send_json( $response );
	}
	function individual_to_threaded() {
		global $wpdb;
		$custom_int = isset( $_POST['custom_int'] ) ? absint( $_POST['custom_int'] ) : 0;

		$args = array(
			'mgs_type'		=> 'message',
			'mgs_parent'	=> 0,
			'mgs_status'	=> 'publish',
			'per_page'		=> 50,
			'orderby'		=> 'mgs_created',
			'mgs_last_reply_by' => 0,
			'fields'		=> array( 'mgs_id' ),
		);
		$messages = fep_get_messages( $args );
		if ( $messages ) {
			foreach ( $messages as $mgs_id ) {
				fep_update_reply_info( $mgs_id );
			}
			$custom_int = $custom_int + count( $messages );
			$response = array(
				'update'	=> 'continue',
				'message'	=> sprintf( _n( '%s message meta updated', '%s messages meta updated', $custom_int, 'front-end-pm' ), number_format_i18n( $custom_int ) ),
				'custom_int'=> $custom_int,
				'custom_str'=> '',
			);
			wp_send_json( $response );
		}
		delete_metadata( 'user', 0, '_fep_user_message_count', '', true );
		update_option( '_fep_message_view_changed', 0 );
	}
	function insert_message( $message ) {
		$arr = array(
			'mgs_id'                => $message->ID,
			'mgs_parent'            => 0,
			'mgs_author'            => $message->post_author,
			'mgs_created'           => $message->post_date_gmt,
			'mgs_title'             => $message->post_title,
			'mgs_content'           => $message->post_content,
			'mgs_type'              => 'message',
			'mgs_status'            => $message->post_status,
		);
		if( 'threaded' == fep_get_message_view() ){
			$arr['mgs_last_reply_by'] = get_post_meta( $message->ID, '_fep_last_reply_by', true );
			$arr['mgs_last_reply_excerpt'] = fep_get_the_excerpt_from_content( 100, get_post_field('post_content', get_post_meta( $message->ID, '_fep_last_reply_id', true ) ) );
			$arr['mgs_last_reply_time'] = get_post_field('post_date_gmt', get_post_meta( $message->ID, '_fep_last_reply_id', true ) );
		}
		$mgs_obj = new FEP_Message;
		$message_id = $mgs_obj->insert( $arr );
		
		if( ! $message_id ){
			return 0;
		}
		$count = 1;
		
		add_post_meta( $message->ID, '_fep_new_id', $message_id, true );
		fep_add_meta( $message_id, '_fep_email_sent', get_post_meta( $message->ID, '_fep_email_sent', true ) );
		
		$this->insert_participants( $message->ID, $mgs_obj );
		$this->insert_attachment( $message->ID, $mgs_obj );
		$this->insert_meta( $message->ID, $message_id );
		$count += $this->insert_replies( $message->ID, $mgs_obj );
		//$this->delete_message( $message->id );
		return $count;
	}

	function insert_replies( $message_id, $mgs_obj ) {
		$args = array(
			'post_type'		=> 'fep_message',
			'posts_per_page'=> -1,
			'post_status'	=> 'any',
			'post_parent'	=> $message_id,
		);
		
		if ( ! $replies = get_posts( $args ) ) {
			return 0;
		}
		foreach ( $replies as $reply ) {
			$arr = array(
				'mgs_id'                => $reply->ID,
				'mgs_parent'            => $mgs_obj->mgs_id,
				'mgs_author'            => $reply->post_author,
				'mgs_created'           => $reply->post_date_gmt,
				'mgs_title'             => $reply->post_title,
				'mgs_content'           => $reply->post_content,
				'mgs_type'              => 'message',
				'mgs_status'            => $reply->post_status,
			);
			$reply_obj = new FEP_Message;
			$reply_id = $reply_obj->insert( $arr );

			if ( $reply_id ) {
				add_post_meta( $reply->ID, '_fep_new_id', $reply_id, true );
				fep_add_meta( $reply_id, '_fep_email_sent', get_post_meta( $reply->ID, '_fep_email_sent', true ) );
				$this->insert_participants( $reply->ID, $reply_obj );
				$this->insert_attachment( $reply->ID, $reply_obj );
				$this->insert_meta( $reply->ID, $reply_id );
			}
		}
		return count( $replies );
	}
	
	function insert_attachment( $message_id, $mgs_obj ) {
		$args = array(
			'post_type'		=> 'attachment',
			'posts_per_page'=> -1,
			'post_status'	=> 'any',
			'post_parent'	=> $message_id,
		);
		if ( ! $attachments = get_posts( $args ) ) {
			return;
		}
		$new_attachments = [];
		
		foreach ( $attachments as $attachment ) {
			$new_attachments[] = [
				'att_mime' => $attachment->post_mime_type,
				'att_file' => get_attached_file( $attachment->ID ),
				'att_status' => in_array( $attachment->post_status, [ 'publish', 'inherit' ] ) ? 'publish' : $attachment->post_status,
			];
		}
		if( $new_attachments ){
			$mgs_obj->insert_attachments( $new_attachments );
		}
	}
	
	function insert_participants( $message_id, $mgs_obj ){
		if( 'threaded' == fep_get_message_view() && $mgs_obj->mgs_parent ){
			$participants = get_post_meta( wp_get_post_parent_id( $message_id ), '_fep_participants' );
		} else {
			$participants = get_post_meta( $message_id, '_fep_participants' );
		}
		$new_participants = [];
		$read_by = get_post_meta( $message_id, '_fep_read_by', true );
		if( ! is_array( $read_by ) ){
			$read_by = [];
		} else {
			$read_by = array_flip( $read_by );
		}
		
		foreach( $participants as $participant ){
			$args = [
				'mgs_participant' => $participant,
				'mgs_read' => isset( $read_by[ $participant ] ) ? $read_by[ $participant ] : 0,
				'mgs_deleted' => get_post_meta( $message_id, '_fep_delete_by_' . $participant, true ),
				'mgs_archived' => get_post_meta( $message_id, '_fep_archived_by_' . $participant, true ),
			];
			if( 'threaded' == fep_get_message_view() && $mgs_obj->mgs_parent ){
				$args['mgs_parent_read'] = get_post_meta( wp_get_post_parent_id( $message_id ), '_fep_parent_read_by_' . $participant, true );
			} else {
				$args['mgs_parent_read'] = get_post_meta( $message_id, '_fep_parent_read_by_' . $participant, true );
			}
			$new_participants[] = $args;
		}
		if( $new_participants ){
			$mgs_obj->insert_participants( $new_participants );
		}
	}
	
	function insert_meta( $prev_id, $new_id ) {
		if( ! $prev_id || ! is_numeric( $prev_id ) || ! $new_id || ! is_numeric( $new_id ) ) {
			return false;
		}
		$meta = get_post_meta( $prev_id );
		if ( ! $meta || ! is_array( $meta ) ) {
			return false;
		}
		unset( $meta['_fep_read_by'], $meta['_fep_deleted_by'], $meta['_fep_participants'], $meta['_fep_participant_roles'], $meta['_fep_email_sent'], $meta['_fep_new_id'], $meta['_fep_last_reply_by'], $meta['_fep_last_reply_id'], $meta['_fep_last_reply_time'] );
		
		foreach ( $meta as $meta_key => $meta_values ) {
			if ( 0 === strpos( $meta_key, '_fep_parent_read_by_' ) || 0 === strpos( $meta_key, '_fep_delete_by_' ) || 0 === strpos( $meta_key, '_fep_archived_by_' ) ) {
				continue;
			}
			foreach ( $meta_values as $meta_value ) {
				fep_add_meta( $new_id, $meta_key, $meta_value );
			}
		}
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

