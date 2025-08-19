<?php
/**
 * Rate provider: CurrencyLayer.
 *
 * @since 1.0.0
 */

namespace WOOMC\Rate\Provider;

/**
 * Class Provider\Currencylayer
 *
 * @since 1.0.0
 */
class Currencylayer extends AbstractProvider {

	/**
	 * Overwrite title.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	public static function title() {
		return 'CurrencyLayer.com ' . \_x( '(existing accounts only)', 'Note about Currencylayer', 'woocommerce-multicurrency' );
	}

	/**
	 * The credentials label (App ID, API key, etc.).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function credentials_label() {
		return 'API Access Key';
	}

	/**
	 * Configure.
	 *
	 * @since 1.0.0
	 * @param array $settings The settings array.
	 */
	public function configure( array $settings ) {
		/**
		 * Free subscription does not include https.
		 *
		 * @noinspection HttpUrlsUsage
		 */
		$this->url_get_rates = 'http://apilayer.net/api/live?source=USD&format=1&access_key=';
		$this->section_rates = 'quotes';

		parent::configure( $settings );
	}


	/**
	 * Remove the "USD" prefix from the currency codes.
	 * 'USDAED' becomes 'AED'.
	 *
	 * @since 1.0.0
	 * @since 2.16.4 Fix: USD (base currency) not present in the retrieved rates. Adding it with rate 1.0.
	 *
	 * @param array $rates The rates array.
	 *
	 * @return array
	 * @example
	 * "USDAED" => 3.672982,
	 * "USDAFN"=> 57.8936,
	 * "USDALL"=> 126.1652,
	 *
	 * Alternative way:
	 * $sanitized_rates = array();
	 * array_walk( $rates, function ( $rate, $code ) use ( &$sanitized_rates ) {
	 * $sanitized_rates[ substr( $code, 3 ) ] = $rate;
	 * } );
	 */
	protected function sanitize_rates( array $rates ) {

		$sanitized_rates =
			array_combine(
				array_map(
					function ( $code ) {
						return substr( $code, 3 );
					},
					array_keys( $rates )
				),
				$rates
			);

		return $sanitized_rates;
	}
}
