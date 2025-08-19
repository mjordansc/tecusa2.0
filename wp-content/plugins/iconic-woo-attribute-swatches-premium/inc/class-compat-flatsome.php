<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Flatsome compatibility.
 *
 * @class          Iconic_WAS_Compat_Flatsome
 * @version        1.0.0
 * @category       Class
 * @author         Iconic
 */
class Iconic_WAS_Compat_Flatsome {
	/**
	 * Run.
	 */
	public static function run() {
		$current_theme = wp_get_theme();

		if ( $current_theme->template !== 'flatsome' ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_styles' ) );
	}

	/**
	 * Frontend styles.
	 */
	public static function frontend_styles() {
		wp_register_style( 'iconic-was-flatsome-styles', ICONIC_WAS_URL . 'assets/frontend/css/flatsome.min.css', array( 'iconic-was-styles' ), Iconic_Woo_Attribute_Swatches::$version );

		wp_enqueue_style( 'iconic-was-flatsome-styles' );
	}
}
