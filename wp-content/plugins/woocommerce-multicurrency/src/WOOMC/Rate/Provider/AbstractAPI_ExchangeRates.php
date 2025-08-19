<?php
/**
 * Rate provider: AbstractAPI_ExchangeRates.
 *
 * @since 3.1.0
 * @link  https://app.abstractapi.com/api/exchange-rates/documentation
 */

namespace WOOMC\Rate\Provider;

/**
 * Class Provider\AbstractAPI_ExchangeRates
 */
class AbstractAPI_ExchangeRates extends AbstractProvider {

	/**
	 * Overwrite title.
	 *
	 * @since 3.1.0
	 * @return string
	 */
	public static function title() {
		return 'AbstractAPI.com: Exchange Rates API';
	}

	/**
	 * The credentials label (App ID, API key, etc.).
	 *
	 * @inheritdoc
	 */
	public static function credentials_label() {
		return 'API key';
	}

	/**
	 * Implement url().
	 *
	 * @since 3.1.0
	 * @inheritDoc
	 */
	public static function url() {
		return 'https://www.abstractapi.com/api/exchange-rate-api/';
	}

	/**
	 * This method must be called to pass the credentials.
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return void
	 */
	public function configure( array $settings ) {
		$this->url_get_rates     = 'https://exchange-rates.abstractapi.com/v1/live?base=USD&api_key=';
		$this->section_rates     = 'exchange_rates';
		$this->section_timestamp = 'last_updated';

		parent::configure( $settings );
	}
}
