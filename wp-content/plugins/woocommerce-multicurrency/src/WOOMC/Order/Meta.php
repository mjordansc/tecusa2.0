<?php
/**
 * Order metas.
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Order;

use WOOMC\Abstracts\Hookable;
use WOOMC\API;
use WOOMC\Dependencies\TIVWP\Abstracts\MetaSetInterface;
use WOOMC\Dependencies\TIVWP\UniMeta\UniMeta_WC_Order;
use WOOMC\Dependencies\TIVWP\WC\Metabox\MetaboxEngine;
use WOOMC\Log;
use WOOMC\Rate;

/**
 * Class Meta
 *
 * @since 1.16.0
 */
class Meta extends Hookable {

	/**
	 * Meta keys prefix.
	 *
	 * @since 1.16.0
	 * @var string
	 */
	const PREFIX = '_woomc_';

	/**
	 * DI.
	 *
	 * @since 1.16.0
	 * @var Rate\Storage
	 */
	protected $rate_storage;

	/**
	 * Meta constructor.
	 *
	 * @since 1.16.0
	 *
	 * @param Rate\Storage $rate_storage Rate Storage instance.
	 */
	public function __construct( $rate_storage ) {
		$this->rate_storage = $rate_storage;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @since 1.16.0
	 * @since 2.16.4 Use woocommerce_checkout_create_order instead of woocommerce_checkout_update_order_meta.
	 * @return void
	 */
	public function setup_hooks() {

		\add_action(
			'woocommerce_checkout_create_order',
			array( $this, 'action__woocommerce_checkout_create_order' )
		);

		\add_action(
			'tivwp_meta_changed',
			array( $this, 'action__tivwp_meta_changed' ),
			10,
			5
		);

		/**
		 * Uncomment to debug.
		 * <code>
		 *
		 * 0 && \add_action(
		 * 'woocommerce_after_order_object_save',
		 * function ( $order ) {
		 * $t        = $order->get_type();
		 * $currency = $order->get_currency();
		 *
		 * return;
		 * }
		 * );
		 *
		 * 0 && \add_action(
		 * 'woocommerce_update_order',
		 * function ( $order_id, $order ) {
		 * $t        = $order->get_type();
		 * $currency = $order->get_currency();
		 *
		 * return;
		 * },
		 * 10,
		 * 2
		 * );
		 * </code>
		 */
	}

	/**
	 * Add order metas at checkout.
	 *
	 * @since 2.16.4
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return void
	 */
	public function action__woocommerce_checkout_create_order( $order ) {
		try {

			// Store the current rate.
			$rate = $this->rate_storage->get_rate( API::default_currency(), API::active_currency() );
			$order->update_meta_data( self::PREFIX . 'rate', $rate );

			// Store the default currency to know what rate was based on.
			$order->update_meta_data( self::PREFIX . 'store_currency', API::default_currency() );

			/**
			 * No need to save the order. It is saved after this hook.
			 *
			 * @see \WC_Checkout::create_order
			 */

		} catch ( \Exception $exception ) {
			Log::error( $exception );
		}
	}

	/**
	 * Act on meta changes.
	 *
	 * @since        3.0.0-rc.1
	 *
	 * @param string           $meta_action Meta action - see MetaboxEngine constants.
	 * @param array            $meta_field  Meta field.
	 * @param mixed            $meta_value  Meta value.
	 * @param MetaSetInterface $meta_set    MetaSet.
	 * @param UniMeta_WC_Order $uni_meta    UniMeta.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function action__tivwp_meta_changed( $meta_action, $meta_field, $meta_value, $meta_set, $uni_meta ) {
		if ( MetaboxEngine::META_ACTION_UPDATE === $meta_action ) {

			if ( '_order_currency' === $meta_field['id'] ) {

				$order_id = 0;
				$order    = null;

				$order_or_post_object = $uni_meta->get_wp_object();
				if ( $order_or_post_object instanceof \WC_Order ) {
					// Subscription is also WC_Order.
					$order    = $order_or_post_object;
					$order_id = $order_or_post_object->get_id();
				} elseif ( $order_or_post_object instanceof \WP_Post ) {
					// Not HPOS?
					if ( 'shop_order' === $order_or_post_object->post_type || 'shop_subscription' === $order_or_post_object->post_type ) {
						$order    = \wc_get_order( $order_id );
						$order_id = $order_or_post_object->ID;
					}
				}

				if ( ! $order_id ) {
					Log::error( 'Attempt to handle _order_currency on a non-order object' );

					return;
				}

				$currency = $meta_value;

				// Update order rate meta.
				$rate = $this->rate_storage->get_rate( API::default_currency(), $currency );
				$uni_meta->set_meta( self::PREFIX . 'rate', $rate );

				// And save the default currency to know what rate was based on.
				$uni_meta->set_meta( self::PREFIX . 'store_currency', API::default_currency() );

				/**
				 * Hook on changing order currency.
				 *
				 * @since 4.4.10
				 *
				 * @param int       $order_id Order ID.
				 * @param string    $currency Currency code.
				 * @param \WC_Order $order    Order object.
				 */
				\do_action( 'woocommerce_multicurrency_order_currency_changed', $order_id, $currency, $order );

				if ( ! empty( $meta_field['order_note_updated'] ) ) {
					$order->add_order_note(
						sprintf( $meta_field['order_note_updated'], $meta_value ),
						0,
						true
					);
				}
			}
		}
	}
}
