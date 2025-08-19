<?php
/**
 * Frontend controller.
 *
 * @since 2.6.3-rc.2
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Frontend;

use WOOMC\App;
use WOOMC\Currency\Detector;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\Scripting;

/**
 * Class Frontend\Controller
 */
class Controller implements InterfaceHookable {

	/**
	 * Security hash.
	 *
	 * @var string
	 */
	protected const SECURITY_HASH = '0c6d616f3a1f137ac9d96c7ceb1101d0';

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		Scripting::init();

		if ( Scripting::is_lazy_mode() ) {
			\add_action( 'wp_loaded', array( $this, 'enqueue_scripts' ), App::HOOK_PRIORITY_LATE );
		} else {
			\add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), App::HOOK_PRIORITY_LATE );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		// Check if user agent is a bot
		if ( $this->is_bot_user_agent() ) {
			return;
		}

		$this->make_state_object();
		$this->inline_front_libs_js();

		$url_assets_js = App::instance()->plugin_dir_url() . 'assets/js/';

		Scripting::enqueue_script(
			'woomc-frontend',
			$url_assets_js . 'frontend.min.js',
			array(),
			WOOCOMMERCE_MULTICURRENCY_VERSION,
			array(),
			1
		);
	}

	/**
	 * Check if user agent is a bot.
	 *
	 * @since 3.2.4-0
	 * @since 4.4.4 Added "Chrome-Lighthouse" (https://pagespeed.web.dev/)
	 *
	 * @return bool
	 */
	protected function is_bot_user_agent() {
		$user_agent = \wc_get_user_agent();

		// Check if the user_agent is an empty string and treat it as a bot
		if ( empty( $user_agent ) ) {
			return true;
		}

		$bot_user_agents = array( 'googlebot', 'bingbot', 'gtmetrix', 'lighthouse' );
		$user_agent      = strtolower( $user_agent );

		foreach ( $bot_user_agents as $bot ) {
			if ( strpos( $user_agent, $bot ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Method sanitized_current_url.
	 *
	 * @since 4.3.0-3
	 * @return string
	 */
	public static function sanitized_current_url() {
		return \remove_query_arg(
			array(
				Detector::GET_FORCED_CURRENCY,
				'min_price',
				'max_price',
			),
			Env::current_url()
		);
	}

	/**
	 * Add the `woomc` global object via localize.
	 *
	 * @since 2.8.3 Remove min_max_price from the URL (added by Filter-by-price widget).
	 * @since 4.3.0-2 Added COOKIEPATH and COOKIE_DOMAIN.
	 *
	 * @return void
	 */
	protected function make_state_object() {

		// Flags.
		// https://github.com/lipis/flag-icons
		$url_flags = 'https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/css/flag-icons.min.css';

		$is_secure = $this->is_secure();

		$state = array(
			'currentURL'       => self::sanitized_current_url(),
			'currency'         => \get_woocommerce_currency(),
			'cookieSettings'   => array(
				'name'    => Detector::COOKIE_FORCED_CURRENCY,
				'expires' => YEAR_IN_SECONDS,
				'path'    => COOKIEPATH ? COOKIEPATH : '/',
				'domain'  => COOKIE_DOMAIN,
			),
			'console_log'      => $is_secure ? 'Y' : 'N',
			'settings'         => array(
				'woocommerce_default_customer_address' => $is_secure ? \get_option( 'woocommerce_default_customer_address' ) : '***',
			),
			'url_flags'        => $url_flags,
			'front_libs_ready' => false,
		);


		Scripting::enqueue_data_script( 'woomc', $state );
	}

	/**
	 * Method inline_front_libs_js.
	 *
	 * @since 4.3.0-3
	 * @return void
	 */
	protected function inline_front_libs_js() {
		\add_action( 'wp_footer', function () {
			echo '<script id="woomc-front-libs-js">';
			include App::instance()->plugin_dir_path() . 'assets/js/front-libs.min.js';
			echo '</script>';
		} );
	}

	/**
	 * True is security cookie is set.
	 *
	 * @return bool
	 */
	protected function is_secure() {

		$secure = false;

		foreach ( $_COOKIE as $k => $v ) {
			if ( hash_equals( self::SECURITY_HASH, md5( $k ) ) && $v ) {
				$secure = true;
				break;
			}
		}

		return $secure;
	}
}
