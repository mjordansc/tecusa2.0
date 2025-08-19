<?php
/**
 * Integration with WP Rocket cache.
 *
 * @since 2.7.1
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Cache;

use WOOMC\Abstracts\Hookable;
use WOOMC\API;
use WOOMC\App;
use WOOMC\Currency\Detector;
use WOOMC\DAO\WP;
use WOOMC\Dependencies\TIVWP\Constants;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Log;
use WOOMC\Settings\Fields;

/**
 * Class WPRocket
 *
 * @since 2.7.1
 */
class WPRocket extends Hookable {

	/**
	 * Rocket mandatory cookie option.
	 *
	 * @since 2.8.5
	 * @var string
	 */
	protected const OPTION_MANDATORY_COOKIE = 'wp_rocket_mandatory_cookie';

	/**
	 * The __FILE__ from Loader is passed to the Constructor.
	 * Used in the (de)activation hooks.
	 *
	 * @since 4.4.4
	 *
	 * @var string
	 */
	protected static $woomc_plugin_file = '';

	/**
	 * Rocket status: is installed?
	 *
	 * @since 4.4.4
	 *
	 * @var bool
	 */
	protected static $is_rocket_installed = false;

	/**
	 * Rocket status: is active?
	 *
	 * @since 4.4.4
	 *
	 * @var bool
	 */
	protected static $is_rocket_active = false;

	/**
	 * Constructor WP_Rocket
	 *
	 * @since 4.4.4
	 *
	 * @param string $woomc_plugin_file The __FILE__ from Loader.
	 */
	public function __construct( string $woomc_plugin_file ) {
		self::$woomc_plugin_file = $woomc_plugin_file;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @since        2.7.1
	 *
	 * @return void
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	public function setup_hooks() {

		$this->check_rocket_state();

		if ( self::$is_rocket_installed ) {
			$this->do_if_rocket_installed();
		}
		if ( self::$is_rocket_active ) {
			$this->do_if_rocket_active();
		}
	}

	/**
	 * Check the Rocket state.
	 *
	 * @since 4.4.4
	 * @return void
	 */
	protected function check_rocket_state(): void {
		if ( Constants::is_true( 'WP_ROCKET_ADVANCED_CACHE' ) ) {
			self::$is_rocket_installed = true;
			self::$is_rocket_active    = true;

			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = \get_plugins();
		if ( isset( $plugins['wp-rocket/wp-rocket.php'] ) ) {
			self::$is_rocket_installed = true;
			if ( \is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
				self::$is_rocket_active = true;
			}
		}
	}

	/**
	 * Do stuff if Rocket is installed.
	 *
	 * @since 4.4.4
	 * @return void
	 */
	protected function do_if_rocket_installed(): void {
		$this->set_rocket_caching_options();
	}

	/**
	 * Do stuff if Rocket is active.
	 *
	 * @since 4.4.4
	 * @return void
	 */
	protected function do_if_rocket_active(): void {
		\register_activation_hook(
			self::$woomc_plugin_file,
			function () {
				self::flush_rocket();
			}
		);

		\register_deactivation_hook(
			self::$woomc_plugin_file,
			function () {
				self::on_woomc_deactivation();
			}
		);

		\add_filter(
			'woocommerce_multicurrency_settings_fields',
			function ( $all_fields ) {
				$this->section_wp_rocket( $all_fields );

				return $all_fields;
			}
		);

		\add_action(
			'woocommerce_multicurrency_after_save_settings',
			function () {
				self::flush_rocket();
			}
		);
	}

	/**
	 * Do stuff on WOOMC deactivation.
	 *
	 * @since 4.4.4
	 * @return void
	 */
	protected static function on_woomc_deactivation(): void {

		// Remove our filters before flushing Rocket, so its config file is clean.

		\remove_filter(
			'rocket_cache_mandatory_cookies',
			array( __CLASS__, 'filter__rocket_cache_mandatory_cookies' ),
			App::HOOK_PRIORITY_LATE
		);

		\remove_filter(
			'rocket_cache_dynamic_cookies',
			array( __CLASS__, 'filter__rocket_cache_dynamic_cookies' ),
			App::HOOK_PRIORITY_LATE
		);

		self::flush_rocket();
	}

	/**
	 * Tell WP Rocket that:
	 * - pages vary by our cookie
	 * - there must be no rewrite rules in .htaccess
	 *
	 * @since 2.7.1
	 * @return void
	 */
	protected function set_rocket_caching_options(): void {

		\add_filter(
			'rocket_htaccess_mod_rewrite',
			'__return_empty_string',
			App::HOOK_PRIORITY_LATE
		);

		\add_filter(
			'rocket_cache_mandatory_cookies',
			array( __CLASS__, 'filter__rocket_cache_mandatory_cookies' ),
			App::HOOK_PRIORITY_LATE
		);

		\add_filter(
			'rocket_cache_dynamic_cookies',
			array( __CLASS__, 'filter__rocket_cache_dynamic_cookies' ),
			App::HOOK_PRIORITY_LATE
		);

		/**
		 * Do not cache empty cart. If cached, the currency symbol never changes until you put something in the cart.
		 *
		 * @since 2.9.4-rc.2
		 */
		\add_filter( 'rocket_cache_wc_empty_cart', '__return_false' );

		\add_filter(
			'rocket_preload_before_preload_url',
			array( $this, 'filter__rocket_preload_before_preload_url' ),
			App::HOOK_PRIORITY_LATE
		);
	}

	/**
	 * Add our cookie to the "special treatment" by WPRocket.
	 *
	 * As per WPRocket support:
	 * - The mandatory one means that cache files are not served for the current visitor
	 *   until this specific cookie has a value.
	 * - The dynamic one means to create different cache files depending on the cookie value.
	 *
	 * @url https://docs.wp-rocket.me/article/1313-create-different-cache-files-with-dynamic-and-mandatory-cookies
	 *
	 * @since        2.8.5
	 *
	 * @param string[] $cookies Cookies list.
	 *
	 * @return string[]
	 * @internal     Filter.
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public static function filter__rocket_cache_mandatory_cookies( $cookies ) {

		if ( \wc_string_to_bool( \get_option( WP::OPTIONS_PREFIX . self::OPTION_MANDATORY_COOKIE, false ) ) ) {
			$cookies[] = Detector::COOKIE_FORCED_CURRENCY;
		}

		return $cookies;
	}

	/**
	 * Add our cookie to the "special treatment" by WPRocket.
	 *
	 * @since        2.7.1
	 *
	 * @param string[] $cookies Cookies list.
	 *
	 * @return string[]
	 * @link         https://docs.wp-rocket.me/article/1313-create-different-cache-files-with-dynamic-and-mandatory-cookies
	 *
	 * @internal     Filter.
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public static function filter__rocket_cache_dynamic_cookies( $cookies ) {

		$cookies[] = Detector::COOKIE_FORCED_CURRENCY;

		return $cookies;
	}

	/**
	 * Regenerate WP Rocket configs and clear the cache.
	 *
	 * @since 2.7.1
	 *
	 * @return void
	 */
	protected static function flush_rocket(): void {

		if ( ! function_exists( 'rocket_generate_config_file' ) ) {
			Log::error( new Message( 'WP-Rocket methods not found' ) );

			return;
		}

		try {
			/**
			 * Update the WP Rocket .htaccess rules.
			 *
			 * @since 2.9.4-rc.2 Set "remove_rules" to True.
			 */
			\flush_rocket_htaccess( true );

			// Update the WP Rocket config file.
			\rocket_generate_config_file();

			// Clear WP Rocket cache.
			\rocket_clean_domain();

			Log::debug( new Message( 'WP-Rocket cache cleared.' ) );

		} catch ( \Exception $e ) {
			Log::error( $e );
		}
	}

	/**
	 * "Preload" support
	 * Based on:
	 *
	 * @link https://docs.wp-rocket.me/article/1676-preload-custom-cookie
	 * @link https://github.com/wp-media/wp-rocket-helpers/blob/master/preload/wp-rocket-preload-dynamic-cookies/wp-rocket-preload-dynamic-cookies.php
	 */

	/**
	 * Helper method rocket_get_combinations.
	 *
	 * @since 4.4.4
	 *
	 * @param array ...$arrays Arrays to combine.
	 *
	 * @return array|array[]
	 */

	protected function rocket_get_combinations( ...$arrays ): array {
		$result = array( array() );
		foreach ( $arrays as $property => $property_values ) {
			$tmp = array();
			foreach ( $result as $result_item ) {
				foreach ( $property_values as $property_value ) {
					$tmp[] = array_merge( $result_item, [ $property => $property_value ] );
				}
			}
			$result = $tmp;
		}

		return $result;
	}

	/**
	 * Helper method rocket_flatten_array.
	 *
	 * @since 4.4.4
	 *
	 * @param array $arr Array of cookies.
	 *
	 * @return array
	 */
	protected function rocket_flatten_array( array $arr ): array {
		$output = array();
		foreach ( $arr as $item => $values ) {
			$row = array();
			foreach ( $values as $value ) {
				$row[] = array( $item, $value );
			}
			$output[] = $row;
		}

		return $output;
	}

	/**
	 * Filter filter__rocket_preload_before_preload_url.
	 *
	 * @since        4.4.4
	 *
	 * @param array $requests Array of Preload requests.
	 *
	 * @return array
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function filter__rocket_preload_before_preload_url( $requests ) {

		// Original comment from the Rocket's code:
		// edit the cookie names and values you want to preload here
		// you can uncomment the 2nd set if you want to preload the values of two different cookies
		$cookies = array(
			'woocommerce_multicurrency_forced_currency' => API::enabled_currencies(),

			/**
			 * Unused, keep for the reference.
			 * <code>
			 *'lang' => [
			 *    'en',
			 *    'es',
			 *],
			 * </code>
			 */
		);

		$cookies = $this->rocket_flatten_array( $cookies );

		$cookies_combines = $this->rocket_get_combinations( ...$cookies );

		$output = array();

		foreach ( $cookies_combines as $cookies ) {
			foreach ( $requests as $request ) {
				$wp_cookies = array();

				/**
				 * Unused $cookie
				 *
				 * @noinspection PhpUnusedLocalVariableInspection
				 */
				foreach ( $cookies as $cookie => $values ) {
					$wp_cookie    = new \WP_Http_Cookie(
						array(
							'name'  => $values[0],
							'value' => $values[1],
						)
					);
					$wp_cookies[] = $wp_cookie;
				}
				$request['headers']['cookies'] = $wp_cookies;

				$output[] = $request;
			}
		}

		return $output;
	}

	/**
	 * Section "WP Rocket" in our settings panel.
	 *
	 * @param array $fields Reference to the All Fields array.
	 *
	 * @return void
	 */
	protected function section_wp_rocket( array &$fields ) {

		$section_id    = Fields::SECTION_ID_PREFIX . 'wp_rocket';
		$section_title = __( 'WP Rocket', 'woocommerce-multicurrency' );
		$section_desc  = '<i class="dashicons dashicons-info"></i>' . __( 'Note: saving changes clears the WP Rocket file cache.', 'woocommerce-multicurrency' );

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$fields[] = array(
			'id'      => WP::OPTIONS_PREFIX . self::OPTION_MANDATORY_COOKIE,
			'title'   => __( 'Set cache mandatory cookie (recommended)', 'woocommerce-multicurrency' ),
			'desc'    => __( "If checked, no cache will be served until the Multi-currency cookie is set in the visitor's browser.", 'woocommerce-multicurrency' ),
			'type'    => 'checkbox',
			'default' => 'yes',
		);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}
}
