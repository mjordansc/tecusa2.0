<?php
/**
 * Metabox field: Number.
 *
 * @since  2.7.0
 * Copyright (c) TIV.NET INC 2024.
 */

namespace WOOMC\Dependencies\TIVWP\WC\Metabox\Fields;

use WOOMC\Dependencies\TIVWP\HTML;
use WOOMC\Dependencies\TIVWP\UniMeta\AbstractUniMeta;

/**
 * Class
 *
 * @since  2.7.0
 */
class Number implements InterfaceMetaboxField {

	/**
	 * Render the field.
	 *
	 * @since  2.7.0
	 *
	 * @param array           $meta_field Meta Field definition.
	 * @param AbstractUniMeta $uni_meta   UniMeta object.
	 *
	 * @return string
	 */
	public static function render( array $meta_field, AbstractUniMeta $uni_meta ): string {
		$field_id = $meta_field['id'];

		$default = $meta_field['default'] ?? '';
		$label   = $meta_field['label'] ?? '';

		$value = $uni_meta->get_meta( $field_id, true, 'edit', $default );

		// We need to remove these
		$attributes = array_diff_key( $meta_field, array_flip( array( 'label', 'default', 'delete_empty' ) ) );

		$attributes['type']       = 'number';
		$attributes['name']       = $attributes['id'];
		$attributes['value']      = $value;
		$attributes['aria-label'] = $label;

		foreach ( array( 'required', 'disabled' ) as $attribute ) {
			if ( $meta_field[ $attribute ] ?? false ) {
				$attributes[ $attribute ] = $attribute;
			}
		}

		return HTML::make_tag( 'input', $attributes );
	}
}
