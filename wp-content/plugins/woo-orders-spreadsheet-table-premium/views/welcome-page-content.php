<?php
defined( 'ABSPATH' ) || exit;

$instance = vgse_woocommerce_orders();
?>
<p><?php _e('Thank you for installing our plugin.', $instance->textname); ?></p>

<?php
$steps = array();
if (!class_exists('WooCommerce')) {
	$steps['install_dependencies_wc'] = '<p>' . sprintf(__('Install the plugin: WooCommerce. <a href="%s" target="_blank" class="button install-plugin-trigger">Click here</a>. This is a WooCommerce extension.', $instance->textname), esc_url($this->get_plugin_install_url('woocommerce'))) . '</p>';
} else {
	$steps['open_editor'] = '<p>' . sprintf(__('You can open the Orders Spreadsheet Now:  <a href="%s" class="button">Click here</a>', $instance->textname), esc_url(VGSE()->helpers->get_editor_url(WP_Sheet_Editor_WooCommerce_Orders::get_orders_sheet_key()))) . '</p>';
}

$steps = apply_filters('vg_sheet_editor/woocommerce_orders/welcome_steps', $steps);

if (!empty($steps)) {
	echo '<ol class="steps">';
	foreach ($steps as $key => $step_content) {
		?>
		<li><?php echo wp_kses_post($step_content); ?></li>		
		<?php
	}

	echo '</ol>';
}