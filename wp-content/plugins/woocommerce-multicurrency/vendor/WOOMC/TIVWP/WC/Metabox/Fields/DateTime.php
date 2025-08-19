<?php
/**
 * Metabox field: DateTime.
 *
 * @since  1.6.0
 * Copyright (c) TIV.NET INC 2021.
 */

namespace WOOMC\Dependencies\TIVWP\WC\Metabox\Fields;

use WOOMC\Dependencies\TIVWP\UniMeta\AbstractUniMeta;

/**
 * Class DateTime
 *
 * @since  1.6.0
 */
class DateTime implements InterfaceMetaboxField {

	/**
	 * Render the field.
	 *
	 * @since  1.6.0
	 * @since  1.9.0 Use UniMeta.
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
		<input type="datetime-local"
				id="<?php echo \esc_attr( $field_id ); ?>"
				name="<?php echo \esc_attr( $field_id ); ?>"
				value="<?php echo \esc_attr( $value ); ?>"
				aria-label="<?php \esc_attr_e( 'Date', 'woocommerce' ); ?>"
		>
		<p
				id="<?php echo \esc_attr( $field_id ); ?>_unsupported"
				class="hidden wp-ui-text-notification"><?php echo \esc_html( $meta_field['unsupported'] ); ?>
		</p>
		<?php //@formatter:off ?>
		<script>
			(function () {
				const el = document.getElementById("<?php echo \esc_attr( $field_id ); ?>");
				if (el.type !== "datetime-local") {
					el.classList.add("hidden");
					document.getElementById("<?php echo \esc_attr( $field_id ); ?>_unsupported")
						.classList.remove("hidden");
				}
			}());
		</script>
		<?php //@formatter:on ?>
		<?php
		return ob_get_clean();
	}
}
