<?php
/**
 * Rate provider: APILayer_ExchangeRates.
 *
 * @since 3.0.0
 */

namespace WOOMC\Rate\Provider;

/**
 * Class Provider\APILayer_ExchangeRates
 *
 * @since 3.0.0
 */
class APILayer_ExchangeRates extends APILayerProvider {

	/**
	 * Overwrite title.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	public static function title() {
		return 'APILayer.com: Exchange Rates Data';
	}

	/**
	 * Implement url().
	 *
	 * @since 3.1.0
	 * @inheritDoc
	 */
	public static function url() {
		return 'https://apilayer.com/marketplace/exchangerates_data-api';
	}

	/**
	 * Configure.
	 *
	 * @since 3.0.0
	 * @param array $settings The settings array.
	 */
	public function configure( array $settings ) {
		$this->url_get_rates = self::APILAYER_URL . 'exchangerates_data/latest?base=USD';
		$this->section_rates = 'rates';

		parent::configure( $settings );
	}
}
