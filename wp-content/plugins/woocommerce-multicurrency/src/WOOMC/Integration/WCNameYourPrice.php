<?php
/**
 * Integration.
 * Plugin Name: Name Your Price
 * Plugin URI: https://woocommerce.com/products/name-your-price/
 *
 * @since 1.11.0
 * @since 3.2.1 Fix: do not convert/fee/charm the amount when entered not in the base currency (@Kathy Darling).
 */

namespace WOOMC\Integration;

use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\App;

/**
 * Class Integration\WCNameYourPrice
 */
class WCNameYourPrice extends AbstractIntegration {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		if ( ! Env::in_wp_admin() ) {

			$raw_price_tags = array(
				'woocommerce_raw_suggested_price',
				'woocommerce_raw_minimum_price',
				'woocommerce_raw_maximum_price',
			);

			/**
			 * Filter tags renamed in NYP 3+.
			 * Method is_nyp_gte exists in NYP 3+.
			 *
			 * @noinspection PhpUndefinedMethodInspection
			 * @noinspection PhpRedundantOptionalArgumentInspection
			 */
			if (
				is_callable( array( '\WC_Name_Your_Price_Compatibility', 'is_nyp_gte' ) )
				&& \WC_Name_Your_Price_Compatibility::is_nyp_gte( '3.0' )
			) {
				$raw_price_tags = array(
					'wc_nyp_raw_suggested_price',
					'wc_nyp_raw_minimum_price',
					'wc_nyp_raw_maximum_price',
				);
			}

			foreach ( $raw_price_tags as $tag ) {
				\add_filter(
					$tag,
					array( $this, 'filter__nyp_prices' ),
					App::HOOK_PRIORITY_EARLY,
					3
				);
			}

			\add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_initial_currency' ) );

			\add_filter(
				'woocommerce_get_cart_item_from_session',
				array( $this, 'filter__woocommerce_get_cart_item_from_session' ),
				App::HOOK_PRIORITY_LATE,
				2
			);

			if ( ! Env::in_wp_admin() ) {
				// Disable the conversion in certain circumstances.
				\add_filter(
					'woocommerce_multicurrency_pre_product_get_price',
					array(
						$this,
						'filter__woocommerce_multicurrency_pre_product_get_price',
					),
					App::HOOK_PRIORITY_LATE,
					4
				);
			}

			// Convert cart editing price.
			\add_filter( 'wc_nyp_edit_in_cart_args', array( $this, 'filter__wc_nyp_edit_in_cart_args' ) );
			\add_filter( 'wc_nyp_get_initial_price', array( $this, 'filter__wc_nyp_get_initial_price' ), 10, 3 );

		}
	}

	/**
	 * Store the initial currency when item is added.
	 *
	 * @since 1.11.0
	 *
	 * @param array $cart_item_data The Cart Item data.
	 *
	 * @return array
	 */
	public function add_initial_currency( $cart_item_data ) {

		if ( isset( $cart_item_data['nyp'] ) ) {
			$cart_item_data['nyp_currency'] = \get_woocommerce_currency();
			$cart_item_data['nyp_original'] = $cart_item_data['nyp'];
		}

		return $cart_item_data;
	}

	/**
	 * Filter Name Your Price Cart prices.
	 *
	 * @since    1.11.0
	 *
	 * @param array $session_data The Session data.
	 * @param array $values       The values.
	 *
	 * @return array
	 *
	 * @internal filter.
	 */
	public function filter__woocommerce_get_cart_item_from_session( $session_data, $values ) {

		// Preserve original currency.
		if ( isset( $values['nyp_currency'] ) ) {
			$session_data['nyp_currency'] = $values['nyp_currency'];
		}

		// Preserve original entered value.
		if ( isset( $values['nyp_original'] ) ) {
			$session_data['nyp_original'] = $values['nyp_original'];
		}

		$current_currency = \get_woocommerce_currency();

		/**
		 * Special processing for Name Your Price:
		 * If the amount entered was not in the store default currency, convert it back to the default.
		 *
		 * @since 2.5.3 Use raw conversion (was losing cents because of rounding).
		 * @since 2.5.3 Refactor to support changing currency when NYP is in the cart.
		 */
		if (
			isset( $session_data['nyp'] )
			&& isset( $session_data['nyp_original'] )
			&& isset( $session_data['nyp_currency'] )
		) {
			/**
			 * Product is in the 'data'.
			 *
			 * @var \WC_Product $product
			 */
			$product =& $session_data['data'];

			$amount_entered_by_the_client   = $session_data['nyp_original'];
			$currency_of_the_entered_amount = $session_data['nyp_currency'];

			// If the currency changed, convert the price entered by the customer into the active currency.
			if ( $currency_of_the_entered_amount !== $current_currency ) {
				$price_in_current_currency = $this->price_controller->convert_raw( $amount_entered_by_the_client, $product, $current_currency, $currency_of_the_entered_amount );

				// Otherwise, put it back to the original amount.
			} else {
				$price_in_current_currency = $amount_entered_by_the_client;
				$this->price_controller->convert_raw( $amount_entered_by_the_client, $product, $current_currency, $currency_of_the_entered_amount );
			}

			// Set converted price. NYP will automatically set the price props.
			$session_data['nyp'] = $price_in_current_currency;

			// Set flags for skipping later conversion.
			$product->add_meta_data( 'nyp_original', $amount_entered_by_the_client );
			$product->add_meta_data( 'nyp_currency', $currency_of_the_entered_amount );

		}

		return $session_data;
	}

	/**
	 * Convert NYP prices.
	 *
	 * @since 2.0.0
	 * @since 2.5.3 - NYP3 passes the product object as 3rd parameter.
	 *
	 * @param string|int|float  $value      The price.
	 * @param int               $product_id Product ID.
	 * @param \WC_Product|false $product    The product object.
	 *
	 * @return float|int|string
	 */
	public function filter__nyp_prices( $value, $product_id, $product = false ) {

		if ( ! $product instanceof \WC_Product ) {
			$product = \wc_get_product( $product_id );
		}

		return $this->price_controller->convert( $value, $product );
	}

	/**
	 * Short-circuit the price conversion for NYP products in the cart.
	 *
	 * @since        3.2.1
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

		/**
		 * Don't convert prices when calculating and NYP product in the cart.
		 */
		$amount_entered_by_the_client   = $product->get_meta( 'nyp_original' );
		$currency_of_the_entered_amount = $product->get_meta( 'nyp_currency' );

		if ( $amount_entered_by_the_client && $currency_of_the_entered_amount ) {
			return $value;
		}

		/**
		 * Default: we do not interfere. Let the calling method continue.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		return $pre_value;
	}

	/**
	 * Add currency to cart edit link.
	 *
	 * @since 3.4.2-1
	 *
	 * @param array $args the args to be appended to the Edit link
	 *
	 * @return array
	 */
	public function filter__wc_nyp_edit_in_cart_args( $args ) {
		$args['nyp_currency'] = \get_woocommerce_currency();

		return $args;
	}

	/**
	 * Maybe convert any prices being edited from the cart.
	 *
	 * @since 3.4.2-1
	 *
	 * @param string      $initial_price The price.
	 * @param \WC_Product $product       The product.
	 * @param string      $suffix        Needed for composites and bundles.
	 *
	 * @return string
	 */
	public function filter__wc_nyp_get_initial_price( $initial_price, $product, $suffix ) {

		// PHPCS: WordPress.Security.NonceVerification.Recommended is invalid in the context of this method.
		0 && \wp_verify_nonce( '' );

		if ( isset( $_REQUEST[ 'nyp_raw' . $suffix ] ) && isset( $_REQUEST['nyp_currency'] ) ) {
			$from_currency    = \wc_clean( $_REQUEST['nyp_currency'] );
			$current_currency = \get_woocommerce_currency();
			if ( $from_currency !== $current_currency ) {
				$raw_price     = \wc_clean( $_REQUEST[ 'nyp_raw' . $suffix ] );
				$initial_price = $this->price_controller->convert_raw( $raw_price, $product, $current_currency, $from_currency );
			}
		}

		return $initial_price;
	}
}
