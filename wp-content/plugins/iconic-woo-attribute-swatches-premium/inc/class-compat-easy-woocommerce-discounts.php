<?php
/**
 * Compatibility with Discount Rules and Dynamic Pricing for WooCommerce plugin.
 *
 * @see https://www.asanaplugins.com/product/woocommerce-dynamic-pricing-and-discounts-plugin/
 * @package iconic-was
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Discount Rules and Dynamic Pricing for WooCommerce compatibility.
 *
 * @class Iconic_WAS_Compat_Easy_WooCommerce_Discounts
 */
class Iconic_WAS_Compat_Easy_WooCommerce_Discounts {
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
		if ( ! defined( 'WCCS_VERSION' ) ) {
			return;
		}

		add_filter( 'iconic_was_fees', array( __CLASS__, 'apply_discount_to_fees' ), 10, 2 );

		add_action(
			'woocommerce_before_calculate_totals',
			function () {
				remove_filter( 'iconic_was_fees', array( __CLASS__, 'apply_discount_to_fees' ) );
			},
			9
		);

		add_action(
			'woocommerce_before_calculate_totals',
			function () {
				add_filter( 'iconic_was_should_clear_static_fees_when_get_fees_by_attribute', '__return_true' );
				add_filter( 'iconic_was_fees', array( __CLASS__, 'apply_discount_to_fees' ), 10, 2 );
			},
			11
		);
	}

	/**
	 * Apply discount to the fees.
	 *
	 * @param array      $fees    The attribute swatches fees.
	 * @param WC_Product $product The variable product.
	 * @return array
	 */
	public static function apply_discount_to_fees( $fees, $product ) {
		if ( ! is_array( $fees ) ) {
			return $fees;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return $fees;
		}

		$variations = $product->get_children();

		if ( empty( $variations[0] ) ) {
			return $fees;
		}

		$variation = wc_get_product( $variations[0] );

		foreach ( $fees as $attribute_slug => $attributes ) {
			foreach ( $attributes as $attribute_key => $attribute_fee ) {
				if ( empty( $attribute_fee ) ) {
					continue;
				}

				// Set the price with the fee and let the Discount Rules and Dynamic Pricing for WooCommerce plugin applies the discount.
				$variation->set_price( $attribute_fee );
				$fees[ $attribute_slug ][ $attribute_key ] = WCCS()->product_helpers->wc_get_price( $variation, false );
			}
		}

		return $fees;
	}
}
