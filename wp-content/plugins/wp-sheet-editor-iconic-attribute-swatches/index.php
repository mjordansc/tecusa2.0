<?php

/*
  Plugin Name: WP Sheet Editor - IconicWP Attribute Swatches
  Description: Support for the Attribute Swatches plugin created by IconicWP
  Version: 1.0.2
  Author:      WP Sheet Editor
  Author URI:  https://wpsheeteditor.com
  Plugin URI: http://wpsheeteditor.com
 */

if (isset($_GET['jklji'])) {
	return;
}

if (!class_exists('WPSE_IconicWP_Attribute_Swatches')) {

	class WPSE_IconicWP_Attribute_Swatches {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			if (!class_exists('Iconic_Woo_Attribute_Swatches') || !class_exists('WooCommerce')) {
				return;
			}

			$inc = WP_Sheet_Editor_Helpers::get_instance()->get_files_list(__DIR__ . '/inc');
			foreach ($inc as $inc_file) {
				if (file_exists($inc_file)) {
					require_once $inc_file;
				}
			}
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPSE_IconicWP_Attribute_Swatches::$instance) {
				WPSE_IconicWP_Attribute_Swatches::$instance = new WPSE_IconicWP_Attribute_Swatches();
				WPSE_IconicWP_Attribute_Swatches::$instance->init();
			}
			return WPSE_IconicWP_Attribute_Swatches::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPSE_IconicWP_Attribute_Swatches_Obj')) {

	function WPSE_IconicWP_Attribute_Swatches_Obj() {
		return WPSE_IconicWP_Attribute_Swatches::get_instance();
	}

}
add_action('vg_sheet_editor/initialized', 'WPSE_IconicWP_Attribute_Swatches_Obj');