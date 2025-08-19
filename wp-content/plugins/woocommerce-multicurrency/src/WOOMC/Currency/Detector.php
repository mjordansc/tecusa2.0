<?php
/**
 * Currency detector.
 *
 * @since 1.0.0
 */

namespace WOOMC\Currency;

use WOOMC\Dependencies\TIVWP\Constants;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Dependencies\TIVWP\WC\WCEnv;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Abstracts\Hookable;
use WOOMC\API;
use WOOMC\App;
use WOOMC\Cookie;
use WOOMC\DAO\Factory;
use WOOMC\DAO\IDAO;
use WOOMC\Integration\Multilingual;
use WOOMC\Log;
use WOOMC\Order\MC_Order;
use WOOMC\User;

/**
 * Class Detector
 */
class Detector extends Hookable {

	/**
	 * Cookie name: forced currency.
	 *
	 * @var string
	 */
	const COOKIE_FORCED_CURRENCY = 'woocommerce_multicurrency_forced_currency';

	/**
	 * HTTP GET parameter to force currency.
	 *
	 * @var string
	 */
	const GET_FORCED_CURRENCY = 'currency';

	/**
	 * Cookie name: language (if multilingual).
	 *
	 * @since 2.6.1
	 *
	 * @var string
	 */
	const COOKIE_LANGUAGE = 'woocommerce_multicurrency_language';

	/**
	 * Prefix for the log message when detected.
	 *
	 * @since 2.14.1-rc.3
	 * @var string
	 */
	const LOG_PREFIX_DETECTED = 'DETECTED';

	/**
	 * Prefix for the log message when validation failed.
	 *
	 * @since 2.14.1-rc.3
	 * @var string
	 */
	const LOG_PREFIX_VALIDATION_FAILED = 'VALIDATION FAILED';

	/**
	 * Language to currency.
	 *
	 * @var string[]
	 */
	protected $language_to_currency;

	/**
	 * Default currency.
	 *
	 * @var string
	 */
	protected $default_currency;

	/**
	 * Cache detected currency.
	 *
	 * @since 2.14.1-rc.3 Moved to the class variable.
	 * @var string
	 */
	protected $cached_detect = '';

	/**
	 * Getter for $this->default_currency.
	 *
	 * @return string
	 */
	public function getDefaultCurrency() {
		return $this->default_currency;
	}

	/**
	 * Setter for $this->default_currency.
	 *
	 * @param string $default_currency The currency.
	 */
	public function setDefaultCurrency( $default_currency ) {
		$this->default_currency = $default_currency;
	}

	/**
	 * Setter for $this->language_to_currency.
	 *
	 * @param string[] $language_to_currency Language-to-currency array.
	 */
	public function setLanguageToCurrency( $language_to_currency ) {
		$this->language_to_currency = $language_to_currency;
	}

	/**
	 * DAO.
	 *
	 * @var  IDAO
	 */
	protected $dao;

	/**
	 * Internal var to override currency.
	 *
	 * @since 3.2.4-0
	 *
	 * @var string
	 */
	protected static $override_currency = '';

	/**
	 * Setter _override_currency
	 *
	 * @since 3.2.4-0
	 *
	 * @param string $override_currency The currency code.
	 */
	public static function set_override_currency( $override_currency = '' ) {
		self::$override_currency = $override_currency;
	}

	/**
	 * Currency\Detector constructor.
	 */
	public function __construct() {

		$this->dao = Factory::getDao();

		$this->setLanguageToCurrency( $this->dao->getLanguageToCurrency() );

		$this->setDefaultCurrency( $this->dao->getDefaultCurrency() );
	}

	/**
	 * Returns true if the language cookie is set and matches the parameter.
	 *
	 * @since 2.6.3-rc.2
	 *
	 * @param string $language The language code.
	 *
	 * @return bool
	 */
	protected function is_language_cookie_matches( $language ) {
		if ( empty( $language ) ) {
			return false;
		}
		if ( empty( $_COOKIE[ self::COOKIE_LANGUAGE ] ) ) {
			return false;
		}
		if ( $language === $_COOKIE[ self::COOKIE_LANGUAGE ] ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns true if the language is linked to a currency in the settings.
	 *
	 * @since 2.14.1-rc.3
	 *
	 * @param string $language The language code.
	 *
	 * @return bool
	 */
	protected function is_language_linked_to_currency( $language ) {
		return ! empty( $this->language_to_currency[ $language ] );
	}

	/**
	 * Compare the current language to the previously set cookie.
	 * (Only if `language_to_currency` is set for this language).
	 *
	 * @since 2.6.1
	 *
	 * @param string $language The language code.
	 *
	 * @return bool True if language does not match the cookie, so has changed.
	 */
	protected function is_language_switched( $language ) {

		if ( $this->is_language_cookie_matches( $language ) ) {
			// Same language as before (comparing to the value in cookie).
			return false;
		}

		return true;
	}

	/**
	 * The currency is linked to the language (on multilingual sites).
	 * - site is multilingual
	 * - the language just switched or was never set before (implies `language_to_currency` is set).
	 *
	 * @since 2.6.3-rc.2
	 *
	 * @return string Detected currency or empty string.
	 */
	protected function detect_by_language() {

		if ( ! Multilingual::is_multilingual() ) {
			return '';
		}

		$language = App::instance()->getLanguage();

		if ( ! $this->is_language_linked_to_currency( $language ) ) {
			return '';
		}

		if ( ! $this->is_language_switched( $language ) ) {
			return '';
		}

		$currency = $this->language_to_currency[ $language ];
		$currency = strtoupper( $currency );

		if ( ! $this->validate_and_save( $currency ) ) {
			return '';
		}

		Log::debug( new Message( array( self::LOG_PREFIX_DETECTED, __FUNCTION__, $currency, $language ) ) );
		$this->log_messages_from_save();

		return $currency;
	}

	/**
	 * Detect if the currency is set in the cookie.
	 *
	 * @since 2.6.3-rc.2
	 * @return string Detected currency or empty string.
	 */
	protected function detect_by_cookie() {

		$currency = self::currency_from_cookie();

		if ( $currency ) {
			Log::debug( new Message( array( 'Currency in cookie', $currency ) ) );
		}

		/**
		 * Filter for 3rd parties to tweak the currency.
		 *
		 * @since      2.6.3
		 *
		 * @param string $currency The currency code.
		 *
		 * @deprecated 2.15.0 See the `woocommerce_multicurrency_override_currency` filter.
		 */
		$filtered_currency = \apply_filters( 'woocommerce_multicurrency_forced_currency', $currency );
		if ( $filtered_currency !== $currency ) {
			Log::debug( new Message( array( 'Currency from cookie changed by a filter', $filtered_currency ) ) );
			$currency = $filtered_currency;
		}

		if ( ! $currency ) {
			return '';
		}

		/**
		 * Saving:
		 * 1. 3rd party could change
		 * 2. Need to update cache.
		 * Note: 3rd party changes are cached.
		 */
		if ( ! $this->validate_and_save( $currency ) ) {
			return '';
		}

		Log::debug( new Message( array( self::LOG_PREFIX_DETECTED, __FUNCTION__, $currency ) ) );
		$this->log_messages_from_save();

		return $currency;
	}

	/**
	 * The currency is defined by the user's location, if one of the enabled currencies.
	 *
	 * @since 1.4.0
	 * @since 1.16.0 Validate if $user is not `null` (for unit tests).
	 * @since 2.1.0 Save the user's currency as forced cookie if not multilingual.
	 * @since 2.6.0 Only check User if geolocation is enabled and ignore obvious robots.
	 * @since 2.6.3-rc.2 Set forced cookie regardless, multilingual or not (in the caller).
	 * @since 2.14.1-rc.3 Additional debug.
	 * @return string Detected currency or empty string.
	 */
	protected function detect_by_geolocation() {

		try {
			if ( ! WCEnv::is_geolocation_enabled() ) {
				throw new Message( 'Geolocation not enabled' );
			}

			if ( ! \did_action( 'woocommerce_init' ) ) {
				throw new Message( 'Geolocation not initialized. Called too early, before `woocommerce_init`' );
			}

			$user = App::instance()->getUser();
			if ( ! $user instanceof User ) {
				throw new \Exception( 'User not initialized' );
			}

			$currency = $user->get_currency();
			if ( ! $currency ) {
				throw new Message( 'User currency not defined' );
			}

		} catch ( Message $m ) {
			Log::debug( $m );

			return '';
		} catch ( \Exception $e ) {
			Log::error( $e );

			return '';
		}

		if ( ! $this->validate_and_save( $currency ) ) {
			return '';
		}

		Log::debug( new Message( array( self::LOG_PREFIX_DETECTED, __FUNCTION__, $currency ) ) );
		$this->log_messages_from_save();

		return $currency;
	}

	/**
	 * Get the forced currency value from URL.
	 *
	 * @since 1.9.0
	 * @since 2.12.0-beta.1 Check $_POST also.
	 *
	 * @return string
	 */
	protected function detect_by_url() {

		$currency = strtoupper( Env::http_get_or_post( self::GET_FORCED_CURRENCY ) );

		if ( ! $this->validate_and_save( $currency ) ) {
			return '';
		}

		Log::debug( new Message( array( self::LOG_PREFIX_DETECTED, __FUNCTION__, $currency ) ) );
		$this->log_messages_from_save();

		return $currency;
	}

	/**
	 * Use the fallback currency.
	 *
	 * @since 2.14.1-rc.3 Moved to a separate method.
	 *
	 * @return string
	 */
	protected function detect_by_fallback() {

		$currency = $this->dao->getFallbackCurrency();

		if ( ! $this->validate( $currency ) ) {
			Log::error( new Message( array(
				'Fallback currency is invalid. Resetting to the Store currency.',
				$currency,
			) ) );
			$currency = $this->default_currency;
			$this->dao->saveFallbackCurrency( $currency );
		}

		/**
		 * Save only if called after 'woocommerce_init'.
		 * Otherwise, geolocation will never run.
		 */
		if ( \did_action( 'woocommerce_init' ) ) {
			$this->save( $currency );
		}

		Log::debug( new Message( array( self::LOG_PREFIX_DETECTED, __FUNCTION__, $currency ) ) );
		$this->log_messages_from_save();

		return $currency;
	}

	/**
	 * Determine the currency settings by several criteria.
	 *
	 * @since 2.6.3-rc.2 Always save the detected currency in a cookie.
	 * @since 2.14.1-rc.3 Not saving if called before 'woocommerce_init'.
	 *
	 * @return string
	 */
	protected function detect() {

		/**
		 * Order is important.
		 *
		 * @since        2.14.1-rc.3 Detect by language before detecting by URL because language switcher keeps the URL.
		 */

		/**
		 * These methods detect and immediately save cookie.
		 */

		$currency = $this->detect_by_language();
		if ( $currency ) {
			return $currency;
		}

		$currency = $this->detect_by_url();
		if ( $currency ) {
			return $currency;
		}

		$currency = $this->detect_by_cookie();
		if ( $currency ) {
			return $currency;
		}

		/**
		 * This will work and save only if called after 'woocommerce_init'.
		 *
		 * @since 2.14.1-rc.3
		 */
		$currency = $this->detect_by_geolocation();
		if ( $currency ) {
			return $currency;
		}

		/**
		 * Give up and use the Fallback Currency.
		 * This will save only if called after 'woocommerce_init'.
		 * Otherwise, geolocation will never run.
		 *
		 * @since 2.8.0
		 */
		$currency = $this->detect_by_fallback();
		if ( $currency ) {
			return $currency;
		}

		return '';
	}

	/**
	 * Validate the currency code: sane and one of the enabled.
	 *
	 * @since 2.14.1-rc.3
	 *
	 * @param string $currency Currency code.
	 *
	 * @return bool
	 */
	protected function validate( $currency ) {

		$error_message = '';

		if ( empty( $currency ) ) {
			$error_message = 'Currency is empty';
		} elseif ( ! is_string( $currency ) ) {
			$error_message = 'Currency is not a string';
		} elseif ( ! ( 3 === strlen( $currency ) ) ) {
			$error_message = 'Currency is not a 3-character string';
		} elseif ( ! API::is_currency_enabled( $currency ) ) {
			$error_message = 'Currency is unsupported';
		}

		if ( $error_message ) {
			Log::debug( new Message( array( self::LOG_PREFIX_VALIDATION_FAILED, $error_message ) ) );

			return false;
		}

		return true;
	}

	/**
	 * Messages from {@see save()}.
	 *
	 * @since 2.14.1
	 *
	 * @var array
	 */
	protected $messages_from_save = array();

	/**
	 * Log the saved messages.
	 *
	 * @since 2.14.1
	 * @return void
	 */
	protected function log_messages_from_save() {
		if ( ! empty( $this->messages_from_save ) ) {
			foreach ( $this->messages_from_save as $msg ) {
				Log::debug( $msg );
			}
		}
	}

	/**
	 * Save the detected currency.
	 *
	 * @since 2.14.1-rc.3
	 *
	 * @param string $currency Currency code.
	 *
	 * @return bool
	 */
	protected function save( $currency ) {

		$this->messages_from_save = array();

		if ( ! ( Env::is_doing_ajax() || Env::is_parameter_in_http_get( 'wc-ajax' ) ) ) {
			// Save currency cookie only on main requests, not AJAX.
			self::set_currency_cookie( $currency );
			$this->messages_from_save[] = new Message( array( '- Currency cookie saved', $currency ) );
		}

		$this->cached_detect = $currency;

		$this->messages_from_save[] = new Message( array( '- Currency cache updated', $currency ) );

		return true;
	}

	/**
	 * Combination of validate and save.
	 *
	 * @since 2.14.1-rc.3
	 *
	 * @param string $currency Currency code.
	 *
	 * @return bool
	 */
	protected function validate_and_save( $currency ) {

		if ( ! $this->validate( $currency ) ) {
			return false;
		}

		return $this->save( $currency );
	}

	/**
	 * Check if the current filter chain contains one of the names specified.
	 *
	 * Example:
	 * The global $wp_current_filter may look like
	 * [ 'wp_loaded', 'woocommerce_product_get_price', 'woocommerce_currency' ]
	 * We want to know if 'woocommerce_product_get_price' is in the list.
	 *
	 * @since 2.16.3
	 *
	 * @param string|string[] $filter_names The filter name(s).
	 *
	 * @return bool
	 */
	protected static function is_in_current_filter( $filter_names ) {
		// The list of current filters.
		global $wp_current_filter;
		if ( ! is_array( $wp_current_filter ) ) {
			return false;
		}

		foreach ( (array) $filter_names as $filter_name ) {
			if ( in_array( $filter_name, $wp_current_filter, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Handle the 'woocommerce_add_order_item' AJAX call.
	 *
	 * Returns the currency of the order or blank string
	 * if we cannot get it or should not do anything (blank string means "no exception").
	 *
	 * @since 2.16.3
	 * @since 2.16.4 Use MC_Order (HPOS compatibility)
	 *
	 * @return string
	 */
	protected function get_currency_for_adding_item_to_order() {

		static $currency_cache = array();

		// Security first.
		\check_ajax_referer( 'order-item', 'security' );

		// Order ID is passed by the AJAX call.
		$order_id = isset( $_POST['order_id'] ) ? \absint( $_POST['order_id'] ) : 0;
		if ( ! $order_id ) {
			return '';
		}

		// Shortcut for some methods that are not our business.
		if ( Env::is_functions_in_backtrace(
			array(
				'wc_get_payment_gateway_by_order',
				'wc_register_widgets',
				array( 'WC_Product', 'is_purchasable' ),
			)
		) ) {
			return '';
		}

		// We need to act on certain hooks only.
		if ( ! self::is_in_current_filter( array(
			'woocommerce_product_get_price',
			'woocommerce_product_variation_get_price',
		) ) ) {
			return '';
		}

		if ( ! empty( $currency_cache[ $order_id ] ) ) {
			return $currency_cache[ $order_id ];
		}

		// Now, let's get the currency of the order.
		$order = new MC_Order( $order_id );
		if ( ! $order->isLoaded() ) {
			return '';
		}

		$order_status = $order->get_status();
		if ( ! $order_status ) {
			return '';
		}

		// Until new order is saved, its status is 'auto-draft'.
		// We do not need to do anything until the order is saved.
		if ( 'auto-draft' === $order->get_status() ) {
			return '';
		}

		$order_currency = $order->get_currency();

		// Existing order, and currency is not enabled anymore?
		if ( ! API::is_currency_enabled( $order_currency ) ) {
			return '';
		}

		$currency_cache[ $order_id ] = $order_currency;

		return $currency_cache[ $order_id ];
	}

	/**
	 * Check if we should return a currency exception, which is different from the general state.
	 *
	 * @since 2.6.3-rc.2
	 * @return string
	 */
	protected function get_currency_exception() {

		/**
		 * Allow phpUnit to force the currency.
		 *
		 * @since 2.6.4
		 */
		if ( Constants::is_true( 'DOING_PHPUNIT' ) ) {
			return Constants::get_constant( 'PHPUNIT_ACTIVE_CURRENCY', '' );
		}

		/**
		 * Special treatment for the Admin - Add/Edit Order.
		 *
		 * @since 2.16.0-rc.1
		 */
		if ( \is_admin() ) {

			/**
			 * Add new order - no exception.
			 * Allow currency conversion, so that the initial order currency is taken from the frontend cookie.
			 *
			 * @since 2.16.0-rc.1
			 */
			if ( Env::is_pagenow( 'post-new.php' ) && 'shop_order' === Env::get_http_get_parameter( 'post_type' ) ) {
				return '';
			}

			/**
			 * When order item is added, use the order currency.
			 *
			 * @since 2.16.0-rc.1
			 * @since 2.16.3 Moved to a separate method.
			 */
			if ( Env::is_doing_ajax() && Env::is_http_post_action( 'woocommerce_add_order_item' ) ) {
				return $this->get_currency_for_adding_item_to_order();
			}
		}

		/**
		 * If in admin area, always return the default currency.
		 *
		 * @since 1.1.0
		 * @since 1.3.0 - Check also for AJAX from within the admin area.
		 */
		if ( ! Env::on_front() ) {
			return $this->default_currency;
		}

		/**
		 * REST request in new WC Admin ("Analytics") - return default.
		 *
		 * @since 2.6.3-rc.2
		 */
		if ( WCEnv::is_analytics_request() ) {
			return $this->default_currency;
		}

		/**
		 * Robots - return default.
		 *
		 * @since 2.6.3-rc.2
		 * @since 2.8.7 Disabled because it breaks Google Feeds.
		 * <code>
		 * if ( WCEnv::is_a_bot() ) {
		 *    return $this->default_currency;
		 * }
		 * </code>
		 */

		/**
		 * On the 'order-pay' page, force the currency of order.
		 * This won't affect any other pages.
		 * Also @see action__parse_request().
		 *
		 * @since 2.5.3
		 */
		$order_pay_currency = $this->get_order_pay_currency();
		if ( $order_pay_currency ) {
			return $order_pay_currency;
		}

		// Default is no exception.
		return '';
	}

	/**
	 * The current currency.
	 *
	 * @since 2.6.1 Cache the result.
	 * @since 2.6.3-rc.2 Exceptions moved to a separate method.
	 *
	 * @return string
	 */
	public function currency() {

		/**
		 * Internal shortcut to override currency.
		 * Easier and faster than setting and removing the below filter.
		 *
		 * @since 3.2.4-0
		 */
		if ( self::$override_currency ) {
			return self::$override_currency;
		}

		/**
		 * Filter for 3rd parties to override the currently active currency.
		 *
		 * @since  2.9.0
		 *
		 * @param string|false $override_currency The currency code to set.
		 *
		 * @return @string
		 */
		$override_currency = \apply_filters( 'woocommerce_multicurrency_override_currency', false );
		if ( $override_currency ) {
			/**
			 * Do not do it: floods the log.
			 * Log::debug( array( 'Currency override', $override_currency ) );
			 */
			return $override_currency;
		}

		/**
		 * Exceptions override the normal currency state.
		 * But they do not change it for the future requests (no forced cookie).
		 *
		 * @since 2.9.4-rc.5 Do not cache exceptions.
		 *                Helps, for instance, showing the order currency in the widget.
		 */
		$currency_exception = $this->get_currency_exception();
		if ( $currency_exception ) {
			return $currency_exception;
		}

		if ( $this->cached_detect ) {
			return $this->cached_detect;
		}

		// Detect the currency. (It will save cache when needed).
		return $this->detect();
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		/**        \add_filter(
		 * 'woocommerce_multicurrency_forced_currency',
		 * array(
		 * $this,
		 * 'filter__woocommerce_multicurrency_forced_currency',
		 * )
		 * );*/

		/**
		 * This filter can be used to get the active currency.
		 * In most situations, one can just call the {@see get_woocommerce_currency()}.
		 * But the filter there could be switched off.
		 *
		 * @since 1.9.0
		 */
		\add_filter( 'woocommerce_multicurrency_active_currency', array( $this, 'currency' ) );

		\add_action( 'parse_request', array( $this, 'action__parse_request' ) );
	}

	/**
	 * Get the forced currency value from cookie.
	 *
	 * @since 2.6.0 Converted to public static.
	 *
	 * @return string
	 */
	public static function currency_from_cookie() {
		$currency = '';
		if ( ! empty( $_COOKIE[ self::COOKIE_FORCED_CURRENCY ] ) ) {
			$currency = \sanitize_text_field( $_COOKIE[ self::COOKIE_FORCED_CURRENCY ] );
		}

		return $currency;
	}

	/**
	 * Set the `COOKIE_FORCED_CURRENCY` cookie.
	 *
	 * @since 2.1.0
	 * @since 2.6.7-beta.2 Added $force parameter. Renamed to `set_currency_cookie`.
	 * @since 2.9.4-rc.6 Made public static.
	 *
	 * @param string $currency The currency code.
	 * @param bool   $force    Allow repeated setcookie calls.
	 *
	 * @return void
	 */
	public static function set_currency_cookie( $currency, $force = false ) {
		Cookie::set( self::COOKIE_FORCED_CURRENCY, $currency, YEAR_IN_SECONDS, $force );
		Cookie::set( self::COOKIE_LANGUAGE, App::instance()->getLanguage() );

		/**
		 * Do action woocommerce_multicurrency_currency_changed.
		 *
		 * @since 2.6.7
		 */
		\do_action( 'woocommerce_multicurrency_currency_changed', $currency );
	}

	/**
	 * Handle the "order-pay" WC endpoint.
	 * That's the link called "Customer payment page" in Admin->Edit order.
	 * We must set the active currency to the currency of order.
	 * Otherwise, the customer can switch currency, but the amount is not changing.
	 *
	 * @since 2.5.3
	 * @since 2.16.4 Use MC_Order (HPOS compatibility)
	 *
	 * @return string
	 */
	protected function get_order_pay_currency() {
		if ( ! \is_wc_endpoint_url( 'order-pay' ) ) {
			return '';
		}

		global $wp;
		$order_id = \absint( $wp->query_vars['order-pay'] );
		if ( ! $order_id ) {
			return '';
		}

		static $currency_cache = array();
		if ( isset( $currency_cache[ $order_id ] ) ) {
			return $currency_cache[ $order_id ];
		}

		// Cannot use wc_get_order() here because of endless loop back here when loads order data.
		$order = new MC_Order( $order_id );
		if ( ! $order->isLoaded() ) {
			return '';
		}

		$order_status = $order->get_status();
		if ( in_array( $order_status, array( 'pending', 'failed' ), true ) ) {
			$order_currency = $order->get_currency();
			// Make sure currency is still enabled.
			if ( $order_currency && API::is_currency_enabled( $order_currency ) ) {
				$currency_cache[ $order_id ] = $order_currency;

				return $currency_cache[ $order_id ];
			}
		}

		return '';
	}

	/**
	 * Act on `parse_request`.
	 *
	 * @since    2.6.7-beta.2
	 * @internal Action.
	 */
	public function action__parse_request() {

		/**
		 * When a subscription is renewed manually, using a URL like
		 * /checkout/order-pay-now/9999/?pay_for_order=true&key=wc_order_XXXX&subscription_renewal=true
		 * it does not stay on that URL and redirects to /checkout/.
		 * Therefore, our {@see get_currency_exception} for order_pay_currency does not work.
		 *
		 * Here we intercept and force it at the time of parsing the initial request, before redirect.
		 *
		 * @since    2.6.7-beta.2
		 */
		$order_pay_currency = $this->get_order_pay_currency();
		if ( $order_pay_currency ) {
			self::set_currency_cookie( $order_pay_currency, true );
		}
	}
}
