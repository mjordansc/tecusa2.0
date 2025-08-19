<?php

if ( ! class_exists( 'WPSE_Products_Iconic_Swatches' ) ) {

	class WPSE_Products_Iconic_Swatches {

		private static $instance = false;
		var $post_type           = 'product';

		private function __construct() {

		}

		function init() {

			if ( ! class_exists( 'WP_Sheet_Editor_WooCommerce' ) ) {
				return;
			}

			add_filter( 'vg_sheet_editor/infinite_serialized_column/column_settings', array( $this, 'filter_column_settings' ), 5, 3 );
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_columns' ) );
			add_filter( 'vg_sheet_editor/options_page/options', array( $this, 'add_settings_page_options' ) );
		}

		/**
		 * Add fields to options page
		 * @param array $sections
		 * @return array
		 */
		function add_settings_page_options( $sections ) {
			$sections['misc']['fields'][] = array(
				'id'       => 'iconic_swatches_max_product_fees_attributes',
				'type'     => 'text',
				'title'    => __( 'Iconic Swatches: Maximum attribute fees', 'vg_sheet_editor' ),
				'desc'     => __( 'We will get the latest N attributes with fees and generate columns for them. By default we only scan 20 products.', 'vg_sheet_editor' ),
				'validate' => 'numeric',
			);
			return $sections;
		}

		function _get_attribute_fees_columns() {
			global $wpdb;
			$cache_key = 'vgse_iconic_was_fees';
			$columns   = get_transient( $cache_key );
			// Clear cache on demand
			if ( method_exists( VGSE()->helpers, 'can_rescan_db_fields' ) && VGSE()->helpers->can_rescan_db_fields( $this->post_type ) ) {
				$columns = false;
			}
			if ( $columns ) {
				return $columns;
			}
			$max_rows = VGSE()->get_option( 'iconic_swatches_max_product_fees_attributes', 20 );
			$rows     = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}iconic_was_fees GROUP BY attribute,fees ORDER BY LENGTH(fees) DESC LIMIT " . (int) $max_rows, ARRAY_A );
			if ( empty( $rows ) ) {
				return;
			}

			$columns = array();
			foreach ( $rows as $row ) {
				$key = $row['attribute'];
				if ( ! isset( $columns[ $key ] ) ) {
					$columns[ $key ] = array();
				}
				$columns[ $key ] = array_merge( $columns[ $key ], maybe_unserialize( $row['fees'] ) );
			}
			set_transient( $cache_key, $columns, DAY_IN_SECONDS );
			return $columns;
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_columns( $editor ) {
			$post_type = $this->post_type;

			if ( ! in_array( $post_type, $editor->args['enabled_post_types'] ) ) {
				return;
			}
			$columns = $this->_get_attribute_fees_columns();
			if ( empty( $columns ) || ! is_array( $columns ) ) {
				return;
			}
			foreach ( $columns as $attribute_key => $fees ) {
				foreach ( $fees as $term_slug => $fee ) {
					if ( strpos( $term_slug, 'pa_' ) === 0 ) {
						$term           = get_term_by( 'slug', $term_slug, $attribute_key );
						$term_name      = $term ? $term->name : $term_slug;
						$attribute_name = wc_attribute_label( $attribute_key );
					} else {
						$term_name      = $term_slug;
						$attribute_name = $attribute_key;
					}
					$editor->args['columns']->register_item(
						'iconic-was-fee=' . $attribute_key . '=' . $term_slug,
						$post_type,
						array(
							'data_type'             => 'meta_data',
							'column_width'          => 200,
							'title'                 => sprintf( __( 'Swatches Fee : %1$s : %2$s', 'vg_sheet_editor' ), $attribute_name, $term_name ),
							'type'                  => '',
							'supports_formulas'     => true,
							'supports_sql_formulas' => false,
							'allow_to_hide'         => true,
							'allow_to_rename'       => true,
							'allow_plain_text'      => true,
							'get_value_callback'    => array( $this, 'get_fee_for_cell' ),
							'save_value_callback'   => array( $this, 'update_fee_for_cell' ),
						)
					);
				}
			}
		}

		function get_fee_for_cell( $post, $cell_key, $cell_args ) {
			global $wpdb;
			$parts = explode( '=', $cell_key );
			$value = '';
			if ( count( $parts ) !== 3 ) {
				return $value;
			}
			$attribute_key = $parts[1];
			$term_slug     = $parts[2];

			$attribute_fees = $wpdb->get_var( $wpdb->prepare( "SELECT fees FROM {$wpdb->prefix}iconic_was_fees WHERE product_id = %d AND attribute = %s", $post->ID, $attribute_key ) );

			if ( empty( $attribute_fees ) ) {
				$attribute_fees = array();
			} else {
				$attribute_fees = maybe_unserialize( $attribute_fees );
			}

			$value = isset( $attribute_fees[ $term_slug ] ) ? $attribute_fees[ $term_slug ] : '';
			return $value;
		}

		function update_fee_for_cell( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			global $wpdb;
			$parts = explode( '=', $cell_key );
			if ( count( $parts ) !== 3 ) {
				return;
			}
			$attribute_key = $parts[1];
			$term_slug     = $parts[2];

			$attribute_fees = $wpdb->get_var( $wpdb->prepare( "SELECT fees FROM {$wpdb->prefix}iconic_was_fees WHERE product_id = %d AND attribute = %s", $post_id, $attribute_key ) );

			if ( empty( $attribute_fees ) ) {
				$attribute_fees = array();
			} else {
				$attribute_fees = maybe_unserialize( $attribute_fees );
			}

			$data_to_save = floatval( $data_to_save );
			if ( empty( $data_to_save ) && isset( $attribute_fees[ $term_slug ] ) ) {
				unset( $attribute_fees[ $term_slug ] );
			} elseif ( ! empty( $data_to_save ) ) {
				$attribute_fees[ $term_slug ] = $data_to_save;
			}
			$wpdb->update(
				$wpdb->prefix . 'iconic_was_fees',
				array(
					'fees' => serialize( $attribute_fees ),
				),
				array(
					'product_id' => $post_id,
					'attribute'  => $attribute_key,
				)
			);
		}

		function filter_column_settings( $column_settings, $serialized_field, $post_type ) {
			global $iconic_was;

			if ( $post_type === $this->post_type && strpos( $column_settings['key'], '_iconic-was' ) !== false ) {
				if ( preg_match( '/(swatch_type)/', $column_settings['key'] ) ) {
					$column_settings['formatted'] = array(
						'editor'        => 'select',
						'selectOptions' => $iconic_was->swatches_class()->get_swatch_types( __( 'None', 'iconic-was' ) ),
					);
				}
				if ( preg_match( '/(swatch_shape)/', $column_settings['key'] ) ) {
					$column_settings['formatted'] = array(
						'editor'        => 'select',
						'selectOptions' => array(
							'round'  => __( 'Round', 'iconic-was' ),
							'square' => __( 'Square', 'iconic-was' ),
						),
					);
				}
				if ( preg_match( '/(tooltips|large_preview|filters|loop)$/', $column_settings['key'] ) ) {
					$column_settings['formatted']     = array(
						'type'              => 'checkbox',
						'checkedTemplate'   => '1',
						'uncheckedTemplate' => '0',
					);
					$column_settings['default_value'] = '0';
				}
				// Accept image URLs in the value columns
				if ( preg_match( '/(value)$/', $column_settings['key'] ) ) {
					$column_settings['prepare_value_for_database'] = array( $this, 'prepare_value_for_database' );
				}
				$column_settings['title'] = str_replace( array( 'Swatch ', 'Iconic Was ', ' Pa ' ), array( '', 'Swatches ', ' ' ), $column_settings['title'] );
			}
			return $column_settings;
		}

		function prepare_value_for_database( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			if ( preg_match( '/^http(s)?:\/\//', $data_to_save ) ) {
				$file_ids     = VGSE()->helpers->maybe_replace_urls_with_file_ids( $data_to_save, $post_id );
				$data_to_save = ( ! empty( $file_ids ) ) ? current( $file_ids ) : $data_to_save;
			}
			return $data_to_save;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new WPSE_Products_Iconic_Swatches();
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

if ( ! function_exists( 'WPSE_Products_Iconic_Swatches_Obj' ) ) {

	function WPSE_Products_Iconic_Swatches_Obj() {
		return WPSE_Products_Iconic_Swatches::get_instance();
	}
}
WPSE_Products_Iconic_Swatches_Obj();