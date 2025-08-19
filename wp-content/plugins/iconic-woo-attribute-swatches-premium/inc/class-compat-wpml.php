<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WAS_WPML.
 *
 * @class    Iconic_WAS_WPML
 * @version  1.0.0
 * @since    1.2.1
 * @author   Iconic
 */
class Iconic_WAS_Compat_WPML {
	/**
	 * Run.
	 */
	public static function run() {
		if ( ! Iconic_WAS_Helpers::is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			return;
		}

		add_filter( 'iconic_was_swatch_data_args', array( __CLASS__, 'swatch_data_args' ), 10, 2 );
		add_filter( 'iconic_was_get_term_meta', array( __CLASS__, 'get_term_meta' ), 10, 2 );
		add_filter( 'iconic_was_get_terms', array( __CLASS__, 'get_terms' ), 10, 2 );
		add_filter( 'iconic_was_swatch_meta', array( __CLASS__, 'swatch_meta' ), 10, 2 );
		add_filter( 'iconic_was_product_specific_fees', array( __CLASS__, 'replace_fees_attributes_with_translated' ), 10, 2 );
		add_filter( 'iconic_was_get_attribute_data_product_id', array( __CLASS__, 'maybe_master_post_from_duplicate' ), 10, 1 );
		add_filter( 'iconic_was_has_fees_product_id', array( __CLASS__, 'maybe_master_post_from_duplicate' ), 10, 1 );
		add_filter( 'iconic_was_get_fees_product', array( __CLASS__, 'replace_duplicate_product_obj_with_master' ), 10, 1 );
		add_filter( 'wcml_js_lock_fields_classes', array( __CLASS__, 'add_js_lock_fields_classes' ), 10, 1 );
	}

	/**
	 * Modify product ID.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function swatch_data_args( $args ) {
		if ( empty( $args['product_id'] ) ) {
			return $args;
		}

		global $sitepress;

		$args['product_id'] = (int) wpml_object_id_filter( $args['product_id'], 'product', true, $sitepress->get_default_language() );

		return $args;
	}

	/**
	 * Modify term meta used for swatch data.
	 *
	 * @param mixed   $term_meta
	 * @param WP_Term $term
	 *
	 * @return mixed
	 */
	public static function get_term_meta( $term_meta, $term ) {
		global $iconic_was;

		return self::get_term_meta_for_default_lang( $term->term_id, $term->taxonomy, $iconic_was->attributes_class()->attribute_term_meta_name, true );
	}

	/**
	 * Modify term meta used for swatch data.
	 *
	 * @param array|false $terms
	 * @param array       $args
	 *
	 * @return mixed
	 */
	public static function get_terms( $terms, $args ) {
		return self::get_terms_for_default_lang( $args );
	}

	/**
	 * Use translated product specific attribute meta.
	 *
	 * @param $swatch_meta
	 * @param $product_id
	 *
	 * @return array
	 */
	public static function swatch_meta( $swatch_meta, $product_id ) {
		if ( empty( $swatch_meta ) ) {
			return $swatch_meta;
		}

		global $sitepress;

		$translated_product_id = (int) wpml_object_id_filter( $product_id, 'product', true, $sitepress->get_current_language() );

		foreach ( $swatch_meta as $attribute_name => $attribute_data ) {
			if ( taxonomy_exists( $attribute_name ) ) {
				$modified_attribute_meta = self::modify_attribute_meta(
					$attribute_data,
					array(
						'attribute_name' => $attribute_name,
						'product_id'     => $translated_product_id,
					)
				);
			} else {
				$modified_attribute_meta = self::modify_per_product_attribute_meta(
					$attribute_data,
					array(
						'attribute_name' => $attribute_name,
						'product_id'     => $translated_product_id,
					)
				);
			}

			$swatch_meta[ $attribute_name ] = $modified_attribute_meta;
		}

		return $swatch_meta;
	}

	/**
	 * Modify global attribute meta.
	 *
	 * @param array $attribute_data
	 * @param array $args
	 *
	 * @return array
	 */
	public static function modify_attribute_meta( $attribute_data, $args = array() ) {
		$defaults = array(
			'attribute_name' => null,
			'product_id'     => null,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( in_array( null, $args ) ) {
			return $attribute_data;
		}

		if ( empty( $attribute_data['values'] ) ) {
			return $attribute_data;
		}

		foreach ( $attribute_data['values'] as $term_slug => $term_data ) {
			$term = get_term_by( 'slug', $term_slug, $args['attribute_name'] );

			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			$attribute_data['values'][ $term->slug ]          = $term_data;
			$attribute_data['values'][ $term->slug ]['label'] = $term->name;
		}

		return $attribute_data;
	}

	/**
	 * Modify per product attribute meta.
	 *
	 * @param array $attribute_data
	 * @param array $args
	 *
	 * @return array
	 */
	public static function modify_per_product_attribute_meta( $attribute_data, $args = array() ) {
		$defaults = array(
			'attribute_name' => null,
			'product_id'     => null,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( in_array( null, $args ) ) {
			return $attribute_data;
		}

		$product                       = wc_get_product( $args['product_id'] );
		$translated_product_attributes = $product->get_meta( '_product_attributes', true );

		$stripped_attribute_name = str_replace( 'attribute_', '', $args['attribute_name'] );

		if ( ! isset( $translated_product_attributes[ $stripped_attribute_name ] ) ) {
			return $attribute_data;
		}

		if ( empty( $attribute_data['values'] ) ) {
			return $attribute_data;
		}

		$values = explode( ' | ', $translated_product_attributes[ $stripped_attribute_name ]['value'] );

		$i = 0;
		foreach ( $attribute_data['values'] as $value => $data ) {
			if ( ! isset( $values[ $i ] ) ) {
				continue;
			}

			$key           = strtolower( $values[ $i ] );
			$data['label'] = $values[ $i ];

			unset( $attribute_data['values'][ $value ] );
			$attribute_data['values'][ $key ] = $data;
			++$i;
		}

		return $attribute_data;
	}

	/**
	 * Get term for default language.
	 *
	 * @param array $args
	 *
	 * @return array|null|WP_Error|WP_Term|false
	 */
	public static function get_terms_for_default_lang( $args ) {
		global $icl_adjust_id_url_filter_off;

		if ( empty( $args ) ) {
			return false;
		}

		$orig_flag_value = $icl_adjust_id_url_filter_off;

		$icl_adjust_id_url_filter_off = true;
		$terms                        = get_terms( $args );
		$icl_adjust_id_url_filter_off = $orig_flag_value;

		return $terms;
	}

	/**
	 * Get term meta for default language.
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 * @param string $key
	 * @param bool   $single
	 *
	 * @return array|null|WP_Error|WP_Term|false
	 */
	public static function get_term_meta_for_default_lang( $term_id, $taxonomy, $key, $single = false ) {
		global $sitepress;
		global $icl_adjust_id_url_filter_off;

		$default_term_id = (int) wpml_object_id_filter( $term_id, $taxonomy, true, $sitepress->get_default_language() );

		$orig_flag_value = $icl_adjust_id_url_filter_off;

		$icl_adjust_id_url_filter_off = true;
		$term_meta                    = get_term_meta( $default_term_id, $key, $single );
		$icl_adjust_id_url_filter_off = $orig_flag_value;

		return $term_meta;
	}

	/**
	 * Replace terms in fees array with translated terms slug.
	 * Example: replace blue with blue-fr.
	 *
	 * @param array $fees       Fees to modify.
	 * @param int   $product_id Product ID.
	 *
	 * @return array
	 */
	public static function replace_fees_attributes_with_translated( $fees, $product_id ) {
		$translated_fees = array();

		$action = filter_input( INPUT_POST, 'action' );

		if ( 'woocommerce_save_attributes' === $action ) {
			return $fees;
		}

		foreach ( $fees as $taxonomy => $attributes ) {
			$translated_fees[ $taxonomy ] = array();

			foreach ( $attributes as $attribute => $fee ) {
				$original_term = get_term_by( 'slug', $attribute, $taxonomy );

				if ( empty( $original_term ) ) {
					continue;
				}

				$translated_term_id = apply_filters( 'wpml_object_id', $original_term->term_taxonomy_id, $taxonomy, true );
				$translated_term    = get_term_by( 'term_taxonomy_id', $translated_term_id, $taxonomy );

				if ( empty( $translated_term ) ) {
					continue;
				}

				$translated_fees[ $taxonomy ][ $translated_term->slug ] = $fee;
			}
		}

		return $translated_fees;
	}

	/**
	 * Return master post id if the product is duplicated, else return the same product id.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return int
	 */
	public static function maybe_master_post_from_duplicate( $product_id ) {
		$master_product_id = self::get_original_translation( $product_id );
		return empty( $master_product_id ) ? $product_id : $master_product_id;
	}

	/**
	 * If the passed product object is a translated one then return original
	 * product object.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return WC_Product
	 */
	public static function replace_duplicate_product_obj_with_master( $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $product;
		}

		$master_product_id = self::get_original_translation( $product->get_id() );
		$product_id        = empty( $master_product_id ) ? $product->get_id() : $master_product_id;

		return wc_get_product( $product_id );
	}

	/**
	 * Add JS lock fields.
	 *
	 * @param array $classes Classes.
	 *
	 * @return array
	 */
	public static function add_js_lock_fields_classes( $classes ) {

		$classes[] = 'iconic-was-fees__input';

		return $classes;
	}

	/**
	 * Get original translation of the given product ID
	 * If the given product is a translated one then return the original product ID.
	 *
	 * In WPML you can either duplicate or translate a post.
	 * This function handles both cases.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return int
	 */
	public static function get_original_translation( $product_id ) {
		static $cache = array();

		if ( ! empty( $cache[ $product_id ] ) ) {
			return $cache[ $product_id ];
		}

		// If this is a duplicated product.
		$master_product_id = apply_filters( 'wpml_master_post_from_duplicate', $product_id );
		if ( $master_product_id ) {
			$cache[ $product_id ] = $master_product_id;
			return $master_product_id;
		}

		// If this is a translated product.
		$trid = apply_filters( 'wpml_element_trid', null, $product_id, 'post_product' );

		if ( empty( $trid ) ) {
			$cache[ $product_id ] = $product_id;
			return $product_id;
		}

		$translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_product' );

		if ( empty( $translations ) ) {
			$cache[ $product_id ] = $product_id;
			return $product_id;
		}

		foreach ( $translations as $translation ) {
			if ( $translation->original ) {
				$cache[ $product_id ] = $translation->element_id;
				return $translation->element_id;
			}
		}

		$cache[ $product_id ] = $product_id;
		return $product_id;
	}
}
