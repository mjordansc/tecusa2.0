<?php
defined( 'ABSPATH' ) || exit;

$instance = vgse_custom_tables();
?>
<p><?php _e('Thank you for installing our plugin.', $instance->textname); ?></p>

<?php
$steps = array();

$sheets = apply_filters('vg_sheet_editor/custom_tables/welcome_sheets', $GLOBALS['wpse_custom_tables_sheet']->get_prop('post_type'));

if (empty($sheets)) {
	$steps['open_editor'] = '<p>' . __('We could not find any custom tables in your database.', $instance->textname) . '</p>';
} else {
	$steps['open_editor'] = '<p>' . sprintf(__('Go to this page where you can enable the spreadsheet for every custom table that you want to use. You can repeat the process multiple times to enable each table. <a href="%s" class="button">Enable a spreadsheet</a>', $instance->textname), admin_url('admin.php?page=vg_sheet_editor_post_type_setup')) . '</p>';
}
include VGSE_DIR . '/views/free-extensions-for-welcome.php';
$steps['free_extensions'] = $free_extensions_html;

$steps = apply_filters('vg_sheet_editor/custom_tables/welcome_steps', $steps);

if (!empty($steps)) {
	echo '<ol class="steps">';
	foreach ($steps as $key => $step_content) {
		if (empty($step_content)) {
			continue;
		}
		?>
		<li><?php echo wp_kses_post($step_content); ?></li>		
		<?php
	}

	echo '</ol>';
}
?>
<p><?php _e('Our plugin provides a spreadsheet editor for every custom table found in your database. We exclude tables that don\'t contain the WP prefix, but if you want to allow those tables, you can go to our settings page and whitelist those table names.', $instance->textname); ?></p>