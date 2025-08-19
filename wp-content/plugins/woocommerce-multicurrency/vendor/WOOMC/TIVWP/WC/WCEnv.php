<?php
/**
 * WooCommerce Environment
 *
 * @since        1.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 * @noinspection PhpUnused
 */

namespace WOOMC\Dependencies\TIVWP\WC;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WOOMC\Dependencies\TIVWP\Env;

/**
 * Class WCEnv
 *
 * @since 1.1.0
 */
class WCEnv {

	/**
	 * Return the method of defining customer location: base or geolocation.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public static function customer_location_method(): string {
		return \get_option( 'woocommerce_default_customer_address', 'base' );
	}

	/**
	 * True if geolocation of user is enabled.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public static function is_geolocation_enabled(): bool {
		return in_array( self::customer_location_method(), array( 'geolocation', 'geolocation_ajax' ), true );
	}

	/**
	 * Return colon-separated country:state of the Store.
	 *
	 * @since 1.1.0
	 * @return bool|mixed
	 * @example CA:ON
	 */
	public static function store_country_state() {
		return \get_option( 'woocommerce_default_country', '' );
	}

	/**
	 * Returns country:state of the Store as a 'location' array.
	 *
	 * @since 1.1.0
	 * @return string[]
	 * @example array( 'country' => 'CA', 'state'   => 'ON' )
	 */
	public static function store_location(): array {
		return \wc_format_country_state_string(
		/**
		 * Hook woocommerce_customer_default_location
		 *
		 * @since 1.1.0
		 */
			\apply_filters(
				'woocommerce_customer_default_location',
				self::store_country_state()
			)
		);
	}

	/**
	 * True if browser signature looks like a robot.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public static function is_a_bot(): bool {
		return (bool) preg_match( '/bot|spider|crawl/', \wc_get_user_agent() );
	}

	/**
	 * Returns REST URL prefix (default is 'wp-json'.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public static function rest_url_prefix(): string {
		$url_prefix = 'wp-json';
		if ( function_exists( 'rest_get_url_prefix' ) ) {
			$url_prefix = \rest_get_url_prefix();
		}

		return $url_prefix;
	}

	/**
	 * True if the current request is a REST API (wp-json) call.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public static function is_rest_api_call(): bool {

		$request_uri = Env::request_uri();

		if ( ! $request_uri ) {
			// Something abnormal.
			return false;
		}

		// True if 'wp-json' is in the URL.
		return false !== strpos( $request_uri, self::rest_url_prefix() );
	}

	/**
	 * True if the current request is a REST in new WC Admin ("Analytics").
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public static function is_analytics_request(): bool {

		$request_uri = Env::request_uri();

		if ( ! $request_uri ) {
			// Something abnormal.
			return false;
		}

		$rest_namespace  = 'wc-analytics';
		$rest_url_prefix = self::rest_url_prefix();

		// True if 'wp-json/wc-analytics' is in the URL.
		return false !== stripos( $request_uri, $rest_url_prefix . '/' . $rest_namespace );
	}

	/**
	 * True if custom order tables are enabled.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public static function is_custom_order_table_usage_enabled(): bool {
		return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) &&
			   OrderUtil::custom_orders_table_usage_is_enabled();
	}
}
