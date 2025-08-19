<?php
/**
 * Plugin methods.
 *
 * @since 1.7.0
 */

namespace WOOMC\Dependencies\TIVWP;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Class Plugin.
 *
 * @since 1.7.0
 */
class Plugin implements InterfaceHookable {

	/**
	 * WooCommerce plugin file (relative).
	 *
	 * @since 2.5.0
	 * @var string
	 */
	protected const WC_PLUGIN_FILE = 'woocommerce/woocommerce.php';

	/**
	 * Var plugin_file.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Var $paths_to_check.
	 *
	 * @since 2.0.0
	 *
	 * @var string[]
	 */
	protected $paths_to_check;

	/**
	 * Var $app_namespace.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $app_namespace;

	/**
	 * Var plugin_data.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected $plugin_data;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 *
	 * @param string   $plugin_file    The full path to the plugin's main file.
	 * @param string   $app_namespace  Namespace of the App class.
	 * @param string[] $paths_to_check Paths to be present, in addition to autoload.
	 */
	public function __construct( string $plugin_file, string $app_namespace, array $paths_to_check = array() ) {

		$this->plugin_file    = $plugin_file;
		$this->paths_to_check = $paths_to_check;
		$this->app_namespace  = $app_namespace;
	}

	/**
	 * Implement
	 *
	 * @since 2.0.0
	 * @inheritDoc
	 */
	public function setup_hooks() {

		$this->validate_and_launch();
	}

	/**
	 * Make sure the extra plugin headers are loaded.
	 *
	 * @since        2.3.2
	 *
	 * @param string[] $headers
	 *
	 * @return string[]
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public static function filter__extra_plugin_headers( $headers ) {
		return array_unique( array_merge( $headers, array(
			'RequiresPHP',
			'RequiresPlugins',
			'WC requires at least',
		) ) );
	}

	/**
	 * Make sure the extra plugin headers are loaded.
	 *
	 * @since 2.3.2
	 * @return void
	 */
	protected function polyfill_get_plugin_data(): void {
		\add_filter( 'extra_plugin_headers', array( __CLASS__, 'filter__extra_plugin_headers' ) );
		$this->plugin_data = \get_plugin_data( $this->plugin_file, false, false );
		\remove_filter( 'extra_plugin_headers', array( __CLASS__, 'filter__extra_plugin_headers' ) );

		$this->plugin_data = array_merge( array(
			'Name'        => basename( $this->plugin_file ),
			'RequiresPHP' => '7.4',
		), $this->plugin_data );
	}

	/**
	 * Method validate_and_launch.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function validate_and_launch() {
		try {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$this->polyfill_get_plugin_data();

			$this->verify_php( $this->plugin_data['RequiresPHP'] );

			$this->verify_packaging( $this->paths_to_check );

			if ( $this->plugin_data['WC requires at least'] ) {
				$this->verify_wc();
				$this->declare_hpos_compatibility( $this->plugin_file );
			}

			self::launch( $this->app_namespace, $this->plugin_file );

		} catch ( \Exception $load_exception ) {
			$message = $load_exception->getMessage();
			$fn      = 'error_log';
			$fn( $this->plugin_data['Name'] . ': ' . $message );
			$this->display_admin_notice( $message );

			\add_action( 'init', function () {
				self::self_deactivate( $this->plugin_file );
			} );
		}
	}

	/**
	 * Method verify_php.
	 * Practically obsolete. WP is doing that check itself since 5.2.0
	 * Keeping for a "curious" case when PHP has downgraded while plugin was already active.
	 *
	 * @see   is_php_version_compatible()
	 *
	 * @since 2.0.0
	 *
	 * @param string $required_version Required version.
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function verify_php( string $required_version ) {
		$current_version = PHP_VERSION;
		if ( version_compare( $current_version, $required_version, '<' ) ) {
			throw new \Exception( 'Required PHP version is ' . \esc_html( $required_version ) . '. Your site is running version ' . \esc_html( $current_version ) . '.' );
		}
	}

	/**
	 * Method verify_packaging.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $paths_to_check Paths to be present, in addition to autoload.
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function verify_packaging( array $paths_to_check ) {

		foreach ( $paths_to_check as $path ) {
			if ( ! is_readable( $path ) ) {
				throw new \Exception( 'Not found: ' . \esc_html( $path ) );
			}
		}
	}

	/**
	 * Method is_woocommerce_active.
	 *
	 * @since 2.5.0
	 * @return bool
	 */
	protected function is_woocommerce_active(): bool {
		if ( class_exists( 'WooCommerce', false ) ) {
			return true;
		}

		$active_plugins = (array) \get_option( 'active_plugins', array() );
		if ( \is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, \get_site_option( 'active_sitewide_plugins', array() ) );
		}

		if (
			in_array( self::WC_PLUGIN_FILE, $active_plugins, true ) ||
			array_key_exists( self::WC_PLUGIN_FILE, $active_plugins )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Method get_woocommerce_version.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	protected function get_woocommerce_version(): string {

		if ( function_exists( 'WC' ) ) {
			return \WC()->version;
		}

		$wc_plugin_data = \get_plugin_data( WP_PLUGIN_DIR . '/' . self::WC_PLUGIN_FILE, false, false );

		return $wc_plugin_data['Version'] ?? '';
	}

	/**
	 * Method verify_wc.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function verify_wc() {

		if ( ! $this->is_woocommerce_active() ) {
			throw new \Exception( 'The WooCommerce plugin must be installed and activated.' );
		}

		$woocommerce_version = $this->get_woocommerce_version();
		if ( empty( $woocommerce_version ) ) {
			// This is impossible, so let's not bother and let it run.
			return;
		}

		// This is here and not a parameter because if WC is not active, this header is not in the plugin data.
		$required_version = $this->plugin_data['WC requires at least'];

		if ( ! version_compare( $woocommerce_version, $required_version, '>=' ) ) {
			throw new \Exception( 'Required WooCommerce version is ' . \esc_html( $required_version ) . ' or later. Your site is running version ' . \esc_html( $woocommerce_version ) . '.' );
		}
	}

	/**
	 * Method declare_hpos_compatibility.
	 *
	 * @since 2.0.0
	 *
	 * @param $plugin_file
	 *
	 * @return void
	 */
	protected function declare_hpos_compatibility( $plugin_file ) {
		\add_action(
			'before_woocommerce_init',
			function () use ( $plugin_file ) {
				if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
					FeaturesUtil::declare_compatibility( 'custom_order_tables', $plugin_file );
				}
			}
		);
	}

	/**
	 * Method launch.
	 *
	 * @since 2.0.0
	 *
	 * @param string $app_namespace App namespace
	 * @param string $plugin_file   The __FILE__ of the plugin loader.
	 *
	 * @return void
	 */
	public static function launch( string $app_namespace, string $plugin_file ) {
		$app_class = "$app_namespace\App";
		if ( class_exists( $app_class ) ) {
			$app_class::instance()->configure( $plugin_file )->setup_hooks();
		}
	}

	/**
	 * Method display_admin_notice.
	 *
	 * @since 2.0.0
	 *
	 * @param string $message Message to display.
	 *
	 * @return void
	 */
	protected function display_admin_notice( string $message ) {
		\add_action( 'admin_init', function () use ( $message ) {
			$heading = sprintf(
				'Cannot activate %1$s %2$s: %3$s',
				$this->plugin_data['Name'],
				$this->plugin_data['Version'],
				$message
			);
			?>
			<div class="error">
				<p><strong><?php echo \esc_html( $heading ); ?></strong></p>
				<p>
					If you need help, please <a target="_"
							href="https://woocommerce.com/my-account/contact-support/">click here to open a Support
						ticket</a>.
				</p>
			</div>
			<?php
		} );
	}

	/**
	 * Self-deactivate a "run-and-go" plugin.
	 *
	 * @since        1.7.0
	 *
	 * @param string $plugin_file The __FILE__ of the plugin loader.
	 */
	public static function self_deactivate( string $plugin_file ) {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Self-deactivation.
		\deactivate_plugins( \plugin_basename( $plugin_file ), true );

		0 && \wp_verify_nonce( '' );
		unset( $_GET['activate'] );
	}
}
