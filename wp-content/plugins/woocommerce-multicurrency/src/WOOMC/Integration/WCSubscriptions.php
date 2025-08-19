<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Subscriptions
 * Plugin URI: https://woocommerce.com/products/woocommerce-subscriptions/
 *
 * @since 1.3.0
 * @since 2.0.0 Own class
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Abstracts\Hookable;
use WOOMC\API;
use WOOMC\App;
use WOOMC\Currency\Detector;
use WOOMC\Log;
use WOOMC\Price\Converter;

/**
 * Class WCSubscriptions
 *
 * @package WOOMC\Integration
 */
class WCSubscriptions extends Hookable {

	/**
	 * If `$product->get_type()` returns one of these values, then it's "my product".
	 *
	 * @var string[]
	 */
	const MY_PRODUCT_TYPES = array(
		'subscription',
		'subscription_variation',
		'variable-subscription',
	);

	/**
	 * If `$product->get_type()` returns one of these values, then the product may have custom prices.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @var string[]
	 */
	const PRODUCT_TYPES_FOR_CUSTOM_PRICES = array(
		'subscription',
		'subscription_variation',
	);

	/**
	 * Price converter.
	 *
	 * @var Converter
	 */
	protected $price_converter;

	/**
	 * WCSubscriptions constructor.
	 *
	 * @param Converter $price_converter
	 */
	public function __construct( Converter $price_converter ) {
		$this->price_converter = $price_converter;
	}

	/**
	 * Is this a WC_Product [of a certain type]?
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param \WC_Product     $product The Product object.
	 * @param string|string[] $type    Optional: array or string of types.
	 *
	 * @return bool
	 */
	public static function is_wc_product( $product, $type = '' ) {
		return (
			$product
			&& is_a( $product, 'WC_Product' )
			&& ( empty( $type ) || $product->is_type( $type ) )
		);
	}

	/**
	 * Is the product "mine"?
	 *
	 * @param \WC_Product $product The Product object.
	 *
	 * @return bool
	 * @noinspection PhpUnused
	 */
	protected function is_my_product( $product ) {
		return self::is_wc_product( $product, self::MY_PRODUCT_TYPES );
	}

	/**
	 * Can the product have custom prices?
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param \WC_Product $product The Product object.
	 *
	 * @return bool
	 */
	protected function can_have_custom_prices( $product ) {
		return self::is_wc_product( $product, self::PRODUCT_TYPES_FOR_CUSTOM_PRICES );
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		if ( Env::on_front() ) {

			/**
			 * TODO.
			 *
			 * $item_total -= $order_item->get_meta( '_synced_sign_up_fee' ) * $order_item->get_quantity();
			 * $item_total -= $order_item->get_meta( '_switched_subscription_sign_up_fee_prorated' ) * $order_item->get_quantity();
			 */

			\add_filter(
				'woocommerce_product_get__subscription_price',
				array( $this, 'filter__subscription_price' ),
				App::HOOK_PRIORITY_EARLY,
				2
			);

			\add_filter(
				'woocommerce_product_variation_get__subscription_price',
				array( $this, 'filter__variation_subscription_price' ),
				App::HOOK_PRIORITY_EARLY,
				2
			);

			/**
			 * Unused.
			 *
			 * @deprecated 3.1.1
			 *
			 * \add_filter(
			 * 'woocommerce_product_get__subscription_sign_up_fee',
			 * array( $this, 'filter__subscription_sign_up_fee' ),
			 * App::HOOK_PRIORITY_EARLY,
			 * 2
			 * );
			 *
			 * \add_filter(
			 * 'woocommerce_product_variation_get__subscription_sign_up_fee',
			 * array( $this, 'filter__variation_subscription_sign_up_fee' ),
			 * App::HOOK_PRIORITY_EARLY,
			 * 2
			 * );
			 */

			\add_filter(
				'woocommerce_subscriptions_product_sign_up_fee',
				array( $this, 'filter__product_sign_up_fee' ),
				App::HOOK_PRIORITY_LATE,
				2
			);

			\add_filter(
				'woocommerce_subscriptions_product_price_string_inclusions',
				array( $this, 'filter__get_price_string' ),
				App::HOOK_PRIORITY_LATE,
				2
			);

			\add_filter(
				'woocommerce_subscription_items_sign_up_fee',
				array( $this, 'filter__woocommerce_subscription_items_sign_up_fee' ),
				App::HOOK_PRIORITY_EARLY,
				4
			);

			\add_filter(
				'woocommerce_order_item_get_total',
				array( $this, 'filter__woocommerce_order_item_get_total' ),
				App::HOOK_PRIORITY_EARLY,
				2
			);

			\add_filter(
				'woocommerce_order_item_get_subtotal',
				array( $this, 'filter__woocommerce_order_item_get_subtotal' ),
				App::HOOK_PRIORITY_EARLY,
				2
			);

			// Disable conversion under certain circumstances.
			\add_filter(
				'woocommerce_multicurrency_pre_product_get_price',
				array(
					$this,
					'filter__woocommerce_multicurrency_pre_product_get_price',
				),
				App::HOOK_PRIORITY_EARLY,
				4
			);

			\add_action(
				'parse_request',
				array( $this, 'maybe_force_currency_to_match_subscription' ),
				App::HOOK_PRIORITY_EARLY
			);
		}

		/**
		 * Setup per-currency metaboxes.
		 *
		 * @since 2.11.0-rc.1 Removed `in_wp_admin` condition. It's both "in admin" and "admin doing ajax".
		 *        And it's probably "cheaper" to add than to check.
		 */
		\add_filter(
			'woocommerce_multicurrency_custom_pricing_meta_keys',
			array(
				$this,
				'filter__woocommerce_multicurrency_custom_pricing_meta_keys',
			),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_action(
			'woocommerce_multicurrency_order_currency_changed',
			array(
				$this,
				'action__woocommerce_multicurrency_order_currency_changed',
			),
			App::HOOK_PRIORITY_EARLY,
			3
		);
	}

	/**
	 * Method action__woocommerce_multicurrency_order_currency_changed.
	 *
	 * @since 4.4.11
	 *
	 * @param int       $order_id Order ID
	 * @param string    $currency Currency
	 * @param \WC_Order $order    Order object
	 *
	 * @return void
	 */
	public function action__woocommerce_multicurrency_order_currency_changed( $order_id, $currency, $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$subscriptions = \wcs_get_subscriptions_for_order( $order_id );
		foreach ( $subscriptions as $subscription ) {
			try {
				$subscription->set_currency( $currency );
				$subscription->save();
				$subscription->add_order_note(
					sprintf( // Translators: %1$s - currency; %2$s - order ID.
						\__( 'Multi-currency: currency set to %1$s to match the order %2$s', 'woocommerce-multicurrency' ),
						$currency,
						$order_id
					),
					0,
					true
				);

			} catch ( \Exception $exception ) {
				Log::error( $exception );
			}
		}
	}

	/**
	 * Get product raw meta. Per-currency overrides default.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param \WC_Product $product  Product.
	 * @param string      $meta_key Meta key.
	 * @param string      $currency [Currency code].
	 *
	 * @return string
	 */
	protected function get_product_raw_meta( $product, $meta_key, $currency = '' ) {

		$value = '';

		if ( ! $currency ) {
			$currency = API::active_currency();
		}

		$post_id = $product->get_id();

		// Check first the per-currency meta and then the main one.
		$keys_to_check = array( $meta_key . '_' . $currency, $meta_key );

		foreach ( $keys_to_check as $key ) {
			// Cannot use $product->get_meta($key) - endless loop. We are filtering get_meta here.
			$meta_value = \get_post_meta( $post_id, $key, true );
			if ( $meta_value ) {
				$value = $meta_value;
				break;
			}
		}

		return $value;
	}

	/**
	 * Meta keys to keep custom pricing values.
	 *
	 * @since 2.0.0
	 * @since 2.4.0 Product object passed instead of product type.
	 *
	 * @param string[]    $keys    Array of meta keys.
	 * @param \WC_Product $product The product object.
	 *
	 * @return string[]
	 */
	public function filter__woocommerce_multicurrency_custom_pricing_meta_keys( $keys, $product ) {
		if ( $this->can_have_custom_prices( $product ) ) {
			$keys = array(
				'_subscription_price_'       => __( 'Regular price', 'woocommerce' ),
				'_sale_price_'               => __( 'Sale price', 'woocommerce' ),
				'_subscription_sign_up_fee_' => __( 'Subscription sign-up fee', 'woocommerce-subscriptions' ),
			);
		}

		return $keys;
	}

	/**
	 * Detect subscription ID when it's in a query: viewing subscription, re-subscribing, etc.
	 *
	 * @since 2.9.4-rc.6
	 * @return int
	 */
	protected function get_queried_subscription_id() {
		global $wp;

		$subscription_id = 0;
		if ( ! empty( $wp->query_vars['view-subscription'] ) ) {
			$subscription_id = $wp->query_vars['view-subscription'];
		} elseif ( Env::is_parameter_in_http_get( 'resubscribe' ) ) {
			$subscription_id = Env::get_http_get_parameter( 'resubscribe' );
		}

		return \absint( $subscription_id );
	}

	/**
	 * Hook on 'parse_request' to force currency to match the subscription.
	 *
	 * @since    2.9.4-rc.6
	 * @return void
	 * @internal Action.
	 */
	public function maybe_force_currency_to_match_subscription() {

		$subscription_id = $this->get_queried_subscription_id();
		if ( $subscription_id ) {
			$subscription = \wcs_get_subscription( $subscription_id );
			if ( \wcs_is_subscription( $subscription ) ) {
				Detector::set_currency_cookie( $subscription->get_currency(), true );
			}
		}
	}

	/**
	 * Filter product meta.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param string|int|float                                                        $value    The value.
	 * @param \WC_Product|\WC_Product_Subscription|\WC_Product_Subscription_Variation $product  Product.
	 * @param string                                                                  $meta_key Meta key.
	 *
	 * @return string|int|float
	 */
	public function filter_product_meta( $value, $product, $meta_key ) {

		$meta_value = $this->get_product_raw_meta( $product, $meta_key );
		if ( $meta_value ) {
			$converted_meta_value = $this->price_converter->convert( $meta_value );
			if ( (string) $value !== (string) $converted_meta_value ) {
				// Debug point.
				$value = $converted_meta_value;
			}
		}

		return $value;
	}

	/**
	 * Convert subscription price.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param float|int|string                     $price   Price.
	 * @param \WC_Product|\WC_Product_Subscription $product Product.
	 *
	 * @return float|int|string
	 */
	public function filter__subscription_price( $price, $product ) {
		$converted_price = $this->price_converter->get_price( $price, '_subscription_price', $product );

		return $converted_price;
	}

	/**
	 * Convert subscription variation price.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param float|int|string                     $price   Price.
	 * @param \WC_Product|\WC_Product_Subscription $product Product.
	 *
	 * @return float|int|string
	 */
	public function filter__variation_subscription_price( $price, $product ) {
		$converted_price = $this->price_converter->get_price( $price, '_subscription_price', $product );

		return $converted_price;
	}

	/**
	 * Returns true is product/variation is in the cart ite.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param array       $cart_item Cart Item.
	 * @param \WC_Product $product   Product.
	 *
	 * @return bool
	 */
	protected function is_product_in_cart_item( $cart_item, $product ) {

		$product_id = $product->get_id();

		foreach ( array( 'product_id', 'variation_id' ) as $id_type ) {
			if ( ! empty( $cart_item[ $id_type ] ) && $product_id === $cart_item[ $id_type ] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns true if Cart has a "subscription action" item, such as "switch", "resubscribe", etc.
	 * Optionally, for a specified product.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param string|string[]  $actions Action name(s).
	 * @param \WC_Product|null $product Product.
	 *
	 * @return bool
	 */
	protected function is_cart_action( $actions, $product = null ) {

		$cart = WC()->cart;
		if ( empty( $cart->cart_contents ) ) {
			return false;
		}

		foreach ( (array) $actions as $action ) {
			foreach ( $cart->cart_contents as $cart_item ) {
				if ( ! empty( $cart_item[ $action ] ) ) {
					// Found if product is in this cart item or not specified.
					if ( ! $product || $this->is_product_in_cart_item( $cart_item, $product ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Convert order subtotal to the store currency.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param string|int|float       $price              The subtotal value.
	 * @param \WC_Order_Item_Product $order_item_product Order item Product.
	 *
	 * @return string|int|float
	 */
	public function filter__woocommerce_order_item_get_subtotal( $price, $order_item_product ) {

		if ( Env::is_functions_in_backtrace(
			array(
				array( 'WC_Subscriptions_Switcher', 'calculate_total_paid_since_last_order' ),
				array( 'WCS_Cart_Renewal', 'get_cart_item_from_session' ),
			)
		) ) {

			$order          = $order_item_product->get_order();
			$order_currency = $order->get_currency( 'edit' );

			$converted_price = $this->price_converter->convert_raw( $price, null, '', $order_currency );

			return $converted_price;
		}

		return $price;
	}

	/**
	 * Convert order total to the store currency.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param string|int|float       $price The total value.
	 * @param \WC_Order_Item_Product $order_item_product
	 *
	 * @return string|int|float
	 */
	public function filter__woocommerce_order_item_get_total( $price, $order_item_product ) {
		if ( Env::is_functions_in_backtrace(
			array(

				/**
				 * Switcher. Trace:
				 *
				 * @see \WC_Subscriptions_Switcher::calculate_total_paid_since_last_order
				 * @see \WCS_Switch_Cart_Item::get_total_paid_for_current_period
				 * @see \WCS_Switch_Cart_Item::get_old_price_per_day
				 * @see \WCS_Switch_Cart_Item::get_switch_type
				 */
				array( 'WC_Subscriptions_Switcher', 'calculate_total_paid_since_last_order' ),

				/**
				 * Renewal.
				 */
				array( 'WCS_Cart_Renewal', 'get_cart_item_from_session' ),
			)
		)
		) {
			$order_currency  = $order_item_product->get_order()->get_currency( 'edit' );
			$converted_price = $this->price_converter->convert_raw( $price, null, '', $order_currency );

			return $converted_price;
		}

		return $price;
	}

	/**
	 * The method {@see \WC_Subscription::get_items_sign_up_fee} gets all data with
	 * the 'edit' context, so we do not apply the meta filters. Here we convert the value.
	 *
	 * @since 2.11.0-rc.1
	 *
	 * @param float                  $sign_up_fee                Sign-up fee.
	 * @param \WC_Order_Item_Product $line_item                  Product in the cart.
	 * @param \WC_Subscription       $subscription               The subscription.
	 * @param string                 $tax_inclusive_or_exclusive Probably do not need.
	 *
	 * @return float
	 * @todo  Maybe restrict to call from {@see \WC_Subscriptions_Switcher::subscription_items_sign_up_fee}.
	 */
	public function filter__woocommerce_subscription_items_sign_up_fee(
		$sign_up_fee,
		$line_item,
		/* @noinspection PhpUnusedParameterInspection */
		$subscription,
		/* @noinspection PhpUnusedParameterInspection */
		$tax_inclusive_or_exclusive // TODO.
	) {
		$product = $line_item->get_product();
		/**
		 * ========== WRONG! ================
		 *
		 * Do not convert from the subscription currency!
		 * All metas we got from the product in the base currency.
		 * <code>
		 *  $subscription_currency = $subscription->get_currency( 'edit' );
		 *    $converted = $this->price_converter->convert( $sign_up_fee, $product, '', $subscription_currency );
		 * </code>
		 * This looks wrong, too.
		 * $converted = $this->price_converter->convert( $sign_up_fee, $product );
		 */

		$meta = $this->filter_product_meta( $sign_up_fee, $product, '_subscription_sign_up_fee' );

		return $meta;
	}

	/**
	 * Short-circuit the price conversion in some specific cases.
	 *
	 * @param false|string|int|float $pre_value  Initially passed as "false". May return the actual value.
	 * @param string|int|float       $value      The price.
	 * @param \WC_Product|null       $product    The product object.
	 * @param string                 $price_type Regular, Sale, etc.
	 *
	 * @return string|int|float|false
	 *
	 * @internal     filter.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__woocommerce_multicurrency_pre_product_get_price( $pre_value, $value, $product = null, $price_type = '' ) {

		if ( false !== $pre_value ) {
			// A previous filter already set the `$pre_value`. We do not disturb.
			return $pre_value;
		}

		if ( ! $this->is_my_product( $product ) ) {
			// Not my business.
			return false;
		}

		/**
		 * Turn off conversion when a Subscription is being switched.
		 * Otherwise, WCS compare old and new prices in different currencies (they switch one of the filters).
		 *
		 * @since 1.15.0
		 * @since 2.9.6-rc.1 No longer valid.
		 * <code>
		 * if ( Env::is_functions_in_backtrace(
		 *    array(
		 *        array( 'WC_Subscriptions_Switcher', 'calculate_prorated_totals' ),
		 *    )
		 * ) ) {
		 *    return $value;
		 * }
		 * </code>
		 */

		/**
		 * If the product has "changes", use them.
		 * They can appear, for instance, with NameYourPrice.
		 *
		 * @since 2.3.0
		 * @since 3.1.1-rc.1 Only use the $changes matching the requested price type.
		 *        Fixed a bug with coupons when signup fee was returned as "price".
		 */
		$price_type_not_prefixed = ltrim( $price_type, '_' );
		$changes                 = $product->get_changes();
		if ( ! empty( $changes[ $price_type_not_prefixed ] ) ) {

			/**
			 * Subscription renewal fix.
			 *
			 * @since 2.9.4-rc.6
			 */

			$cart = WC()->cart;
			if ( ! empty( $cart->cart_contents ) ) {
				foreach ( $cart->cart_contents as $cart_item ) {

					/**
					 * Does this `cart_item` hold an existing subscription? (Renewal?).
					 */
					$subscription_id = 0;
					if ( ! empty( $cart_item['subscription_resubscribe']['subscription_id'] ) ) {
						$subscription_id = $cart_item['subscription_resubscribe']['subscription_id'];
					} elseif ( ! empty( $cart_item['subscription_renewal']['subscription_id'] ) ) {
						$subscription_id = $cart_item['subscription_renewal']['subscription_id'];
					}
					if ( ! $subscription_id ) {
						// No, not this case.
						continue;
					}

					/**
					 * Precaution: the product is indeed a subscription and the cart_item is correctly built.
					 */

					if ( 'subscription' === $product->get_type() ) {
						if ( empty( $cart_item['product_id'] ) || $cart_item['product_id'] !== $product->get_id() ) {
							continue;
						}
					}
					if ( 'subscription_variation' === $product->get_type() ) {
						if ( empty( $cart_item['variation_id'] ) || $cart_item['variation_id'] !== $product->get_id() ) {
							continue;
						}
					}
					$subscription = \wcs_get_subscription( $subscription_id );
					if ( ! \wcs_is_subscription( $subscription ) ) {
						continue;
					}

					/**
					 * All looks valid. We have the total in `changes`.
					 * -----------Let's convert with `FROM` = the subscription currency.
					 */
					return $changes[ $price_type_not_prefixed ];
					/**
					 * // return $this->price_converter->convert(
					 * //    $changes['price'],
					 * //    $product,
					 * //    '',
					 * //    $subscription->get_currency()
					 * // );
					 */
				}
			}

			// TODO: Convert FROM the subscription currency???
			return $this->price_converter->convert( $changes['price'] );
		}

		/**
		 * A "hack": to avoid double conversion, let's get the subscription metas ourselves and convert.
		 * Unused.
		 *
		 * @since 2.0.0
		 * // $price_meta_key = $price_type ? $price_type : '_price';
		 * //
		 * // $price = \get_post_meta( $product->get_id(), $price_meta_key, true );
		 * // if ( (string) $value !== (string) $price ) {
		 * //    return $this->price_converter->convert( $price );
		 * // }
		 */

		// Default: we do not interfere. Let the calling method continue.
		return false;
	}

	/**
	 * Conditionally modify the price HTML string.
	 *
	 * @see   \WC_Subscriptions_Product::get_price_string()
	 *
	 * @since 3.1.1
	 *
	 * @param array                             $include_flags An associative array of flags to indicate how to calculate the price and what to include.
	 * @param \WC_Product_Variable_Subscription $product       The product.
	 *
	 * @return array
	 */
	public function filter__get_price_string( $include_flags, $product ) {

		if ( $product instanceof \WC_Product_Variable_Subscription ) {
			// With variable subscriptions, the "From: ... /month, fee: ...",
			// The sign_up_fee is shown incorrectly.
			// It's not converted, and doesn't consider custom pricing.
			// A fix looks too hard to do, so hiding it for now.
			$include_flags['sign_up_fee'] = false;
		}

		return $include_flags;
	}

	/**
	 * Convert sign-up fee.
	 *
	 * @since 3.1.1
	 *
	 * @param float|int|string $price   Price.
	 * @param \WC_Product      $product The product.
	 *
	 * @return float|int|string
	 */
	public function filter__product_sign_up_fee( $price, $product ) {

		if (
			$this->is_cart_action( 'subscription_switch', $product )
			&& Env::is_function_in_backtrace(
				array( 'WC_Subscriptions_Cart', 'set_subscription_prices_for_calculation' )
			)
		) {
			// Exclude repeated conversion.
			return $price;
		}

		if ( $this->is_cart_action( 'subscription_resubscribe', $product ) ) {
			// TODO: maybe only if $price is 0, i.e. resubscribe w/o sign-up fee?
			return $price;
		}

		$caller_restrictions =
			array(
				array( 'WCS_Switch_Totals_Calculator', 'set_upgrade_cost' ),
			);

		if ( Env::is_functions_in_backtrace( $caller_restrictions ) ) {
			return $price;
		}

		$price = $this->price_converter->get_price( $price, '_subscription_sign_up_fee', $product );

		return $price;
	}

	/**
	 * ===========================
	 * TO DELETE
	 * ===========================
	 */
	/**
	 * Convert subscription sign-up fee.
	 *
	 * @since      2.11.0-rc.1
	 *
	 * @param float|int|string                     $price   Fee.
	 * @param \WC_Product|\WC_Product_Subscription $product Product.
	 *
	 * @return float|int|string
	 * @deprecated 3.1.1 Use {@see filter__product_sign_up_fee}
	 *                                                      public function filter__subscription_sign_up_fee( $price, $product ) {
	 *
	 * if ( $this->is_cart_action( 'subscription_switch', $product )
	 * && Env::is_function_in_backtrace(
	 * array( 'WC_Subscriptions_Cart', 'set_subscription_prices_for_calculation' )
	 * )
	 * ) {
	 * // Exclude repeated conversion.
	 * return $price;
	 * }
	 *
	 * if ( $this->is_cart_action( 'subscription_resubscribe', $product ) ) {
	 * // TODO: maybe only if $price is 0, i.e. resubscribe w/o sign-up fee?
	 * return $price;
	 * }
	 *
	 * // TODO: get from meta. NYP? Custom price for sign-up.
	 * $converted_price = $this->price_converter->get_price( $price, '_subscription_sign_up_fee', $product );
	 *
	 * return $converted_price;
	 * }
	 */

	/**
	 * Convert subscription variation sign-up fee.
	 *
	 * @since      2.11.0-rc.1
	 *
	 * @param float|int|string                               $price   Fee.
	 * @param \WC_Product|\WC_Product_Subscription_Variation $product Product.
	 *
	 * @return float|int|string
	 * @deprecated 3.1.1 Use {@see filter__product_sign_up_fee}
	 *                                                                public function filter__variation_subscription_sign_up_fee( $price, $product ) {
	 *
	 * if ( $this->is_cart_action( 'subscription_switch', $product )
	 * && Env::is_function_in_backtrace(
	 * array( 'WC_Subscriptions_Cart', 'set_subscription_prices_for_calculation' )
	 * )
	 * ) {
	 * // Exclude repeated conversion.
	 * return $price;
	 * }
	 *
	 * if ( $this->is_cart_action( 'subscription_resubscribe', $product ) ) {
	 * // TODO: maybe only if $price is 0, i.e. resubscribe w/o sign-up fee?
	 * return $price;
	 * }
	 *
	 * $caller_restrictions =
	 * array(
	 * array( 'WCS_Switch_Totals_Calculator', 'set_upgrade_cost' ),
	 * );
	 *
	 * if ( Env::is_functions_in_backtrace( $caller_restrictions ) ) {
	 * return $price;
	 * }
	 *
	 * // This doesn't consider custom priced fee.
	 * // $price = $this->filter_product_meta( $price, $product, '_subscription_sign_up_fee' );
	 * $price = $this->price_converter->get_price( $price, '_subscription_sign_up_fee', $product );
	 * return $price;
	 * }
	 */
}
