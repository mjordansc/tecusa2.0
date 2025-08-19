<?php
/**
 * Metabox field: SelectCountries.
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
class SelectCountries implements InterfaceMetaboxField {

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

		$selected = $uni_meta->get_meta( $field_id, true, 'edit' );
		$selected = (array) $selected;

		$countries = \WC()->countries->get_allowed_countries();
		asort( $countries );

		ob_start();
		?>
		<select multiple="multiple"
			<?php \disabled( $meta_field['disabled'] ?? false ); ?>
				id="<?php echo \esc_attr( $field_id ); ?>"
				name="<?php echo \esc_attr( $field_id ); ?>[]"
				class="wc-enhanced-select"
				aria-label="<?php \esc_attr_e( 'Country / Region', 'woocommerce' ); ?>"
				style="width:50%"
				data-sortable="true"
				data-allow_clear="true"
				data-placeholder="<?php \esc_attr_e( 'Select', 'woocommerce' ); ?>&hellip;">
			<?php
			if ( ! empty( $countries ) ) {
				foreach ( $countries as $key => $val ) {
					echo '<option value="' . \esc_attr( $key ) . '"' . \wc_selected( $key, $selected ) . '>' . \esc_html( $val ) . '</option>';
				}
			}
			?>
		</select>
		<?php
		return ob_get_clean();
	}
}
