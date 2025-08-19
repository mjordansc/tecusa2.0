<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Woocommerce Variations Table - Grid compatibility.
 *
 * Plugin by Spyros Vlachopoulos.
 *
 * @class          Iconic_WAS_Compat_Woo_Variations_Table
 * @version        1.0.0
 * @category       Class
 * @author         Iconic
 */
class Iconic_WAS_Compat_Woo_Variations_Table {
	/**
	 * Run.
	 */
	public static function run() {
		if ( ! Iconic_WAS_Helpers::is_plugin_active( 'woo-variations-table-grid/woo-variations-table.php' ) ) {
			return;
		}

		add_filter( 'vartable_dl_options', array( __CLASS__, 'attribute_labels' ), 10 );
		add_filter( 'vartable_header_attributes_join', array( __CLASS__, 'attribute_label_headers' ), 10, 3 );
	}

	/**
	 * Clean attribute labels in table.
	 *
	 * @param string $attribute_label
	 *
	 * @return string
	 */
	public static function attribute_labels( $attribute_label ) {
		return self::clean_label( $attribute_label );
	}

	/**
	 * Clean attribute value headers.
	 *
	 * @param string $attribute_value
	 * @param int    $product_id
	 * @param string $attribute_slug
	 *
	 * @return string
	 */
	public static function attribute_label_headers( $attribute_value, $product_id, $attribute_slug ) {
		return self::clean_label( $attribute_value );
	}

	/**
	 * Clean label.
	 *
	 * @param string $label
	 *
	 * @return string
	 */
	public static function clean_label( $label ) {
		return trim( strip_tags( $label ), ': ' );
	}
}
