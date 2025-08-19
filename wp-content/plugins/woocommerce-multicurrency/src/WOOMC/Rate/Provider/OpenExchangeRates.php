<?php
/**
 * Rate provider: OpenExchangeRates.
 *
 * @since 1.0.0
 */

namespace WOOMC\Rate\Provider;

/**
 * Class Provider\OpenExchangeRates
 */
class OpenExchangeRates extends AbstractProvider {

	/**
	 * Overwrite title.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	public static function title() {
		return 'OpenExchangeRates.org';
	}

	/**
	 * The credentials label (App ID, API key, etc.).
	 *
	 * @inheritdoc
	 */
	public static function credentials_label() {
		return 'App ID';
	}

	/**
	 * Implement url().
	 *
	 * @since 3.1.0
	 * @inheritDoc
	 */
	public static function url() {
		return 'https://openexchangerates.org/';
	}

	/**
	 * This method must be called to pass the credentials.
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return void
	 */
	public function configure( array $settings ) {
		$this->url_get_rates = 'https://openexchangerates.org/api/latest.json?app_id=';
		$this->section_rates = 'rates';

		parent::configure( $settings );
	}
}
