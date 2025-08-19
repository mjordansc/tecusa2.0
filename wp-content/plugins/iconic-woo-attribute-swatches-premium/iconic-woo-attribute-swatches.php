<?php
/**
 * Plugin Name: WooCommerce Attribute Swatches by Iconic
 * Plugin URI: https://iconicwp.com
 * Description: Swatches for your variable products.
 * Version: 1.20.2
 * Author: Iconic <support@iconicwp.com>
 * Author URI: https://iconicwp.com
 * Text Domain: iconic-was
 * WC requires at least: 2.6.14
 * WC tested up to: 9.8.2
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	wp_die();
}

use Iconic_WAS_NS\StellarWP\ContainerContract\ContainerInterface;

class Iconic_Woo_Attribute_Swatches {
	/**
	 * Long name
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $name
	 */
	public static $name = 'WooCommerce Attribute Swatches';

	/**
	 * Short name
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $shortname
	 */
	protected $shortname = 'Attribute Swatches';

	/**
	 * Slug - Hyphen
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $slug
	 */
	public $slug = 'iconic-was';

	/**
	 * Class prefix
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $class_prefix
	 */
	protected $class_prefix = 'Iconic_WAS_';

	/**
	 * Version.
	 *
	 * @var string
	 */
	public static $version = '1.20.2';

	/**
	 * Plugin URL
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $plugin_url trailing slash
	 */
	protected $plugin_url;

	/**
	 * Attributes
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var Iconic_WAS_Attributes
	 */
	public $attributes;

	/**
	 * Helpers
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var Iconic_WAS_Helpers
	 */
	public $helpers;

	/**
	 * Products
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var Iconic_WAS_Products
	 */
	public $products;

	/**
	 * Swatches
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var Iconic_WAS_Swatches
	 */
	public $swatches;

	/**
	 * Settings.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * The singleton instance of the plugin.
	 *
	 * @var Iconic_Woo_Attribute_Swatches
	 */
	private static $instance;

	/**
	 * The DI container.
	 *
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * Construct
	 */
	public function __construct() {
		if ( ! $this->is_plugin_active( 'woocommerce/woocommerce.php' ) && ! $this->is_plugin_active( 'woocommerce-old/woocommerce.php' ) ) {
			return;
		}

		$this->define_constants();
		$this->load_classes();
		$this->install();

		$this->container = new Iconic_WAS_Core_Container();

		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		add_action( 'init', array( $this, 'textdomain' ) );
		add_action( 'init', array( $this, 'init_hook' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded_hook' ) );
	}

	/**
	 * Instantiate a single instance of our plugin.
	 *
	 * @return Iconic_Woo_Attribute_Swatches
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the DI container.
	 *
	 * @return ContainerInterface
	 */
	public function container() {
		return $this->container;
	}

	/**
	 * Load textdomain
	 */
	public function textdomain() {
		load_plugin_textdomain( 'iconic-was', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Load classes
	 */
	private function load_classes() {
		require_once ICONIC_WAS_PATH . 'vendor-prefixed/autoload.php';
		require_once ICONIC_WAS_INC_PATH . 'class-core-autoloader.php';

		Iconic_WAS_Core_Autoloader::run(
			array(
				'prefix'   => 'Iconic_WAS_',
				'inc_path' => ICONIC_WAS_INC_PATH,
			)
		);

		$this->init_license();
		$this->init_telemetry();

		Iconic_WAS_Core_Settings::run(
			array(
				'vendor_path'   => ICONIC_WAS_VENDOR_PATH,
				'title'         => 'WooCommerce Attribute Swatches',
				'version'       => self::$version,
				'menu_title'    => 'Attribute Swatches',
				'settings_path' => ICONIC_WAS_INC_PATH . 'admin/settings.php',
				'option_group'  => 'iconic_was',
				'docs'          => array(
					'collection'      => 'woocommerce-attribute-swatches/',
					'troubleshooting' => 'woocommerce-attribute-swatches/was-troubleshooting/',
					'getting-started' => 'woocommerce-attribute-swatches/how-to-install-woocommerce-attribute-swatches/',
				),
				'cross_sells'   => array(
					'iconic-woo-show-single-variations',
					'iconic-woothumbs',
				),
			)
		);

		$this->attributes_class()->run();
		$this->products_class()->run();
		Iconic_WAS_Compat_WPML::run();
		Iconic_WAS_Compat_Flatsome::run();
		Iconic_WAS_Compat_Salient::run();
		Iconic_WAS_Compat_Woo_Variations_Table::run();
		Iconic_WAS_Compat_Oceanwp::run();
		Iconic_WAS_Fees::run();
		Iconic_WAS_Shortcodes::run();
		Iconic_WAS_Compat_WooCS::run();
		Iconic_WAS_Compat_Aelia_Currency_Switcher::run();
		Iconic_WAS_Compat_Woo_Fees_And_Discounts::run();
		Iconic_WAS_Compat_WCML::run();
		Iconic_WAS_Compat_Easy_WooCommerce_Discounts::run();
		Iconic_WAS_Compat_Curcy_WooCommerce_Multi_Currency::run();

		include_once ICONIC_WAS_PATH . 'inc/admin/product-block-editor/class-product-block-editor.php';
		Iconic_WAS_Product_Block_Editor::run();

		add_action( 'plugins_loaded', array( 'Iconic_WAS_Core_Onboard', 'run' ), 10 );
	}

	/**
	 * Init license class.
	 */
	public function init_license() {
		// Allows us to transfer Freemius license.
		if ( file_exists( ICONIC_WAS_PATH . 'class-core-freemius-sdk.php' ) ) {
			require_once ICONIC_WAS_PATH . 'class-core-freemius-sdk.php';

			new Iconic_WAS_Core_Freemius_SDK(
				array(
					'plugin_path'        => ICONIC_WAS_PATH,
					'plugin_file'        => ICONIC_WAS_FILE,
					'uplink_plugin_slug' => 'iconic-was',
					'freemius'           => array(
						'id'         => '1041',
						'slug'       => 'iconic-woo-attribute-swatches',
						'public_key' => 'pk_7b128a35b24f5882ab7935dc845d4',
					),
				)
			);
		}

		Iconic_WAS_Core_License_Uplink::run(
			array(
				'basename'        => ICONIC_WAS_BASENAME,
				'plugin_slug'     => 'iconic-was',
				'plugin_name'     => self::$name,
				'plugin_version'  => self::$version,
				'plugin_path'     => ICONIC_WAS_PLUGIN_PATH_FILE,
				'plugin_class'    => 'Iconic_Woo_Attribute_Swatches',
				'option_group'    => 'iconic_was',
				'urls'            => array(
					'product' => 'https://iconicwp.com/products/woocommerce-attribute-swatches/',
				),
				'container_class' => self::class,
				'license_class' => Iconic_WAS_Core_Uplink_Helper::class,
			)
		);
	}

	/**
	 * Init telemetry class.
	 *
	 * @return void
	 */
	public function init_telemetry() {
		Iconic_WAS_Core_Telemetry::run(
			array(
				'file'                  => __FILE__,
				'plugin_slug'           => 'iconic-was',
				'option_group'          => 'iconic_was',
				'plugin_name'           => self::$name,
				'plugin_url'            => ICONIC_WAS_URL,
				'opt_out_settings_path' => 'sections/license/fields',
				'container_class'       => self::class,
			)
		);
	}

	/**
	 * Install plugin.
	 */
	private function install() {
		add_action( 'plugins_loaded', array( 'Iconic_WAS_Fees', 'install' ) );
	}

	/**
	 * Class: Swatches
	 *
	 * Access the swatches class without loading multiple times
	 */
	public function swatches_class() {
		if ( ! $this->swatches ) {
			$this->swatches = new Iconic_WAS_Swatches();
		}

		return $this->swatches;
	}

	/**
	 * Class: Products
	 *
	 * Access the products class without loading multiple times
	 */
	public function products_class() {
		if ( ! $this->products ) {
			$this->products = new Iconic_WAS_Products();
		}

		return $this->products;
	}

	/**
	 * Class: Attributes
	 *
	 * Access the attributes class without loading multiple times
	 */
	public function attributes_class() {
		if ( ! $this->attributes ) {
			$this->attributes = new Iconic_WAS_Attributes();
		}

		return $this->attributes;
	}

	/**
	 * Class: Helpers
	 *
	 * Access the helpers class without loading multiple times
	 */
	public function helpers_class() {
		if ( ! $this->helpers ) {
			$this->helpers = new Iconic_WAS_Helpers();
		}

		return $this->helpers;
	}

	/**
	 * Autoloader
	 *
	 * Classes should reside within /inc and follow the format of
	 * Iconic_The_Name ~ class-the-name.php or Iconic_WAS_The_Name ~ class-the-name.php
	 */
	private function autoload( $class_name ) {
		/**
		 * If the class being requested does not start with our prefix,
		 * we know it's not one in our project
		 */
		if ( 0 !== strpos( $class_name, 'Iconic_' ) && 0 !== strpos( $class_name, $this->class_prefix ) ) {
			return;
		}

		$file_name = strtolower(
			str_replace(
				array(
					$this->class_prefix,
					'Iconic_',
					'_',
				),      // Prefix | Plugin Prefix | Underscores
				array( '', '', '-' ),                              // Remove | Remove | Replace with hyphens
				$class_name
			)
		);

		// Compile our path from the current location
		$file = __DIR__ . '/inc/class-' . $file_name . '.php';

		// If a file is found
		if ( file_exists( $file ) ) {
			// Then load it up!
			require $file;
		}
	}

	/**
	 * Set constants
	 */
	public function define_constants() {
		$this->define( 'ICONIC_WAS_FILE', __FILE__ );
		$this->define( 'ICONIC_WAS_PATH', plugin_dir_path( __FILE__ ) );
		$this->define( 'ICONIC_WAS_URL', plugin_dir_url( __FILE__ ) );
		$this->define( 'ICONIC_WAS_INC_PATH', ICONIC_WAS_PATH . 'inc/' );
		$this->define( 'ICONIC_WAS_VENDOR_PATH', ICONIC_WAS_INC_PATH . 'vendor/' );
		$this->define( 'ICONIC_WAS_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'ICONIC_WAS_PLUGIN_PATH_FILE', str_replace( trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) ), '', wp_normalize_path( ICONIC_WAS_FILE ) ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name
	 * @param string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Init.
	 */
	public function init_hook() {
		$this->settings = Iconic_WAS_Core_Settings::$settings;
	}

	/**
	 * Plugins Loaded.
	 */
	public function plugins_loaded_hook() {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

			add_filter( 'jck_qv_modal_classes', array( $this, 'qv_modal_classes' ), 10, 1 );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_styles' ) );
			add_filter( 'post_class', array( $this, 'add_accordion_class' ) );
		}
	}

	/**
	 * Frontend: Styles
	 */
	public function frontend_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'iconic_was_load_slider_assets', true ) ) {
			wp_register_style( 'flickity', ICONIC_WAS_URL . 'assets/vendor/flickity/flickity' . $suffix . '.css', array(), self::$version );
			wp_enqueue_style( 'flickity' );
		}

		wp_register_style( 'iconic-was-styles', ICONIC_WAS_URL . 'assets/frontend/css/main' . $suffix . '.css', array(), self::$version );
		wp_enqueue_style( 'iconic-was-styles' );
	}

	/**
	 * Frontend: Scripts
	 */
	public function frontend_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'iconic_was_load_slider_assets', true ) ) {
			wp_register_script( 'flickity', ICONIC_WAS_URL . 'assets/vendor/flickity/flickity.pkgd' . $suffix . '.js', array( 'jquery' ), self::$version, true );
			wp_enqueue_script( 'flickity' );
		}

		wp_register_script( 'iconic-was-scripts', ICONIC_WAS_URL . 'assets/frontend/js/main' . $suffix . '.js', array( 'jquery', 'accounting' ), self::$version, true );

		wp_enqueue_script( 'accounting' );
		wp_enqueue_script( 'iconic-was-scripts' );

		$vars = apply_filters(
			'iconic_was_script_vars',
			array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( $this->slug ),
				'is_mobile' => wp_is_mobile(),
				'currency'  => array(
					'format_num_decimals'  => wc_get_price_decimals(),
					'format_symbol'        => get_woocommerce_currency_symbol(),
					'format_decimal_sep'   => esc_attr( wc_get_price_decimal_separator() ),
					'format_thousand_sep'  => esc_attr( wc_get_price_thousand_separator() ),
					'format'               => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ),
					'price_display_suffix' => get_option( 'woocommerce_price_display_suffix' ),
				),
				'i18n'      => array(
					'calculating'  => __( 'Calculating Price...', 'iconic-was' ),
					'no_selection' => __( 'No selection', 'iconic-was' ),
				),
			)
		);

		wp_localize_script( 'iconic-was-scripts', 'iconic_was_vars', $vars );
	}

	/**
	 * Admin: Styles
	 */
	public function admin_styles() {
		global $post, $pagenow;

		wp_register_style( 'iconic-was-admin-styles', ICONIC_WAS_URL . 'assets/admin/css/main.min.css', array(), self::$version );

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'iconic-was-admin-styles' );
	}

	/**
	 * Admin: Scripts
	 */
	public function admin_scripts() {
		global $post;

		$current_screen = get_current_screen();
		$min            = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$page           = ! empty( $_GET['page'] ) ? $_GET['page'] : false;
		$taxonomy       = ! empty( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : false;
		$product_edit   = $current_screen->base == 'post' && $current_screen->post_type == 'product';

		wp_register_script(
			'iconic-was-conditional',
			ICONIC_WAS_URL . 'assets/vendor/js/jquery.conditional.min.js',
			array(
				'jquery',
			),
			self::$version,
			true
		);

		wp_register_script(
			'iconic-was-scripts',
			ICONIC_WAS_URL . 'assets/admin/js/main' . $min . '.js',
			array(
				'jquery',
				'wp-color-picker',
				'iconic-was-conditional',
			),
			self::$version,
			true
		);

		if ( $page == 'product_attributes' || substr( $taxonomy, 0, 3 ) === 'pa_' || $product_edit ) {
			wp_enqueue_media();
			wp_enqueue_script( 'iconic-was-conditional' );
			wp_enqueue_script( 'iconic-was-scripts' );

			/**
			 * Filter the script variables being localized.
			 *
			 * @param array $vars Array of variables to localize.
			 */
			$vars = apply_filters(
				'iconic_was_admin_script_vars',
				array(
					'url_params' => $this->helpers_class()->get_filtered_input( '', 'get', 'all' ),
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'iconic_was' ),
					'i18n'       => array(
						'select_colour' => __( 'Select', 'iconic-was' ),
					),
				)
			);

			wp_localize_script( 'iconic-was-scripts', 'iconic_was_vars', $vars );
		}
	}

	/**
	 * Check whether the plugin is active.
	 *
	 * @since 1.0.1
	 *
	 * @param string $plugin Base plugin path from plugins directory.
	 *
	 * @return bool True if inactive. False if active.
	 */
	public function is_plugin_active( $plugin ) {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || $this->is_plugin_active_for_network( $plugin );
	}

	/**
	 * Check whether the plugin is active for the entire network.
	 *
	 * Only plugins installed in the plugins/ folder can be active.
	 *
	 * Plugins in the mu-plugins/ folder can't be "activated," so this function will
	 * return false for those plugins.
	 *
	 * @since 1.0.1
	 *
	 * @param string $plugin Base plugin path from plugins directory.
	 *
	 * @return bool True, if active for the network, otherwise false.
	 */
	public function is_plugin_active_for_network( $plugin ) {
		if ( ! is_multisite() ) {
			return false;
		}
		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins[ $plugin ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add classes to quickview modal
	 *
	 * @since 1.0.1
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public function qv_modal_classes( $classes ) {
		$classes[] = 'jck-qc-has-swatches';

		return $classes;
	}

	public function add_accordion_class( $classess ) {
		if ( ! is_product() ) {
			return $classess;
		}

		$enable_accordion = false;
		if ( isset( $this->settings['style_general_accordion'] ) ) {
			$enable_accordion = $this->settings['style_general_accordion'] == 'yes' ? true : false;
		}

		if ( $show_accordion = apply_filters( 'iconic_was_show_accordion', $enable_accordion, get_the_ID() ) ) {
			$classess[] = 'iconic-was-accordion';
		}

		return $classess;
	}

	/**
	 * Declare HPOS compatiblity.
	 *
	 * @since 1.14.3
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
}

$GLOBALS['iconic_was'] = Iconic_Woo_Attribute_Swatches::instance();
