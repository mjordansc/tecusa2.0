<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * OceanWP compatibility.
 *
 * @class          Iconic_WAS_Compat_Oceanwp
 * @version        1.0.0
 * @category       Class
 * @author         Iconic
 */
class Iconic_WAS_Compat_Oceanwp {

	/**
	 * Run.
	 */
	public static function run() {

		$current_theme = wp_get_theme();

		if ( $current_theme->template !== 'oceanwp' ) {
			return;
		}

		add_action( 'init', array( __CLASS__, 'position_swatches_in_loop' ) );
	}

	/**
	 * Position swatches in loop
	 */
	public static function position_swatches_in_loop() {
		global $iconic_was;

		// remove the old filters.
		$loop_position = apply_filters( 'iconic_was_loop_position', 'woocommerce_after_shop_loop_item' );
		$loop_priority = apply_filters( 'iconic_was_loop_priority', 8 );
		remove_action( $loop_position, array( $iconic_was->products_class(), 'add_swatches_to_loop' ), $loop_priority );

		// add new to the ocean theme.
		add_action( 'ocean_after_archive_product_item', array( $iconic_was->products_class(), 'add_swatches_to_loop' ) );
	}
}
