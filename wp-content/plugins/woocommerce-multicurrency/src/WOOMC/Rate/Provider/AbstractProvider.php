<?php
/**
 * Rate Provider abstract class.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate\Provider;

use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Log;

/**
 * Abstract Provider Class
 */
abstract class AbstractProvider {

	/**
	 * Provider's URL.
	 *
	 * @since 3.1.0
	 * @return  string
	 */
	protected static function url() {
		return '';
	}

	/**
	 * Provider's API URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $url_get_rates;

	/**
	 * Rates section in the data received from the provider.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $section_rates;

	/**
	 * Timestamp section in the data received from the provider.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $section_timestamp = 'timestamp';

	/**
	 * The credentials.
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	protected $credentials;

	/**
	 * Formatted rates timestamp.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $timestamp;

	/**
	 * The provider ID.
	 *
	 * @since 1.15.0
	 * @since 3.1.0 ID must match the class name, or {@see \WOOMC\Rate\Updater::getProvider} fails.
	 *
	 * @return string
	 */
	final public static function id() {
		return str_replace( __NAMESPACE__ . '\\', '', static::class );
	}

	/**
	 * The provider title.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public static function title() {
		return static::id();
	}

	/**
	 * The credentials label (App ID, API key, etc.).
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public static function credentials_label() {
		return '';
	}

	/**
	 * Provider description (e.g., link to their site).
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public static function description() {
		$url = static::url();

		return $url ? '<a target="_" href="' . $url . '">' . $url . '</a>' : '';
	}

	/**
	 * Getter for $this->credentials.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public function getCredentials() {
		return $this->credentials;
	}

	/**
	 * Setter for $this->credentials.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $credentials The credentials.
	 */
	protected function setCredentials( $credentials ) {
		$this->credentials = $credentials;
	}

	/**
	 * Getter for $this->timestamp.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * Getter for $this->timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timestamp The timestamp.
	 */
	public function setTimestamp( $timestamp ) {
		$this->timestamp = $timestamp;
	}

	/**
	 * This method must be called to pass the credentials.
	 * The derived classes also must set the URL and additional parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The array of settings.
	 *
	 * @return void
	 */
	public function configure( array $settings ) {
		if ( isset( $settings['credentials'] ) ) {
			$this->setCredentials( $settings['credentials'] );
		}
	}

	/**
	 * Method to call the provider service.
	 *
	 * @since 3.0.0
	 * @since 3.2.0-2 Added $args.
	 *
	 * @param array $args Optional. Request arguments. Default empty array.
	 *
	 * @return array|\WP_Error
	 */
	protected function remote_call( $args = array() ) {
		return \wp_safe_remote_get( $this->url_get_rates . $this->getCredentials(), $args );
	}

	/**
	 * Call the provider API to retrieve the rates.
	 * It is public because called by Updater.
	 *
	 * @since 1.0.0
	 * @since 1.15.0 Logging.
	 * @since 1.18.3 Log response body if error and if debug.
	 *
	 * @return float[]
	 * @throws \Exception Caught in {@see \WOOMC\Rate\Updater::update}.
	 */
	public function retrieve_rates() {

		$provider_title = static::title();

		Log::info( new Message( array( $provider_title, 'Retrieving rates' ) ) );

		$credentials = $this->getCredentials();
		if ( ! $credentials ) {
			$error_message = 'No credentials';
			throw new \Exception( \esc_html( $provider_title . ': ' . $error_message ) );
		}

		$remote_get_response = $this->remote_call();
		if ( \is_wp_error( $remote_get_response ) ) {
			$error_message = $remote_get_response->get_error_message();
			throw new \Exception( \esc_html( $provider_title . ': ' . $error_message ) );
		}

		$response_code = \wp_remote_retrieve_response_code( $remote_get_response );
		if ( 200 !== $response_code ) {
			$error_message = '(' . $response_code . ') ' . \wp_remote_retrieve_response_message( $remote_get_response );
			throw new \Exception( \esc_html( $provider_title . ': ' . $error_message ) );
		}

		$response_body = \wp_remote_retrieve_body( $remote_get_response );
		if ( ! $response_body ) {
			$error_message = 'No data received';
			throw new \Exception( \esc_html( $provider_title . ': ' . $error_message ) );
		}

		/**
		 * No warning about missing ext-json.
		 *
		 * @noinspection PhpComposerExtensionStubsInspection
		 */
		$response_array = json_decode( $response_body, true );
		if ( empty( $response_array[ $this->section_rates ] ) ) {
			$error_message = 'Invalid data received: no rates';
			throw new \Exception( \esc_html( $provider_title . ': ' . $error_message ) );
		}
		if ( empty( $response_array[ $this->section_timestamp ] ) ) {
			$error_message = 'Invalid data received: no timestamp';
			throw new \Exception( \esc_html( $provider_title . ': ' . $error_message ) );
		}

		$rates = $this->sanitize_rates( $response_array[ $this->section_rates ] );

		/**
		 * USD is the base exchange currency. Its 1:1 rate must always present.
		 * Otherwise, USD cannot be selected in the "Currencies" list.
		 *
		 * @since 3.1.2
		 */
		$rates['USD'] = 1.0;

		$this->setTimestamp( $response_array[ $this->section_timestamp ] );

		return $rates;
	}

	/**
	 * Stub for sanitizing rates.
	 *
	 * @since 1.0.0
	 *
	 * @param array $rates The array of ratings.
	 *
	 * @return array
	 */
	protected function sanitize_rates( array $rates ) {
		return $rates;
	}
}
