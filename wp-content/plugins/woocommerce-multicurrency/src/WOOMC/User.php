<?php
/**
 * User.
 *
 * @since   1.4.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use WOOMC\Dependencies\TIVWP\Country;
use WOOMC\Dependencies\TIVWP\WC\WCEnv;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Currency\Detector;
use WOOMC\User\Profile;

/**
 * Class User
 */
class User extends \WC_Data implements InterfaceHookable {

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'multicurrency_user';

	/**
	 * Data array.
	 *
	 * @var array
	 */
	protected $data = array(
		'currency' => '',
		'country'  => '',
	);

	/**
	 * Key for storing data.
	 *
	 * @var string
	 */
	protected $storage_key = '';

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		$user_profile = new Profile();
		$user_profile->setup_hooks();

		$this->storage_key = 'woocommerce_' . $this->object_type;

		if ( Env::on_front() ) {

			\add_action( 'init', array( $this, 'action__init' ), App::HOOK_PRIORITY_EARLY );

			\add_action( 'woocommerce_init', array( $this, 'action__woocommerce_init' ), App::HOOK_PRIORITY_EARLY );
		}
	}

	/**
	 * Initialize the User w/o Geolocation yet.
	 *
	 * @since    2.14.1-rc.3
	 *
	 * @internal Action.
	 */
	public function action__init() {

		// We use WC session. It might not be initialized yet.
		\WC()->initialize_session();

		// Try to retrieve the stored data.
		$this->retrieve();

		if ( ! $this->get_currency() ) {
			$currency = Detector::currency_from_cookie(); // TODO
			if ( $currency ) {
				Log::debug( new Message(
					array( __CLASS__, 'Got currency_from_cookie', $currency )
				) );
				// TODO validate
				$this->set_currency( $currency );
				$this->store();
			}
		}
	}

	/**
	 * Geolocate the User.
	 *
	 * @since    1.12.0 Fix: Hooked to 'woocommerce_init'. Otherwise, geolocation is not initialized yet.
	 * @since    2.14.1-rc.3 Separated from the main initialization,
	 *                so if there is a cookie, we do not need to wait for the geolocation.
	 * @since    3.4.1-0 Logic moved to {@see geolocate()}.
	 *
	 * @internal Action.
	 */
	public function action__woocommerce_init() {
		$this->geolocate();
		$this->store();
	}

	/**
	 * Detect country.
	 *
	 * @since 3.2.3-0
	 * @return string
	 */
	protected function detect_country() {

		/**
		 * First, attempt to get the country code from the filter.
		 *
		 * @since 3.2.3
		 */
		$country_code = \apply_filters( 'woocommerce_geolocate_ip', false, '', false, false );

		if ( ! empty( $country_code ) && is_string( $country_code ) ) {
			Log::debug( new Message( array(
				__CLASS__,
				'Country via woocommerce_geolocate_ip filter',
				$country_code,
			) ) );

			return $country_code;
		}

		// Now, get the IP address of the visitor.
		$ip_address = \WC_Geolocation::get_ip_address();

		// Bail out if it's 127.0.0.1 or similar.
		if ( ! filter_var(
			$ip_address,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		) ) {
			Log::debug( new Message( array( __CLASS__, 'IP Invalid or reserved', $ip_address ) ) );

			return '';
		}

		// Record the IP in the debug log.
		Log::debug( new Message( array( __CLASS__, 'get_ip_address()', $ip_address ) ) );

		// Try to geolocate the IP.
		$location = \WC_Geolocation::geolocate_ip( $ip_address, true );
		if ( empty( $location['country'] ) ) {
			Log::debug( new Message( array( __CLASS__, 'Cannot geolocate IP', $ip_address ) ) );

			return '';
		}

		// Geolocation successful.
		return $location['country'];
	}

	/**
	 * Get user data by location.
	 *
	 * @since 1.4.0
	 * @since 3.4.1-0 Get country even if currency already known.
	 *
	 * @return void
	 */
	protected function geolocate() {

		if ( WCEnv::is_a_bot() || ! WCEnv::is_geolocation_enabled() ) {
			return;
		}

		if ( ! $this->get_country() ) {
			$country_code = $this->detect_country();
			if ( empty( $country_code ) || ! is_string( $country_code ) ) {
				return;
			}

			$this->set_country( $country_code );
			Log::debug( new Message(
				array( __CLASS__, 'geolocate', 'set_country', $country_code )
			) );
		}

		// If we know the country, and if currency was not already set from cookie on `init`.
		if ( ! empty( $country_code ) && ! $this->get_currency() ) {
			$country_obj = new Country( $country_code );
			$currency    = $country_obj->getCurrency();
			if ( $currency ) {
				$this->set_currency( $currency );
				Log::debug( new Message(
					array( __CLASS__, 'geolocate', 'set_currency', $currency )
				) );
			} else {
				Log::error( new Message( array(
					__CLASS__,
					'Unknown currency for country',
					$country_code,
				) ) );

			}
		}
	}

	/**
	 * Store the user data.
	 *
	 * @since   1.4.0
	 * @since   2.0.0 Use WC session instead of cookie.
	 */
	protected function store() {

		if ( ! \WC()->session ) {
			return;
		}

		\WC()->session->set( $this->storage_key, $this->data );
	}

	/**
	 * Retrieve the user data.
	 *
	 * @since   1.4.0
	 * @since   2.0.0 Use WC session instead of cookie.
	 */
	protected function retrieve() {

		if ( ! \WC()->session ) {
			return;
		}

		$retrieved_data = \WC()->session->get( $this->storage_key, $this->data );
		foreach ( array_keys( $this->data ) as $key ) {
			if ( isset( $retrieved_data[ $key ] ) ) {
				$this->set_prop( $key, $retrieved_data[ $key ] );
				Log::debug( new Message(
					array( __CLASS__, 'Retrieved from session', $key, $retrieved_data[ $key ] )
				) );
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get currency.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_currency( $context = 'view' ) {
		return $this->get_prop( 'currency', $context );
	}

	/**
	 * Get country.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_country( $context = 'view' ) {
		return $this->get_prop( 'country', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set currency.
	 *
	 * @param string $value Currency.
	 */
	public function set_currency( $value ) {
		$this->set_prop( 'currency', \wc_clean( $value ) );
	}

	/**
	 * Set country.
	 *
	 * @param string $value Country.
	 */
	public function set_country( $value ) {
		$this->set_prop( 'country', \wc_clean( $value ) );
	}
}
