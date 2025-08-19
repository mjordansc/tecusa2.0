<?php

/*
  Plugin Name: WP Sheet Editor - Polylang
  Description: Add columns to edit the polylang language and connect the translations on the spreadsheet.
  Version: 1.0.2
  Author:      WP Sheet Editor
  Author URI:  https://wpsheeteditor.com
  Plugin URI: http://wpsheeteditor.com
 */
if ( isset( $_GET['asdfasdfasdf'] ) ) {
	return;
}
if ( ! class_exists( 'WPSE_Polylang' ) ) {

	class WPSE_Polylang {

		private static $instance = false;

		private function __construct() {

		}

		function init() {
			if ( ! defined( 'POLYLANG_VERSION' ) || ! function_exists( 'pll_is_translated_post_type' ) ) {
				return;
			}
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_columns' ), 50 );
			add_filter( 'vg_sheet_editor/global_js_data', array( $this, 'always_use_initial_language' ) );
		}
		function always_use_initial_language( $settings ) {
			$settings['ajax_url'] = add_query_arg( 'lang', pll_current_language(), $settings['ajax_url'] );
			return $settings;
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_columns( $editor ) {

			foreach ( $editor->args['enabled_post_types'] as $post_type ) {
				$toolbars = $editor->args['toolbars'];
				if ( empty( $toolbars ) ) {
					continue;
				}
				$license_toolbar_for_post_type = $toolbars->get_item( 'wpse_license', $post_type, 'secondary' );
				$fs_id                         = (int) $license_toolbar_for_post_type['fs_id'];
				$fs                            = freemius( $fs_id );
				if ( ! $fs ) {
					continue;
				}
				$user = $fs->get_user();
				if ( ! $user ) {
					continue;
				}
				$is_post_type_allowed = post_type_exists( $post_type ) && pll_is_translated_post_type( $post_type );
				$is_taxonomy_allowed  = taxonomy_exists( $post_type ) && pll_is_translated_taxonomy( $post_type );
				if ( $is_post_type_allowed ) {
					$editor->args['columns']->register_item(
						'polylang_original_post',
						$post_type,
						array(
							'data_type'           => 'post_data',
							'column_width'        => 210,
							'title'               => __( 'Polylang: Translation of', 'vg_sheet_editor' ),
							'type'                => '',
							'supports_formulas'   => true,
							'formatted'           => array(
								'type'   => 'autocomplete',
								'source' => 'searchPostByKeyword',
							),
							'allow_to_hide'       => true,
							'allow_to_rename'     => true,
							'get_value_callback'  => array( $this, 'get_translation_of_post' ),
							'save_value_callback' => array( $this, 'update_translation_of_post' ),
						)
					);
					$editor->args['columns']->register_item(
						'language',
						$post_type,
						array(
							'data_type'         => 'post_terms',
							'column_width'      => 150,
							'title'             => __( 'Polylang: Language', 'vg_sheet_editor' ),
							'type'              => '',
							'supports_formulas' => true,
							'formatted'         => array(
								'editor'        => 'select',
								'selectOptions' => VGSE()->data_helpers->get_taxonomy_terms( 'language' ),
							),
							'allow_to_hide'     => true,
							'allow_to_rename'   => true,
						)
					);
				}
				if ( $is_taxonomy_allowed ) {
					$editor->args['columns']->register_item(
						'polylang_term_language',
						$post_type,
						array(
							'data_type'           => 'post_terms',
							'column_width'        => 150,
							'title'               => __( 'Polylang: Language', 'vg_sheet_editor' ),
							'type'                => '',
							'supports_formulas'   => true,
							'formatted'           => array(
								'editor'        => 'select',
								'selectOptions' => VGSE()->data_helpers->get_taxonomy_terms( 'language' ),
							),
							'allow_to_hide'       => true,
							'allow_to_rename'     => true,
							'get_value_callback'  => array( $this, 'get_term_language' ),
							'save_value_callback' => array( $this, 'update_term_language' ),
						)
					);
					$editor->args['columns']->register_item(
						'polylang_original_term',
						$post_type,
						array(
							'data_type'             => 'post_data',
							'column_width'          => 210,
							'title'                 => __( 'Polylang: Translation of', 'vg_sheet_editor' ),
							'type'                  => '',
							'formatted'             => array(
								'type'         => 'autocomplete',
								'source'       => 'loadTaxonomyTerms',
								'taxonomy_key' => $post_type,
							),
							'supports_formulas'     => true,
							'supports_sql_formulas' => false,
							'get_value_callback'    => array( $this, 'get_translation_of_term' ),
							'save_value_callback'   => array( $this, 'update_translation_of_term' ),
						)
					);
				}
			}
		}

		function get_term_language( $post, $cell_key, $cell_args ) {
			$value = pll_get_term_language( $post->ID, 'name' );
			return $value;
		}

		function get_translation_of_term( $post, $cell_key, $cell_args ) {
			$default_language_code = pll_default_language();
			$translations          = pll_get_term_translations( $post->ID );
			$value                 = '';
			if ( is_array( $translations ) && isset( $translations[ $default_language_code ] ) && $translations[ $default_language_code ] !== $post->ID ) {
				$term  = get_term_by( 'term_id', $translations[ $default_language_code ], $post->post_type );
				$value = html_entity_decode( $term->name );
			}
			return $value;
		}

		function get_translation_of_post( $post, $cell_key, $cell_args ) {
			global $wpdb;
			$default_language_code = pll_default_language();
			$translations          = pll_get_post_translations( $post->ID );
			$value                 = '';
			if ( is_array( $translations ) && isset( $translations[ $default_language_code ] ) && $translations[ $default_language_code ] !== $post->ID ) {
				$raw_title = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE post_type = %s AND ID = %d LIMIT 1", $post->post_type, $translations[ $default_language_code ] ) );
				$value     = html_entity_decode( $raw_title );
			}
			return $value;
		}

		function update_term_language( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			$language = get_term_by( 'name', $data_to_save, 'language' );
			if ( ! $language ) {
				return;
			}
			$language_code = $language->slug;
			if ( ! $data_to_save ) {
				$language_code = pll_default_language();
			}
			pll_set_term_language( $post_id, $language->slug );
		}

		function update_translation_of_term( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			if ( empty( $data_to_save ) ) {
				PLL()->model->term->delete_translation( $post_id );
			} else {
				$current_lang          = pll_current_language();
				$default_language_code = pll_default_language();
				PLL_WPML_API::wpml_switch_language( $default_language_code );
				$terms_saved = VGSE()->data_helpers->prepare_post_terms_for_saving( $data_to_save, $post_type, '====' );
				PLL_WPML_API::wpml_switch_language( $current_lang );

				if ( $terms_saved ) {
					$main_term_id                           = current( $terms_saved );
					$translations                           = pll_get_term_translations( $post_id );
					$translations[ $default_language_code ] = $main_term_id;
					pll_save_term_translations( $translations );
				}
			}
		}

		function update_translation_of_post( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			global $wpdb;
			if ( empty( $data_to_save ) ) {
				PLL()->model->post->delete_translation( $post_id );
			} else {
				$main_post_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_title = %s LIMIT 1", $post_type, $data_to_save ) );

				if ( $main_post_id ) {
					$translations                           = pll_get_post_translations( $post_id );
					$default_language_code                  = pll_default_language();
					$translations[ $default_language_code ] = $main_post_id;
					pll_save_post_translations( $translations );
				}
			}
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new WPSE_Polylang();
				self::$instance->init();
			}
			return self::$instance;
		}

		function __set( $name, $value ) {
			$this->$name = $value;
		}

		function __get( $name ) {
			return $this->$name;
		}

	}

}

if ( ! function_exists( 'WPSE_Polylang_Obj' ) ) {

	function WPSE_Polylang_Obj() {
		return WPSE_Polylang::get_instance();
	}
}
add_action( 'vg_sheet_editor/initialized', 'WPSE_Polylang_Obj' );