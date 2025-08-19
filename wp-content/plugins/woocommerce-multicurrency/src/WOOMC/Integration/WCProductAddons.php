<?php
/**
 * Integration.
 * Plugin Name: WooCommerce Product Add-ons.
 * Plugin URI: https://woocommerce.com/products/product-add-ons/
 *
 * @since   1.6.0
 * @since   1.13.0 Refactored and moved to a separate class.
 *
 * @package WOOMC\Integration
 * Copyright (c) 2019. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use WOOMC\API;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\App;
use WOOMC\Price;


/**
 * Class WCProductAddons
 *
 * @package WOOMC\Integration
 */
class WCProductAddons implements InterfaceHookable {

	/**
	 * Used to mark product in Cart as PAO.
	 *
	 * @since 2.8.5
	 * @var string
	 */
	const META_MARKED_AS_PAO = '_woomc_is_pao';

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * Constructor.
	 *
	 * @param Price\Controller $price_controller The Price controller instance.
	 */
	public function __construct( Price\Controller $price_controller ) {
		$this->price_controller = $price_controller;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @see \WC_Product_Addons_Cart::get_item_data displays addons with prices in the cart
	 * @see \WC_Product_Addons_Cart::update_product_price ads addon prices to the (sub)totals in the cart
	 *
	 * @return void
	 */
	public function setup_hooks() {
		// return;
		$this->replace_pao_hooks();

		\add_filter(
			'woocommerce_product_addons_option_price_raw',
			array( $this, 'filter__woocommerce_product_addons_option_price_raw' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_product_addons_price_raw',
			array( $this, 'filter__woocommerce_product_addons_price_raw' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		0 && \add_filter(
			'woocommerce_get_price_excluding_tax',
			array( $this, 'filter__product_addons' ),
			App::HOOK_PRIORITY_EARLY
		);

		0 && \add_filter(
			'woocommerce_get_price_including_tax',
			array( $this, 'filter__product_addons' ),
			App::HOOK_PRIORITY_EARLY
		);

		/**
		 * TEST: DO NOT USE!
		 * \add_filter(
		 * 'woocommerce_product_addons_update_product_price',
		 * function($updated_product_prices, $cart_item_data, $prices ){
		 * $updated_product_prices = [
		 * 'price'         => 100,
		 * 'regular_price' => 100,
		 * 'sale_price'    => 0,
		 * ];
		 * return $updated_product_prices;
		 * },10, 3);
		 * */
	}

	/**
	 * Replace some PAO hooks with our versions.
	 *
	 * @since 2.8.5
	 */
	protected function replace_pao_hooks() {

		// Tell PAO not to adjust prices.
		\add_filter( 'woocommerce_product_addons_adjust_price', '__return_false' );

		/**
		 * Alternatively, remove their hooks.
		 * $this->remove_pao_hook( 'woocommerce_add_cart_item', 'WC_Product_Addons_Cart', 'add_cart_item', 20 );
		 * $this->remove_pao_hook( 'woocommerce_get_cart_item_from_session', 'WC_Product_Addons_Cart', 'get_cart_item_from_session', 20 );
		 */

		\add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 20 );

		\add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 20, 2 );
	}

	/**
	 * Remove a hook.
	 *
	 * @since        2.8.5
	 *
	 * @param string $tag           Tag.
	 * @param string $class_name    Class name.
	 * @param string $function_name Function name.
	 * @param int    $priority      Priority.
	 *
	 * @noinspection PhpUnused
	 */
	protected function remove_pao_hook( $tag, $class_name, $function_name, $priority ) {
		/**
		 * Global.
		 *
		 * @global \WP_Hook[] $wp_filter
		 */
		global $wp_filter;

		if ( isset( $wp_filter[ $tag ]->callbacks[ $priority ] ) ) {
			$hook = $wp_filter[ $tag ];
			foreach ( $hook->callbacks[ $priority ] as $function_key => $the_ ) {
				if ( isset( $the_['function'][0], $the_['function'][1] ) && is_object( $the_['function'][0] ) ) {
					$the_class  = get_class( $the_['function'][0] );
					$the_method = $the_['function'][1];
					if ( $class_name === $the_class && $function_name === $the_method ) {
						$hook->remove_filter( $tag, $function_key, $priority );

						return;
					}
				}
			}
		}
	}

	/**
	 * Overrides {@see \WC_Product_Addons_Cart::add_cart_item()}.
	 * Add cart item. Fires after add cart item data filter.
	 *
	 * @since 2.8.5
	 *
	 * @param array $cart_item_data Cart item meta data.
	 *
	 * @return array
	 */
	public function add_cart_item( $cart_item_data ) {
		$quantity = $cart_item_data['quantity'];

		// #$# CHANGE_START
		if ( ! empty( $cart_item_data['addons'] ) ) {

			/**
			 * Product in cart item.
			 *
			 * @var \WC_Product $product
			 */
			$product      = $cart_item_data['data'];
			$custom_price = API::get_custom_price( $product );
			if ( $custom_price ) {
				$product_price = $this->price_controller->convert_back_raw( $custom_price );
			} else {
				$product_price = (float) $product->get_price( 'edit' );
			}

			$price = $product_price;
			$this->mark_cart_item_as_pao( $product );
			0 && \wp_verify_nonce( '' );
			// #$# CHANGE_END

			// Compatibility with Smart Coupons self-declared gift amount purchase.
			if ( empty( $price ) && ! empty( $_POST['credit_called'] ) ) {
				/** $_POST['credit_called'] is an array. */
				if ( isset( $_POST['credit_called'][ $product->get_id() ] ) ) {
					$price = (float) $_POST['credit_called'][ $product->get_id() ];
				}
			}

			if ( empty( $price ) && ! empty( $cart_item_data['credit_amount'] ) ) {
				$price = (float) $cart_item_data['credit_amount'];
			}

			// Save the price before price type calculations to be used later.
			$cart_item_data['addons_price_before_calc'] = (float) $price;

			foreach ( $cart_item_data['addons'] as $addon ) {
				$price_type  = $addon['price_type'];
				$addon_price = $addon['price'];

				switch ( $price_type ) {
					case 'percentage_based':
						// #$# CHANGE_START
						$price += (float) ( $product_price * ( $addon_price / 100 ) );
						// #$# CHANGE_END
						break;
					case 'flat_fee':
						$price += (float) ( $addon_price / $quantity );
						break;
					default:
						$price += (float) $addon_price;
						break;
				}
			}

			$product->set_price( $price );
		}

		return $cart_item_data;
	}

	/**
	 * Copy of {@see \WC_Product_Addons_Cart::get_cart_item_from_session()}.
	 * Need to override because it calls {@see \WC_Product_Addons_Cart::add_cart_item()}.
	 * Get cart item from session.
	 *
	 * @since 2.8.5
	 *
	 * @param array $cart_item Cart item data.
	 * @param array $values    Cart item values.
	 *
	 * @return array
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {
		if ( ! empty( $values['addons'] ) ) {
			$cart_item['addons'] = $values['addons'];
			$cart_item           = $this->add_cart_item( $cart_item );
		}

		return $cart_item;
	}

	/**
	 * Convert addon prices. For the checkbox, radio, etc.
	 *
	 * @since    1.6.0
	 * @since    1.14.1 Fix: do not convert percentage-based addons.
	 *
	 * @param float|int|string $option_price Cost of the add-on option.
	 * @param string[]         $option       The option.
	 *
	 * @return float|int|string
	 *
	 * @internal filter
	 */
	public function filter__woocommerce_product_addons_option_price_raw( $option_price, $option ) {
		if ( in_array( $option['price_type'], array( 'flat_fee', 'quantity_based' ), true ) ) {
			$option_price = $this->price_controller->convert( $option_price );
		}

		return $option_price;
	}

	/**
	 * Convert addon prices. For the add-ons that are not "multiple choice".
	 *
	 * @since    1.15.0
	 *
	 * @param float|int|string $addon_price Cost of the add-on.
	 * @param string[]         $addon       The add-on.
	 *
	 * @return float|int|string
	 *
	 * @internal filter
	 */
	public function filter__woocommerce_product_addons_price_raw( $addon_price, $addon ) {
		if ( in_array( $addon['price_type'], array( 'flat_fee', 'quantity_based' ), true ) ) {
			$addon_price = $this->price_controller->convert( $addon_price );
		}

		return $addon_price;
	}

	/**
	 * Filter Product Add-ons display prices. For special cases only: the Cart page
	 *
	 * @note     As of POA Version: 3.0.5, there is a bug in the function
	 *
	 * @see      \WC_Product_Addons_Helper::get_product_addon_price_for_display.
	 * `if ( ( is_cart() || is_checkout() ) && null !== $cart_item ) {` is wrong
	 * because it does not consider the mini-cart widget.
	 *
	 * @since    1.6.0
	 *
	 * @param int|float|string $price The price.
	 *
	 * @return float|int|string
	 *
	 * @internal filter.
	 */
	public function filter__product_addons( $price ) {

		// Only if called by certain functions.
		if (
			Env::is_functions_in_backtrace(
				array(
					array( 'WC_Product_Addons_Cart', 'get_item_data' ),
					array( 'Product_Addon_Display', 'totals' ),
				)
			)
		) {
			/**
			 * Only if the price was not retrieved from the Product (and therefore already converted),
			 * but passed as a parameter to...
			 *
			 * @see \wc_get_price_excluding_tax
			 * @see \wc_get_price_including_tax
			 */
			$called_by = Env::get_hook_caller();
			if ( ! empty( $called_by['args'][1]['price'] ) ) {
				$price = $this->price_controller->convert( $price );
			}
		}

		return $price;
	}

	/**
	 * Mark cart item as PAO.
	 *
	 * @since 2.8.5
	 *
	 * @param \WC_Product $product_in_cart Product in Cart.
	 */
	protected function mark_cart_item_as_pao( $product_in_cart ) {
		$product_in_cart->update_meta_data( self::META_MARKED_AS_PAO, 1 );
	}

	/**
	 * Returns true if product is marked as PAO in the Cart.
	 *
	 * @since 2.8.5
	 *
	 * @param \WC_Product $product Product to check.
	 *
	 * @return bool
	 */
	public static function is_product_marked_as_pao( $product ) {
		return (bool) $product->get_meta( self::META_MARKED_AS_PAO, true, 'edit' );
	}
}
