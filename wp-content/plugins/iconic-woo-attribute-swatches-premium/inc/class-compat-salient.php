<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Salient compatibility.
 *
 * @class          Iconic_WAS_Compat_Salient
 * @version        1.0.0
 * @category       Class
 * @author         Iconic
 */
class Iconic_WAS_Compat_Salient {
	/**
	 * Run.
	 */
	public static function run() {
		$current_theme = wp_get_theme();

		if ( $current_theme->template !== 'salient' ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_styles' ) );
	}

	/**
	 * Frontend styles.
	 */
	public static function frontend_styles() {
		wp_register_style( 'iconic-was-salient-styles', ICONIC_WAS_URL . 'assets/frontend/css/salient.min.css', array( 'iconic-was-styles' ), Iconic_Woo_Attribute_Swatches::$version );

		wp_enqueue_style( 'iconic-was-salient-styles' );
	}
}
