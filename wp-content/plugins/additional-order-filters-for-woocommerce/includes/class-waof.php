<?php

/**
 * Additional Order Filters for WooCommerce / Main Class
 *
 * @package   Additional Order Filters for WooCommerce
 * @author    Anton Bond
 * @license   GPL-2.0+
 * @since     1.11
 */

defined( 'ABSPATH' ) || exit;

class AOF_Woo_Additional_Order_Filters {

	function __construct() {
		add_action( 'plugins_loaded', [$this, 'woaf_load_textdomain'] );
		add_action( 'admin_enqueue_scripts', [$this, 'woaf_admin_styles_and_scripts'] );
	}

	function woaf_load_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), WOAF_PLUGIN_DOMAIN );
		load_textdomain( WOAF_PLUGIN_DOMAIN, trailingslashit( WP_LANG_DIR ) . WOAF_PLUGIN_DOMAIN . '/' . WOAF_PLUGIN_DOMAIN . '-' . $locale . '.mo' );
		load_plugin_textdomain( 'woaf-plugin', false, WOAF_PLUGIN_DOMAIN . '/languages/' );
	}

	function woaf_admin_styles_and_scripts( $page ) {
		global $typenow;

		if ( $typenow === 'shop_order' || $page === 'toplevel_page_additional-order-filters-woocommerce' || $page === 'filters-of-orders_page_custom-additional-order-filters' || $page === 'woocommerce_page_wc-orders' ) {
			wp_enqueue_style( 'woaf_admin_styles', plugins_url( 'assets/css/woaf-admin.css', dirname( __FILE__ ) ) );
		}

		if ( $typenow === 'shop_order' || $page === 'woocommerce_page_wc-orders' ) {
			wp_enqueue_script( 'woaf-admin-scripts', plugins_url( 'assets/js/woaf-admin-filters.js', dirname( __FILE__ ) ) );
			wp_set_script_translations( 'woaf-admin-scripts', 'woaf-plugin', WOAF_PLUGIN_DIR . '/languages/' );
		}

		if ( $page === 'toplevel_page_additional-order-filters-woocommerce' || $page === 'filters-of-orders_page_custom-additional-order-filters'  ) {
			wp_enqueue_script( 'woaf-admin-options-scripts', plugins_url( 'assets/js/woaf-admin-options.js', dirname( __FILE__ ) ) );
			wp_set_script_translations( 'woaf-admin-options-scripts', 'woaf-plugin', WOAF_PLUGIN_DIR . '/languages/' );
		}

		if ( $page === 'filters-of-orders_page_custom-additional-order-filters' ) {
			wp_enqueue_script( 'woaf_select2_script', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js' );
			wp_enqueue_style( 'woaf_select2_styles', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
		}

	}
}

new AOF_Woo_Additional_Order_Filters();