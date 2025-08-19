<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Fees and Discounts compatibility.
 */
class Iconic_WAS_Compat_Woo_Fees_And_Discounts {

	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'modify_calculate_totals' ), 20 );
	}

	/**
	 * Remove and re-add calculate_totals so that we handle
	 * fee calculation before WCFAD, which is hooked in at
	 * priority 2.
	 */
	public static function modify_calculate_totals() {
		if ( ! defined( 'WCFAD_PLUGIN_VERSION' ) || is_admin() ) {
			return;
		}

		remove_action( 'woocommerce_before_calculate_totals', array( 'Iconic_WAS_Fees', 'calculate_totals' ), 10 );
		add_action( 'woocommerce_before_calculate_totals', array( 'Iconic_WAS_Fees', 'calculate_totals' ), 1 );
	}
}
