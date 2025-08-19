<?php
/**
 * Metabox field: DescriptionOnly.
 *
 * @since  2.7.0
 * Copyright (c) TIV.NET INC 2024.
 */

namespace WOOMC\Dependencies\TIVWP\WC\Metabox\Fields;

use WOOMC\Dependencies\TIVWP\UniMeta\AbstractUniMeta;

/**
 * Class
 *
 * @since  2.7.0
 */
class DescriptionOnly implements InterfaceMetaboxField {

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
		return '';
	}
}
