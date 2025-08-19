<?php
/**
 * Providers
 *
 * @since 3.1.1
 * Copyright (c) 2022, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate;

use WOOMC\Dependencies\TIVWP\Constants;

/**
 * Class Providers
 *
 * @since 3.1.1
 */
class Providers {

	/**
	 * Class name - ordered.
	 */
	protected const CLASS_NAMES = array(
		'FixedRates',
		'AbstractAPI_ExchangeRates',
		'APILayer_ExchangeRates',
		'APILayer_Fixer',
		'ExchangeRateAPI',
		'OpenExchangeRates',
		'Currencylayer',
	);

	/**
	 * Var providers_id_title.
	 *
	 * @since 4.4.5
	 *
	 * @var array
	 */

	protected static $providers_id_title = array();

	/**
	 * Var providers_description.
	 *
	 * @since 4.4.5
	 *
	 * @var array
	 */

	protected static $providers_description = array();

	/**
	 * Var providers_credentials_name.
	 *
	 * @since 4.4.5
	 *
	 * @var array
	 */

	protected static $providers_credentials_name = array();

	/**
	 * Populate the arrays once.
	 *
	 * @since 4.4.5
	 * @return void
	 */
	protected static function populate(): void {
		if ( count( self::$providers_id_title ) ) {
			// Already done.
			return;
		}

		$class_names = self::CLASS_NAMES;

		if ( Constants::is_true( 'WOOMC_TEST_PROVIDER_ENABLED' ) ) {
			$class_names[] = 'Test';
		}

		foreach ( $class_names as $class_name ) {
			$class = __NAMESPACE__ . '\Provider\\' . $class_name;
			// Call `class_exists` to autoload.
			if ( class_exists( $class ) ) {
				self::$providers_id_title[ $class::id() ]         = $class::title();
				self::$providers_description[ $class::id() ]      = $class::description();
				self::$providers_credentials_name[ $class::id() ] = $class::credentials_label();
			}
		}
	}

	/**
	 * List rate providers id.
	 *
	 * @since 4.4.5
	 * @return array
	 */
	public static function providers_id() {

		/**
		 * Provider ID is the same as class name!
		 *
		 * @see \WOOMC\Rate\Provider\AbstractProvider::id
		 */

		$class_names = self::CLASS_NAMES;

		if ( Constants::is_true( 'WOOMC_TEST_PROVIDER_ENABLED' ) ) {
			$class_names[] = 'Test';
		}

		/**
		 * Filter woocommerce_multicurrency_provider_ids.
		 *
		 * @since 4.4.5
		 *
		 * @param array $providers_id
		 */
		return \apply_filters( 'woocommerce_multicurrency_provider_ids', $class_names );
	}

	/**
	 * List rate providers id=>title.
	 *
	 * @since 3.1.1
	 * @since 3.2.0-2 Added 'Test' provider.
	 * @return array
	 */
	public static function providers_id_title() {

		self::populate();

		/**
		 * Filter woocommerce_multicurrency_providers.
		 *
		 * @since 1.0.0
		 * @since 3.0.0 Added APILayer_ExchangeRates, APILayer_Fixer
		 * @since 3.1.0 Added ExchangeRateAPI, AbstractAPI_ExchangeRates
		 *
		 * @param array $providers_id_title
		 *
		 */
		return \apply_filters( 'woocommerce_multicurrency_providers', self::$providers_id_title );
	}

	/**
	 * List rate providers id=>credentials name (label).
	 *
	 * @since 3.1.1
	 * @since 3.2.0-2 Added 'Test' provider.
	 * @return array
	 */
	public static function providers_id_credentials_name() {

		self::populate();

		/**
		 * Filter woocommerce_multicurrency_providers_credentials_name.
		 *
		 * @since 1.0.0
		 * @since 3.0.0 Added APILayer_ExchangeRates
		 * @since 3.1.0 Added ExchangeRateAPI, AbstractAPI_ExchangeRates
		 */
		return \apply_filters(
			'woocommerce_multicurrency_providers_credentials_name', self::$providers_credentials_name );
	}

	/**
	 * List rate providers id=>description.
	 *
	 * @since 3.1.1
	 * @since 3.2.0-2 Added 'Test' provider.
	 * @return array
	 */
	public static function providers_id_description() {

		self::populate();

		/**
		 * Filter woocommerce_multicurrency_providers_description.
		 *
		 * @since 3.1.1
		 */
		return \apply_filters(
			'woocommerce_multicurrency_providers_description', self::$providers_description );
	}
}
