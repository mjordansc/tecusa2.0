<?php
/**
 * APILayer Provider
 * Copyright (c) 2022, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate\Provider;

/**
 * Class APILayerProvider
 *
 * @since 3.0.0
 */
class APILayerProvider extends AbstractProvider {

	/**
	 * APILayer base URL.
	 *
	 * @since 3.0.0
	 * @type string
	 */
	const APILAYER_URL = 'https://api.apilayer.com/';

	/**
	 * The credentials label.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	public static function credentials_label() {
		return 'API Key';
	}

	/**
	 * Override remote_call.
	 *
	 * @since 3.0.0
	 * @inheritDoc
	 */
	protected function remote_call( $args = array() ) {
		return \wp_safe_remote_get(
			$this->url_get_rates,
			array(
				'headers' => array(
					'apikey' => $this->getCredentials(),
				),
			)
		);
	}
}
