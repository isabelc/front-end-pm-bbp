<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fep_Cpt {

	private static $instance;
	
	public static function init() {
		if( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
		
	function actions_filters() {
		add_action( 'init', array( $this, 'create_cpt' ) );
		//add_action( 'contextual_help', array( $this, 'contextual_help' ), 10, 3 );
		add_action( 'save_post_fep_message', array( $this, 'save_message' ), 10, 3 );
		add_action( 'fep_save_message', array( $this, 'fep_save_message_to' ), 10, 3 );
		add_action( 'fep_save_message', array( $this, 'fep_save_message' ), 10, 3 );
		
		add_action( 'edit_form_after_title', array( $this, 'edit_form_after_title' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_filter( 'redirect_post_location', array( $this, 'redirect_post_location' ), 10, 2 );
		add_filter( 'manage_fep_message_posts_columns', array( $this, 'columns_head' ) );
		add_filter( 'post_row_actions', array( $this, 'view_link' ), 10, 2 );
		add_action( 'manage_fep_message_posts_custom_column', array( $this, 'columns_content' ), 10, 2);
		
		//add_action( 'post_submitbox_start', array( $this, 'post_submitbox_start_info' ) );
		//add_action( 'pre_get_posts', array( $this, 'sortable_orderby' ) );
	}

	function create_cpt() {
		/** fep_message Post Type */
		$labels = array(
			'name' 				=> _x( 'Messages', 'post type general name', 'front-end-pm' ),
			'singular_name' 	=> _x( 'Message', 'post type singular name', 'front-end-pm' ),
			'add_new' 			=> __( 'New Message', 'front-end-pm' ),
			'add_new_item' 		=> __( 'New Message', 'front-end-pm' ),
			'edit_item' 		=> __( 'Edit Message', 'front-end-pm' ),
			'new_item' 			=> __( 'New Message', 'front-end-pm' ),
			'all_items' 		=> __( 'All Messages', 'front-end-pm' ),
			'view_item' 		=> __( 'View Message', 'front-end-pm' ),
			'search_items' 		=> __( 'Search Message', 'front-end-pm' ),
			'not_found' 		=>  __( 'No Messages found', 'front-end-pm' ),
			'not_found_in_trash'=> __( 'No Messages found in Trash', 'front-end-pm' ),
			'parent_item_colon' => '',
			'menu_name' 		=> fep_is_pro() ? 'Front End PM PRO' : 'Front End PM'
		);
		$args = array(
			'labels' 			=> apply_filters( 'fep_message_cpt_labels', $labels ),
			'query_var' 		=> false,
			'rewrite' 			=> false,
			'show_ui' 			=> true,
			//'show_in_menu' 		=> true,
			'capability_type' 	=> 'fep_message',
			'capabilities' => array(
				'create_posts' => 'do_not_allow', //will be changed in next version to send message from BACK END
				 ),
			'map_meta_cap'      => true,
			'menu_icon'   		=> 'dashicons-email-alt',
			'supports' 			=> apply_filters( 'fep_message_cpt_supports', array( 'title', 'editor' ) ),
			'can_export'		=> true
		);
		register_post_type( 'fep_message', apply_filters( 'fep_message_cpt_args', $args )  );
	}
	function contextual_help( $contextual_help, $screen_id, $screen ) { 
		if ( 'fep_message' == $screen->id ) {
			$contextual_help = '<h2>Message</h2>
			<p>Test help.</p> 
			<p>Test help.</p>';
		} elseif ( 'edit-fep_message' == $screen->id ) {
			$contextual_help = '<h2>Editing Message</h2>
			<p>Test help.</p> 
			<p>Test help.</p>';
		}
		return $contextual_help;
	}

	function edit_form_after_title( $post ) {
		if( 'fep_message' != $post->post_type ) {
			return;
		}
		wp_nonce_field( 'fep_nonce', 'fep_nonce' );
	}

	function add_meta_boxes() {
		add_meta_box( 
			'fep_message_to_box',
			__( 'Message To', 'front-end-pm' ),
			array( $this, 'fep_message_to_box_content' ),
			'fep_message',
			'side',
			'high'
		);
		remove_meta_box( 'slugdiv', 'fep_message', 'normal' );
		 //remove_meta_box( 'submitdiv', 'fep_message', 'core' );
	}
	function fep_message_to_box_content( $post ) {
		if ( isset( $_GET['action'])  && $_GET['action'] == 'edit' ) {
			$participants = fep_get_participants( $post->ID );
			if( $participants ) {
				foreach( $participants as $participant ) {
					if( $participant != $post->post_author )
					echo '<a href="'. get_edit_user_link( $participant ) .'" target="_blank">'. esc_attr( fep_user_name( $participant ) ) .'</a><br />';
				}
			}
			echo '<hr />';
			echo '<h2><strong>' . __( 'Sender', 'front-end-pm' ) . '</strong></h2>';
			echo '<a href="' . get_edit_user_link( $post->post_author ) .'" target="_blank">'. esc_html( fep_user_name( $post->post_author ) ) . '</a>';
		} else {
			$parent = ( !empty( $_REQUEST['fep_parent_id'] ) ) ? absint( $_REQUEST['fep_parent_id'] ) : '';
			$to 	= ( !empty( $_REQUEST['fep_to'] ) ) ? $_REQUEST['fep_to'] : '';
			if( $parent ) {
				echo 'You are replying to <a href="'.fep_query_url( 'viewmessage', array( 'fep_id' => $parent ) ).'" title="" target="_blank">' . $parent . '</a>';
				echo '<input type="hidden" name="fep_parent_id" value="' . $parent . '" />';
			} else {
				wp_register_script( 'fep-script', FEP_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), '3.1', true );
				wp_localize_script( 'fep-script', 'fep_script', 
					array( 
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce' => wp_create_nonce( 'fep-autosuggestion' ),
					) 
				);
				wp_enqueue_script( 'fep-script' ); ?>
								
				<input type="hidden" name="message_to" id="fep-message-to" autocomplete="off" value="<?php echo fep_get_userdata( $to, 'user_login' ); ?>" />		
				<input type="text" name="message_top" id="fep-message-top" autocomplete="off" value="<?php echo fep_user_name( fep_get_userdata( $to, 'ID' ) ); ?>" />
				<img src="<?php echo FEP_PLUGIN_URL; ?>assets/images/loading.gif" class="fep-ajax-img" style="display:none;"/>
				<div id="fep-result"></div><?php
			} 
		}
	}

	function fep_save_message_to( $message_id, $message, $update ){
		if( ! empty( $_REQUEST['message_to'] ) ) { //BACK END message_to return login of participants
			if( is_array( $_REQUEST['message_to'] ) ) {
				foreach( $_REQUEST['message_to'] as $participant ) {
					add_post_meta( $message_id, '_fep_participants', fep_get_userdata( $participant, 'ID', 'login' ) );
				}
			} else {
				add_post_meta( $message_id, '_fep_participants', fep_get_userdata( $_REQUEST['message_to'], 'ID', 'login' ) );
			}
			add_post_meta( $message_id, '_fep_participants', $message->post_author );
			
			unset( $_REQUEST['message_to'] );
		}
	}

	function fep_save_message( $message_id, $message, $update ){
		if( ! empty( $_REQUEST['fep_parent_id'] ) ) {
			remove_action( 'fep_save_message', array( $this, 'fep_save_message' ), 10, 3 );
			wp_update_post(
				array(
					'ID' => $message_id, 
					'post_parent' => absint( $_REQUEST['fep_parent_id'])
				)
			);
			unset( $_REQUEST['fep_parent_id'] );
			add_action( 'fep_save_message', array( $this, 'fep_save_message' ), 10, 3 );
		}
	}
	function post_submitbox_start_info() {
		global $post;
		if('fep_message' != $post->post_type) {
			return;
		}
		echo 'Can NOT be edited once published';
	}

	function save_message( $message_id, $message, $update ) {
		if ( ! is_admin() ) return; //only for BACK END . for FRONT END use 'fep_action_message_after_send' action hook
		if ( empty( $message_id ) || empty( $message ) || empty( $_POST ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( is_admin() && ( empty( $_POST['fep_nonce']) || ! wp_verify_nonce( $_POST['fep_nonce'], 'fep_nonce' ) ) ) return;
		if ( wp_is_post_revision( $message ) ) return;
		if ( wp_is_post_autosave( $message ) ) return;
		//if ( ! current_user_can( 'edit_fep_messages' ) ) return;
		if ( ! current_user_can( 'edit_fep_message', $message_id ) && ! current_user_can( 'delete_fep_message', $message_id ) ) return;
		//if ( $message->post_type != 'fep_message' ) return;
		
		do_action( 'fep_save_message', $message_id, $message, $update );
	}
	function view_link( $actions, $post ) {
		if ( $post->post_type=='fep_message' ) {
			$actions['fep_view'] = '<a href="'.fep_query_url( 'viewmessage', array( 'fep_id' => $post->ID ) ).'" title="" target="_blank">' . __("View", "front-end-pm") . '</a>';
			$actions['fep_reply'] = '<a href="'.fep_query_url( 'viewmessage', array( 'fep_id' => $post->ID ) ).'#fep-reply-form" title="" target="_blank">' . __("Reply", "front-end-pm") . '</a>';
		}
		return $actions;
	}

	function columns_head( $defaults) {
		$defaults['author'] = __( 'From', 'front-end-pm' );
		$defaults['participants'] = __( 'To', 'front-end-pm' );
		$defaults['parent'] = __( 'Parent', 'front-end-pm' );
		return $defaults;
	}
	function columns_content( $column_name, $post_ID) {
		global $post;
		if ( $column_name == 'parent' ) {
			$parent = $post->post_parent;
			if( $parent ) {
				echo '<a href="'.fep_query_url( 'viewmessage', array( 'fep_id' => $parent ) ).'" title="" target="_blank">' . esc_attr( $parent ) . '</a>';
			} else {
				_e( 'No Parent', 'front-end-pm' );
			}
		}
		if ( $column_name == 'participants' ) {
			$participants = fep_get_participants( $post_ID );
			$out = '';
			if( $participants ) {
				foreach( $participants as $participant ) {
					if( $participant != $post->post_author ){
						$out .= '<a href="'. get_edit_user_link( $participant ) .'" target="_blank">'. esc_attr( fep_user_name( $participant ) ) .'</a><br />';
					}
				}
			} else {
				$out .= __('No Participants', 'front-end-pm');
			}
			echo apply_filters( 'fep_filter_cpt_display_participants', $out );
		}
	}
	function sortable_column( $columns ) {
		$columns['parent'] = 'parent';
	 
		return $columns;
	}
	function sortable_orderby( $query ) {
		if( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) != 'fep_message' ){
			return false;
		}	 
		$orderby = $query->get( 'orderby' );
		if( 'parent' == $orderby ) {
			//$query->set( 'meta_key','_fep_parent_id' );
			//$query->set( 'orderby','meta_value_num' );
			//$query->set( 'orderby','parent' );
		}
	}
	/**
	 * Redirect to the edit.php on post save or publish.
	 */
	function redirect_post_location( $location, $post_id ) {
		if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) {
			$post_type = get_post_type( $post_id );
			if ( 'fep_message' == $post_type ){
				return admin_url( "edit.php?post_type=fep_message" );
			}			
		}
		return $location;
	}
}

add_action( 'init', array( Fep_Cpt::init(), 'actions_filters' ), 5 );
