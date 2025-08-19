<?php
/**
 * Integration.
 * Plugin Name: Google Listings and Ads
 * Plugin URL: https://wordpress.org/plugins/google-listings-and-ads/
 *
 * @since   4.4.0
 */

namespace WOOMC\Integration;

use WOOMC\App;
use WOOMC\Dependencies\TIVWP\Env;

/**
 * Class WCGoogleListingsAndAds
 *
 * @since 4.4.0
 */
class WCGoogleListingsAndAds extends AbstractIntegration {

	/**
	 * Implement setup_hooks().
	 *
	 * @since 4.4.0
	 * @inheritDoc
	 */
	public function setup_hooks() {

		// Disable the conversion under certain circumstances.
		\add_filter(
			'woocommerce_multicurrency_pre_product_get_price',
			array(
				$this,
				'filter__woocommerce_multicurrency_pre_product_get_price',
			),
			App::HOOK_PRIORITY_EARLY,
			4
		);
	}

	/**
	 * Short-circuit the price conversion.
	 *
	 * @since        4.4.0
	 *
	 * @param false|string|int|float                 $pre_value  Initially passed as "false". May return the actual value.
	 * @param string|int|float                       $value      The price.
	 * @param \WC_Product_Accommodation_Booking|null $product    The product object.
	 * @param string                                 $price_type Regular, Sale, etc.
	 *
	 * @return string|int|float|false
	 *
	 * @internal     filter.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__woocommerce_multicurrency_pre_product_get_price( $pre_value, $value, $product = null, $price_type = '' ) {

		if ( false !== $pre_value ) {
			// A previous filter already set the `$pre_value`. We do not disturb.
			return $pre_value;
		}

		/**
		 * Do not convert prices. Google has its own conversion.
		 */
		if ( Env::is_functions_in_backtrace(
			array(
				array( 'Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter', 'get_product_field' ),
				array( 'Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter', 'map_wc_product_price' ),
				array(
					'Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter',
					'map_wc_product_sale_price',
				),
			)
		)
		) {
			return $value;
		}

		// Default: we do not interfere. Let the calling method continue.
		return false;
	}
}
