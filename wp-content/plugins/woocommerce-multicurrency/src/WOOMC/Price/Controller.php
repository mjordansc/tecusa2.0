<?php
/**
 * Price controller.
 *
 * @since 1.0.0
 */

namespace WOOMC\Price;

use WOOMC\API;
use WOOMC\DAO;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\Currency\Detector;
use WOOMC\Integration;
use WOOMC\Product;

/**
 * Class Price\Controller
 */
class Controller implements InterfaceHookable {

	/**
	 * Instance of the Calculator.
	 *
	 * @var Calculator
	 */
	protected $price_calculator;

	/**
	 * Instance of the Detector.
	 *
	 * @var Detector
	 */
	protected $currency_detector;

	/**
	 * Apply rounding, charming, and fee to the shipping fees?
	 *
	 * @since 4.2.0
	 *
	 * @var bool
	 */
	protected $is_raw_shipping_conversion = false;

	/**
	 * Controller constructor.
	 *
	 * @param Calculator $price_calculator  Instance of the Calculator.
	 * @param Detector   $currency_detector Instance of the Detector.
	 */
	public function __construct( Calculator $price_calculator, Detector $currency_detector ) {
		$this->price_calculator  = $price_calculator;
		$this->currency_detector = $currency_detector;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Integrations moved to own controller.
		 *
		 * @since 2.0.0
		 */
		$integration_controller = new Integration\Controller( $this );
		$integration_controller->setup_hooks();

		/**
		 * Product pricing moved to its own class.
		 *
		 * @since 1.19.0
		 */
		$product_pricing = new Product\Pricing( $this );
		$product_pricing->setup_hooks();

		$this->is_raw_shipping_conversion = DAO\Factory::getDao()->isRawShippingConversion();

		// The rest - only on front.
		if ( ! Env::on_front() ) {
			return;
		}

		/**
		 * Handle coupons.
		 *
		 * @since 1.8.0
		 */
		\add_action( 'woocommerce_coupon_loaded', array( $this, 'filter__woocommerce_coupon_loaded' ) );
	}

	/**
	 * Convert the fixed amount coupon data.
	 *
	 * @since    1.8.0
	 *
	 * @param \WC_Coupon $coupon The coupon object.
	 *
	 * @return \WC_Coupon
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_coupon_loaded( $coupon ) {

		$discount_type = $coupon->get_discount_type();
		if ( in_array(
			$discount_type,
			array(
				'fixed_cart',
				'fixed_product',
				'sign_up_fee',
				'recurring_fee',
				'renewal_fee',
				'renewal_cart',
				'booking_person',
			),
			true
		)
		) {

			$coupon->set_amount( $this->convert( $coupon->get_amount( 'edit' ) ) );
			$coupon->set_maximum_amount( $this->convert( $coupon->get_maximum_amount( 'edit' ) ) );
			$coupon->set_minimum_amount( $this->convert( $coupon->get_minimum_amount( 'edit' ) ) );
		}

		return $coupon;
	}

	/**
	 * The Converter.
	 *
	 * @since 1.3.0 is in a separate method.
	 * @since 1.10.1 Parameters $to and $from.
	 * @since 1.12.1 Parameter $reverse.
	 * @since 1.17.0 Do not attempt to convert non-numeric values.
	 * @since 2.7.0 Do not call `calculate()` if to=from.
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is the currently selected.
	 * @param string           $from    Currency convert from. Default is store base.
	 * @param bool             $reverse If this is a reverse conversion.
	 *
	 * @return float|int|string
	 */
	public function convert(
		$value,
		/* @noinspection PhpUnusedParameterInspection */
		$product = null,
		$to = '',
		$from = '',
		$reverse = false
	) {
		if ( $value && is_numeric( $value ) ) {
			$to   = $to ? $to : $this->currency_detector->currency();
			$from = $from ? $from : $this->currency_detector->getDefaultCurrency();
			if ( $to !== $from ) {
				$value = $this->price_calculator->calculate( $value, $to, $from, $reverse );
			}
		}

		return $value;
	}

	/**
	 * The Raw Converter.
	 *
	 * @since 1.16.0
	 * @since 1.17.0 Do not attempt to convert non-numeric values.
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is the currently selected.
	 * @param string           $from    Currency convert from. Default is store base.
	 *
	 * @return float|int|string
	 */
	public function convert_raw(
		$value,
		/* @noinspection PhpUnusedParameterInspection */
		$product = null,
		$to = '',
		$from = ''
	) {
		if ( $value && is_numeric( $value ) ) {
			$to   = $to ? $to : $this->currency_detector->currency();
			$from = $from ? $from : $this->currency_detector->getDefaultCurrency();

			$value = $this->price_calculator->calculate_raw( $value, $to, $from );
		}

		return $value;
	}

	/**
	 * The shipping fees converter.
	 *
	 * @since 4.2.0
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is the currently selected.
	 * @param string           $from    Currency convert from. Default is store base.
	 *
	 * @return float|int|string
	 */
	public function convert_shipping_fee( $value, $product = null, $to = '', $from = '' ) {
		if ( API::is_default_currency_active() ) {
			return $value;
		}
		if ( $this->is_raw_shipping_conversion ) {
			return $this->convert_raw( $value, $product, $to, $from );
		} else {
			return $this->convert( $value, $product, $to, $from );
		}
	}

	/**
	 * Convert back to the default currency.
	 *
	 * @see        convert() with reverse=true.
	 *
	 * @note       The result might differ from the original amount because of rounding and adjustments.
	 * @since      1.15.0 Parameters $to and $from.
	 * @since      1.17.0 Do not attempt to convert non-numeric values.
	 *
	 * @since      1.8.0
	 *
	 * @param string|int|float $value   The amount.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is store base.
	 * @param string           $from    Currency convert from. Default is the currently selected.
	 *
	 * @return float|int|string
	 */
	public function convert_back(
		$value,
		/* @noinspection PhpUnusedParameterInspection */
		$product = null,
		$to = '',
		$from = ''
	) {
		if ( $value && is_numeric( $value ) ) {
			// The current/default are swapped here.
			$to   = $to ? $to : $this->currency_detector->getDefaultCurrency();
			$from = $from ? $from : $this->currency_detector->currency();

			// Reverse=true signals to reverse the rounding.
			$value = $this->price_calculator->calculate( $value, $to, $from, true );
		}

		return $value;
	}

	/**
	 * The Raw back-Converter.
	 *
	 * @since        2.8.3
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is store base.
	 * @param string           $from    Currency convert from. Default is the currently selected.
	 *
	 * @return float|int|string
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function convert_back_raw( $value, $product = null, $to = '', $from = '' ) {

		if ( $value && is_numeric( $value ) ) {
			// The current/default are swapped here.
			$to   = $to ? $to : $this->currency_detector->getDefaultCurrency();
			$from = $from ? $from : $this->currency_detector->currency();

			$value = $this->price_calculator->calculate_raw( $value, $to, $from );
		}

		return $value;
	}

	/**
	 * Convert an array of prices.
	 *
	 * @since   1.4.0
	 * @since   1.10.1 Parameters $to and $from.
	 * @since   1.12.1 Parameter $reverse.
	 *
	 * @param array  $values  The array of values.
	 * @param string $to      Currency convert to. Default is the currently selected.
	 * @param string $from    Currency convert from. Default is store base.
	 * @param bool   $reverse If this is a reverse conversion.
	 *
	 * @return array
	 * @example convert_array( ['price' => '10', 'sale_price' => 5] ) --> ['price' => '12.4', 'sale_price' => 6.2]
	 */
	public function convert_array( $values, $to = '', $from = '', $reverse = false ) {
		foreach ( $values as $key => $value ) {
			if ( $value ) {
				$values[ $key ] = $this->convert( $value, $to, $from, $reverse );
			}
		}

		return $values;
	}

	/**
	 * Raw convert an array of prices.
	 *
	 * @since   4.2.0
	 *
	 * @param array  $values  The array of values.
	 * @param string $to      Currency convert to. Default is the currently selected.
	 * @param string $from    Currency convert from. Default is store base.
	 * @param bool   $reverse If this is a reverse conversion.
	 *
	 * @return array
	 */
	public function convert_array_raw( $values, $to = '', $from = '', $reverse = false ) {
		foreach ( $values as $key => $value ) {
			if ( $value ) {
				$values[ $key ] = $this->convert_raw( $value, $to, $from, $reverse );
			}
		}

		return $values;
	}

	/**
	 * Raw/normal convert an array depending on is_raw_shipping_conversion.
	 *
	 * @since   4.2.0
	 *
	 * @param array  $values  The array of values.
	 * @param string $to      Currency convert to. Default is the currently selected.
	 * @param string $from    Currency convert from. Default is store base.
	 * @param bool   $reverse If this is a reverse conversion.
	 *
	 * @return array
	 */
	public function convert_array_shipping( $values, $to = '', $from = '', $reverse = false ) {
		if ( API::is_default_currency_active() ) {
			return $values;
		}
		if ( $this->is_raw_shipping_conversion ) {
			return $this->convert_array_raw( $values, $to, $from, $reverse );
		} else {
			return $this->convert_array( $values, $to, $from, $reverse );
		}
	}
}
