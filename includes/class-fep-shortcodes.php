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
		add_shortcode( 'front-end-pm', array( fep_main_class::init(), 'main_shortcode_output' ) ); //for FRONT END PM
	}
} //END CLASS
add_action( 'init', array( Fep_Shortcodes::init(), 'actions_filters' ) );
