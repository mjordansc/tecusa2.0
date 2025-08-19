<?php
/**
 * Plugin for WP Super Cache.
 *
 * To enable, replace the value of `$wp_cache_plugins_dir` variable to:
 * $wp_cache_plugins_dir = WP_CONTENT_DIR . '/plugins/woocommerce-multicurrency/wp-super-cache/plugins';
 * in the wp-cache-config.php file
 *
 * @since        1.6.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 * @noinspection PhpUndefinedFunctionInspection
 */

/**
 * Let Super Cache react on our cookie.
 *
 * @see          \wp_cache_get_cookies_values()
 *
 * @param string $sz The cookies already considered by Super Cache.
 *
 * @return string Returned with our cookie added.
 * @noinspection PhpUnused
 */
function woomc_super_cache( $sz ) {
	$cookie_name = 'woocommerce_multicurrency_forced_currency';
	if ( isset( $_COOKIE[ $cookie_name ] ) ) {
		$sz .= '|WOOMC|' . sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
	}

	return $sz;
}

/**
 * Prints information about this plugin in the Super Cache's "Plugins" admin tab.
 *
 * @noinspection PhpUnused
 */
function woomc_super_cache_admin() {
	?>
	<h4><?php esc_html_e( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' ); ?></h4>
	<p><?php esc_html_e( 'Provides support for multiple currencies in WooCommerce store by making different cache snapshots for different currencies.', 'woocommerce-multicurrency' ); ?></p>
	<label>
		<input type="radio" checked="checked"/>
		<?php esc_html_e( 'Enabled', 'wp-super-cache' ); ?>
	</label>

	<?php
}

// Hook our actions.
add_cacheaction( 'wp_cache_get_cookies_values', 'woomc_super_cache' );
add_cacheaction( 'cache_admin_page', 'woomc_super_cache_admin' );

// Include the bundled plugins.
$wp_super_cache_plugins = glob( WPCACHEHOME . 'plugins/*.php' );
if ( is_array( $wp_super_cache_plugins ) ) {
	foreach ( $wp_super_cache_plugins as $a_plugin ) {
		if ( is_file( $a_plugin ) ) {
			/**
			 * Include.
			 *
			 * @noinspection PhpIncludeInspection
			 */
			require_once $a_plugin;
		}
	}
}
