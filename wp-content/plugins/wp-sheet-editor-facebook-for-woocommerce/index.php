<?php

/*
  Plugin Name: WP Sheet Editor - Facebook for WooCommerce
  Description: Add the column "FB Sync Enabled" to the products spreadsheet and trigger the synchronization done by the plugin "Facebook for WooCommerce" everytime that column is edited.
  Version: 1.0.1
  Author:      WP Sheet Editor
  Author URI:  https://wpsheeteditor.com
  Plugin URI: http://wpsheeteditor.com
 */

if (!class_exists('WPSE_FB_WC')) {

	class WPSE_FB_WC {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			if (!function_exists('facebook_for_woocommerce')) {
				return;
			}
			add_action('vg_sheet_editor/editor/register_columns', array($this, 'register_columns'));
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_columns($editor) {
			if ($editor->args['provider'] !== 'product') {
				return;
			}
			$editor->args['columns']->register_item('_wc_facebook_sync_enabled', 'product', array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => 'FB Sync Enabled',
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'save_value_callback' => array($this, 'update_fb_sync'),
				'formatted' => array(
					'type' => 'checkbox',
					'checkedTemplate' => 'yes',
					'uncheckedTemplate' => 'no',
				),
				'default_value' => 'no',
			));
		}

		function update_fb_sync($post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns) {

			update_post_meta($post_id, $cell_key, $data_to_save === 'yes' ? 'yes' : 'no');

			if ($data_to_save === 'yes') {
				\WooCommerce\Facebook\Products::enable_sync_for_products(array(wc_get_product($post_id)));
			} else {
				\WooCommerce\Facebook\Products::disable_sync_for_products(array(wc_get_product($post_id)));
			}
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPSE_FB_WC::$instance) {
				WPSE_FB_WC::$instance = new WPSE_FB_WC();
				WPSE_FB_WC::$instance->init();
			}
			return WPSE_FB_WC::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPSE_FB_WC_Obj')) {

	function WPSE_FB_WC_Obj() {
		return WPSE_FB_WC::get_instance();
	}

}
add_action('vg_sheet_editor/initialized', 'WPSE_FB_WC_Obj');