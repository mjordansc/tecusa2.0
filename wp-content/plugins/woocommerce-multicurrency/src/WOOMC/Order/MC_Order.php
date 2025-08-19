<?php
/**
 * MC_Order
 *
 * @since 2.16.4
 * Copyright (c) 2022, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Order;

use WOOMC\App;
use WOOMC\Currency\Controller as CurrencyController;
use WOOMC\Log;

/**
 * Class MC_Order.
 *
 * @since 2.16.4
 */
class MC_Order {

	/**
	 * Order ID.
	 *
	 * @since 2.16.4
	 *
	 * @var int
	 */
	protected $order_id = 0;

	/**
	 * Order object.
	 *
	 * @since 2.16.4
	 *
	 * @var \WC_Order
	 */
	protected $order;

	/**
	 * True if loaded.
	 *
	 * @since 2.16.4
	 *
	 * @var bool
	 */
	protected $loaded = false;

	/**
	 * Getter Loaded
	 *
	 * @since 2.16.4
	 *
	 * @return bool
	 */
	public function isLoaded() {
		return $this->loaded;
	}

	/**
	 * Currency Controller.
	 *
	 * @since 2.16.4
	 *
	 * @var CurrencyController
	 */
	protected $currency_controller;

	/**
	 * Do we need to re-enable conversion?
	 *
	 * @since 2.16.4
	 *
	 * @var bool
	 */
	protected $need_to_enable_conversion = false;

	/**
	 * Constructor OrderUtils
	 *
	 * @since 2.16.4
	 *
	 * @param int  $order_id              Order ID.
	 * @param bool $is_conversion_enabled True if currency conversion is done during loading [Default=false].
	 */
	public function __construct( $order_id, $is_conversion_enabled = false ) {

		/**
		 * Get the Currency Controller instance from App.
		 *
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$this->currency_controller = App::instance()->getCurrencyController();

		if ( is_numeric( $order_id ) ) {
			$this->order_id = \absint( $order_id );
			$this->load( $is_conversion_enabled );
		}
	}

	/**
	 * Load order.
	 *
	 * @since 2.16.4
	 *
	 * @param bool $is_conversion_enabled True if currency conversion is done during loading [Default=false].
	 * @return bool
	 */
	public function load( $is_conversion_enabled = false ) {

		$this->loaded = false;

		try {

			if ( ! $this->order_id ) {
				throw new \Exception( 'No Order ID' );
			}

			// Cannot call 'wc_get_order' until Woo registered its post types.
			if ( ! \did_action( 'woocommerce_after_register_post_type' ) ) {
				throw new \Exception( 'Called before woocommerce_after_register_post_type' );
			}

			if ( ! $is_conversion_enabled ) {
				$this->maybe_disable_conversion();
			}
			$order = \wc_get_order( $this->order_id );
			if ( ! $is_conversion_enabled ) {
				$this->maybe_enable_conversion();
			}

			if ( ! $order instanceof \WC_Order ) {
				throw new \Exception( 'wc_get_order() failed.' );
			}

			$this->order  = $order;
			$this->loaded = true;

		} catch ( \Exception $e ) {
			Log::error( $e );
		}

		return $this->loaded;
	}

	/**
	 * Disable currency conversion if wasn't disabled already.
	 *
	 * @since 2.16.4
	 *
	 * @return void
	 */
	protected function maybe_disable_conversion() {
		if ( $this->currency_controller->isCurrencyFilteringEnabled() ) {
			$this->currency_controller->disable_currency_filtering();

			$this->need_to_enable_conversion = true;
		}
	}

	/**
	 * Re-enable currency conversion if we disabled it previously.
	 *
	 * @since 2.16.4
	 *
	 * @return void
	 */
	protected function maybe_enable_conversion() {
		if ( $this->need_to_enable_conversion ) {
			$this->currency_controller->enable_currency_filtering();

			$this->need_to_enable_conversion = false;
		}
	}

	/**
	 * Gets order currency.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_currency( $context = 'edit' ) {
		if ( $this->isLoaded() ) {
			return $this->order->get_currency( $context );
		}
		return '';
	}

	/**
	 * Return the order status without wc- internal prefix.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'edit' ) {
		if ( $this->isLoaded() ) {
			return $this->order->get_status( $context );
		}
		return '';
	}
}
