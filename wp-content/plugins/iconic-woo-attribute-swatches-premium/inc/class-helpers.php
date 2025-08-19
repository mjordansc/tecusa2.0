<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Helpers
 *
 * This class is for anything that can be used over and over,
 * a helper, if you will
 *
 * @class          Iconic_WAS_Helpers
 * @version        1.0.0
 * @category       Class
 * @author         Iconic
 */
class Iconic_WAS_Helpers {
	/**
	 * Get field
	 */
	public function get_field( $args = array() ) {
		$defaults = array(
			'type'        => 'text',
			'class'       => false,
			'name'        => false,
			'value'       => '',
			'options'     => array(),
			'id'          => false,
			'conditional' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$args['id'] = $args['id'] ? $args['id'] : self::strip_brackets( $args['name'] );

		$field_method_name = sprintf( 'get_%s_field', $args['type'] );

		if ( ! method_exists( $this, $field_method_name ) ) {
			return false;
		}

		return sprintf( '<div class="iconic-was-field iconic-was-field--%s">%s</div>', $args['type'], $this->$field_method_name( $args ) );
	}

	/**
	 * Strip square brackets.
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	public static function strip_brackets( $string ) {
		return str_replace( array( ']', '[' ), array( '', '-' ), $string );
	}

	/**
	 * Field: Text
	 */
	public function get_text_field( $args = array() ) {
		return sprintf( '<input type="text" name="%s" value="%s" id="%s">', esc_attr( $args['name'] ), $args['value'], $args['id'] );
	}

	/**
	 * Field: Number.
	 */
	public function get_number_field( $args = array() ) {
		return sprintf( '<input type="number" name="%s" value="%s" id="%s">', esc_attr( $args['name'] ), esc_attr( $args['value'] ), esc_attr( $args['id'] ) );
	}

	/**
	 * Field: Dimensions
	 */
	public function get_dimensions_field( $args = array() ) {
		$width  = sprintf( '<input type="number" name="%s[width]" value="%s" id="%s">', esc_attr( $args['name'] ), esc_attr( $args['value']['width'] ), esc_attr( $args['id'] ) );
		$height = sprintf( '<input type="number" name="%s[height]" value="%s" id="%s">', esc_attr( $args['name'] ), esc_attr( $args['value']['height'] ), esc_attr( $args['id'] ) );

		return sprintf( '<table class="iconic-was-field__dimensions-table"><tr><td><div class="iconic-was-field__dimensions-table-dimension">%s %s</div><div class="iconic-was-field__dimensions-table-dimension">%s %s</div></td></tr></table>', __( 'W', 'iconic-was' ), $width, __( 'H', 'iconic-was' ), $height );
	}

	/**
	 * Field: Select
	 */
	public function get_select_field( $args = array() ) {
		$field = sprintf( '<select name="%s" id="%s" class="iconic-was-select %s" data-conditional="%s">', $args['name'], $args['id'], $args['class'], $args['conditional'] );

		if ( ! empty( $args['options'] ) ) {
			foreach ( $args['options'] as $value => $text ) {
				$selected = selected( $value, $args['value'], false );

				$field .= sprintf( '<option value="%s" %s>%s</option>', $value, $selected, $text );
			}
		}

		$field .= '<select>';

		return $field;
	}

	/**
	 * Field: Groups
	 */
	public function get_groups_field( $args = array() ) {
		ob_start();
		?>
		<select class="iconic-was-tags-field" multiple="multiple" name="iconic_was_attribute_meta[groups][]">
			<?php if ( ! empty( $args['value'] ) ) { ?>
				<?php foreach ( $args['value'] as $value ) { ?>
					<option value="<?php esc_attr_e( $value ); ?>" selected="selected"><?php echo $value; ?></option>
				<?php } ?>
			<?php } ?>
		</select>
		<script>
			jQuery( '.iconic-was-tags-field' ).selectWoo( {
				tags: true
			} );
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Convert Hex to RGB
	 *
	 * @param string $hex
	 *
	 * @return bool|array $rgb
	 */
	public function hex_to_rgb( $hex ) {
		if ( strpos( $hex, '#' ) !== 0 ) {
			return false;
		}

		$hex = str_replace( '#', '', $hex );

		if ( strlen( $hex ) == 3 ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}

		$rgb = array( $r, $g, $b );

		return $rgb;
	}

	/**
	 * Luma
	 *
	 * Takes a hex value and converts it to a lightness
	 * value between 0 (dark) and 1 (light).
	 *
	 * @param string $hex
	 *
	 * @return bool|int
	 */
	public function luma( $hex ) {
		$rgb = $this->hex_to_rgb( $hex );

		if ( ! $rgb ) {
			return false;
		}

		return ( 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2] ) / 255;
	}

	/**
	 * Unset item in array
	 *
	 * @param string $item
	 * @param array  $array
	 *
	 * @return array
	 */
	public function unset_item( $item, $array ) {
		if ( ( $key = array_search( $item, $array ) ) !== false ) {
			unset( $array[ $key ] );
		}

		return $array;
	}

	/**
	 * Get size information for all currently-registered image sizes.
	 *
	 * @global $_wp_additional_image_sizes
	 * @uses   get_intermediate_image_sizes()
	 * @return array $sizes Data for all currently-registered image sizes.
	 */
	public function get_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
				$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}

		return $sizes;
	}

	/**
	 * Get size information for a specific image size.
	 *
	 * @uses   get_image_sizes()
	 *
	 * @param  string $size The image size for which to retrieve data.
	 *
	 * @return bool|array $size Size data about an image size or false if the size doesn't exist.
	 */
	public function get_image_size( $size ) {
		$sizes = $this->get_image_sizes();

		if ( isset( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		}

		return false;
	}

	/**
	 * Get the width of a specific image size.
	 *
	 * @uses   get_image_size()
	 *
	 * @param  string $size The image size for which to retrieve data.
	 *
	 * @return bool|string $size Width of an image size or false if the size doesn't exist.
	 */
	public function get_image_width( $size ) {
		if ( ! $size = $this->get_image_size( $size ) ) {
			return false;
		}

		if ( isset( $size['width'] ) ) {
			return $size['width'];
		}

		return false;
	}

	/**
	 * Get the height of a specific image size.
	 *
	 * @uses   get_image_size()
	 *
	 * @param  string $size The image size for which to retrieve data.
	 *
	 * @return bool|string $size Height of an image size or false if the size doesn't exist.
	 */
	public function get_image_height( $size ) {
		if ( ! $size = $this->get_image_size( $size ) ) {
			return false;
		}

		if ( isset( $size['height'] ) ) {
			return $size['height'];
		}

		return false;
	}

	/**
	 * Check whether the plugin is inactive.
	 *
	 * Reverse of is_plugin_active(). Used as a callback.
	 *
	 * @since 3.1.0
	 * @see   is_plugin_active()
	 *
	 * @param string $plugin Base plugin path from plugins directory.
	 *
	 * @return bool True if inactive. False if active.
	 */
	public static function is_plugin_active( $plugin ) {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || self::is_plugin_active_for_network( $plugin );
	}

	/**
	 * Check whether the plugin is active for the entire network.
	 *
	 * Only plugins installed in the plugins/ folder can be active.
	 *
	 * Plugins in the mu-plugins/ folder can't be "activated," so this function will
	 * return false for those plugins.
	 *
	 * @since 3.0.0
	 *
	 * @param string $plugin Base plugin path from plugins directory.
	 *
	 * @return bool True, if active for the network, otherwise false.
	 */
	public static function is_plugin_active_for_network( $plugin ) {
		if ( ! is_multisite() ) {
			return false;
		}
		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins[ $plugin ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get thumbnail size name.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public static function get_image_size_name( $name ) {
		if ( version_compare( WC_VERSION, '3.3', '<' ) ) {
			return $name;
		} else {
			switch ( $name ) {
				case 'shop_thumbnail':
				case 'shop_catalog':
					return 'woocommerce_thumbnail';
				case 'shop_single':
					return 'woocommerce_single';
			}
		}

		return $name;
	}

	/**
	 * Remove array item by value.
	 *
	 * @param array $array
	 * @param mixed $value
	 *
	 * @return array
	 */
	public static function remove_array_item_by_value( $array, $value ) {
		if ( ( $key = array_search( $value, $array ) ) !== false ) {
			unset( $array[ $key ] );
		}

		return $array;
	}

	/**
	 * Get min price.
	 *
	 * @param WC_Product $product
	 *
	 * @return bool|string
	 */
	public static function get_min_price( $product ) {
		if ( empty( $product ) || ! $product->is_type( 'variable' ) ) {
			return false;
		}

		$prices = $product->get_variation_prices( true );

		if ( empty( $prices ) ) {
			return false;
		}

		return current( $prices['price'] );
	}

	/**
	 * Get the price string.
	 *
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public static function get_price_string( $product, $min_price ) {
		$variation_price_reg  = $product->get_variation_regular_price();
		$variation_price_sale = $product->get_variation_sale_price();
		$variation_prices     = $product->get_variation_prices( true );
		$unique_prices        = array_unique( $variation_prices['price'], SORT_NUMERIC );

		if ( count( $unique_prices ) > 1 ) {
			$price_string = sprintf( '%s: %s', __( 'From', 'iconic-was' ), wc_price( $min_price ) );
		} else {
			// We need to ensure that we show the original price when
			// a sale price is being used, but also ensure that tax
			// is correctly calculated.
			if ( $variation_price_sale && $variation_price_reg !== $variation_price_sale ) {
				$variation_price_reg  = wc_price( wc_get_price_to_display( $product, array( 'price' => $variation_price_reg ) ) );
				$variation_price_sale = wc_price( wc_get_price_to_display( $product, array( 'price' => $variation_price_sale ) ) );
				$price_string         = wc_format_sale_price( $variation_price_reg, $variation_price_sale );
			} else {
				$price_string = wc_price( $min_price );
			}
		}

		return $price_string;
	}

	/**
	 * Get filtered input.
	 *
	 * @param string $key         Key.
	 * @param string $method      Input method; either post or get.
	 * @param string $type        String|int|etc.
	 * @param string $nonce_key   Nonce key.
	 * @param string $nonce_value Nonce Value.
	 *
	 * @return array|int|mixed|string
	 */
	public static function get_filtered_input( $key, $method = 'post', $type = 'string', $nonce_key = '', $nonce_value = '' ) {
		if ( $nonce_key ) {
			check_admin_referer( $nonce_key, $nonce_value );
		}

		if ( 'post' === strtolower( $method ) ) {
			if ( $key && ! isset( $_POST[ $key ] ) && 'all' !== $type ) {
				return false;
			} else {
				$input_data = $_POST;
			}
		} elseif ( 'get' === strtolower( $method ) ) {
			if ( $key && ! isset( $_GET[ $key ] ) && 'all' !== $type ) {
				return false;
			} else {
				$input_data = $_GET;
			}
		} else {
			return false;
		}

		if ( ( empty( $key ) && 'all' !== $type ) || ( ! isset( $input_data[ $key ] ) && 'all' !== $type ) ) {
			return false;
		}

		if ( 'all' === $type ) {
			$input = $input_data;
		} elseif ( 'string' === $type ) {
			$input = sanitize_text_field( wp_unslash( $input_data[ $key ] ) );
		} elseif ( 'int' === $type ) {
			$input = (int) sanitize_text_field( filter_var( wp_unslash( $input_data[ $key ] ), FILTER_SANITIZE_NUMBER_INT ) );
		} elseif ( 'array' === $type ) {
			$input = $input_data[ $key ];
		} else {
			$input = filter_var( wp_unslash( $input_data[ $key ] ) );
		}

		return $input;
	}
}
