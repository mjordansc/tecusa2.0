<?php

/*
  Plugin Name: WP Sheet Editor - IconicWP's WooThumbs
  Description: Support for the WooThumbs plugin created by IconicWP
  Version: 1.0.1
  Author:      WP Sheet Editor
  Author URI:  https://wpsheeteditor.com
  Plugin URI: http://wpsheeteditor.com
 */

if (isset($_GET['jklji'])) {
	return;
}

if (!class_exists('WPSE_IconicWP_WooThumbs')) {

	class WPSE_IconicWP_WooThumbs {

		static private $instance = false;
		var $post_type = 'product';
		var $variation_post_type = 'product_variation';

		private function __construct() {
			
		}

		function init() {
			if (!class_exists('Iconic_WooThumbs') || !class_exists('WP_Sheet_Editor_WooCommerce')) {
				return;
			}
			add_action('vg_sheet_editor/editor/register_columns', array($this, 'register_columns'), 99);
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_columns($editor) {
			$post_type = $this->post_type;

			if (!in_array($post_type, $editor->args['enabled_post_types'])) {
				return;
			}

			$editor->args['columns']->register_item('_product_image_gallery', $post_type, array(
				'allow_for_variations' => true,
					), true);
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPSE_IconicWP_WooThumbs::$instance) {
				WPSE_IconicWP_WooThumbs::$instance = new WPSE_IconicWP_WooThumbs();
				WPSE_IconicWP_WooThumbs::$instance->init();
			}
			return WPSE_IconicWP_WooThumbs::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPSE_IconicWP_WooThumbs_Obj')) {

	function WPSE_IconicWP_WooThumbs_Obj() {
		return WPSE_IconicWP_WooThumbs::get_instance();
	}

}
add_action('vg_sheet_editor/initialized', 'WPSE_IconicWP_WooThumbs_Obj');
