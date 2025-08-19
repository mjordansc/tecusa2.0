<?php
/**
 * Metabox field: Hidden.
 *
 * @since  1.10.0
 * Copyright (c) TIV.NET INC 2021.
 */

namespace WOOMC\Dependencies\TIVWP\WC\Metabox\Fields;

use WOOMC\Dependencies\TIVWP\UniMeta\AbstractUniMeta;

/**
 * Class Hidden
 *
 * @since  1.10.0
 */
class Hidden implements InterfaceMetaboxField {

	/**
	 * Render the field.
	 *
	 * @since  1.10.0
	 *
	 * @param array           $meta_field Meta Field definition.
	 * @param AbstractUniMeta $uni_meta   UniMeta object.
	 *
	 * @return string
	 */
	public static function render( array $meta_field, AbstractUniMeta $uni_meta ): string {
		$field_id = $meta_field['id'];

		$default = $meta_field['default'] ?? '';

		$value = $uni_meta->get_meta( $field_id, true, 'edit', $default );

		ob_start();
		?>
		<input type="hidden"
				id="<?php echo \esc_attr( $field_id ); ?>"
				name="<?php echo \esc_attr( $field_id ); ?>"
				value="<?php echo \esc_attr( $value ); ?>"
		>
		<?php
		return ob_get_clean();
	}
}
