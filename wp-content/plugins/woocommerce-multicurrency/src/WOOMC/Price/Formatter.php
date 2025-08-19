<?php
/**
 * Price Formatter.
 *
 * @since 1.0.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Price;

use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Abstracts\Hookable;
use WOOMC\API;
use WOOMC\App;
use WOOMC\Currency\Decimals;
use WOOMC\DAO\Factory;
use WOOMC\Locale;

/**
 * Class Formatter
 */
class Formatter extends Hookable {

	/**
	 * Array of price formats per currency.
	 *
	 * @var string[]
	 */
	protected $currency_to_price_format;

	/**
	 * DI: Locale.
	 *
	 * @since 2.1.0
	 *
	 * @var Locale
	 */
	protected $locale;

	/**
	 * Price\Formatter constructor.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param Locale $locale Locale object.
	 */
	public function __construct( Locale $locale ) {
		$this->locale = $locale;
		$this->setCurrencyToPriceFormat( Factory::getDao()->getCurrencyToPriceFormat() );
	}

	/**
	 * Setter.
	 *
	 * @param string[] $currency_to_price_format Array of "currency-to-price-format".
	 */
	public function setCurrencyToPriceFormat( $currency_to_price_format ) {
		$this->currency_to_price_format = $currency_to_price_format;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function setup_hooks() {

		if ( ! Env::on_front() ) {
			return;
		}

		/**
		 * Hooked to `wp_loaded` instead of `wp`.
		 *
		 * @since 3.4.1-0
		 */
		\add_action( 'wp_loaded', array( $this, 'action__setup_hooks_on_full_load' ) );
	}

	/**
	 * Setup hooks when WP and WC are fully loaded.
	 *
	 * @since 2.9.4-rc.1 Special treatment for My Account pages.
	 * @since 2.9.4-rc.3 Special treatment for My Account pages - also for the price format.
	 * @since 2.9.4-rc.5 Special treatment for the `order-pay` page.
	 */
	public function action__setup_hooks_on_full_load() {

		if ( \is_account_page() || \is_wc_endpoint_url( 'order-pay' ) ) {

			/**
			 * Set format and separators according to the order currency.
			 * Altering arguments of {@see \wc_price()} affects both
			 * /my-account/orders via {@see \WC_Order::get_formatted_order_total()}
			 * and /my-account/view-order/... pages.
			 */

			\add_filter( 'wc_price_args',
				function ( $args ) {

					if ( ! empty( $args['currency'] ) && ( API::default_currency() !== $args['currency'] ) ) {
						$currency = $args['currency'];

						$format_of_currency = $this->get_format( $currency );
						if ( $format_of_currency ) {
							$args['price_format'] = $format_of_currency;
						}

						$matching_locale_info = $this->locale->get_locale_info_by_currency( $currency );
						if ( ! empty( $matching_locale_info ) ) {
							$args['decimal_separator']  = $matching_locale_info['decimal_sep'];
							$args['thousand_separator'] = $matching_locale_info['thousand_sep'];
						}

						$args['decimals'] = Decimals::get_price_decimals( $currency );

					}

					return $args;
				}
			);
		} else {

			//
			// Continue - on pages other than My Account.
			//

			\add_filter(
				'woocommerce_price_format',
				array( $this, 'filter__woocommerce_price_format' ),
				App::HOOK_PRIORITY_EARLY
			);

			/**
			 * Filter get_option( 'woocommerce_price_decimal_sep' ) );
			 *
			 * @since 2.1.0
			 */
			\add_filter(
				'wc_get_price_decimal_separator',
				array( $this->locale, 'getDecimalSeparator' )
			);

			/**
			 * Filter get_option( 'woocommerce_price_thousand_sep' )
			 *
			 * @since 2.1.0
			 */
			\add_filter(
				'wc_get_price_thousand_separator',
				array( $this->locale, 'getThousandSeparator' )
			);
		}
	}

	/**
	 * If we have a format for the current WC currency, return it.
	 *
	 * @param string $format The currency format to filter.
	 *
	 * @return string
	 */
	public function filter__woocommerce_price_format( $format ) {
		$format_of_currency = $this->get_format( $this->get_woocommerce_currency() );

		return $format_of_currency ? $format_of_currency : $format;
	}

	/**
	 * If we have a format for this currency, return it.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	protected function get_format( $currency ) {
		return empty( $this->currency_to_price_format[ $currency ] )
			? ''
			: $this->currency_to_price_format[ $currency ];
	}

	/**
	 * Wrapper for PHPUnit mocking.
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function get_woocommerce_currency() {
		return \get_woocommerce_currency();
	}
}
