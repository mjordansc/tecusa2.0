<?php
/**
 * Settings panel.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Settings;

use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Dependencies\TIVWP\WC\Admin\AdminUtils;
use WOOMC\Log;
use WOOMC\Rate\CurrentProvider;
use WOOMC\Rate\Providers;
use WOOMC\Rate\Update\Manager as RateUpdateManager;

/**
 * Class Settings\Panel
 *
 * @since 1.0.0
 */
class Panel implements InterfaceHookable {

	/**
	 * WooCommerce's settings tab slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TAB_SLUG = 'multicurrency';

	/**
	 * The Fields instance.
	 *
	 * @since 1.0.0
	 * @var Fields
	 */
	protected $fields;

	/**
	 * Panel constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Fields $fields The Fields instance.
	 */
	public function __construct( Fields $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function setup_hooks() {

		\add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		\add_action( 'woocommerce_settings_' . self::TAB_SLUG, array( $this, 'output_fields' ) );

		if ( ! App::instance()->isReadOnlySettings() ) {
			$this->setup_hooks_on_provider_change();
			\add_action(
				'woocommerce_update_options_' . self::TAB_SLUG,
				array( $this, 'save_fields' ),
				App::HOOK_PRIORITY_LATE
			);
		}
	}

	/**
	 * Add our tab to the WooCommerce Settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings_tabs Existing tabs.
	 *
	 * @return array Tabs with ours added.
	 */
	public static function add_settings_tab( array $settings_tabs ) {
		$settings_tabs[ self::TAB_SLUG ] = _x( 'Multi-currency', 'Settings tab title', 'woocommerce-multicurrency' );

		return $settings_tabs;
	}

	/**
	 * Display fields on our tab.
	 *
	 * @since 1.0.0
	 */
	public function output_fields() {
		global $current_tab;

		\WC_Admin_Settings::output_fields( $this->fields->get_all() );
		$this->fields->js_show_hide_credentials();
		$this->fields->js_rounding_calculator();
		if ( App::instance()->isReadOnlySettings() ) {
			$this->fields->js_disable_save();
		}

		$this->enqueue_scripts();

		AdminUtils::show_tivwp_wc_about( $current_tab );
		AdminUtils::style_external_links();
		AdminUtils::style_setting_comment();
	}

	/**
	 * When {@see Panel::save_fields} is invoked,
	 * if Provider ID or credentials changed, we need to update the rates.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function setup_hooks_on_provider_change() {

		$dao = Factory::getDao();

		$provider_ids = Providers::providers_id();

		\add_action( 'update_option_' . $dao->key_rates_provider_id(), array( $this, 'force_update_rates' ) );

		foreach ( $provider_ids as $provider_id ) {
			$option = $dao->key_rates_provider_credentials( $provider_id );
			\add_action( "add_option_{$option}", array( $this, 'force_update_rates' ) );
			\add_action( "update_option_{$option}", array( $this, 'force_update_rates' ) );
		}
	}

	/**
	 * Force updating rates.
	 *
	 * @since 1.0.0
	 * @since 1.15.0 this method is static.
	 * @since 1.20.0 Calls Updater directly.
	 * @since 1.20.0 Moved to Panel class. Made dynamic again.
	 *
	 * @return void
	 * @internal
	 */
	public function force_update_rates() {
		RateUpdateManager::setNeedToUpdate();
	}

	/**
	 * Update the settings.
	 *
	 * @since 1.0.0
	 * @throws \Exception Caught.
	 */
	public function save_fields() {

		\WC_Admin_Settings::save_fields( $this->fields->get_all() );

		/**
		 * The default currency must always be enabled.
		 *
		 * @since 1.15.0
		 */
		$dao = Factory::getDao();
		$dao->add_enabled_currency( $dao->getDefaultCurrency() );

		/**
		 * With FixedRates provider, force update rates on every settings save.
		 * Otherwise, the manual rates won't go to the rates array.
		 *
		 * @since 1.15.0
		 */
		if ( CurrentProvider::isFixedRates() ) {
			Log::debug( new Message(
				array( 'Saving settings with FixedRate provider', 'Have to force update rates.' ) ) );
			$this->force_update_rates();
		}

		/**
		 * Force update if there was an error before.
		 *
		 * @since 3.1.1
		 */
		if ( $dao->getRatesProviderID() && ! $dao->getRatesRetrievalStatus() ) {
			// Provider is set but rates not retrieved.
			Log::debug( new Message(
				array( 'Previous update ended with an error', 'Have to force update rates.' ) ) );
			$this->force_update_rates();
		}


		/**
		 * Call the update. It will only act if needed.
		 *
		 * @since 1.20.0
		 */
		RateUpdateManager::update();

		/**
		 * Act after settings saved.
		 *
		 * @since 2.7.1
		 */
		\do_action( 'woocommerce_multicurrency_after_save_settings' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 3.2.0-2
	 * @return void
	 */
	protected function enqueue_scripts() {
		static $js_names = array( 'rounding-calculator' );

		$url_base_js = App::instance()->plugin_dir_url() . 'assets/js/';

		foreach ( $js_names as $js_name ) {
			$script_url = $url_base_js . $js_name . '.min.js';

			\wp_enqueue_script(
				'woomc-' . $js_name,
				$script_url,
				array(),
				WOOCOMMERCE_MULTICURRENCY_VERSION,
				true
			);
		}
	}
}
