<?php
/**
 * Shortcode to convert a static price displayed on a page.
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Shortcode;

use WOOMC\AbstractConverter;
use WOOMC\API;
use WOOMC\Currency\Decimals;

/**
 * Class Convert
 *
 * @package WOOMC\Shortcode
 */
class Convert extends AbstractConverter {

	/**
	 * Shortcode tag.
	 *
	 * @since 1.16.0
	 *
	 * @var string
	 */
	const TAG = 'woomc-convert';

	/**
	 * Setup actions and filters.
	 *
	 * @since 1.16.0
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_shortcode( self::TAG, array( $this, 'process_shortcode' ) );
	}

	/**
	 * Process shortcode.
	 *
	 * @since    1.16.0
	 * @since    2.12.0-beta.1 Added the "raw" attribute (false by default).
	 *
	 * @formatter:off
	 * @param string[] $atts The shortcode attributes.
	 * @formatter:on
	 *
	 * @type float|string|int $value Value to convert.
	 * @type bool|string|int $formatting Pass 0/off/no/false to skip formatting with {@see \wc_price()}.
	 * @type string $currency Convert to this currency. Default is the currently selected.
	 * @type int $decimals Format using this number of decimals. Default depends on the currency.
	 *
	 * @return string
	 *
	 * @example
	 *         <code>
	 *         [woomc-convert value="5"] - Convert "5" to the currently selected currency and display formatted as WC price.
	 *         [woomc-convert value="5" formatting="false"] - Convert same as above but displays just the number.
	 *         [woomc-convert value="5" currency="JPY"] - Convert to "JPY" currency.
	 *         [woomc-convert value="5" currency="JPY" decimals=1] - Same as above but forces the number of decimals.
	 *         </code>
	 *
	 * @internal
	 */
	public function process_shortcode( $atts = array() ) {

		// Defaults if not passed.
		$atts = \shortcode_atts( $this->default_atts(), $atts, self::TAG );

		$value    = (float) $atts['value'];
		$currency = (string) $atts['currency'];

		/**
		 * Pass 'DEFAULT' instead of hard-coded store currency.
		 *
		 * @since 2.11.0
		 */
		if ( 'DEFAULT' === $currency ) {
			$currency = API::default_currency();
		}

		// If decimals not passed (default is null) then get them from our list.
		$decimals = ( null !== $atts['decimals'] ) ? (int) $atts['decimals'] : Decimals::get_price_decimals( $currency );

		if ( $atts['raw'] ) {
			$value = $this->price_controller->convert_raw( $value, null, $currency );
		} else {
			$value = $this->price_controller->convert( $value, null, $currency );
		}

		// Filter returns TRUE for "1", "true", "on" and "yes". Returns FALSE otherwise.
		$formatting = filter_var( $atts['formatting'], FILTER_VALIDATE_BOOLEAN );

		if ( $formatting ) {
			$value = \wc_price(
				$value,
				array(
					'currency' => $currency,
					'decimals' => $decimals,
				)
			);
		}

		return (string) $value;
	}

	/**
	 * Default shortcode attribute values.
	 *
	 * @since 1.16.0
	 * @since 2.12.0-beta.1 Added the "raw" attribute (false by default).
	 *
	 * @return array
	 */
	protected function default_atts() {
		return array(
			'value'      => 0,
			'formatting' => true,
			'currency'   => \get_woocommerce_currency(),
			'decimals'   => null,
			'raw'        => false,
		);
	}
}
