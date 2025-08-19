<?php
/**
 * Class: WooCommerce_Multilingual compatibility.
 *
 * @package iconic-was
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Multilingual compatibility.
 *
 * @class Iconic_WAS_Compat_WCML
 */
class Iconic_WAS_Compat_WCML {
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
		if ( ! defined( 'WCML_VERSION' ) ) {
			return;
		}

		global $woocommerce_wpml;

		if ( ! $woocommerce_wpml ) {
			return;
		}

		$settings = $woocommerce_wpml->get_settings();

		if ( empty( $settings['currency_switcher_product_visibility'] ) ) {
			return;
		}

		add_filter( 'iconic_was_fees', array( __CLASS__, 'iconic_was_fees' ), 1, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'woocommerce_get_cart_item_from_session' ), 16, 3 );
	}

	/**
	 * Converts the fees introduced by WAS from
	 * shop's base currency to the active currency.
	 *
	 * @param array      $product_was_fees Fees for each attribute.
	 * @param WC_Product $product          WC_Product object.
	 *
	 * @return array
	 */
	public static function iconic_was_fees( $product_was_fees, $product ) {
		foreach ( $product_was_fees as $attribute => $attr_values_fees ) {
			$product_was_fees[ $attribute ] = array_map(
				function ( $fee_amount ) {
					/**
					 * Filter a raw price/fee value and return the converted
					 * price using the current front-end currency.
					 *
					 * @see https://wpml.org/wcml-hook/wcml_raw_price_amount/
					 * @since 1.12.0
					 * @param integer|float $price    The price/fee to be converted.
					 * @param string        $currency The current currency e.g GBP.
					 */
					return is_numeric( $fee_amount ) && ! empty( $fee_amount ) ? apply_filters( 'wcml_raw_price_amount', $fee_amount ) : $fee_amount;
				},
				$attr_values_fees
			);
		}

		return $product_was_fees;
	}

	/**
	 * Converts product prices when they are loaded
	 * from the user's session. This is required for
	 * compatibility with some 3rd party plugins, which
	 * will need this information to perform their duty.
	 *
	 * @param array  $cart_item The cart item, which contains, amongst other things, the product added to cart.
	 * @param array  $values The values associated to the cart item key.
	 * @param string $key The cart item key.
	 *
	 * @return array The processed cart item, with the product prices converted in the selected currency.
	 */
	public static function woocommerce_get_cart_item_from_session( $cart_item, $values, $key ) {
		// Remove the fees cached by the Attribute Swatches, so that they can be recalculated
		// in the active currency.
		unset( $cart_item['iconic_was_fee'] );

		return $cart_item;
	}
}
