<?php
/**
 * Add currency selector to WooCommerce Analytics
 *
 * @since 2.5.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin\Analytics;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use WOOMC\Abstracts\Hookable;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\API;
use WOOMC\App;
use WOOMC\Dependencies\TIVWP\WC\WCEnv;
use WOOMC\Log;

/**
 * Class Admin\Analytics\Controller
 *
 * @since 2.5.0
 */
class Controller extends Hookable {

	/**
	 * Name of the JS script.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	protected const JS_NAME = 'analytics';

	/**
	 * WC-Admin pages that need the currency filter.
	 *
	 * @since 2.5.0
	 * @var string[]
	 */
	protected const ANALYTICS_PAGES = array(
		'overview',
		'orders',
		'revenue',
		'products',
		'categories',
		'coupons',
		'taxes',
		'variations',
	);

	/**
	 * Var is_legacy.
	 *
	 * @since 4.4.5
	 *
	 * @var bool
	 */
	protected static $is_legacy = false;

	/**
	 * Setup actions and filters.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Do nothing if WC admin disabled.
		 */
		if ( \apply_filters( 'woocommerce_admin_disabled', false ) ) {
			return;
		}

		self::$is_legacy = ! WCEnv::is_custom_order_table_usage_enabled();

		\add_action( 'init', array( $this, 'add_currency_settings' ) );

		foreach ( self::ANALYTICS_PAGES as $analytics_page ) {
			\add_filter(
				"woocommerce_analytics_{$analytics_page}_query_args",
				array( $this, 'apply_currency_arg' )
			);
			\add_filter( "woocommerce_analytics_{$analytics_page}_stats_query_args",
				array( $this, 'apply_currency_arg' )
			);
		}

		1 && \add_filter(
			'woocommerce_analytics_clauses_join',
			array( $this, 'filter__clauses_join' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		1 && \add_filter(
			'woocommerce_analytics_clauses_where',
			array( $this, 'filter__clauses_where' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		1 && \add_filter(
			'woocommerce_analytics_clauses_select',
			array( $this, 'filter__clauses_select' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_action(
			'admin_enqueue_scripts',
			array( $this, 'add_extension_register_script' )
		);
	}

	/**
	 * Add currency settings to the AssetDataRegistry.
	 *
	 * @since 2.5.0
	 * @since 2.14.0 Decode HTML entity in currency names. Ex: "Polish z&#x142;oty".
	 */
	public function add_currency_settings() {

		$enabled_currencies = API::enabled_currencies();

		$currency_names = API::currency_names();

		$currencies = array();
		foreach ( $enabled_currencies as $enabled_currency ) {
			$currencies[] = array(
				'label' => $enabled_currency . ': ' . trim( html_entity_decode( $currency_names[ $enabled_currency ], ENT_COMPAT, 'UTF-8' ) ),
				'value' => $enabled_currency,
			);
		}

		$WooMC = array(
			'i18n'       => array(
				'Currency' => \__( 'Currency', 'woocommerce' ),
			),
			'currencies' => $currencies,
		);

		try {

			$depend_on = array(
				'\Automattic\WooCommerce\Blocks\Package',
			);
			foreach ( $depend_on as $class_name ) {
				if ( ! class_exists( $class_name, false ) ) {
					throw new \Exception( 'Class not loaded: ' . $class_name );
				}
			}

			/**
			 * AssetDataRegistry.
			 *
			 * @var AssetDataRegistry $data_registry
			 */
			$data_registry = Package::container()->get( AssetDataRegistry::class );

			/**
			 * The $data_registry->add method has changed at some point.
			 * Was: \method_exists( $data, '__invoke' )
			 * Now: \is_callable( $data )
			 * 3rd parties might include an older (broken) version.
			 * Here is a hack to prevent a fatal error.
			 */
			if ( method_exists( $data_registry, 'exists' ) ) {
				$data_registry->add( 'WooMC', $WooMC );
			} else {
				Log::error( new Message( 'Internal error: $data_registry->add is broken.' ) );
			}

		} catch ( \Exception $e ) {
			Log::error( $e );
		}
	}

	/**
	 * Return the active currency: from _GET or store default.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	protected function get_active_currency() {
		$currency = Env::get_http_get_parameter( 'currency' );

		return $currency ? $currency : API::default_currency();
	}

	/**
	 * Add currency to the admin GET query arguments.
	 *
	 * @since 2.5.0
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function apply_currency_arg( $args ) {

		$args['currency'] = $this->get_active_currency();

		return $args;
	}

	/**
	 * Is sql_with_subquery?
	 * When a report is filtered by specific product or category,
	 * Analytics first makes a subquery with a GROUP BY clause.
	 * In subquery, we need the join, where and select restrictions by currency.
	 * After that, it makes a "wrapper" query, with the subquery is one of the "fields".
	 * The wrapper query (a) cannot use JOIN; (b) must use the `currency` field from the subquery.
	 * Without this "hack", queries fail with
	 * "WordPress database error Unknown column 'wp_wc_order_stats.order_id' in 'on clause' for query"
	 *
	 * @since 2.15.2-beta.1
	 *
	 * @param string $context
	 *
	 * @return bool
	 */
	protected function is_sql_with_subquery( $context ) {
		return 'products' === $context && (
				Env::is_parameter_in_http_get( 'categories' ) ||
				Env::is_parameter_in_http_get( 'products' )
			);
	}

	/**
	 * Add currency to the JOIN clause.
	 *
	 * @since 2.5.0
	 *
	 * @param string[] $clauses The array of clauses.
	 * @param string   $context Context.
	 *
	 * @return string[]
	 */
	public function filter__clauses_join( $clauses, $context ) {

		if ( $this->is_on_customers_page( $context ) ) {
			return $clauses;
		}
		if ( $this->is_sql_with_subquery( $context ) ) {
			return $clauses;
		}

		global $wpdb;

		if ( self::$is_legacy ) {
			$clauses[] = "JOIN {$wpdb->postmeta} currency_postmeta ON {$wpdb->prefix}wc_order_stats.order_id = currency_postmeta.post_id";
		} else {
			$orders_table = \esc_sql( OrdersTableDataStore::get_orders_table_name() );
			// Keeping "AS currency_postmeta" historical.
			$clauses[] = "JOIN {$orders_table} currency_postmeta ON {$wpdb->prefix}wc_order_stats.order_id = currency_postmeta.id";
		}

		return $clauses;
	}

	/**
	 * Add currency to the WHERE clause.
	 *
	 * @since 2.5.0
	 *
	 * @param string[] $clauses The array of clauses.
	 * @param string   $context Context.
	 *
	 * @return string[]
	 */
	public function filter__clauses_where( $clauses, $context ) {

		if ( $this->is_on_customers_page( $context ) ) {
			return $clauses;
		}
		if ( $this->is_sql_with_subquery( $context ) ) {
			return $clauses;
		}

		$currency = $this->get_active_currency();
		if ( self::$is_legacy ) {
			$clauses[] = "AND currency_postmeta.meta_key = '_order_currency' AND currency_postmeta.meta_value = '{$currency}'";
		} else {
			$clauses[] = "AND currency_postmeta.currency = '{$currency}'";
		}

		return $clauses;
	}

	/**
	 * Are we on the WooCommerce->Customers page?
	 *
	 * @since 2.15.3
	 *
	 * @param string $context The context of the filters.
	 *
	 * @return bool
	 */
	protected function is_on_customers_page( $context ) {
		if ( in_array( $context,
			array(
				'customers_subquery',
				'customers_stats_subquery',
				'customers_stats',
			), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add currency to the SELECT clause.
	 *
	 * @since 2.5.0
	 *
	 * @param string[] $clauses The array of clauses.
	 * @param string   $context Context.
	 *
	 * @return string[]
	 */
	public function filter__clauses_select( $clauses, $context ) {

		if ( $this->is_on_customers_page( $context ) ) {
			return $clauses;
		}

		if ( ! $this->is_sql_with_subquery( $context ) ) {
			if ( self::$is_legacy ) {
				$clauses[] = ', currency_postmeta.meta_value AS currency';
			} else {
				$clauses[] = ', currency_postmeta.currency AS currency';
			}
		} else {
			$clauses[] = ', currency';
		}

		return $clauses;
	}

	/**
	 * Register the JS.
	 *
	 * @since 2.5.0
	 * @since 2.15.5 Do not use PageController or Loader classes (WC backward incompatibility and warnings).
	 */
	public
	function add_extension_register_script() {

		if ( 'wc-admin' !== Env::get_http_get_parameter( 'page' ) ) {
			return;
		}

		$script_url = App::instance()->plugin_dir_url() . 'assets/js/' . self::JS_NAME . '.min.js';

		\wp_enqueue_script(
			'woomc-' . self::JS_NAME,
			$script_url,
			array( 'wp-hooks' ),
			WOOCOMMERCE_MULTICURRENCY_VERSION,
			true
		);
	}
}
