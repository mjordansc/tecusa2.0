<?php
/**
 * Plugin Name: TIV Multi-currency
 * Plugin URI: https://woocommerce.com/products/multi-currency/
 * Version: 4.5.0
 * Description: Multi-currency support for WooCommerce
 * Author: TIV.NET INC
 * Author URI: https://woocommerce.com/vendor/tiv-net-inc/
 * Developer: TIV.NET
 * Developer URI: https://tivnet.com/
 * Text Domain: woocommerce-multicurrency
 * Domain Path: /languages/
 * Requires at least: 6.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0.0
 * WC tested up to: 10.0.2
 *
 * Copyright: © 2025 TIV.NET INC.
 * License: GPL-3.0-or-later
 * License URI: https://spdx.org/licenses/GPL-3.0-or-later.html
 *
 * @noinspection PhpDefineCanBeReplacedWithConstInspection
 * Woo: 3202901:9b5d903ce4283ced8ede8522c606324b

 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WOOMC\Integration\Cache\WPRocket;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Short-circuit special WP calls.
 *
 * @since 1.18.3
 * @since 2.16.4 Also check for 'WP_CLI' and 'favicon\.ico|\.txt'
 * @since 3.4.1 Added /wp-json/wc-admin/(onboarding|options) to the regex.
 * @since 4.4.3-0 Allow `wp cron event run` WP_CLI.
 */
if (
	// xmlrpc.php:13
	( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
	|| (
		! empty( $_SERVER['REQUEST_URI'] )
		&& preg_match( '^/wp-(login|signup|trackback)\.php|favicon\.ico|\.txt|/wp-json/wc-admin/(onboarding|options)^i', \esc_url_raw( \wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) )
) {
	return;
}
if ( ! is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}
require_once __DIR__ . '/vendor/autoload.php';

if ( ! class_exists( 'WOOMC\Dependencies\TIVWP\Plugin' ) ) {
	return;
}

\add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'product_block_editor', plugin_basename( __FILE__ ), false );
		}
	}
);

define( 'WOOCOMMERCE_MULTICURRENCY_VERSION', '4.5.0' );

( new WOOMC\Dependencies\TIVWP\Plugin(
	__FILE__,
	'WOOMC',
	array(
		__DIR__ . '/vendor/WOOMC/TIVWP',
	)
) )->setup_hooks();

( new WPRocket(__FILE__) )->setup_hooks();


