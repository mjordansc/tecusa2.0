<?php
/**
 * Add shortcodes.
 *
 * @class   Iconic_WAS_Shortcodes
 * @package Iconic_WAS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Iconic_WAS_Shortcodes class.
 */
class Iconic_WAS_Shortcodes {
	/**
	 * Run.
	 */
	public static function run() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		add_shortcode( 'iconic_was_catalog_swatches', array( __CLASS__, 'catalog_swatches' ) );
	}

	/**
	 * Display catalog swatches.
	 */
	public static function catalog_swatches() {
		global $iconic_was, $product;

		if ( ! $iconic_was || ! $product ) {
			return;
		}

		ob_start();
		$iconic_was->products_class()->add_swatches_to_loop();

		return ob_get_clean();
	}
}
