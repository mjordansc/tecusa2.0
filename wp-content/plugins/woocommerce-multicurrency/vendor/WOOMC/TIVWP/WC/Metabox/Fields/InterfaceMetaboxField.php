<?php
/**
 * Metabox field interface
 *
 * @since  1.9.0
 * Copyright (c) TIV.NET INC 2022.
 */

namespace WOOMC\Dependencies\TIVWP\WC\Metabox\Fields;

use WOOMC\Dependencies\TIVWP\UniMeta\AbstractUniMeta;

interface InterfaceMetaboxField {

	/**
	 * Render the field.
	 *
	 * @param array           $meta_field Meta Field definition.
	 * @param AbstractUniMeta $uni_meta   UniMeta object.
	 *
	 * @return string
	 */
	public static function render( array $meta_field, AbstractUniMeta $uni_meta ): string;
}
