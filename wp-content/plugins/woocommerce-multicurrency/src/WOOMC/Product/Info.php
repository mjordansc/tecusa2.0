<?php
/**
 * Product information.
 *
 * @since 1.13.0
 * @since 1.19.0 Renamed to Info
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Product;

use WOOMC\API;

/**
 * Class Product\Info
 */
class Info {

	/**
	 * Return this if garbage was passed to {@see classname()}.
	 *
	 * @since 1.13.0
	 *
	 * @var string
	 */
	const NOT_AN_OBJECT = 'NOT_AN_OBJECT';

	/**
	 * The WC Product object.
	 *
	 * @since 1.19.0
	 *
	 * @var \WC_Product
	 */
	protected $product;

	/**
	 * Custom prices.
	 *
	 * @since 2.11.0-rc.1
	 * @var array
	 */
	protected $custom_prices = array();

	/**
	 * Getter.
	 *
	 * @since 2.11.0-rc.1
	 * @return array
	 */
	public function getCustomPrices() {
		return $this->custom_prices;
	}

	/**
	 * Active currency.
	 *
	 * @since 2.11.0-rc.1
	 * @var string
	 */
	protected $active_currency = '';

	/**
	 * True is the product has any custom price.
	 *
	 * @since 2.11.0-rc.1
	 * @var bool
	 */
	protected $is_custom_priced = false;

	/**
	 * Info constructor.
	 *
	 * @since 1.19.0
	 *
	 * @param \WC_Product $product The product object.
	 */
	public function __construct( \WC_Product $product ) {

		$this->product = $product;

		$this->active_currency = API::active_currency();

		if ( API::is_custom_pricing_enabled() && ! API::is_default_currency_active() ) {
			$this->load_custom_prices();
		}
	}

	/**
	 * Get meta for custom price.
	 *
	 * @since 2.11.0-rc.1
	 * @since 2.16.4 Use product->get_meta instead of get_post_meta.
	 * @param string $key Meta key.
	 * @return string
	 */
	protected function get_custom_meta( $key ) {

		$key .= '_' . $this->active_currency;
		return $this->product->get_meta( $key );
	}

	/**
	 * Polyfill for ?: operator.
	 *
	 * @since 2.11.0-rc.1
	 * @param mixed $a               A.
	 * @param mixed $if_not_a_then_b B.
	 * @return mixed
	 */
	protected function elvis( $a, $if_not_a_then_b ) {
		return $a ? $a : $if_not_a_then_b;
	}

	/**
	 * Load custom prices at constructor.
	 *
	 * @since 2.11.0-rc.1
	 */
	protected function load_custom_prices() {

		/**
		 * Load sale price.
		 * Only if not scheduled or within the schedule.
		 */
		if ( $this->has_sale_schedule() && ! $this->is_on_sale_schedule() ) {
			// Has a schedule but we are not within it.
			$this->custom_prices['_sale_price'] = '';
		} else {
			$this->custom_prices['_sale_price'] = $this->get_custom_meta( '_sale_price' );
		}

		/**
		 * Load regular price.
		 */
		$custom_regular_price = $this->get_custom_meta( '_regular_price' );
		if ( $custom_regular_price ) {
			$this->custom_prices['_regular_price'] = $custom_regular_price;
		} else {
			// Else: Custom regular is not set. Check if custom sale is set and use it.
			$this->custom_prices['_regular_price'] = $this->custom_prices['_sale_price'];
		}

		/**
		 * Load price as (sale ?: regular).
		 */
		$this->custom_prices['_price'] = $this->elvis(
			$this->custom_prices['_sale_price'],
			$this->custom_prices['_regular_price']
		);

		/**
		 * If any custom price found, consider this product "custom priced".
		 */
		$this->is_custom_priced = (bool) array_filter( $this->custom_prices );

		// Sign-up fee is treated separately and does not affect `is_custom_priced`.
		$this->custom_prices['_subscription_sign_up_fee'] = $this->get_custom_meta( '_subscription_sign_up_fee' );
	}

	/**
	 * Get the class of product object.
	 *
	 * @since 1.13.0
	 *
	 * @param mixed $product Can be anything: product, its ID, null, etc.
	 *
	 * @return string The class.
	 */
	public static function classname( $product ) {
		$product_class = self::NOT_AN_OBJECT;

		if ( null !== $product ) {
			if ( is_numeric( $product ) ) {
				// Product ID passed.
				$product = \wc_get_product( $product );
			}
			if ( is_object( $product ) ) {
				$product_class = get_class( $product );
			}
		}

		return $product_class;
	}

	/**
	 * Check if the product has a sale schedule.
	 *
	 * @since 1.19.0
	 * @since 2.14.2-beta.1 Handle partially entered dates.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 */
	public function has_sale_schedule( $context = 'edit' ) {

		return $this->product->get_date_on_sale_from( $context ) || $this->product->get_date_on_sale_to( $context );
	}

	/**
	 * Check if the product is currently on sale schedule.
	 * Checks the dates only and ignores the prices.
	 *
	 * @since 1.19.0
	 * @since 2.14.2-beta.1 Handle partially entered dates.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 */
	public function is_on_sale_schedule( $context = 'edit' ) {

		// 1. At least one of the sale dates must be set.
		if ( ! $this->has_sale_schedule( $context ) ) {
			return false;
		}

		// 2. OK, No.1 above is true. Setting on_sale, unless...
		$on_sale = true;

		// 3. ... unless dates are in the past or in the future...
		$current_time = time();

		if ( $this->product->get_date_on_sale_from( $context ) && $this->product->get_date_on_sale_from( $context )->getTimestamp() > $current_time ) {
			// In the future.
			$on_sale = false;
		}

		if ( $this->product->get_date_on_sale_to( $context ) && $this->product->get_date_on_sale_to( $context )->getTimestamp() < $current_time ) {
			// In the past.
			$on_sale = false;
		}

		return $on_sale;
	}

	/**
	 * Check if the product has custom pricing.
	 *
	 * @since 1.19.0
	 * @since 2.11.0-rc.1 Defined in the constructor.
	 *
	 * @return bool
	 */
	public function is_custom_priced() {
		return $this->is_custom_priced;
	}

	/**
	 * Get custom price.
	 *
	 * @since 2.8.5
	 * @since 2.10.0 Fix: Do not use base sale price in calculations.
	 * @since 2.10.0 Fix: Cache values per currency.
	 * @since 2.11.0 Fix: Subscription price is the same as regular price.
	 * @since 2.11.0 Added: get_custom_subscription_sign_up_fee.
	 *
	 * @param string $price_type One of the 'price', 'sale_price', 'regular_price'.
	 *
	 * @return string|false
	 */
	public function get_custom_price( $price_type ) {

		if ( ! $this->is_custom_priced() ) {
			return false;
		}

		if ( '_subscription_price' === $price_type ) {
			$price_type = '_regular_price';
		}

		$custom_price = empty( $this->custom_prices[ $price_type ] ) ? '' : $this->custom_prices[ $price_type ];

		return $custom_price;
	}
}
