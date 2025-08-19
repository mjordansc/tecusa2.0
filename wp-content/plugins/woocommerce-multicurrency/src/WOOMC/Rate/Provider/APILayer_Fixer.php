<?php
/**
 * Rate provider: APILayer_Fixer.
 *
 * @since 3.0.0
 */

namespace WOOMC\Rate\Provider;

/**
 * Class Provider\APILayer_Fixer
 *
 * @since 3.0.0
 */
class APILayer_Fixer extends APILayerProvider {

	/**
	 * Overwrite title.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	public static function title() {
		return 'APILayer.com: Fixer';
	}

	/**
	 * Implement url().
	 *
	 * @since 3.1.0
	 * @inheritDoc
	 */
	public static function url() {
		return 'https://apilayer.com/marketplace/fixer-api';
	}

	/**
	 * Configure.
	 *
	 * @since 3.0.0
	 * @param array $settings The settings array.
	 */
	public function configure( array $settings ) {
		$this->url_get_rates = self::APILAYER_URL . 'fixer/latest?base=USD';
		$this->section_rates = 'rates';

		parent::configure( $settings );
	}
}
