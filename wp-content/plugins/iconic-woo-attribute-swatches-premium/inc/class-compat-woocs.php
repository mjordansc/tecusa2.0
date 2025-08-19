<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCS compatibility.
 */
class Iconic_WAS_Compat_WooCS {

	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'plugins_loaded', array( __CLASS__, 'hooks' ) );
	}

	/**
	 * Hooks.
	 */
	public static function hooks() {
		// PHPCS:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		global $WOOCS;

		if ( empty( $WOOCS ) || is_admin() ) {
			return;
		}

		add_filter( 'iconic_was_fees', array( __CLASS__, 'currency_convert_fees' ), 10, 2 );
		add_filter( 'woocommerce_before_calculate_totals', array( __CLASS__, 'remove_currency_convert_fees' ) );
		add_filter( 'woocommerce_after_calculate_totals', array( __CLASS__, 'add_currency_convert_fees' ) );
		add_filter( 'iconic_was_cart_item_price', array( __CLASS__, 'currency_convert_cart_item_price' ), 10, 3 );
	}

	/**
	 * Convert fees.
	 *
	 * @param array      $fees    Fees.
	 * @param WC_Product $product Product.
	 *
	 * @return array $fees Updated fees.
	 */
	public static function currency_convert_fees( $fees, $product ) {
		global $WOOCS;

		foreach ( $fees as $attribute_key => &$attribute_fees ) {
			foreach ( $attribute_fees as $term => $term_fee ) {
				$attribute_fees[ $term ] = floatval( $WOOCS->woocs_exchange_value( $term_fee ) );
			}
		}

		return $fees;
	}

	/**
	 * Don't convert fees when calculating cart totals.
	 * WOOCS does this automatically.
	 */
	public static function remove_currency_convert_fees() {
		remove_filter( 'iconic_was_fees', array( __CLASS__, 'currency_convert_fees' ), 10 );
	}

	/**
	 * Add the fee conversion back in after calculating totals.
	 */
	public static function add_currency_convert_fees() {
		add_filter( 'iconic_was_fees', array( __CLASS__, 'currency_convert_fees' ), 10, 2 );
	}

	/**
	 * Convert the prices in the cart.
	 *
	 * @param float  $cart_item_price The cart item price including fee(s).
	 * @param array  $cart_item       Cart item data.
	 * @param string $cart_item_key   Cart item key.
	 */
	public static function currency_convert_cart_item_price( $cart_item_price, $cart_item, $cart_item_key ) {
		global $WOOCS;

		$cart_item_price = floatval( $WOOCS->woocs_exchange_value( $cart_item_price ) );

		return $cart_item_price;
	}
}
