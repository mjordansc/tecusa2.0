<?php
/**
 * Integrate with WooCommerce Product Block editor.
 *
 * @see https://github.com/woocommerce/woocommerce/tree/trunk/docs/product-editor-development
 * @package iconic-was
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;

/**
 * WooCommerce Product Block editor integration Class
 *
 * @since 1.19.0
 */
class Iconic_WAS_Product_Block_Editor {
	/**
	 * Initialize the hooks.
	 *
	 * @return void
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'woocommerce_block_template_area_product-form_after_add_block_variations', array( __CLASS__, 'add_attribute_swatches_fields' ) );
		add_action( 'woocommerce_rest_insert_product_object', array( __CLASS__, 'save_attribute_swatches_data' ), 10, 2 );

		add_filter( 'woocommerce_rest_prepare_product_object', array( __CLASS__, 'add_attribute_swatches_data_to_rest_response' ), 10, 3 );
	}

	/**
	 * Add WAS fields to the Product Block editor.
	 *
	 * The WAS fields are added within Variations group.
	 *
	 * @param BlockInterface $variations_group The variations group.
	 * @return void
	 */
	public static function add_attribute_swatches_fields( $variations_group ) {
		$section = $variations_group->add_section(
			array(
				'id'         => 'iconic-was-section',
				'order'      => 15,
				'attributes' => array(
					'title' => __( 'Attribute Swatches', 'iconic-was' ),
				),
			)
		);

		$section->add_block(
			array(
				'id'         => 'iconic-was-fields',
				'blockName'  => 'iconic-was/product-block-editor',
				'order'      => 10,
				'attributes' => array(
					'property' => 'iconic_was_attribute_swatches',
				),
			)
		);
	}

	/**
	 * Register the WAS block.
	 *
	 * This function mimics the way WooCommerce registers its blocks to the Product Block Editor.
	 *
	 * @return void
	 */
	public static function register_block() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-admin' ) { // phpcs:ignore WordPress.Security.NonceVerification
			BlockRegistry::get_instance()->register_block_type_from_metadata( ICONIC_WAS_INC_PATH . '/admin/product-block-editor/build' );
		}
	}

	/**
	 * Add WAS data to the Product Block editor REST response.
	 *
	 * @param WP_REST_Response $response The Product Block editor REST response.
	 * @param WC_Product       $product  The edited product.
	 * @param WP_REST_Request  $request  The request to retrieve the product object.
	 * @return WP_REST_Response
	 */
	public static function add_attribute_swatches_data_to_rest_response( WP_REST_Response $response, WC_Product $product, WP_REST_Request $request ) {
		if ( ! FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return $response;
		}

		if ( 'edit' !== $request->get_param( 'context' ) ) {
			return $response;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return $response;
		}

		global $iconic_was;

		add_filter(
			'iconic_was_get_attribute_data_product_id',
			/**
			 * Add product ID sent in the request.
			 */
			function ( $product_id ) use ( $product ) {
				if ( ! empty( $product_id ) ) {
					return $product_id;
				}

				return $product->get_id();
			},
			10
		);

		$attributes = $iconic_was->attributes_class()->get_variation_attributes_for_product( $product->get_id() );
		$data       = array();
		$fees       = Iconic_WAS_Fees::get_fees( $product );

		foreach ( $attributes as $key => $attribute ) {
			$saved_values = $iconic_was->products_class()->get_product_swatch_data_for_attribute( $product->get_id(), $attribute['slug'] );

			if ( 'image-swatch' === $saved_values['swatch_type'] ) {
				$saved_values['values'] = array_map(
					/**
					 * Add the image URL to `url` key.
					 */
					function ( $value ) {
						$value['value'] = (int) $value['value'];

						$url = wp_get_attachment_image_url( $value['value'] );

						if ( ! $url ) {
							return $value;
						}

						$value['url'] = $url;

						return $value;
					},
					$saved_values['values']
				);
			}

			$saved_values['fees'] = $fees[ $key ] ?? array();

			$data[ $key ]['slug']    = $attribute['slug'];
			$data[ $key ]['label']   = $attribute['label'];
			$data[ $key ]['options'] = $attribute['options'];
			$data[ $key ]['values']  = $saved_values;

			$fields = $iconic_was->attributes_class()->get_attribute_fields(
				array(
					'attribute_slug' => $attribute['slug'],
					'product_id'     => $product->get_id(),
				)
			);

			$fields =
				array_map(
					/**
					 * Prepare the options for select fields.
					 */
					function ( $field, $key ) {
						unset( $field['field'] );
						$field['field_name'] = $key;

						if ( 'select' !== $field['field_settings']['type'] ) {
							return $field;
						}

						$field['field_settings']['options'] = array_map(
							function ( $label, $value ) {
								return array(
									'label' => $label,
									'value' => $value,
								);
							},
							$field['field_settings']['options'] ?? array(),
							array_keys( $field['field_settings']['options'] ?? array() )
						);

						return $field;
					},
					$fields,
					array_keys( $fields )
				);

			$data[ $key ]['fields'] = $fields;
		}

		$response->data['iconic_was_attribute_swatches'] = $data;

		return $response;
	}

	/**
	 * Save attribute swatches field.
	 *
	 * @param WC_Product      $product The product object saved.
	 * @param WP_REST_Request $request The HTTP request.
	 * @return void
	 */
	public static function save_attribute_swatches_data( WC_Product $product, WP_REST_Request $request ) {
		if ( ! FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return;
		}

		global $iconic_was;

		$attribute_swatches = $request->get_param( 'iconic_was_attribute_swatches' );

		/**
		 * When the product attributes change in the block editor,
		 * we need to update the attribute swatches to reflect this change.
		 */
		if ( is_null( $attribute_swatches ) && ! empty( $request->get_param( 'attributes' ) ) ) {
			$attributes = $iconic_was->attributes_class()->get_variation_attributes_for_product( $product->get_id() );

			if ( ! is_array( $attributes ) ) {
				return;
			}

			$saved_values = $product->get_meta( '_iconic-was' );

			foreach ( $attributes as $attribute_key => $attribute_data ) {
				if ( empty( $saved_values[ $attribute_key ] ) ) {
					$saved_values[ $attribute_key ] = array( 'swatch_type' => '' );
					continue;
				}

				// add missing terms
				$available_options = array();
				foreach ( $attribute_data['options'] as $option ) {
					if ( empty( $option['slug'] ) ) {
						continue;
					}

					$available_options[] = $option['slug'];

					if ( empty( $saved_values[ $attribute_key ]['values'][ $option['slug'] ] ) ) {
						$saved_values[ $attribute_key ]['values'][ $option['slug'] ] = array(
							'label' => $option['name'] ?? $option['slug'],
							'value' => '',
						);

						continue;
					}
				}

				// remove unavailable terms
				foreach ( array_keys( $saved_values[ $attribute_key ]['values'] ) as $term_slug ) {
					if ( in_array( $term_slug, $available_options, true ) ) {
						continue;
					}

					unset( $saved_values[ $attribute_key ]['values'][ $term_slug ] );
				}
			}

			$product->update_meta_data( '_iconic-was', $saved_values );
			$product->save();

			return;
		}

		$product_settings = array();

		foreach ( $attribute_swatches as $attribute_slug => $attribute_data ) {
			if ( empty( $attribute_data['values']['swatch_type'] ) ) {
				$product_settings[ $attribute_slug ] = array( 'swatch_type' => '' );
			}

			$fees = $attribute_data['values']['fees'] ?? array();
			Iconic_WAS_Fees::set_fees( $product->get_id(), $attribute_slug, $fees );

			unset( $attribute_data['values']['fees'] );

			$product_settings[ $attribute_slug ] = $attribute_data['values'];
		}

		$product->update_meta_data( '_iconic-was', $product_settings );
		$product->save();
	}
}
