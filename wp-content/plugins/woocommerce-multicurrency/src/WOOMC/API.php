<?php
/**
 * Public methods.
 *
 * @since 1.17.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use WOOMC\DAO\Factory;

/**
 * Class API
 *
 * @package WOOMC
 */
class API {

	/**
	 * Currency conversion.
	 *
	 * @since 1.17.0
	 *
	 * @param int|float|string $value The value to convert.
	 * @param string           $to    Currency to convert to.
	 * @param string           $from  Currency to convert from.
	 *
	 * @return int|float|string
	 */
	public static function convert( $value, $to, $from ) {

		$rate_storage  = new Rate\Storage();
		$price_rounder = new Price\Rounder();
		$calculator    = new Price\Calculator( $rate_storage, $price_rounder );

		return $calculator->calculate( $value, $to, $from );
	}

	/**
	 * Raw currency conversion.
	 *
	 * @since 1.17.0
	 *
	 * @param int|float|string $value The value to convert.
	 * @param string           $to    Currency to convert to.
	 * @param string           $from  Currency to convert from.
	 *
	 * @return int|float|string
	 */
	public static function convert_raw( $value, $to, $from ) {

		$rate_storage  = new Rate\Storage();
		$price_rounder = new Price\Rounder();
		$calculator    = new Price\Calculator( $rate_storage, $price_rounder );

		return $calculator->calculate_raw( $value, $to, $from );
	}

	/**
	 * The base ("Store") currency, unfiltered.
	 *
	 * @since 1.19.0
	 * @return string
	 */
	public static function default_currency() {
		return \get_option( 'woocommerce_currency' );
	}

	/**
	 * Currently active currency.
	 *
	 * @since 2.11.0-rc.1
	 * @return string
	 */
	public static function active_currency() {
		return \get_woocommerce_currency();
	}

	/**
	 * List of enabled currency codes, including the store currency.
	 *
	 * @since 1.19.0
	 * @return string[]
	 */
	public static function enabled_currencies() {
		return Factory::getDao()->getEnabledCurrencies();
	}

	/**
	 * Returns true if currency is in the list of enabled.
	 *
	 * @since 2.14.1-rc.3
	 *
	 * @param string $currency Currency code.
	 *
	 * @return bool
	 */
	public static function is_currency_enabled( $currency ) {
		return in_array( strtoupper( $currency ), self::enabled_currencies(), true );
	}

	/**
	 * List of enabled currency codes, excluding the store currency.
	 *
	 * @since 1.19.0
	 * @return array
	 */
	public static function extra_currencies() {
		return array_diff( self::enabled_currencies(), (array) self::default_currency() );
	}

	/**
	 * List of enabled currency codes, excluding the currently active currency.
	 *
	 * @since 2.12.0-beta.1
	 * @return array
	 */
	public static function inactive_currencies() {
		return array_diff( self::enabled_currencies(), (array) self::active_currency() );
	}

	/**
	 * All WooCommerce's currencies in the form Code => Name.
	 *
	 * @since 2.5.0
	 * @return string[]
	 */
	public static function currency_names() {
		return \get_woocommerce_currencies();
	}

	/**
	 * Returns true if the currently selected currency is the store base currency.
	 *
	 * @since 2.8.5
	 * @return bool
	 */
	public static function is_default_currency_active() {
		return self::default_currency() === self::active_currency();
	}

	/**
	 * Is price per product allowed?
	 *
	 * @since 2.11.0-rc.1
	 * @return bool
	 */
	public static function is_custom_pricing_enabled() {
		return Factory::getDao()->isAllowPricePerProduct();
	}

	/**
	 * Get custom price.
	 *
	 * @since 2.10.0
	 * @since 3.4.2-1 Moved to API and made public.
	 *
	 * @param \WC_Product $product     The product object.
	 * @param             $price_type
	 * @param bool        $include_tax Return price with tax?
	 *
	 * @return false|float|string False if product has no custom prices, not regular and not sale.
	 */
	public static function get_custom_price( $product, $price_type = '_price', $include_tax = false ) {

		if ( ! self::is_custom_pricing_enabled() ) {
			return false;
		}

		$product_info = new Product\Info( $product );
		$custom_price = $product_info->get_custom_price( $price_type );

		if ( ! $custom_price ) {
			// Empty or false - return it now.
			return $custom_price;
		}

		$value = $custom_price;

		/**
		 * The custom price does not include tax.
		 * If we need to `$include_tax`, let's add it now.
		 * This is needed for {@see \WC_Product_Variable::get_price_html }, for example,
		 * to display the single product price with tax.
		 *
		 * @since 2.6.3
		 */
		if ( $include_tax ) {
			$value = \wc_get_price_including_tax( $product, array( 'qty' => 1, 'price' => $value ) );
		}

		return $value;
	}
}
