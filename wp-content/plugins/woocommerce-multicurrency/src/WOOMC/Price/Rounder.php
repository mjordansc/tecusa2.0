<?php
/**
 * Price rounder.
 *
 * @since 1.0.0
 */

namespace WOOMC\Price;

use WOOMC\Currency\Decimals;
use WOOMC\DAO\Factory;


/**
 * Price\Rounder
 */
class Rounder {

	/**
	 * Default "Round To" value.
	 *
	 * @var float
	 */
	const DEFAULT_ROUND_TO = 0.01;

	/**
	 * Default "Price charm" value.
	 *
	 * @var float
	 */
	const DEFAULT_PRICE_CHARM = 0.0;

	/**
	 * Default "Fee percent" value.
	 *
	 * @var float
	 */
	const DEFAULT_FEE_PERCENT = 0.0;

	/**
	 * Default minimum value of number to round.
	 * All values under this will not be rounded.
	 *
	 * @since 3.2.0-1
	 * @var float
	 */
	const MIN_ROUND_TO_VALUE = 0.01;

	/**
	 * Default automatic rounding value.
	 *
	 * @since 3.2.0-1
	 * @var bool
	 */
	const DEFAULT_AUTO_ROUNDING = false;

	/**
	 * The settings array.
	 *
	 * @var float[]
	 */
	protected $settings = array(
		'round_to'      => self::DEFAULT_ROUND_TO,
		'price_charm'   => self::DEFAULT_PRICE_CHARM,
		'fee_percent'   => self::DEFAULT_FEE_PERCENT,
		'auto_rounding' => self::DEFAULT_AUTO_ROUNDING,
	);

	/**
	 * Price\Rounder constructor.
	 */
	public function __construct() {

		$dao = Factory::getDao();
		$this->setRoundTo( $dao->getRoundTo() );
		$this->setPriceCharm( $dao->getPriceCharm() );
		$this->setFeePercent( $dao->getFeePercent() );
		$this->setAutomaticRounding( $dao->getAutomaticRounding() );
	}

	/**
	 * Discard invalid parameters and values lower than 1 cent (that also discards negative values).
	 *
	 * @param mixed $value    The value.
	 * @param float $fallback The fallback if not sanitize-able.
	 *
	 * @return float
	 */
	protected function sanitize_setting_value( $value, $fallback = 0.0 ) {
		if ( ! is_numeric( $value ) || $value < 0.01 ) {
			$value = $fallback;
		}

		return (float) $value;
	}

	/**
	 * Getter for "Round to".
	 *
	 * @return float
	 */
	public function getRoundTo() {
		return $this->settings['round_to'];
	}

	/**
	 * Setter for "Round to".
	 *
	 * @param float|int $value The value.
	 */
	public function setRoundTo( $value ) {
		$this->settings['round_to'] = $this->sanitize_setting_value( $value, 0.01 );
	}

	/**
	 * Getter for "Price charm".
	 *
	 * @return float
	 * @noinspection PhpUnused
	 */
	public function getPriceCharm() {
		return $this->settings['price_charm'];
	}

	/**
	 * Setter for "Price charm".
	 *
	 * @param float|int $value The value.
	 */
	public function setPriceCharm( $value ) {
		$this->settings['price_charm'] = $this->sanitize_setting_value( $value );
	}

	/**
	 * Getter for "Fee percent".
	 *
	 * @return float
	 * @noinspection PhpUnused
	 */
	public function getFeePercent() {
		return $this->settings['fee_percent'];
	}

	/**
	 * Setter for "Fee percent".
	 *
	 * @param float|int $value The value.
	 */
	public function setFeePercent( $value ) {
		$this->settings['fee_percent'] = $this->sanitize_setting_value( $value );
	}

	/**
	 * Setter for "Automatic rounding".
	 *
	 * @param bool $auto_rounding_set rounding or not.
	 */
	public function setAutomaticRounding( $auto_rounding_set ) {
		$this->settings['auto_rounding'] = (bool) $auto_rounding_set;
	}

	/**
	 * Getter for "Automatic rounding".
	 *
	 * @return bool
	 */
	public function getAutomaticRounding() {
		return $this->settings['auto_rounding'];
	}

	/**
	 * Set round_to and price_charm depending on value.
	 *
	 * @since 3.2.0-1
	 *
	 * @param float $value The value.
	 *
	 * @return int $multiplier The multiplier.
	 */
	protected function get_adaptive_rounding_multiplier( $value ) {
		switch ( true ) {
			case ( $value < 100 ):
				$multiplier = 1;
				break;
			case ( $value < 5000 ):
				$multiplier = 10;
				break;
			case ( $value < 50000 ):
				$multiplier = 100;
				break;
			default:
				$multiplier = 1000;
		}

		return $multiplier;
	}

	/**
	 * Round the value.
	 *
	 * @since 3.2.0-1
	 * @since 3.4.2-1 Fix float math (a visible 310.0 might be 310.00000000000001 internally).
	 *
	 * @param float|int $value The value.
	 *
	 * @return float|int
	 */
	protected function round_value( $value, $round_to ) {

		if ( is_float( $value ) ) {
			$value = ( (int) ( $value * 10000 ) ) / 10000;
		}

		return ceil( $value / $round_to ) * $round_to;
	}

	/**
	 * Round up a value.
	 *
	 * @since 1.0.0
	 * @since 3.2.0-1 Added "smart rounding"
	 *
	 * @param float|int $value    The value to round.
	 * @param string    $currency The currency code.
	 *
	 * @return float
	 */
	public function up( $value, $currency = '' ) {

		if ( ! is_numeric( $value ) ) {
			$value = 0.0;
		}

		/**
		 * Do not touch negative values.
		 *
		 * @since 1.15.0 Do not touch zero values either.
		 */
		if ( $value <= 0 ) {
			return $value;
		}

		$value = (float) $value;

		$multiplier = 1;

		if ( $this->getAutomaticRounding() ) {
			// Set the round to and price charm values based on the input value
			$multiplier = $this->get_adaptive_rounding_multiplier( $value );
		}

		/**
		 * Filter woocommerce_multicurrency_rounder_settings.
		 *
		 * @since 1.0.0
		 */
		$this->settings = \apply_filters( 'woocommerce_multicurrency_rounder_settings', $this->settings, $currency );

		// Apply the fee percentage
		$value *= 1.0 + $this->settings['fee_percent'] / 100.0;

		if ( $this->settings['round_to'] >= self::MIN_ROUND_TO_VALUE ) {
			$value = $this->round_value( $value, $this->getRoundTo() * $multiplier );
		}

		// Subtract the price charm
		$value -= $this->getPriceCharm() * $multiplier;

		/**
		 * Do not round if currency decimals are > 2.
		 *
		 * @since 2.12.2
		 */
		if ( $currency && Decimals::get_price_decimals( $currency ) > 2 ) {
			return $value;
		}

		return round( $value, 2 );
	}

	/**
	 * Reverse to the {@see up()}.
	 *
	 * @param float  $value    The value to reverse-round.
	 * @param string $currency The currency code.
	 *
	 * @return float
	 */
	public function down( $value, $currency = '' ) {
		if ( ! is_numeric( $value ) ) {
			$value = 0.0;
		}

		// Do not touch negative values.
		if ( $value < 0 ) {
			return $value;
		}

		$value = (float) $value;

		/**
		 * Filter woocommerce_multicurrency_rounder_settings.
		 *
		 * @since 1.0.0
		 */
		$this->settings = \apply_filters( 'woocommerce_multicurrency_rounder_settings', $this->settings, $currency );

		// Un-charm.
		$value += $this->settings['price_charm'];

		if ( $this->settings['round_to'] > 0.01 ) {
			// Cannot restore the value before rounding, so make it down by half of the "round_to".
			$value -= $this->settings['round_to'] / 2;
		}

		// Un-fee.
		$value /= ( 1.0 + $this->settings['fee_percent'] / 100.0 );

		/**
		 * Do not round if currency decimals are > 2.
		 *
		 * @since 2.12.2
		 */
		if ( $currency && Decimals::get_price_decimals( $currency ) > 2 ) {
			return $value;
		}

		return round( $value, 2 );
	}
}
