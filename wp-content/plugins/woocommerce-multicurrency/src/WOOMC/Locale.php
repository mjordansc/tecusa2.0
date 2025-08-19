<?php
/**
 * Locale.
 *
 * @since 2.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use WOOMC\Abstracts\Hookable;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Integration\Multilingual;

/**
 * Class Locale
 *
 * @package WOOMC
 */
class Locale extends Hookable {


	/**
	 * Locale-info.
	 *
	 * @since 2.9.4-rc.1
	 * @var string[][]
	 */
	protected $locale_info = array();

	/**
	 * Country.
	 *
	 * @var string
	 */
	protected $country = '';

	/**
	 * Decimal separator.
	 *
	 * @var string
	 */
	protected $decimal_separator = '.';

	/**
	 * Thousands separator.
	 *
	 * @var string
	 */
	protected $thousand_separator = ',';

	/**
	 * Getter.
	 *
	 * @return string
	 */
	public function getDecimalSeparator() {
		return $this->decimal_separator;
	}

	/**
	 * Setter.
	 *
	 * @param string $decimal_separator
	 */
	public function setDecimalSeparator( $decimal_separator ) {
		$this->decimal_separator = $decimal_separator;
	}

	/**
	 * Getter.
	 *
	 * @return string
	 */
	public function getThousandSeparator() {
		return $this->thousand_separator;
	}

	/**
	 * Setter.
	 *
	 * @param string $thousand_separator
	 */
	public function setThousandSeparator( $thousand_separator ) {
		$this->thousand_separator = $thousand_separator;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		$this->set_default_separators();

		\add_action(
			'init',
			array( $this, 'setup' ),
			App::HOOK_PRIORITY_LATE
		);
	}

	/**
	 * Default values for separators are taken from the Woo General Tab.
	 *
	 * @return void
	 */
	protected function set_default_separators() {
		$this->setDecimalSeparator( \get_option( 'woocommerce_price_decimal_sep', '.' ) );
		$this->setThousandSeparator( \get_option( 'woocommerce_price_thousand_sep', ',' ) );
	}

	/**
	 * Setup locale.
	 *
	 * @return void
	 */
	public function setup() {

		$this->load_locale_info();

		$this->country = $this->detect_country();

		if ( isset( $this->locale_info[ $this->country ] ) ) {
			if ( \get_woocommerce_currency() === $this->locale_info[ $this->country ]['currency_code'] ) {
				$this->decimal_separator  = $this->locale_info[ $this->country ]['decimal_sep'];
				$this->thousand_separator = $this->locale_info[ $this->country ]['thousand_sep'];
			}
		}
	}

	/**
	 * Load locale-info array.
	 *
	 * @since        2.9.4-rc.1
	 *
	 * @return void
	 * @noinspection PhpIncludeInspection
	 */
	protected function load_locale_info() {
		$wc_locale_info = include \WC()->plugin_path() . '/i18n/locale-info.php';

		$this->locale_info = array_merge( $wc_locale_info, $this->additional_locales() );
	}

	/**
	 * Parse locale and return the country part, uppercase.
	 *
	 * @return string
	 */
	protected function get_country_from_locale() {

		$locale = \get_locale();

		if ( false !== strpos( $locale, '_' ) ) {
			// Locale in the form `en_US`.
			list( , $country ) = explode( '_', $locale );

		} else {
			// Locale in the form `de`.
			$country = $locale;
		}

		return strtoupper( $country );
	}

	/**
	 * Detect country of user.
	 *
	 * @return string
	 */
	public function detect_country() {

		if ( Multilingual::is_multilingual() ) {
			$country_from_locale = $this->get_country_from_locale();
			Log::debug( new Message( array( __CLASS__, '$country_from_locale', $country_from_locale ) ) );

			return $country_from_locale;
		}

		/**
		 * Try Geolocation.
		 */
		$user = App::instance()->getUser();
		if ( $user ) {
			$country_of_user = $user->get_country();
			if ( $country_of_user ) {
				Log::debug( new Message( array( __CLASS__, '$country_of_user', $country_of_user ) ) );

				return $country_of_user;
			}
		}

		$base_country = \WC()->countries->get_base_country();
		Log::debug( new Message( array( __CLASS__, '$base_country', $base_country ) ) );

		return $base_country;
	}

	/**
	 * Locales missing in i18n/locale-info.php.
	 *
	 * @return array
	 */
	protected function additional_locales() {
		return array(
			'RU' =>
				array(
					'currency_code'  => 'RUB',
					'currency_pos'   => 'right_space',
					'thousand_sep'   => ' ',
					'decimal_sep'    => ',',
					'num_decimals'   => 2,
					'weight_unit'    => 'kg',
					'dimension_unit' => 'cm',
				),
			'CH' =>
				array(
					'currency_code'  => 'CHF',
					'currency_pos'   => 'right_space',
					'thousand_sep'   => "'",
					'decimal_sep'    => '.',
					'num_decimals'   => 2,
					'weight_unit'    => 'kg',
					'dimension_unit' => 'cm',
				),
		);
	}

	/**
	 * Get locale-info matching country and optionally, currency.
	 *
	 * @since        2.9.4-rc.1
	 *
	 * @param string $country  Country code.
	 * @param string $currency Currency code (Optional).
	 *
	 * @return string[]
	 * @noinspection PhpUnused
	 */
	public function get_locale_info_by_country( $country, $currency = '' ) {
		$match = array();
		if (
			isset( $this->locale_info[ $country ] )
			&& ( ! $currency || $currency === $this->locale_info[ $country ]['currency_code'] )
		) {
			$match = $this->locale_info[ $country ];
		}

		return $match;
	}

	/**
	 * Filter the locale_info array to have only locales with the specified currency.
	 *
	 * @since 2.9.4-rc.3
	 *
	 * @param string $currency Currency code.
	 *
	 * @return string[][]
	 */
	public function get_locales_by_currency( $currency ) {
		return array_filter( $this->locale_info, function ( $locale ) use ( $currency ) {
			return $locale['currency_code'] === $currency;
		} );
	}

	/**
	 * Get locale_info matching the currency specified.
	 *
	 * @since 2.9.4-rc.3
	 *
	 * @param string $currency The currency code.
	 *
	 * @return array|string[]
	 */
	public function get_locale_info_by_currency( $currency ) {

		// Default is no match.
		$matching_locale_info = array();

		$locales_having_this_currency = $this->get_locales_by_currency( $currency );

		if ( count( $locales_having_this_currency ) === 1 ) {
			// Only one locale found, use it.
			$matching_locale_info = reset( $locales_having_this_currency );
		} elseif ( count( $locales_having_this_currency ) > 1 ) {
			// Find locale that matches the user country and the `$currency`.
			$country_of_user      = $this->detect_country();
			$matching_locale_info = $locales_having_this_currency[ $country_of_user ] ?? reset( $locales_having_this_currency );
		}

		return $matching_locale_info;
	}
}
