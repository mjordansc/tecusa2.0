<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Aelia Currency Switcher compatibility.
 *
 * @class          Iconic_WAS_Compat_Aelia_Currency_Switcher
 * @version        1.0.0
 * @category       Class
 * @author         Iconic
 */
class Iconic_WAS_Compat_Aelia_Currency_Switcher {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'plugins_loaded', array( __CLASS__, 'hooks' ) );
	}

	/**
	 * Indicates if the Aelia Currency Switcher is active.
	 *
	 * @return boolean
	 */
	protected static function is_aelia_cs_active() {
		return isset( $GLOBALS['woocommerce-aelia-currencyswitcher'] );
	}

	/**
	 * Hooks.
	 */
	public static function hooks() {
		// If the Aelia Currency Switcher is not active, stop here
		if ( ! self::is_aelia_cs_active() ) {
			return;
		}

		add_filter( 'wc_aelia_is_frontend', array( __CLASS__, 'wc_aelia_is_frontend' ), 10, 2 );
		add_filter( 'iconic_was_fees', array( __CLASS__, 'iconic_was_fees' ), 1, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'woocommerce_get_cart_item_from_session' ), 16, 3 );
		add_filter( 'iconic_was_calculate_totals_base_price', array( __CLASS__, 'iconic_was_calculate_totals_base_price' ), 10, 2 );
	}

	/**
	 * Checks if we're processing the Ajax actions used by WAS,
	 * to determine if we are on the frontend or the backend.
	 *
	 * This fixes the glitch of the currency symbol displayed by
	 * the plugin reflecting the last selected currency.
	 *
	 * @param bool   $is_frontend
	 * @param string $plugin_slug
	 *
	 * @return bool
	 */
	public static function wc_aelia_is_frontend( $is_frontend, $plugin_slug ) {
		if ( $is_frontend && defined( 'DOING_AJAX' ) ) {
			$ajax_action = strtolower( isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '' );
			$is_frontend = ! in_array(
				$ajax_action,
				array(
					'iconic_was_get_attribute_fields',
					'iconic_was_get_product_attribute_fields',
				)
			);
		}

		return $is_frontend;
	}

	/**
	 * Converts the fees introduced by WAS from
	 * shop's base currency to the active currency.
	 *
	 * @param array      $product_was_fees
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	public static function iconic_was_fees( $product_was_fees, $product ) {
		static $base_currency = null;

		// Cache shop's base currency, to avoid calling get_option() every time. The shop's
		// base currency never changes (unless someone uses a low-level filter to replace it,
		// but that's an edge case, which should never occur, as it's the wrong approach)
		if ( empty( $base_currency ) ) {
			$base_currency = get_option( 'woocommerce_currency' );
		}

		$active_currency = get_woocommerce_currency();

		foreach ( $product_was_fees as $attribute => $attr_values_fees ) {
			// Convert each fee from shop's base currency to the active currency
			$product_was_fees[ $attribute ] = array_map(
				function ( $fee_amount ) use ( $base_currency, $active_currency ) {
					return is_numeric( $fee_amount ) && ! empty( $fee_amount ) ? apply_filters( 'wc_aelia_cs_convert', $fee_amount, $base_currency, $active_currency ) : $fee_amount;
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
		// in the active currency
		unset( $cart_item['iconic_was_fee'] );

		return $cart_item;
	}

	/**
	 * Returns the base price of a product in the cart item,
	 * in the active currency.
	 *
	 * @param float      $product_base_price
	 * @param WC_Product $cart_product
	 *
	 * @return float|string
	 */
	public static function iconic_was_calculate_totals_base_price( $product_base_price, $cart_product ) {
		static $products_for_base_prices = array();

		// Cache a copy of the product instance. We can't use the $cart_product instance directly,
		// because its price could have been altered in the cart
		if ( isset( $products_for_base_prices[ $cart_product->get_id() ] ) ) {
			$product = $products_for_base_prices[ $cart_product->get_id() ];
		} else {
			$product = $products_for_base_prices[ $cart_product->get_id() ] = wc_get_product( $cart_product->get_id() );
		}

		// Let the Aelia Currency Switcher return the product's base price in the active currency
		return apply_filters(
			'wc_aelia_cs_get_product_base_price_in_currency',
			$product_base_price,
			$product,
			'price',
			get_woocommerce_currency()
		);
	}
}
