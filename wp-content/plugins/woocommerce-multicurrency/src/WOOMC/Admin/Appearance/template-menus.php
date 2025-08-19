<?php
/**
 * Template: Admin/Appearance/Menus
 *
 * @since 2.11.0
 * @global array $items
 *
 * Copyright (c) 2021, TIV.NET INC. All Rights Reserved.
 */

$div_id    = 'posttype-multi-currency';
$object_id = -4242;

?>
<div id="<?php echo \esc_attr( $div_id ); ?>" class="posttypediv">
	<div id="tabs-panel-multi-currency" class="tabs-panel tabs-panel-active">
		<ul id="multi-currency-checklist" class="categorychecklist form-no-clear">
			<?php
			foreach ( $items as $item ) :
				?>
				<li>
					<label class="menu-item-title">
						<input type="checkbox" class="menu-item-checkbox"
								name="menu-item[<?php echo \esc_attr( $object_id ); ?>][menu-item-object-id]"
								value="<?php echo \esc_attr( $object_id ); ?>"/>
						<?php echo \esc_html( $item['menu-item-title'] ); ?>
					</label>
					<?php foreach ( $item as $key => $value ) : ?>
						<input type="hidden"
								class="<?php echo \esc_attr( $key ); ?>"
								name="menu-item[<?php echo \esc_attr( $object_id ); ?>][<?php echo \esc_attr( $key ); ?>]"
								value="<?php echo \esc_attr( $value ); ?>"/>
					<?php endforeach; ?>
				</li>
				<?php
				--$object_id;
			endforeach;
			?>
		</ul>
	</div>
	<p class="button-controls">
			<span class="add-to-menu">
					<button type="submit" class="button-secondary submit-add-to-menu right"
							value="<?php \esc_attr_e( 'Add to Menu' ); ?>" name="add-post-type-menu-item"
							id="submit-<?php echo \esc_attr( $div_id ); ?>"><?php \esc_html_e( 'Add to Menu' ); ?></button>
					<span class="spinner"></span>
			</span>
	</p>
</div>

