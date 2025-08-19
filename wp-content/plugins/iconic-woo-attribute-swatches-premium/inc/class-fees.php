<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Fees
 *
 * This class is for attribute fees.
 *
 * @class          Iconic_WAS_Fees
 * @version        1.0.0
 * @category       Class
 * @author         Iconic
 */
class Iconic_WAS_Fees {
	/**
	 * DB version.
	 *
	 * @var string
	 */
	protected static $db_version = '1.0.0';

	/**
	 * DB name.
	 *
	 * @var string
	 */
	public static $db_name = 'iconic_was_fees';

	/**
	 * Install/update the DB table.
	 */
	public static function install() {
		if ( version_compare( get_site_option( 'iconic_was_db_version' ), self::$db_version, '>=' ) ) {
			return;
		}

		$table_name = self::get_table_name();

		$sql = "CREATE TABLE $table_name (
		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`product_id` bigint(20) DEFAULT NULL,
		`attribute` varchar(200) DEFAULT NULL,
		`fees` longtext,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'iconic_was_db_version', self::$db_version );
	}

	/**
	 * Run actions/filters for this class.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Run on init.
	 */
	public static function init() {
		if ( apply_filters( 'iconic_was_disable_fees', false ) ) {
			return;
		}

		add_action( 'woocommerce_update_product', array( __CLASS__, 'on_update_product' ), 10 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'calculate_totals' ), 10 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'cart_item_price' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'add_cart_item_fees' ), 10, 2 );
		add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );
		add_action( 'woocommerce_before_variations_form', array( __CLASS__, 'output_fees_in_form' ), 10 );
		add_filter( 'woocommerce_variation_option_name', array( __CLASS__, 'variation_option_name' ), 10, 4 );
		add_filter( 'woocommerce_variable_price_html', array( __CLASS__, 'variable_price_html' ), 10, 2 );
		add_filter( 'woocommerce_show_variation_price', array( __CLASS__, 'show_variation_price' ), 10, 3 );
		add_filter( 'iconic_was_attribute_fields', array( __CLASS__, 'add_fee_field_to_attribute_term' ), 10, 4 );
		add_filter( 'woocommerce_get_price_excluding_tax', array( __CLASS__, 'add_order_item_fees' ), 10, 3 );
	}

	/**
	 * Add fees meta row.
	 *
	 * @param WC_Product_Attribute $attribute
	 * @param int                  $i
	 */
	public static function add_fees_meta_row( $attribute ) {
		$attribute_data = self::get_attribute_data( $attribute );

		if ( ! $attribute->get_variation() ) {
			return;
		}

		ob_start();
		?>
		<table class="iconic-was-table widefat fixed striped">
			<thead>
			<th><?php esc_html_e( 'Value', 'iconic-was' ); ?></th>
			<th><?php esc_html_e( 'Fee', 'iconic-was' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</th>
			</thead>
			<tbody>
			<?php foreach ( $attribute_data['values'] as $slug => $value ) { ?>
				<tr>
					<td><?php echo esc_html( $value['label'] ); ?></td>
					<td>
						<input
						name="iconic-was-fees[<?php echo esc_attr( $attribute_data['slug'] ); ?>][<?php echo esc_attr( $slug ); ?>]"
						class="short wc_input_price iconic-was-fees__input"
						type="number"
						min="0"
						step="0.01"
						onkeypress="return event.charCode >= 48 || 46 === event.charCode"
						value="<?php echo esc_attr( $value['value'] ); ?>"
						placeholder="<?php echo ! empty( $value['default'] ) ? esc_attr( sprintf( '%s: %.2f', __( 'Default', 'iconic-was' ), $value['default'] ) ) : ''; ?>"
						>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get attribute data.
	 *
	 * @param WC_Product_Attribute $attribute
	 * @param int                  $product_id
	 *
	 * @return array
	 */
	public static function get_attribute_data( $attribute, $product_id = null ) {
		if ( ! $product_id ) {
			if ( isset( $_GET['post'] ) ) {
				$product_id = absint( $_GET['post'] );
			} elseif ( isset( $_POST['post_id'] ) ) {
				$product_id = absint( $_POST['post_id'] );
			}
		}

		$product_id = apply_filters( 'iconic_was_get_attribute_data_product_id', $product_id );

		$return = array(
			'slug'   => sanitize_title( $attribute->get_name() ),
			'values' => array(),
		);

		if ( ! $product_id ) {
			return $return;
		}

		$return['slugs']   = $attribute->get_slugs();
		$return['options'] = $attribute->get_options();

		foreach ( $return['options'] as $index => $option ) {
			$label                = $option;
			$attribute_value_slug = $return['slugs'][ $index ];
			$default              = false;

			if ( $attribute->get_taxonomy() ) {
				$term    = get_term_by( 'id', $option, $attribute->get_taxonomy() );
				$label   = $term->name;
				$default = floatval( Iconic_WAS_Swatches::get_swatch_value( 'taxonomy', 'fee', $term ) );
			}

			$fee = self::get_fees_by_attribute( $product_id, $return['slug'], $attribute_value_slug );

			$return['values'][ $attribute_value_slug ] = array(
				'label'   => $label,
				'value'   => is_numeric( $fee ) ? $fee : '',
				'default' => $default,
			);
		}

		return $return;
	}

	/**
	 * Update product.
	 *
	 * @param $product_id
	 */
	public static function on_update_product( $product_id ) {
		$posted_fees = filter_input( INPUT_POST, 'iconic-was-fees', FILTER_DEFAULT, FILTER_FORCE_ARRAY );

		if ( is_null( $posted_fees ) ) {
			$posted_data = filter_input( INPUT_POST, 'data' );

			if ( ! $posted_data ) {
				return;
			}

			parse_str( $posted_data, $data );

			$posted_fees = isset( $data['iconic-was-fees'] ) ? $data['iconic-was-fees'] : null;
		}

		if ( is_null( $posted_fees ) ) {
			return;
		}

		foreach ( $posted_fees as $attribute => $fees ) {
			self::set_fees( $product_id, $attribute, $fees );
		}
	}

	/**
	 * Set fees.
	 *
	 * @param int    $product_id
	 * @param string $attribute
	 * @param array  $fees
	 */
	public static function set_fees( $product_id, $attribute, $fees ) {
		global $wpdb;

		$fees       = array_filter( $fees, 'is_numeric' );
		$table_name = self::get_table_name();

		$data = array(
			'product_id' => absint( $product_id ),
			'attribute'  => $attribute,
		);

		if ( empty( $fees ) ) {
			$wpdb->delete(
				$table_name,
				array(
					'product_id' => $data['product_id'],
					'attribute'  => $data['attribute'],
				)
			);

			return;
		}

		$data['fees'] = $fees;

		$format = array(
			'%d',
			'%s',
			'%s',
		);

		$current_fees = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE product_id = %d AND attribute = %s",
				$product_id,
				$attribute
			),
			ARRAY_A
		);

		$data['fees'] = serialize( $data['fees'] );

		if ( $current_fees && ! is_wp_error( $current_fees ) ) {
			// Update existing records.
			$where = array(
				'product_id' => $data['product_id'],
				'attribute'  => $data['attribute'],
			);

			$where_format = array( '%d', '%s' );
			$wpdb->update( $table_name, $data, $where, $format, $where_format );
		} else {
			$wpdb->insert(
				$table_name,
				$data,
				$format
			);
		}
	}

	/**
	 * Get all fees for product.
	 *
	 * @param int|WC_Product $product Product or product ID.
	 *
	 * @return bool|array
	 */
	public static function get_fees( $product ) {
		static $fees = array();

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		$product = apply_filters( 'iconic_was_get_fees_product', $product );

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return false;
		}

		$product_id = $product->get_id();

		if ( ! wp_doing_ajax() && isset( $fees[ $product_id ] ) ) {
			return apply_filters( 'iconic_was_fees', $fees[ $product_id ], $product );
		}

		$fees[ $product_id ] = array();
		$attributes          = Iconic_WAS_Attributes::get_product_attributes( $product_id );

		if ( empty( $attributes ) ) {
			return apply_filters( 'iconic_was_fees', $fees[ $product_id ], $product );
		}

		$product_fees = self::get_product_specific_fees( $product_id );

		// Loop all variable attributes.
		foreach ( $attributes as $attribute_key => $attribute ) {
			$fees[ $product_id ][ $attribute_key ] = array();
			$options                               = $attribute->get_options();

			// Loop through terms and assign fee.
			foreach ( $options as $option ) {
				if ( $attribute->is_taxonomy() ) {
					$term = get_term_by( 'id', absint( $option ), $attribute->get_name() );

					if ( empty( $term ) ) {
						continue;
					}

					$option = $term->slug;
				}

				// Use product specific fee if it exists.
				if ( isset( $product_fees[ $attribute_key ] ) && isset( $product_fees[ $attribute_key ][ $option ] ) ) {
					$fees[ $product_id ][ $attribute_key ][ $option ] = $product_fees[ $attribute_key ][ $option ];
					continue;
				}

				// Don't use global fees on the admin side (product edit screen).
				if ( is_admin() && ! wp_doing_ajax() ) {
					$fees[ $product_id ][ $attribute_key ][ $option ] = '';
					continue;
				}

				// If it's not a taxonomy, set to 0 and exit.
				if ( ! $attribute->is_taxonomy() ) {
					$fees[ $product_id ][ $attribute_key ][ $option ] = 0;
					continue;
				}

				// Otherwise, check the global term for a fee.
				$global_term_value = Iconic_WAS_Swatches::get_swatch_value( 'taxonomy', 'fee', $term );

				if ( $global_term_value ) {
					if ( is_admin() && ! wp_doing_ajax() ) {
						$fees[ $product_id ][ $attribute_key ][ $option ] = '';
					} else {
						$fees[ $product_id ][ $attribute_key ][ $option ] = floatval( $global_term_value );
					}
				}
			}
		}

		return apply_filters( 'iconic_was_fees', $fees[ $product_id ], $product );
	}

	/**
	 * Get fees assigned to a product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool|mixed
	 */
	public static function get_product_specific_fees( $product_id ) {
		global $wpdb;

		static $fees = array();

		if ( ! wp_doing_ajax() && isset( $fees[ $product_id ] ) ) {
			return $fees[ $product_id ];
		}

		$table_name = self::get_table_name();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE product_id = %d",
				$product_id
			),
			ARRAY_A
		);

		if ( empty( $results ) || is_wp_error( $results ) ) {
			return false;
		}

		$fees[ $product_id ] = array();

		foreach ( $results as $result ) {
			$fees[ $product_id ][ $result['attribute'] ] = array_map( 'floatval', (array) maybe_unserialize( $result['fees'] ) );
		}

		return apply_filters( 'iconic_was_product_specific_fees', $fees[ $product_id ], $product_id );
	}

	/**
	 * Get fees by attribute.
	 *
	 * Static response to reduce DB queries.
	 *
	 * @param int    $product_id
	 * @param string $attribute The attribute name.
	 * @param bool   $value     Return the fee for a specific attribute value.
	 *
	 * @return array|bool
	 */
	public static function get_fees_by_attribute( $product_id, $attribute, $value = false ) {
		if ( ! $product_id || ! $attribute ) {
			return false;
		}

		static $fees = array();

		/**
		 * Filter whether the static variable `$fees` should be cleared when
		 * retrieving the fees by attribute in the get_fees_by_attribute function.
		 *
		 * @since 1.18.0
		 * @hook iconic_was_should_clear_static_fees_when_get_fees_by_attribute
		 * @param  bool   $should_clear_static_fees Default: false.
		 * @param  int    $product_id               The product ID.
		 * @param  string $attribute                The attribute name.
		 * @param  bool   $value                    Return the fee for a specific attribute value.
		 * @return bool New value
		 */
		$should_clear_static_fees = apply_filters( 'iconic_was_should_clear_static_fees_when_get_fees_by_attribute', false, $product_id, $attribute, $value );

		if ( $should_clear_static_fees ) {
			$fees = array();
		}

		if ( ! wp_doing_ajax() && isset( $fees[ $product_id ] ) && isset( $fees[ $product_id ][ $attribute ] ) ) {
			if ( false !== $value ) {
				return isset( $fees[ $product_id ][ $attribute ][ $value ] ) ? $fees[ $product_id ][ $attribute ][ $value ] : false;
			}

			return $fees[ $product_id ][ $attribute ];
		}

		$all_fees  = self::get_fees( $product_id );
		$attribute = str_replace( 'attribute_', '', $attribute );

		// If no fees are set, return false.
		if ( empty( $all_fees ) || ! isset( $all_fees[ $attribute ] ) ) {
			$fees[ $product_id ][ $attribute ] = false;

			return $fees[ $product_id ][ $attribute ];
		}

		// Otherwise, assign the fees to the attirbute.
		$fees[ $product_id ][ $attribute ] = $all_fees[ $attribute ];

		// If we fetching all fees for this attribute, return.
		if ( false === $value ) {
			return $fees[ $product_id ][ $attribute ];
		}

		// If the value/term we want is not set, return false.
		if ( ! isset( $all_fees[ $attribute ][ $value ] ) ) {
			$fees[ $product_id ][ $attribute ][ $value ] = false;

			return $fees[ $product_id ][ $attribute ][ $value ];
		}

		// Otherwise, assign the value and return it.
		$fees[ $product_id ][ $attribute ][ $value ] = is_numeric( $all_fees[ $attribute ][ $value ] ) ? floatval( $all_fees[ $attribute ][ $value ] ) : '';

		return $fees[ $product_id ][ $attribute ][ $value ];
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . self::$db_name;
	}

	/**
	 * Modify cart item prices.
	 *
	 * @param WC_Cart $cart
	 */
	public static function calculate_totals( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Avoiding hook repetition (when using price calculations for example)
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		$cart_items = $cart->get_cart();

		if ( empty( $cart_items ) ) {
			return;
		}

		foreach ( $cart_items as $key => $cart_item ) {
			if ( empty( $cart_item['variation'] ) ) {
				continue;
			}

			// If the price has already been set, use it.
			if ( ! empty( $cart_item['iconic_was_fee'] ) ) {
				$cart_items[ $key ]['data']->set_price( $cart_item['iconic_was_fee'] );
				continue;
			}

			// Context is 'edit' because we want the real price without filters applied.
			$base_price = floatval( $cart_item['data']->get_price( 'edit' ) );
			$base_price = apply_filters( 'iconic_was_calculate_totals_base_price', $base_price, $cart_item['data'] );

			foreach ( $cart_item['variation'] as $attribute => $attribute_value ) {
				if ( empty( $attribute_value ) ) {
					continue;
				}

				$attribute = str_replace( 'attribute_', '', $attribute );
				$fees      = self::get_fees_by_attribute( $cart_item['product_id'], $attribute, $attribute_value );

				if ( $fees ) {
					$base_price += $fees;
				}
			}

			$cart_items[ $key ]['data']->set_price( $base_price );
			$cart_items[ $key ]['iconic_was_fee'] = $base_price;
		}

		$cart->set_cart_contents( $cart_items );
	}

	/**
	 * Modify cart item price for mini cart, mainly.
	 *
	 * @param string $price_html    HTML string containing the price.
	 * @param array  $cart_item     Cart item data.
	 * @param string $cart_item_key Cart item key.
	 *
	 * @return string
	 */
	public static function cart_item_price( $price_html, $cart_item, $cart_item_key ) {
		if ( empty( $cart_item['iconic_was_fee'] ) ) {
			return $price_html;
		}

		/**
		 * Filter the cart item price; useful for plugin compatibility.
		 *
		 * @since 1.14.2
		 * @filter iconic_was_cart_item_price
		 * @param float  $cart_item_price The cart item price including fee(s).
		 * @param array  $cart_item       Cart item data.
		 * @param string $cart_item_key   Cart item key.
		 */
		$cart_item_price = apply_filters( 'iconic_was_cart_item_price', $cart_item['iconic_was_fee'], $cart_item, $cart_item_key );

		return wc_price( $cart_item_price );
	}

	/**
	 * Add fee to product terms (variation dropdowns).
	 *
	 * @param array  $terms
	 * @param int    $product_id
	 * @param string $taxonomy
	 * @param array  $args
	 *
	 * @return array
	 */
	public static function get_product_terms( $terms, $product_id, $taxonomy, $args ) {
		if ( is_admin() || strpos( $taxonomy, 'pa_' ) === false ) {
			return $terms;
		}

		if ( empty( $terms ) ) {
			return $terms;
		}

		foreach ( $terms as $index => $term ) {
			$fee = self::get_fees_by_attribute( $product_id, $taxonomy, $term->slug );

			if ( ! $fee ) {
				continue;
			}

			$terms[ $index ]->name = self::add_fee_to_label( $term->name, $fee, $product_id );
		}

		return $terms;
	}

	/**
	 * Add fee to swatch label (taxonomy).
	 *
	 * @param string       $term_name
	 * @param WP_Term|null $term
	 * @param string       $attribute_slug
	 * @param WC_Product   $product
	 *
	 * @return string
	 */
	public static function variation_option_name( $term_name, $term = null, $attribute_slug = null, $product = null ) {
		if ( empty( $product ) ) {
			global $product;
		}

		// Backwards compatibility check (as term, attribute_slug and product are all optional).
		if ( ( ! $product instanceof WC_Product ) ||
			( ! empty( $_POST['action'] ) && in_array(
				$_POST['action'],
				array(
					'woocommerce_load_variations',
					'woocommerce_add_variation',
				)
			) ) ||
			( is_admin() && ! wp_doing_ajax() )
		) {
			return $term_name;
		}

		$product_id = $product->get_id();

		if ( is_a( $term, 'WP_Term' ) ) {
			$term_slug = $term->slug;
		} else {
			$term_slug      = $term_name;
			$attribute_slug = sanitize_title( $attribute_slug );
		}

		$fee = self::get_fees_by_attribute( $product_id, sanitize_title( $attribute_slug ), $term_slug );

		if ( ! $fee ) {
			return $term_name;
		}

		return self::add_fee_to_label( $term_name, $fee, $product_id );
	}

	/**
	 * Add fee to label.
	 *
	 * @param string $label
	 * @param float  $fee
	 *
	 * @return string
	 */
	public static function add_fee_to_label( $label, $fee, $product_id ) {
		if ( apply_filters( 'iconic_was_hide_attribute_label_fee', false ) ) {
			return $label;
		}

		$prefix = $fee > 0 ? '+' : '';

		$product = wc_get_product( $product_id );
		$fee     = self::call_function_with_positive_value( $product, $fee, 'wc_get_price_to_display' );

		if ( ! $fee ) {
			return $label;
		}

		return wp_strip_all_tags( sprintf( '%s (%s%s)', $label, $prefix, wc_price( $fee ) ) );
	}

	/**
	 * Modify variable price.
	 *
	 * @param string              $price
	 * @param WC_Product_Variable $product
	 *
	 * @return string
	 */
	public static function variable_price_html( $price, $product ) {
		if ( ! self::has_fees( $product->get_id() ) ) {
			return $price;
		}

		$min_price = Iconic_WAS_Helpers::get_min_price( $product );

		if ( ! $min_price ) {
			return $price;
		}

		$price_string = Iconic_WAS_Helpers::get_price_string( $product, $min_price );

		$wc_product_instance = new WC_Product();

		$suffix = $wc_product_instance->get_price_suffix( $min_price, 1 );

		if ( $suffix ) {
			$price_string = $price_string . ' ' . $suffix;
		}

		return apply_filters( 'iconic_was_price_from', $price_string, $product );
	}

	/**
	 * Does this product have fees associated to it?
	 *
	 * @param int $product_id
	 *
	 * @return bool
	 */
	public static function has_fees( $product_id ) {
		static $fees = array();

		if ( isset( $fees[ $product_id ] ) ) {
			return $fees[ $product_id ];
		}

		$product_fees = self::get_fees( $product_id );

		if ( empty( $product_fees ) ) {
			return false;
		}

		foreach ( $product_fees as $attribute => $fees ) {
			$fees = array_filter( $fees );

			if ( ! empty( $fees ) ) {
				continue;
			}

			unset( $product_fees[ $attribute ] );
		}

		$product_fees = array_filter( $product_fees );

		return ! empty( $product_fees ) ? $product_fees : false;
	}

	/**
	 * Output fees in form.
	 */
	public static function output_fees_in_form() {
		global $product;

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$fees = self::has_fees( $product->get_id() );

		if ( ! $fees ) {
			return;
		}

		$fees_processed = array();

		foreach ( $fees as $attribute_key => $attribute_values ) {
			foreach ( $attribute_values as $attribute_value => $fee ) {
				$fees_processed[ $attribute_key ][ $attribute_value ] = array(
					'default'             => self::call_function_with_positive_value( $product, $fee, 'wc_get_price_to_display' ),
					'price_including_tax' => self::call_function_with_positive_value( $product, $fee, 'wc_get_price_including_tax' ),
					'price_excluding_tax' => self::call_function_with_positive_value( $product, $fee, 'wc_get_price_excluding_tax' ),
				);
			}
		}
		?>
		<script class="iconic-was-fees" type="application/json"><?php echo json_encode( $fees_processed ); ?></script>
		<?php
	}

	/**
	 * Show variation price on product page.
	 *
	 * @param bool                 $show
	 * @param WC_Product_Variable  $product
	 * @param WC_Product_Variation $variation
	 *
	 * @return bool
	 */
	public static function show_variation_price( $show, $product, $variation ) {
		if ( ! self::has_fees( $product->get_id() ) ) {
			return $show;
		}

		return true;
	}

	/**
	 * Add fee field to gloabl attribute.
	 *
	 * @param $fields
	 * @param $is_edit_page
	 * @param $term
	 * @param $swatch_type
	 *
	 * @return mixed
	 */
	public static function add_fee_field_to_attribute_term( $fields, $is_edit_page, $term, $swatch_type ) {
		$value = $is_edit_page ? Iconic_WAS_Swatches::get_swatch_value( 'taxonomy', 'fee', $term ) : '';

		$fields[] = array(
			'label'       => sprintf( '<label for="iconic-was-fee-field">%s (%s)</label>', __( 'Fee', 'iconic-was' ), get_woocommerce_currency_symbol() ),
			'field'       => sprintf( '<input type="number" name="iconic_was_term_meta[fee]" value="%s" class="short wc_input_price" type="number" step="0.01" />', esc_attr( $value ) ),
			'description' => '',
		);

		return $fields;
	}

	/**
	 * This function would pass the positive value of the given price
	 * to the mentioned function and then return the value back with the
	 * original sign (+ or -).
	 *
	 * Why do we need this function?
	 * wc_get_price_to_display, wc_get_price_including_tax etc functions only accept
	 * positive value, we can't pass negetive values to them. That's where this functions
	 * comes to use.
	 *
	 * @param Object $product  Product.
	 * @param float  $price    Price.
	 * @param string $function Function to call.
	 *
	 * @return float.
	 */
	public static function call_function_with_positive_value( $product, $price, $function ) {
		// Save the original sign (positive or negetive) of the price.
		$multiplier = $price < 0 ? -1 : 1;

		if ( ! function_exists( $function ) || empty( $price ) ) {
			return false;
		}

		$price = $multiplier * call_user_func( $function, $product, array( 'price' => abs( $price ) ) );
		return round( $price, wc_get_price_decimals() );
	}

	/**
	 * Add WAS fees to order items.
	 *
	 * @param float|int $return_price Product price.
	 * @param int       $qty          Product quantity.
	 * @param object    $product      Product object.
	 * @return float|int
	 */
	public static function add_order_item_fees( $return_price, $qty, $product ) {
		if (
			! is_admin() ||
			! wp_doing_ajax() ||
			( empty( $_REQUEST['action'] ) || 'woocommerce_add_order_item' !== sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! $product->is_type( 'variation' )
		) {
			return $return_price;
		}

		$attributes = $product->get_attributes( $product->get_id() );

		if ( ! $attributes ) {
			return $return_price;
		}

		$fees = self::get_fees( $product->get_parent_id() );

		if ( ! $fees ) {
			return $return_price;
		}

		// In this context, the base return price must be the same
		// as a single unit price of the product item.
		$new_return_price = $return_price / $qty;

		foreach ( $attributes as $attribute_slug => $attribute_value ) {
			if ( ! empty( $fees[ $attribute_slug ] ) &&
				array_key_exists( $attribute_value, $fees[ $attribute_slug ] )
			) {
				$new_return_price = $new_return_price + $fees[ $attribute_slug ][ $attribute_value ];
			}
		}

		return $new_return_price * $qty;
	}

	/**
	 * Add formatted fees to attribute value labels
	 * in the cart/mini-cart/checkout.
	 *
	 * @param array $data      Item data.
	 * @param array $cart_item Cart item.
	 *
	 * @return array
	 */
	public static function add_cart_item_fees( $data, $cart_item ) {
		/**
		 * Filter: whether to add cart item fees.
		 *
		 * @since 1.8.0
		 * @param bool $add Boolean true to add cart item fees.
		 *
		 * @return bool
		 */
		if ( ! apply_filters( 'iconic_was_add_cart_item_fees', true ) ) {
			return $data;
		}

		if ( empty( $cart_item['product_id'] ) ) {
			return $data;
		}

		$fees = self::get_fees( $cart_item['product_id'] );

		if ( empty( $fees ) ) {
			return $data;
		}

		foreach ( $data as $key => $item ) {
			$custom_slug = sanitize_title( $item['key'] );
			$global_slug = 'pa_' . sanitize_title( $item['key'] );
			$fee         = false;

			if ( ! empty( $fees[ $custom_slug ][ $item['value'] ] ) ) {
				// Custom attributes.
				$fee = $fees[ $custom_slug ][ $item['value'] ];
			} elseif ( ! empty( $fees[ $global_slug ][ sanitize_title( $item['value'] ) ] ) ) {
				// Global attributes.
				$fee = $fees[ $global_slug ][ sanitize_title( $item['value'] ) ];
			}

			if ( ! empty( $fee ) ) {
				$formatted_fee         = ' (+' . wc_price( $fee ) . ')';
				$data[ $key ]['value'] = $data[ $key ]['value'] . $formatted_fee;
			}
		}

		return $data;
	}
}
