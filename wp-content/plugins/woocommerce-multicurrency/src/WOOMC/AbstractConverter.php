<?php
/**
 * Abstract currency converter class.
 *
 * @since   2.6.7-beta.1
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\Integration\WCProductAddons;

/**
 * Class AbstractConverter
 */
abstract class AbstractConverter implements InterfaceHookable {

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * Constructor.
	 *
	 * @param Price\Controller $price_controller The price controller.
	 */
	public function __construct( Price\Controller $price_controller ) {
		$this->price_controller = $price_controller;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	abstract public function setup_hooks();

	/**
	 * Pass-through to the Converter.
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is the currently selected.
	 * @param string           $from    Currency convert from. Default is store base.
	 * @param bool             $reverse If this is a reverse conversion.
	 *
	 * @return float|int|string
	 */
	public function convert(
		$value,
		$product = null,
		$to = '',
		$from = '',
		$reverse = false
	) {

		return $this->price_controller->convert( $value, $product, $to, $from, $reverse );
	}

	/**
	 * Pass-through to the {@see \WOOMC\Price\Controller::convert_raw}
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is the currently selected.
	 * @param string           $from    Currency convert from. Default is store base.
	 *
	 * @return float|int|string
	 */
	public function convert_raw(
		$value,
		$product = null,
		$to = '',
		$from = ''
	) {

		return $this->price_controller->convert_raw( $value, $product, $to, $from );
	}

	/**
	 * Pass-through to the {@see \WOOMC\Price\Controller::convert_shipping_fee()}
	 *
	 * @since 4.2.0
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is the currently selected.
	 * @param string           $from    Currency convert from. Default is store base.
	 *
	 * @return float|int|string
	 */
	public function convert_shipping_fee(
		$value,
		$product = null,
		$to = '',
		$from = ''
	) {

		return $this->price_controller->convert_shipping_fee( $value, $product, $to, $from );
	}

	/**
	 * True if no need to convert (the base currency is active, etc.)
	 *
	 * @param \WC_Product|null $product The product object.
	 *
	 * @return bool
	 */
	protected function is_no_conversion_required( $product = null ) {

		// Base currency is active.
		if ( API::is_default_currency_active() ) {
			return true;
		}

		if ( $product ) {

			// Deal with variations, not Variable products.
			if ( is_a( $product, 'WC_Product_Variable' ) ) {
				return true;
			}
		}

		/**
		 * Short-circuits (no conversion).
		 *
		 * @since 2.0.0 When it's just an 'is_purchasable' check.
		 * @since 2.6.2-rc.1 When exporting products.
		 */
		if ( Env::is_functions_in_backtrace( array(
				array( 'WC_Product', 'is_purchasable' ),
				array( 'WC_Admin_Exporters', 'do_ajax_product_export' ),
				/**
				 * These break Dynamic Pricing.
				 * array( 'WC_Product', 'is_on_sale' ),
				 * array( 'WC_Product_Simple', 'is_on_sale' ),
				 * array( 'WC_Product_Variable', 'is_on_sale' ),
				 */
			)
		) ) {
			return true;
		}

		/**
		 * Filter for the 3rd party plugins to override the 'is_no_conversion_required' return value.
		 *
		 * @since 4.3.0-1
		 *
		 * @param bool        $default_value Default is false, meaning that conversion is required.
		 * @param \WC_Product $product       Product object [null]
		 */
		return \apply_filters( 'woocommerce_multicurrency_is_no_conversion_required', false, $product );
	}

	/**
	 * True if we need to convert the value right now and skip any further checking.
	 *
	 * @since 2.10.0
	 *
	 * @param string           $price_type Regular, Sale, etc.
	 * @param \WC_Product|null $product    The product object.
	 *
	 * @return bool
	 */
	protected function is_convert_as_is( $price_type, $product = null ) {

		if ( ! is_a( $product, 'WC_Product' ) ) {
			// For example, WC_Product_Booking_Person_Type.
			return true;
		}

		/**
		 * Integration tweak: Product Add-ons.
		 * When a PAO product is retrieved from a cart, its price already calculated, in Store base currency.
		 *
		 * @since 2.8.5
		 * @since 2.9.1 Do not convert for Booking. It's already converted.
		 */
		if (
			$product && '_price' === $price_type
			&& class_exists( '\WOOMC\Integration\WCProductAddons', false )
			&& WCProductAddons::is_product_marked_as_pao( $product )
			&& Env::is_function_in_backtrace( array( 'WC_Cart_Totals', 'get_items_from_cart' ) )
			&& 'booking' !== $product->get_type()
		) {
			return true;
		}

		return false;
	}

	/**
	 * Generic price conversion.
	 *
	 * @param string|int|float $value       The price.
	 * @param string           $price_type  Regular, Sale, etc.
	 * @param \WC_Product      $product     The product object.
	 * @param bool             $include_tax Return price with tax?
	 *
	 * @return string
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function get_price( $value, $price_type, $product, $include_tax = false ) {

		// "Hard" short-circuits. Do nothing.
		if ( $this->is_no_conversion_required( $product ) ) {
			return $value;
		}

		// Convert as-is, no further checking.
		if ( $this->is_convert_as_is( $price_type, $product ) ) {
			$value = $this->convert( $value, $product );

			return $value;
		}

		/**
		 * If the custom price is set, return it.
		 *
		 * @since 2.10.0
		 * @since 3.4.2-0 Fix: do not do it when Product Addons are getting the addon price
		 *    via a "fake" product in
		 *    {@see \WC_Product_Addons_Helper::create_product_with_filtered_addon_prices}.
		 */
		if ( ! Env::is_functions_in_backtrace( array(
				array( 'WC_Product_Addons_Cart', 'get_item_data' ),
				// NO! This shows the product price in the "Price" column on the cart page.
				// array( 'WC_Product_Addons_Display', 'remove_flat_fees_from_cart_item_price' ),
				// NO! This shows the product price on the product page.
				// array( 'WC_Product_Addons_Display', 'totals' ),
			)
		) ) {
			$custom_price = $this->get_custom_price( $product, $price_type, $include_tax );
			if ( false !== $custom_price ) {

				/**
				 * Special treatment for custom pricing with Product Addons.
				 *
				 * @since 3.4.2-1
				 */
				if ( class_exists( 'WC_Product_Addons', false ) ) {
					if ( Env::is_functions_in_backtrace( array(
							array( 'WC_Product_Addons_Display', 'remove_flat_fees_from_cart_item_price' ),
							array( 'WC_Cart_Totals', 'calculate_item_totals' ),
							array( 'WC_Cart', 'get_product_subtotal' ),
						)
					) ) {
						$cart = \WC()->cart;
						if ( ! empty( $cart->cart_contents ) ) {
							foreach ( $cart->cart_contents as $cart_item ) {
								if (
									! empty( $cart_item['addons'] ) &&
									isset( $cart_item['product_id'] ) &&
									$product->get_id() === $cart_item['product_id']
								) {
									$value = $this->convert( $value, $product );

									return $value;
								}
							}
						}
					}
				}

				return $custom_price;
			}
		}

		// "Soft" short-circuits.
		$pre_value = false;
		/**
		 * Pre-filter to allow short-circuiting.
		 * If a non-false value comes out of the filter, it will be returned.
		 *
		 * @since 2.11.0-rc.1
		 *
		 * @param false|string|int|float $pre_value  Initially passed as "false". May return the actual value.
		 * @param string|int|float       $value      The price.
		 * @param \WC_Product|null       $product    The product object.
		 * @param string                 $price_type Regular, Sale, etc.
		 */
		$pre_value = apply_filters( 'woocommerce_multicurrency_pre_product_get_price', $pre_value, $value, $product, $price_type );
		if ( false !== $pre_value ) {
			return $pre_value;
		}

		// No more obstacles. Convert and return.
		$value = $this->convert( $value, $product );

		return $value;
	}

	/**
	 * Get custom price.
	 *
	 * @since 2.10.0
	 *
	 * @param \WC_Product $product     The product object.
	 * @param             $price_type
	 * @param bool        $include_tax Return price with tax?
	 *
	 * @return false|float|string False if product has no custom prices, not regular and not sale.
	 */
	protected function get_custom_price( $product, $price_type, $include_tax = false ) {

		if ( ! API::is_custom_pricing_enabled() ) {
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
