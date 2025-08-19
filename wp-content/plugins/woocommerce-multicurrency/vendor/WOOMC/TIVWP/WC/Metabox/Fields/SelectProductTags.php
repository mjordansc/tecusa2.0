<?php
/**
 * Metabox field: SelectProductTags.
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
class SelectProductTags implements InterfaceMetaboxField {

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

		ob_start();
		?>
		<select multiple="multiple"
			<?php \disabled( $meta_field['disabled'] ?? false ); ?>
				id="<?php echo \esc_attr( $field_id ); ?>"
				name="<?php echo \esc_attr( $field_id ); ?>[]"
				class="multiselect wc-taxonomy-term-search"
				aria-label="<?php \esc_attr_e( 'Select', 'woocommerce' ); ?>"
				style="width: 50%;"
				data-taxonomy="product_tag"
				data-sortable="true"
				data-allow_clear="true"
				data-placeholder="<?php \esc_attr_e( 'Search for a tag&hellip;', 'woocommerce' ); ?>"
				data-action="woocommerce_json_search_taxonomy_terms">
			<?php
			foreach ( $selected as $slug ) {
				$tag = \get_term_by( 'slug', $slug, 'product_tag' );
				if ( $tag ) {
					$value = $tag->slug;
					$text  = $tag->name;
					?>
					<option value="<?php echo \esc_attr( $value ); ?>" selected>
						<?php echo \esc_html( \wp_strip_all_tags( $text ) ); ?>
					</option>
					<?php
				}
			}
			?>
		</select>
		<?php
		return ob_get_clean();
	}
}
