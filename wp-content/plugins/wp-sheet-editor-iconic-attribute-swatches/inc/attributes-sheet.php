<?php

if (!class_exists('WPSE_Attributes_Iconic_Swatches')) {

	class WPSE_Attributes_Iconic_Swatches {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {

			if (!class_exists('WPSE_WC_Attributes_Sheet')) {
				return;
			}

			add_action('vg_sheet_editor/editor/register_columns', array($this, 'register_taxonomies_columns'));
			add_action('vg_sheet_editor/editor/register_columns', array($this, 'register_term_columns'));
			add_filter('vg_sheet_editor/custom_columns/columns_detected_settings_before_cache', array($this, 'remove_private_columns'), 10, 2);
		}

		// Don't register columns for the serialized field automatically because we have registered special columns
		function remove_private_columns($columns, $post_type) {
			$global_attributes = wc_get_attribute_taxonomy_names();
			if (!in_array($post_type, $global_attributes, true)) {
				return $columns;
			}
			if (!empty($columns['serialized'])) {
				if (!empty($columns['serialized']['iconic_was_term_meta'])) {
					unset($columns['serialized']['iconic_was_term_meta']);
				}
			}
			return $columns;
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_term_columns($editor) {
			global $iconic_was;
			$global_attributes = wc_get_attribute_taxonomy_names();

			if (!array_intersect($editor->args['enabled_post_types'], $global_attributes)) {
				return;
			}

			foreach ($global_attributes as $taxonomy) {

				$swatch_type = $iconic_was->swatches_class()->get_swatch_option('swatch_type', $taxonomy);

				if ($swatch_type === 'image-swatch') {
					$editor->args['columns']->register_item('iconic_swatches_image-swatch', $taxonomy, array(
						'data_type' => 'meta_data',
						'column_width' => 170,
						'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('
Image Swatch', 'iconic-was'),
						'type' => 'boton_gallery',
						'supports_formulas' => true,
						'allow_to_hide' => true,
						'allow_to_rename' => true,
						'supports_sql_formulas' => false,
						'get_value_callback' => array($this, 'get_term_value_for_cell'),
						'save_value_callback' => array($this, 'update_term_value_for_cell'),
						'formatted' => array(
							'renderer' => 'wp_media_gallery'
						),
					));
				} elseif ($swatch_type === 'colour-swatch') {

					$editor->args['columns']->register_item('iconic_swatches_colour-swatch', $taxonomy, array(
						'data_type' => 'meta_data',
						'column_width' => 170,
						'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Colour Swatch', 'iconic-was'),
						'type' => '',
						'supports_formulas' => true,
						'allow_to_hide' => true,
						'allow_to_rename' => true,
						'supports_sql_formulas' => false,
						'get_value_callback' => array($this, 'get_term_value_for_cell'),
						'save_value_callback' => array($this, 'update_term_value_for_cell'),
					));
				}
			}
		}

		function register_taxonomies_columns($editor) {
			global $iconic_was, $wpdb;
			$post_type = $wpdb->prefix . 'woocommerce_attribute_taxonomies';

			if (!in_array($post_type, $editor->args['enabled_post_types'], true)) {
				return;
			}

			$editor->args['columns']->register_item('iconic_swatches_swatch_type', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Swatch Type', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'formatted' => array(
					'editor' => 'select',
					'selectOptions' => $iconic_was->swatches_class()->get_swatch_types(__('None', 'iconic-was'))
				),
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_swatch_shape', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Swatch Shape', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'formatted' => array(
					'editor' => 'select',
					'selectOptions' => array('round' => __('Round', 'iconic-was'), 'square' => __('Square', 'iconic-was'))
				),
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_swatch_size=width', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Swatch Size: Width (px)', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_swatch_size=height', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Swatch Size: Height (px)', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_tooltips', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Enable Tooltips?', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'formatted' => array(
					'type' => 'checkbox',
					'checkedTemplate' => '1',
					'uncheckedTemplate' => '0',
				),
				'default_value' => '0',
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_large_preview', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Show Large Preview?', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'formatted' => array(
					'type' => 'checkbox',
					'checkedTemplate' => '1',
					'uncheckedTemplate' => '0',
				),
				'default_value' => '0',
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_filters', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Show Swatch in Filters?', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'formatted' => array(
					'type' => 'checkbox',
					'checkedTemplate' => '1',
					'uncheckedTemplate' => '0',
				),
				'default_value' => '0',
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_loop', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Show Swatch in Catalog?', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'formatted' => array(
					'type' => 'checkbox',
					'checkedTemplate' => '1',
					'uncheckedTemplate' => '0',
				),
				'default_value' => '0',
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_loop-method', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Catalog Swatch Method', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'formatted' => array(
					'editor' => 'select',
					'selectOptions' => array(
						'link' => __('Link to Product', 'iconic-was'),
						'image' => __('Change Product Image', 'iconic-was'),
					),
				),
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
			$editor->args['columns']->register_item('iconic_swatches_groups', $post_type, array(
				'data_type' => 'meta_data',
				'column_width' => 170,
				'title' => __('Swatches: ', vgse_taxonomy_terms()->textname) . __('Groups', 'iconic-was'),
				'type' => '',
				'supports_formulas' => true,
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'supports_sql_formulas' => false,
				'get_value_callback' => array($this, 'get_taxonomy_value_for_cell'),
				'save_value_callback' => array($this, 'update_taxonomy_value_for_cell'),
			));
		}

		function get_term_value_for_cell($post, $cell_key, $cell_args) {
			$swatch_options = get_term_meta($post->ID, 'iconic_was_term_meta', true);
			$key = str_replace('iconic_swatches_', '', $cell_key);
			$value = '';

			if (empty($swatch_options) || !is_array($swatch_options)) {
				return $value;
			}

			$value = isset($swatch_options[$key]) ? $swatch_options[$key] : '';

			if (!empty($value) && $key === 'image-swatch') {
				$value = VGSE()->helpers->get_gallery_cell_content($post->ID, $cell_key, null, $value);
			}
			return $value;
		}

		function update_term_value_for_cell($post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns) {
			$swatch_options = get_term_meta($post_id, 'iconic_was_term_meta', true);
			$key = str_replace('iconic_swatches_', '', $cell_key);

			if (empty($swatch_options) || !is_array($swatch_options)) {
				$swatch_options = array();
			}

			if (!empty($data_to_save) && $key === 'image-swatch') {
				$file_ids = VGSE()->helpers->maybe_replace_urls_with_file_ids($data_to_save, $post_id);
				$data_to_save = (!empty($file_ids)) ? current($file_ids) : $data_to_save;
			}

			$swatch_options[$key] = $data_to_save;
			update_term_meta($post_id, 'iconic_was_term_meta', $swatch_options);
		}

		function get_taxonomy_value_for_cell($post, $cell_key, $cell_args) {
			$swatch_options = get_option('iconic_was_attribute_meta_' . $post->ID);
			$key = str_replace('iconic_swatches_', '', $cell_key);
			$value = '';

			if (empty($swatch_options) || !is_array($swatch_options)) {
				return $value;
			}

			$dot_notation = str_replace('=', '.', $key);
			$value = VGSE()->helpers->get_with_dot_notation($swatch_options, $dot_notation, $value);
			return $value;
		}

		function update_taxonomy_value_for_cell($post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns) {
			$option_key = 'iconic_was_attribute_meta_' . $post_id;
			$swatch_options = get_option($option_key);
			$key = str_replace('iconic_swatches_', '', $cell_key);

			if (empty($swatch_options) || !is_array($swatch_options)) {
				$swatch_options = array();
			}
			$dot_notation = str_replace('=', '.', $key);
			VGSE()->helpers->set_with_dot_notation($swatch_options, $dot_notation, $data_to_save);
			update_option($option_key, $swatch_options);
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPSE_Attributes_Iconic_Swatches::$instance) {
				WPSE_Attributes_Iconic_Swatches::$instance = new WPSE_Attributes_Iconic_Swatches();
				WPSE_Attributes_Iconic_Swatches::$instance->init();
			}
			return WPSE_Attributes_Iconic_Swatches::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPSE_Attributes_Iconic_Swatches_Obj')) {

	function WPSE_Attributes_Iconic_Swatches_Obj() {
		return WPSE_Attributes_Iconic_Swatches::get_instance();
	}

}
WPSE_Attributes_Iconic_Swatches_Obj();
