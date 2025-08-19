<?php
/**
 * WooCommerce Status Report.
 *
 * @since   1.13.0
 * @package WOOMC\Admin
 */

namespace WOOMC\Admin;

use WOOMC\Dependencies\TIVWP\WC\AbstractStatusReport;
use WOOMC\Dependencies\TIVWP\WC\WCEnv;
use WOOMC\Currency\Detector;
use WOOMC\DAO\WP;

/**
 * Class StatusReport
 */
class StatusReport extends AbstractStatusReport {

	/**
	 * Add or modify the report key-value options array.
	 *
	 * @since 2.6.7
	 *
	 * @param array $options The options to report.
	 *
	 * @return array
	 */
	public function filter__tivwp_wc_status_report_options( $options ) {

		static $truncate_at = 100;

		// Unserialize currencies.
		if ( ! empty( $options['enabled_currencies'] ) ) {
			$enabled_currencies = \maybe_unserialize( $options['enabled_currencies'] );
			if ( is_array( $enabled_currencies ) ) {
				$options['enabled_currencies'] = implode( ',', $enabled_currencies );
			}
		}

		if ( ! isset( $options['rates'] ) ) {
			$options['rates'] = '-';
		} else {
			// Truncate long rates string.
			if ( strlen( $options['rates'] ) > $truncate_at ) {
				$options['rates_count'] = 'array(' . count( \maybe_unserialize( $options['rates'] ) ) . ')';
			}
			$options['rates'] = substr( $options['rates'], 0, $truncate_at ) . '...';
		}

		// Cookie (of the admin who reports).
		$options[ 'cookie_' . Detector::COOKIE_FORCED_CURRENCY ] = Detector::currency_from_cookie();

		// Some WC settings.
		$options['woocommerce_prices_include_tax'] = \get_option( 'woocommerce_prices_include_tax', '-' );
		$options['woocommerce_tax_display_cart']   = \get_option( 'woocommerce_tax_display_cart', '-' );
		$options['woocommerce_tax_display_shop']   = \get_option( 'woocommerce_tax_display_shop', '-' );

		// Geolocation settings.
		$options['customer_location_method'] = WCEnv::customer_location_method();

		// MaxMind: has key?
		$option_maxmind                 = \maybe_unserialize( \get_option( 'woocommerce_maxmind_geolocation_settings', '' ) );
		$options['maxmind_license_set'] = empty( $option_maxmind['license_key'] ) ? 'no' : 'yes';

		/**
		 * Selector widgets.
		 *
		 * @since 2.12.0
		 */
		$selector_widgets = array();
		$widget_prefix    = 'woocommerce-currency-';

		$sidebars_widgets = \wp_get_sidebars_widgets();
		if ( ! empty( $sidebars_widgets ) ) {
			foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
				if ( 'wp_inactive_widgets' === $sidebar_id ) {
					continue;
				}
				foreach ( $widgets as $widget_id ) {
					if ( strpos( $widget_id, $widget_prefix ) === 0 ) {
						$selector_widgets[] = str_replace( $widget_prefix, '', $widget_id );
					}
				}
			}
		}
		$options['selector_widgets'] = implode( ', ', $selector_widgets );

		$options['WP_CACHE'] = WP_CACHE ? 'yes' : 'no';

		return $options;
	}

	/**
	 * Render the report.
	 *
	 * @internal action.
	 */
	public function action__woocommerce_system_status_report() {

		$label         = 'Multicurrency';
		$option_prefix = WP::OPTIONS_PREFIX;

		\add_filter( 'tivwp_wc_status_report_options', array( $this, 'filter__tivwp_wc_status_report_options' ) );

		$this->do_report( $label, $option_prefix );
	}
}
