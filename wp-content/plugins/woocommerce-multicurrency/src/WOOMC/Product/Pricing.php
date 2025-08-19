<?php
/**
 * Product prices.
 *
 * @since 1.19.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Product;

use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\AbstractConverter;
use WOOMC\App;

/**
 * Class Pricing
 *
 * @package WOOMC\Product
 */
class Pricing extends AbstractConverter {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Prevent price conversion in admin.
		 * Example: add/remove order items.
		 *
		 * @since 2.11.0-rc.1
		 * @since 1.14.0 Developers can override this using a special filter.
		 */
		if (
			! Env::on_front()
			/**
			 * Filter woocommerce_multicurrency_ok_to_run_in_admin.
			 *
			 * @since 1.14.0
			 */
			&& ! \apply_filters( 'woocommerce_multicurrency_ok_to_run_in_admin', false )
		) {
			return;
		}

		$this->setup_get_props_filters();

		\add_filter(
			'woocommerce_product_get_price',
			array( $this, 'filter__woocommerce_product_get_price' ),
			App::HOOK_PRIORITY_EARLY,
			3
		);

		\add_filter(
			'woocommerce_product_get_regular_price',
			array( $this, 'filter__woocommerce_product_get_regular_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_product_variation_get_regular_price',
			array( $this, 'filter__woocommerce_product_get_regular_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_product_get_sale_price',
			array( $this, 'filter__woocommerce_product_get_sale_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_product_variation_get_sale_price',
			array( $this, 'filter__woocommerce_product_get_sale_price' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_variation_prices',
			array(
				$this,
				'filter__woocommerce_variation_prices',
			),
			App::HOOK_PRIORITY_EARLY,
			3
		);
	}

	/**
	 * Filter `$product->get_price()`.
	 *
	 * @param string|int|float                  $value       The price.
	 * @param \WC_Product|\WC_Product_Variation $product     The product object.
	 * @param bool                              $include_tax Return price with tax?
	 *
	 * @return string
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_product_get_price( $value, $product, $include_tax = false ) {
		$price_type = '_price';

		return $this->get_price( $value, $price_type, $product, $include_tax );
	}

	/**
	 * Filter `$product->get_regular_price()`.
	 *
	 * @param string|int|float $value       The price.
	 * @param \WC_Product      $product     The product object.
	 * @param bool             $include_tax Return price with tax?
	 *
	 * @return string
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_product_get_regular_price( $value, $product, $include_tax = false ) {
		$price_type = '_regular_price';

		return $this->get_price( $value, $price_type, $product, $include_tax );
	}

	/**
	 * Filter `$product->get_sale_price()`.
	 *
	 * @param string|int|float $value       The price.
	 * @param \WC_Product      $product     The product object.
	 * @param bool             $include_tax Return price with tax?
	 *
	 * @return string
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_product_get_sale_price( $value, $product, $include_tax = false ) {
		$price_type = '_sale_price';

		return $this->get_price( $value, $price_type, $product, $include_tax );
	}

	/**
	 * Convert variation prices.
	 *
	 * @since        1.0.0
	 * @since        2.0.0 Moved to Product\Pricing.
	 * @since        2.4.0 Calculate product variation's price using the same methods
	 *                     as with the regular product - to consider custom pricing.
	 *
	 * @param string[][]  $transient_cached_prices_array The `$price_type => $values` array.
	 * @param \WC_Product $product                       The Product object.
	 * @param bool        $for_display                   If true, prices will be adapted for display based on the `woocommerce_tax_display_shop` setting (including or excluding taxes).
	 *
	 * @return string[][]
	 *
	 * @internal     filter.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__woocommerce_variation_prices(
		$transient_cached_prices_array,
		$product = null,
		$for_display = false
	) {

		/**
		 * Are we asked to return prices with tax?
		 *
		 * @since 2.6.3
		 */
		$include_tax = $for_display && ( 'incl' === \get_option( 'woocommerce_tax_display_shop' ) );

		foreach ( $transient_cached_prices_array as $price_type => $variation_prices ) {
			$method = "filter__woocommerce_product_get_{$price_type}";
			foreach ( $variation_prices as $variation_id => $value ) {
				if ( $value ) {
					$variation = \wc_get_product( $variation_id );
					$price     = $this->$method( $value, $variation, $include_tax );

					$transient_cached_prices_array[ $price_type ][ $variation_id ] = $price;
				}
			}
		}

		return $transient_cached_prices_array;
	}

	/**
	 * Setup filters for get_prop_* methods.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Moved to a function in Pricing.
	 */
	protected function setup_get_props_filters() {

		/**
		 * Simple and Variable product prices.
		 */
		static $filter_tags = array(
			'woocommerce_product_get_price',
			'woocommerce_product_variation_get_price',
			// 'woocommerce_product_get_regular_price', -- Excluded intentionally @since 1.19.0.
			// 'woocommerce_product_get_sale_price', -- Excluded intentionally @since 1.19.0.
			// 'woocommerce_product_variation_get_sale_price', -- Excluded intentionally @since 2.4.0.
			// 'woocommerce_product_variation_get_regular_price', -- Excluded intentionally @since 2.4.0.
		);

		/**
		 * --- MUST COME AFTER THE INTEGRATIONS TO HOOK ALL TAGS ---
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $filter_tags
		 */
		$filter_tags = \apply_filters( 'woocommerce_multicurrency_get_props_filters', $filter_tags );
		$filter_tags = array_unique( $filter_tags );

		foreach ( $filter_tags as $tag ) {
			\add_filter( $tag, array( $this, 'filter__woocommerce_product_get_price' ), App::HOOK_PRIORITY_EARLY, 2 );
		}

		/**
		 * --- DO NOT USE ---
		 *
		 * - woocommerce_get_price_excluding_tax
		 * - woocommerce_get_price_including_tax
		 * They usually come after the prices already calculated. Exception: Product Add-ons, see below.
		 *
		 * - raw_woocommerce_price
		 * This is a "strange" filter.
		 * Not sure how it can be used, because it affects every price,
		 * and does not tell, which one.
		 *
		 * - woocommerce_subscriptions_cart_get_price
		 * - woocommerce_variation_prices_price
		 * - woocommerce_variation_prices_regular_price
		 * - woocommerce_variation_prices_sale_price
		 * - woocommerce_variation_prices_sign_up_fee
		 * Covered by other hooks.
		 *
		 * Additional hooks are in {@see WC_Product_Variable_Data_Store_CPT::read_price_data}.
		 * Should not use those because the prices then stored in transients.
		 */
	}
}
