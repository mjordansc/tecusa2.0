<?php
/*
	Plugin Name: Additional Order Filters for WooCommerce
	Description: Adds additional order filters for WooCommerce
	Version: 1.23
	Requires at least: 6.2
	Requires PHP: 7.0
	Author: Anton Bond
	Author URI: facebook.com/antonbondarevych
	License: GPL2
	Text Domain: woaf-plugin
	Domain Path: /languages
	 
	Additional Order Filters for WooCommerce is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	any later version.
	 
	Additional Order Filters for WooCommerce is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	 
	You should have received a copy of the GNU General Public License
	along with Additional Order Filters for WooCommerce. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	define('WOAF_PLUGIN_DOMAIN', 'additional-order-filters-for-woocommerce');
	define('WOAF_PLUGIN_DIR', plugin_dir_path( __FILE__ ));

	// load classes
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-waof.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-waof-admin-options.php' );

	//check if HPOS enabled
	$option = get_option('woocommerce_custom_orders_table_enabled');

	if ( !empty($option) && $option == 'yes' )
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-waof-default-filters-hpos-storage.php' );
	else
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-waof-default-filters.php' );
	

} else {
	add_action( 'admin_notices', 'woaf_woocoommerce_deactivated' );
}

/**
* WooCommerce Deactivated Notice
**/
if ( ! function_exists( 'woaf_woocoommerce_deactivated' ) ) {

	function woaf_woocoommerce_deactivated() {
		echo '<div class="error"><p>' . sprintf( __( 'Additional Order Filters for WooCommerce %s to be installed and active.', 'woaf-plugin' ), '<a href="https://www.woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</p></div>';
	}
}

if (is_admin()) {
    add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'woaf_plugin_settings_link');

	function woaf_plugin_settings_link($links) { 
		$settings_link = '<a href="admin.php?page=additional-order-filters-woocommerce">Settings</a>'; 
		array_unshift( $links, $settings_link ); 
		return $links; 
	}
}