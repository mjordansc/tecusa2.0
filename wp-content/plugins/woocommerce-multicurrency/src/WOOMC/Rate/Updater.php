<?php
/**
 * Update currency rates.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate;

use WOOMC\Dependencies\TIVWP\Constants;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\DAO\Factory;
use WOOMC\Log;
use WOOMC\Rate\Provider\AbstractProvider;

/**
 * Class Rate\Updater
 */
class Updater {

	/**
	 * Option key
	 *
	 * @since 3.1.1
	 * @var string
	 */
	const OPTION_KEY_UPDATE_ERROR = 'woocommerce_multicurrency_update_error';

	/**
	 * Update rates.
	 *
	 * @since 1.0.0
	 * @since 1.20.0 Do not need parameters.
	 *
	 * @return int Number of rates received (array size).
	 * @throws \Exception Caught.
	 * @uses  AbstractProvider::retrieve_rates
	 */
	public function update() {

		$rates = array();

		try {
			/**
			 * Disable rates update in wp-config.
			 *
			 * @since 1.20.0
			 */
			if ( Constants::is_true( 'WOOMC_RATE_UPDATES_DISABLED' ) ) {
				throw new \Exception( 'WOOMC_RATE_UPDATES_DISABLED is set to True.' );
			}

			// May throw an exception.
			$provider = $this->getProvider();

			// May throw an exception.
			$rates = $provider->retrieve_rates();

			Storage::save_rates( $rates );
			Factory::getDao()->saveRatesTimestamp( $provider->getTimestamp() );
			Factory::getDao()->setRatesRetrievalStatus( true );
			$this->set_error_message( '' );

			Log::info( new Message( array( 'Rates updated', 'Provider: ' . $provider::id() ) ) );

		} catch ( \Exception $exception ) {
			Factory::getDao()->setRatesRetrievalStatus( false );
			$this->set_error_message( $exception->getMessage() );
			Log::error( $exception );
			Log::error( new Message( array( 'Rates not updated' ) ) );
		}

		return count( $rates );
	}

	/**
	 * Get the current rates provider object.
	 *
	 * @since 1.20.0
	 *
	 * @return AbstractProvider
	 * @throws \Exception Caught in the caller.
	 */
	protected function getProvider() {

		$provider_id = Factory::getDao()->getRatesProviderID();
		if ( ! $provider_id ) {
			throw new \Exception( 'Rates provider not set.' );
		}

		$class_name = __NAMESPACE__ . '\\Provider\\' . $provider_id;
		if ( ! class_exists( $class_name ) ) {
			throw new \Exception( '(Internal error) Rates provider not found: ' . \esc_html( $class_name ) );
		}

		/**
		 * The Provider object.
		 *
		 * @var AbstractProvider $provider
		 */
		$provider = new $class_name();
		$provider->configure( array( 'credentials' => Factory::getDao()->getRatesProviderCredentials() ) );

		return $provider;
	}

	/**
	 * Set error message.
	 *
	 * @since 3.1.1
	 *
	 * @param string $message Error message.
	 *
	 * @return void
	 */
	protected function set_error_message( $message ) {
		\update_option( self::OPTION_KEY_UPDATE_ERROR,
			array(
				'message'   => $message,
				'timestamp' => time(),
			)
		);
	}

	/**
	 * Get error message.
	 *
	 * @since 3.1.1
	 * @return false|array
	 */
	public static function get_error_message() {
		return \get_option( self::OPTION_KEY_UPDATE_ERROR );
	}
}
