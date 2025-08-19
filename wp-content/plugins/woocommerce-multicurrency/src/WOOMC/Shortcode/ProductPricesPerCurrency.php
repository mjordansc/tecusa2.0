<?php
/**
 * Shortcode to show product prices in various currencies.
 *
 * @since 3.2.4-0
 *
 * Copyright (c) 2023, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Shortcode;

use WOOMC\AbstractConverter;
use WOOMC\API;
use WOOMC\Currency\Detector;
use WOOMC\Dependencies\TIVWP\Env;

/**
 * Class ProductPricesPerCurrency
 *
 * @package WOOMC\Shortcode
 */
class ProductPricesPerCurrency extends AbstractConverter {

	/**
	 * Shortcode tag.
	 *
	 * @since 3.2.4-0
	 *
	 * @var string
	 */
	const TAG = 'woomc-product-prices-per-currency';

	/**
	 * Setup actions and filters.
	 *
	 * @since 3.2.4-0
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_shortcode( self::TAG, array( __CLASS__, 'process_shortcode' ) );
	}

	/**
	 * Process shortcode and print the output.
	 *
	 * @since  3.2.4-1
	 *
	 * @param string[] $atts The shortcode attributes.
	 *
	 * @return void
	 * @example
	 *    <code>
	 *         add_action( 'woocommerce_before_add_to_cart_form',
	 *                function () {
	 *                if ( class_exists( '\WOOMC\Shortcode\ProductPricesPerCurrency' ) ) {
	 *                    \WOOMC\Shortcode\ProductPricesPerCurrency::process_shortcode_e(
	 *                        array( 'show_current_price' => true )
	 *                    );
	 *                }
	 *                }
	 *            );
	 *    </code>
	 * @noinspection PhpUnused
	 */
	public static function process_shortcode_e( $atts = array() ) {
		echo \wp_kses_post( self::process_shortcode( $atts ) );
	}

	/**
	 * Process shortcode.
	 *
	 * @since        3.2.4-0
	 *
	 * @param string[] $atts The shortcode attributes.
	 *
	 * @return string
	 *
	 * @example
	 *         <code>
	 *         [woomc-product-prices-per-currency]
	 *         [woomc-product-prices-per-currency show_current_price=true]
	 *         </code>
	 *
	 */
	public static function process_shortcode( $atts = array() ) {

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		if ( Env::is_function_in_backtrace( array( 'WC_Structured_Data', 'generate_product_data' ) ) ) {
			// Not needed here.
			return '';
		}

		// Defaults if not passed.
		$atts = \shortcode_atts( self::default_atts(), $atts, self::TAG );

		$show_current_price = (bool) $atts['show_current_price'];

		$currencies = $show_current_price ? API::enabled_currencies() : API::inactive_currencies();
		if ( empty( $currencies ) ) {
			// Strange.
			return '';
		}

		$output = '<div class="' . \esc_attr( self::TAG ) . '">';
		foreach ( $currencies as $currency ) {
			Detector::set_override_currency( $currency );

			$output .= '<div class="price currency-' . \esc_attr( $currency ) . '">' . $product->get_price_html() . '</div>';
		}
		Detector::set_override_currency();
		$output .= '</div>';

		return $output;
	}

	/**
	 * Default shortcode attribute values.
	 *
	 * @since 3.2.4-1
	 *
	 * @return array
	 */
	protected static function default_atts() {
		return array(
			'show_current_price' => false,
		);
	}
}
