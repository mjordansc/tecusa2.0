<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $iconic_was;

if ( $iconic_was->swatches_class()->is_swatch_visual( $swatch_type ) && ! empty( $attribute['options'] ) ) { ?>
	<div class="iconic-was-swatch-options">
		<?php
		foreach ( $attribute['options'] as $option ) {
			$values = isset( $saved_values['values'][ $option['slug'] ] ) ? $saved_values['values'][ $option['slug'] ] : false;

			// swatch field
			$fields_method_name  = sprintf( 'get_%s_fields', str_replace( '-', '_', $swatch_type ) );
			$saved_values_fields = $iconic_was->swatches_class()->$fields_method_name(
				array(
					'term'           => $option['term'],
					'field_value'    => isset( $values['value'] ) ? $values['value'] : false,
					'field_name'     => sprintf( 'iconic-was[%s][values][%s][value]', $attribute['slug'], $option['slug'] ),
					'attribute_type' => 'product',
					'field_label'    => $option['name'],
				)
			);

			if ( $saved_values_fields ) {
				?>
				<div class="iconic-was-swatch-option">
					<?php foreach ( $saved_values_fields as $saved_values_field ) { ?>
						<strong class="iconic-was-swatch-option__label"><?php echo $saved_values_field['label']; ?></strong>
						<div class="iconic-was-swatch-option__field"><?php echo $saved_values_field['field']; ?></div>
					<?php } ?>
				</div>
			<?php } ?>

			<input type="hidden" name="<?php printf( 'iconic-was[%s][values][%s][label]', $attribute['slug'], $option['slug'] ); ?>" value="<?php echo $option['name']; ?>">
		<?php } ?>
	</div>
<?php } ?>