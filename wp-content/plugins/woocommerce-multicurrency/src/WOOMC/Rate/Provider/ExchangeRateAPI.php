<?php
/**
 * Rate provider: ExchangeRateAPI.
 *
 * @since 3.1.0
 */

namespace WOOMC\Rate\Provider;

/**
 * Class Provider\ExchangeRateAPI
 */
class ExchangeRateAPI extends AbstractProvider {

	/**
	 * Timestamp section in the data received from the provider.
	 *
	 * @var string
	 */
	protected $section_timestamp = 'time_last_update_unix';

	/**
	 * Overwrite title.
	 *
	 * @since 3.1.0
	 * @inheritdoc
	 */
	public static function title() {
		return 'ExchangeRate-API.com';
	}

	/**
	 * Overwrite credentials_label.
	 *
	 * @since 3.1.0
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
		return 'https://www.exchangerate-api.com/';
	}

	/**
	 * This method must be called to pass the credentials.
	 *
	 * @since 3.1.0
	 * @inheritDoc
	 */
	public function configure( array $settings ) {
		$this->url_get_rates = 'https://v6.exchangerate-api.com/v6/%s/latest/USD';
		$this->section_rates = 'conversion_rates';

		parent::configure( $settings );
	}

	/**
	 * Overwrite remote_call().
	 *
	 * @since 3.1.0
	 * @inheritDoc
	 */
	protected function remote_call( $args = array() ) {
		return \wp_safe_remote_get(
			sprintf( $this->url_get_rates, $this->getCredentials() ),
			$args
		);
	}
}
