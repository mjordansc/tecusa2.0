<?php
/**
 * Convert Standard shipping fees stored in the Options table.
 *
 * @since 1.6.0
 * @since 1.8.0 Hooked early.
 * @since 2.6.7-beta.1 Moved to own class from Price\Controller.
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Shipping;

use WOOMC\AbstractConverter;
use WOOMC\App;

/**
 * Class Shipping\OptionsConverter
 *
 * @package WOOMC\Shipping
 */
class OptionsConverter extends AbstractConverter {

	/**
	 * Setup actions and filters.
	 *
	 * @since 1.6.0
	 * @since 1.8.0 Hooked early.
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * WPDB.
		 *
		 * @global \wpdb $wpdb
		 */
		global $wpdb;

		/**
		 * Find all shipping methods and their instances in the database.
		 *
		 * @since        2.6.4 Retrieve only enabled methods.
		 * @noinspection SqlResolve
		 */
		$methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE is_enabled = 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// Add filters to get shipping method settings from the Options table.
		foreach ( $methods as $method ) {
			$option_name = sprintf( 'woocommerce_%s_%d_settings', $method->method_id, $method->instance_id );
			\add_filter(
				'option_' . $option_name,
				array(
					$this,
					'filter__shipping_method_costs',
				),
				App::HOOK_PRIORITY_EARLY
			);
		}
	}

	/**
	 * Convert every shipping setting that "looks like" cost or amount.
	 *
	 * @since    1.6.0
	 * @since    1.8.0 added the "_fee" pattern (used by USPS and Table Rate).
	 * @since    1.9.0 convert numbers extracted from string - for the settings with shortcodes such as `1 * [qty]`.
	 * @since    1.17.0 preserve the variable type.
	 * @since    1.17.0 attempt to convert only scalars.
	 * @since    2.6.4 Support commas and min/max_fee.
	 *
	 * @param array $settings Shipping method settings.
	 *
	 * @return array Settings with the amounts converted.
	 *
	 * @internal filter.
	 */
	public function filter__shipping_method_costs( $settings ) {

		// Filter out settings keys by regex.
		$metrics = preg_grep( '/cost|amount|_fee/', array_keys( $settings ) );

		foreach ( $metrics as $metric ) {
			if ( ! empty( $settings[ $metric ] ) && is_scalar( $settings[ $metric ] ) ) {

				$value_type = gettype( $settings[ $metric ] );

				/**
				 * From the flat-rate shipping method description:
				 * $cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'woocommerce' ) . '<br/><br/>' . __( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'woocommerce' );
				 */

				// Convert to string.
				settype( $settings[ $metric ], 'string' );

				if ( preg_match( '/(^[\d.,]*)(.*)/', $settings[ $metric ], $matches ) ) {
					list( , $number, $formula ) = $matches;

					$number = $this->convert_localized_number( $number );

					$formula = preg_replace_callback(
						'/(_fee=")([\d,.]+)/',
						array( $this, 'callback__convert_min_max_fees' ),
						$formula
					);

					$settings[ $metric ] = $number . $formula;
				}

				// Restore the original variable type (preg_replace_callback converts to string).
				settype( $settings[ $metric ], $value_type );
			}
		}

		return $settings;
	}

	/**
	 * Callback for {@see filter__shipping_method_costs}.
	 *
	 * @since    2.6.4
	 *
	 * @param array $matches Matches from preg_replace.
	 *
	 * @return string Converted values.
	 *
	 * @internal filter callback.
	 */
	public function callback__convert_min_max_fees( $matches ) {
		return $matches[1] . $this->convert_localized_number( $matches[2] );
	}

	/**
	 * Convert a "localized" number string with comma as the decimal separator.
	 *
	 * @param string $sz The numeric string.
	 *
	 * @return string
	 */
	protected function convert_localized_number( $sz ) {
		$sz = str_replace( ',', '.', $sz );

		return $this->convert_shipping_fee( $sz );
	}

	/**
	 * Method money_to_standard_form.
	 *
	 * @since        4.2.0
	 *
	 * @param string $money_number Money string in international notation.
	 *
	 * @return string
	 * @noinspection PhpUnused
	 * @todo         Test and use in {@see convert_localized_number()}.
	 */
	public static function money_to_standard_form( $money_number ): string {

		if ( ! is_string( $money_number ) ) {
			return $money_number;
		}

		// Remove spaces and non-breaking space characters
		$money_number = preg_replace( '/\s+|\u00A0|&nbsp;/', '', $money_number );

		// If comma is used as decimal separator, replace with dot
		if ( strpos( $money_number, ',' ) !== false ) {
			$money_number = str_replace( ',', '.', $money_number );
		}

		// If comma is used as decimal separator and period as thousands separator, swap them
		if ( strpos( $money_number, ',' ) !== false && strpos( $money_number, '.' ) !== false ) {
			$money_number = str_replace( '.', '', $money_number ); // Remove dots
			$money_number = str_replace( ',', '.', $money_number ); // Replace commas with dots
		}

		// If apostrophe is used as thousands separator, remove it
		if ( strpos( $money_number, "'" ) !== false ) {
			$money_number = str_replace( "'", '', $money_number );
		}

		// If commas are used as both thousands and lakhs separators (INR format), remove them
		if ( strpos( $money_number, ',' ) !== false ) {
			$money_number = str_replace( ',', '', $money_number );
		}

		return $money_number;
	}
}
