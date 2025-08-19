<?php
/*
 * Plugin Name: Acowebs Product Labels For Woocommerce Pro
 * Version: 4.0.0
 * Description: Product Labels For Woocommerce Pro
 * Author: Acowebs
 * Author URI: http://acowebs.com
 * Requires at least: 4.9
 * Tested up to: 6.8
 * Text Domain: aco-product-labels-for-woocommerce-pro
 * WC requires at least: 4.9
 * WC tested up to: 9.8
 * Requires Plugins: woocommerce
 */

define('ACOPLW_POST_TYPE', 'acoplw_badges');
define('ACOPLW_PRODUCT_LIST', 'acoplw_prod_list');
define('ACOPLW_DP_PRODUCT_LIST', 'awdp_pt_products'); // Dynamic Pricing Product List
define('ACOPLW_DP_RULES', 'awdp_pt_rules'); // Dynamic Pricing Rules
define('ACOPLW_PRODUCTS', 'product'); // WC Products

define('ACOPLW_TOKEN', 'acoplw');
define('ACOPLW_VERSION', '4.0.0');
define('ACOPLW_FILE', __FILE__);
define('ACOPLW_URL', plugin_dir_url(__FILE__));
define('ACOPLW_ITEM_ID', 116373);
define('ACOPLW_PLUGIN_NAME', 'Product Labels For Woocommerce');
define('ACOPLW_PRODUCTS_TRANSIENT_KEY', 'acoplw_list_key');
define('ACOPLW_PRODUCTS_LANG_TRANSIENT_KEY', 'acoplw_list_lang_key');
define('ACOPLW_PRODUCTS_SCHEDULE_TRANSIENT_KEY', 'acoplw_onsale_key');
define('ACOPLW_API_TRANSIENT_KEY', 'acoplw_badges_key');
define('ACOPLW_STORE_URL', 'https://api.acowebs.com');
define('ACOPLW_CDN_URL', 'https://woo-product-labels.acowebscdn.com'); //URL for external resources
// define('ACOPLW_LICENSE_TRANSIENT_KEY', 'acoplw_license_key');

define('ACOPLW_Wordpress_Version', get_bloginfo('version'));

function acoplw_check_free_version() {

    if ( in_array ( 'aco-product-labels-for-woocommerce/start.php', apply_filters ( 'active_plugins', get_option ( 'active_plugins' ) ) ) ) {
        deactivate_plugins(WP_PLUGIN_DIR . '/aco-product-labels-for-woocommerce/start.php'); // deactivate free version
        if ( in_array ( 'aco-product-labels-for-woocommerce/start.php', apply_filters ( 'active_plugins', get_option ( 'active_plugins' ) ) ) ) {
            $free_version_name = get_plugin_data ( WP_PLUGIN_DIR . '/aco-product-labels-for-woocommerce/start.php' );
            $message = 'Please remove ' . $free_version_name['Name'] . ' in order to function this plugin properly';
            echo $message; // Deactivation message
            @trigger_error ( $message, E_USER_ERROR );
        }
    }

}
register_activation_hook(__FILE__, 'acoplw_check_free_version');

if ( !function_exists('acoplw_init') ) {

    function acoplw_init()
    {
        $plugin_rel_path = basename(dirname(__FILE__)) . '/languages'; /* Relative to WP_PLUGIN_DIR */
        load_plugin_textdomain('aco-product-labels-for-woocommerce', false, $plugin_rel_path);
    }

}


if ( !function_exists('acoplw_autoloader') ) {

    function acoplw_autoloader($class_name)
    {
        if ( 0 === strpos($class_name, 'ACOPLW') ) {
            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
            require_once $classes_dir . $class_file;
        }
    }

}

/*
 * Creating acoplw-badges folder on wordpress upload directory
 * ver 4.0.0
 */
function create_acoplw_badges_folder() {
    $upload_dir = wp_upload_dir();
    $folder_name = 'acoplw-badges';
    $target_dir = $upload_dir['basedir'] . '/' . $folder_name;
    if (!is_dir($target_dir)) {
        if (!@mkdir($target_dir, 0755, true)) {
            add_action('admin_notices', function() use ($folder_name, $target_dir) {
                echo '<div class="notice notice-error"><p>⚠️ Failed to create the folder <strong>' . esc_html($folder_name) . '</strong>. Please check the permissions for: <code>' . esc_html($target_dir) . '</code></p></div>';
            });
        }
    }
}

add_action('admin_init', 'create_acoplw_badges_folder');

if ( !function_exists('ACOPLW') ) {

    function ACOPLW()
    {
        $instance = ACOPLW_Backend::instance(__FILE__, ACOPLW_VERSION);
        return $instance;
    }

}
add_action('plugins_loaded', 'acoplw_init');
spl_autoload_register('acoplw_autoloader');
if ( is_admin() ) {
    ACOPLW();
}
new ACOPLW_Api();

$badge = new ACOPLW_Badge();

new ACOPLW_Front_End($badge, __FILE__, ACOPLW_VERSION);

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );