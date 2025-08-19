<?php
/**
 * Product tab.
 *
 * @package iconic-was
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$product_id = filter_input( INPUT_GET, 'post' );
?>

<div id="<?php echo esc_attr( $iconic_was->slug ); ?>-options" class="panel wc-metaboxes-wrapper">

	<?php $attributes = $iconic_was->attributes_class()->get_variation_attributes_for_product( $product_id ); ?>

	<?php if ( ! empty( $attributes ) ) { ?>
		<div class="wc-metaboxes">

			<?php foreach ( $attributes as $attribute ) { ?>

				<?php
				$saved_values = $this->get_product_swatch_data_for_attribute( $product_id, $attribute['slug'] );
				$swatch_type  = isset( $saved_values['swatch_type'] ) ? $saved_values['swatch_type'] : '';
				$fields       = $iconic_was->attributes_class()->get_attribute_fields(
					array(
						'attribute_slug' => $attribute['slug'],
						'product_id'     => $product_id,
					)
				);
				$sections     = array(
					'swatch_settings' => __( 'Swatches', 'iconic-was' ),
					'fees_settings'   => __( 'Fees', 'iconic-was' ),
				);
				?>
				<div class="iconic-was-attribute-wrapper-title"><?php echo esc_html( $attribute['label'] ); ?></div>
				<?php
				foreach ( $sections as $section_key => $section_label ) {
					?>
					<div data-taxonomy="<?php echo esc_attr( $attribute['slug'] ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>" class="wc-metabox closed taxonomy <?php echo esc_attr( $attribute['slug'] ); ?> iconic-was-attribute-wrapper">
						<h3 class="attribute-name iconic-was-attribute-name">
							<div class="handlediv" title="Click to toggle" aria-expanded="true"></div>
							<svg class="indent" width="16" height="8" viewBox="0 0 50 25" fill="none" xmlns="http://www.w3.org/2000/svg">
								<line x1="2" x2="2" y2="25" stroke="#CCCCCC" stroke-width="4"/>
								<line y1="23" x2="50" y2="23" stroke="#CCCCCC" stroke-width="4"/>
							</svg>
							<?php echo esc_html( $section_label ); ?>
							<?php
							if ( 'swatch_settings' === $section_key ) {
								$swatch_type_output = ( $swatch_type ) ? str_replace( '-swatch', '', $swatch_type ) : __( 'Default', 'iconic-was' );
								?>
								(<span class="iconic-was-swatch-type"><?php echo esc_html( ucwords( $swatch_type_output ) ); ?></span>)
								<?php
							}
							?>
						</h3>

						<div class="wc-metabox-content" style="display: none;">

							<table cellpadding="0" cellspacing="0" class="iconic-was-attributes">
								<tbody>
								<?php if ( $fields ) { ?>
									<?php
									foreach ( $fields as $key => $field ) {
										if ( ( 'fees_settings' === $section_key && 'attribute_fees' !== $key ) ||
											( 'swatch_settings' === $section_key && 'attribute_fees' === $key )
										) {
											continue;
										}
										?>

										<tr
											class="iconic-was-attribute-row iconic-was-attributes__<?php echo esc_attr( str_replace( '_', '-', $key ) ); ?> <?php echo esc_attr( implode( ' ', $field['class'] ) ); ?>"
											<?php if ( $field['condition'] ) { ?>
												data-condition="<?php echo is_array( $field['condition'] ) ? esc_js( wp_json_encode( $field['condition'] ) ) : esc_attr( $field['condition'] ); ?>"
												data-match="<?php echo esc_js( wp_json_encode( $field['match'] ) ); ?>"
											<?php } ?>
										>
											<?php
											if ( 'swatch_settings' === $section_key ) {
												?>
												<td><?php echo esc_html( $field['label'] ); ?></td>
												<?php
											}
											?>
											<td><?php echo $field['field']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										</tr>

									<?php } ?>
									<?php
								}

								if ( 'swatch_settings' === $section_key ) {
									?>
									<tr class="iconic-was-attributes__swatch-options">
										<td colspan="2">
											<?php include 'product-attribute-options.php'; ?>
										</td>
									</tr>
									<?php
								}
								?>
								</tbody>
							</table>

						</div>

					</div>

					<?php
				}
			}
			?>

		</div>

	<?php } else { ?>

		<div class="inline notice woocommerce-message">
			<p><?php esc_html_e( 'Before you can modify swatches, you need to add some attributes for variations on the <strong>Attributes</strong> tab. Once you\'ve saved your product, you can come back here!', 'iconic-was' ); ?></p>
		</div>

	<?php } ?>

</div>
