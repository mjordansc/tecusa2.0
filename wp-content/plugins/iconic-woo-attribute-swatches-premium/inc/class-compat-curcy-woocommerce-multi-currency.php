<?php
/**
 * Compatibility with CURCY - WooCommerce Multi Currency plugin.
 *
 * @see https://villatheme.com/extensions/woo-multi-currency/
 * @package Iconic_Woo_Bundled_Products
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CURCY – WooCommerce Multi Currency compatibility Class.
 *
 * @class Iconic_WAS_Compat_Curcy_WooCommerce_Multi_Currency
 */
class Iconic_WAS_Compat_Curcy_WooCommerce_Multi_Currency {
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
		if ( ! defined( 'WOOMULTI_CURRENCY_VERSION' ) ) {
			return;
		}

		add_filter( 'iconic_was_fees', array( __CLASS__, 'maybe_update_fees' ), 10, 2 );

		add_action(
			'woocommerce_before_calculate_totals',
			function () {
				remove_filter( 'iconic_was_fees', array( __CLASS__, 'maybe_update_fees' ) );
			},
			9
		);

		add_action(
			'woocommerce_before_calculate_totals',
			function () {
				add_filter( 'iconic_was_should_clear_static_fees_when_get_fees_by_attribute', '__return_true' );
				add_filter( 'iconic_was_fees', array( __CLASS__, 'maybe_update_fees' ), 10, 2 );
			},
			11
		);

		add_filter( 'iconic_was_cart_item_price', array( __CLASS__, 'apply_curcy' ) );
	}

	/**
	 * Apply CURCY to update the cart item price based on the selected currency.
	 *
	 * @param float $cart_item_price The cart item price including fee(s).
	 * @return float
	 */
	public static function apply_curcy( $cart_item_price ) {
		if ( empty( $cart_item_price ) ) {
			return $cart_item_price;
		}

		if ( ! function_exists( 'wmc_get_price' ) ) {
			return $cart_item_price;
		}

		return wmc_get_price( $cart_item_price );
	}

	/**
	 * Maybe update fees based on the selected currency.
	 *
	 * @param array      $fees    The attribute swatches fees.
	 * @param WC_Product $product The variable product.
	 * @return array
	 */
	public static function maybe_update_fees( $fees, $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $fees;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return $fees;
		}

		if ( ! is_array( $fees ) ) {
			return $fees;
		}

		if ( ! function_exists( 'wmc_get_price' ) ) {
			return $fees;
		}

		foreach ( $fees as $attribute_slug => $values ) {
			if ( ! is_array( $values ) ) {
				continue;
			}

			$fees[ $attribute_slug ] = array_map(
				function ( $fee ) {
					if ( ! is_numeric( $fee ) ) {
						return $fee;
					}

					return wmc_get_price( $fee );
				},
				$values
			);
		}

		return $fees;
	}
}
