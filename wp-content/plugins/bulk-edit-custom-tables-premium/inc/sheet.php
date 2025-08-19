<?php defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPSE_Custom_Tables_Sheet' ) ) {

	class WPSE_Custom_Tables_Sheet extends WPSE_Sheet_Factory {

		var $custom_tables           = array();
		var $meta_tables             = array();
		var $predefined_meta_schemas = array();

		function __construct() {
			if ( wpsect_fs()->can_use_premium_code__premium_only() ) {
				$this->custom_tables = $this->get_custom_tables();
				parent::__construct(
					array(
						'fs_object'            => wpsect_fs(),
						'post_type'            => array( $this, 'get_custom_tables_and_labels' ),
						'register_default_taxonomy_columns' => false,
						'bootstrap_class'      => 'WPSE_Custom_Tables_Spreadsheet_Bootstrap',
						'columns'              => array( $this, 'get_columns' ),
						'sheets_list_priority' => 50,
						'allow_to_enable_individual_sheets' => true,
					)
				);
				add_filter( 'vg_sheet_editor/provider/default_provider_key', array( $this, 'set_default_provider' ), 10, 2 );
				add_filter( 'vg_sheet_editor/acf/fields', array( $this, 'deactivate_acf_fields' ), 10, 2 );
				add_filter( 'vgse_sheet_editor/provider/custom_table/meta_table_name', array( $this, 'use_different_meta_table' ), 10, 2 );
				add_filter( 'vgse_sheet_editor/provider/custom_table/meta_table_post_id_key', array( $this, 'use_different_meta_id_column' ), 10, 2 );
				add_action( 'vg_sheet_editor/editor/register_columns', array( $this, '_register_columns' ), 1 );
				add_filter( 'vg_sheet_editor/provider/custom_table/meta_value_column_key', array( $this, 'get_meta_value_column_key' ), 10, 2 );
				add_filter( 'vg_sheet_editor/provider/custom_table/meta_key_column_key', array( $this, 'get_meta_key_column_key' ), 10, 2 );
				add_filter( 'vg_sheet_editor/provider/custom_table/table_schema', array( $this, 'apply_columns_manager_formatting' ) );
				add_filter( 'vg_sheet_editor/options_page/options', array( $this, 'add_settings_page_options' ) );
				add_action( 'vg_sheet_editor/provider/custom_table/before_delete_row', array( $this, 'delete_meta_data' ), 10, 2 );
				add_filter( 'vg_sheet_editor/save_rows/new_rows_ids', array( $this, 'create_new_rows_with_full_data' ), 10, 4 );
			}
		}

		function create_new_rows_with_full_data( $new_ids, $data, $settings, $post_type ) {
			global $wpdb;
			if ( VGSE()->helpers->get_current_provider()->key !== 'custom_table' ) {
				return $new_ids;
			}
			foreach ( $data as $row_index => $item ) {
				if ( empty( $item['ID'] ) || ! VGSE()->helpers->sanitize_integer( $item['ID'] ) ) {
					$item['post_type'] = $post_type;
					$id                = VGSE()->helpers->get_current_provider()->create_item( $item );
					if ( $id && ! is_wp_error( $id ) ) {
						$new_ids[] = $id;
					} else {
						throw new Exception( $wpdb->last_error );
					}
				}
			}

			return $new_ids;
		}

		function delete_meta_data( $id, $table ) {
			global $wpdb;
			$meta_table_name = $this->_get_meta_table_name( $table );
			if ( ! $meta_table_name || ! isset( $this->meta_tables[ $meta_table_name ] ) ) {
				return;
			}

			$wpdb->delete(
				$meta_table_name,
				array(
					$this->meta_tables[ $meta_table_name ]['id'] => $id,
				)
			);
		}

		/**
		 * Add fields to options page
		 * @param array $sections
		 * @return array
		 */
		function add_settings_page_options( $sections ) {
			$sections['misc']['fields'][] = array(
				'id'    => 'custom_tables_keywords',
				'type'  => 'text',
				'title' => __( 'Custom tables : Only register spreadsheets for tables containing these keywords', 'vg_sheet_editor' ),
				'desc'  => __( 'By default, we register a spreadsheet for every table in your database, but this can be very slow if you have thousands of tables, so you can use this option to only register tables containing a prefix or keyword. You can enter multiple keywords separated with commas. ', 'vg_sheet_editor' ),
			);
			$sections['misc']['fields'][] = array(
				'id'    => 'custom_tables_whitelisted',
				'type'  => 'text',
				'title' => __( 'Custom tables : Always register spreadsheet for these custom tables', 'vg_sheet_editor' ),
				'desc'  => __( 'By default, we register a spreadsheet for every table in your database that contains the WP database prefix, tables without the WP prefix are excluded because they might belong to another website. You can enter the table names separated with commas.', 'vg_sheet_editor' ),
			);
			return $sections;
		}

		function apply_columns_manager_formatting( $schema ) {
			$post_type = $schema['table_name'];
			if ( ! in_array( $post_type, $this->post_type ) ) {
				return $schema;
			}

			foreach ( $schema['columns'] as $column_key => $column ) {
				$column_settings = WP_Sheet_Editor_Columns_Manager::get_instance()->get_column_settings( $column_key, $post_type );
				if ( empty( $column_settings ) ) {
					continue;
				}

				if ( $column_settings['field_type'] === 'text_editor' ) {
					$schema['columns'][ $column_key ]['type'] = 'safe_html';
				}
			}
			return $schema;
		}

		function get_meta_value_column_key( $key, $table_key ) {
			if ( isset( $this->predefined_meta_schemas[ $table_key ] ) ) {
				$key = $this->predefined_meta_schemas[ $table_key ]['meta_value'];
			}
			return $key;
		}

		function get_meta_key_column_key( $key, $table_key ) {
			if ( isset( $this->predefined_meta_schemas[ $table_key ] ) ) {
				$key = $this->predefined_meta_schemas[ $table_key ]['meta_key'];
			}
			return $key;
		}

		function _get_meta_table_name( $post_type ) {

			if ( isset( $this->predefined_meta_schemas[ $post_type ] ) && ! empty( $this->predefined_meta_schemas[ $post_type ]['meta_table_name'] ) ) {
				return $this->predefined_meta_schemas[ $post_type ]['meta_table_name'];
			}

			$out              = null;
			$meta_table1      = $post_type . 'meta';
			$meta_table2      = preg_replace( '/s$/', '', $post_type ) . 'meta';
			$meta_tables_keys = array_keys( $this->meta_tables );
			if ( in_array( $meta_table1, $meta_tables_keys, true ) ) {
				$out = $meta_table1;
			} elseif ( in_array( $meta_table2, $meta_tables_keys, true ) ) {
				$out = $meta_table2;
			}
			return $out;
		}

		function use_different_meta_id_column( $id_column, $post_type ) {
			if ( ! in_array( $post_type, $this->post_type ) ) {
				return $id_column;
			}

			$meta_table = $this->_get_meta_table_name( $post_type );
			if ( ! $meta_table ) {
				return $id_column;
			}

			$id_column = $this->meta_tables[ $meta_table ]['id'];
			return $id_column;
		}

		function use_different_meta_table( $table_name, $post_type ) {
			if ( ! in_array( $post_type, $this->post_type ) ) {
				return $table_name;
			}
			$meta_table = $this->_get_meta_table_name( $post_type );
			if ( $meta_table ) {
				$table_name = $meta_table;
			}
			return $table_name;
		}

		function after_full_core_init() {
			// Zero custom tables found
			if ( empty( $this->custom_tables ) ) {
				return;
			}
			parent::after_full_core_init();
			add_filter( 'vg_sheet_editor/advanced_filters/all_fields_groups', array( $this, 'add_fields_to_advanced_filters' ), 10, 2 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'filter_rows_query_post_data' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'filter_rows_query_meta' ), 10, 3 );
			add_action( 'vg_sheet_editor/editor_page/after_editor_page', array( $this, 'customize_search_form' ) );
			add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_toolbars' ) );
		}

		public function register_toolbars( $editor ) {
			if ( ! in_array( $editor->args['provider'], $this->custom_tables, true ) ) {
				return;
			}
			$post_type = $editor->args['provider'];
			// Remove "add new" toolbar so we can add our own
			$editor->args['toolbars']->remove_item( 'add_rows', 'primary', $post_type );

			if ( VGSE()->helpers->user_can_edit_post_type( $post_type ) ) {
				$editor->args['toolbars']->register_item(
					'add_row_custom_table',
					array(
						'type'                  => 'button',
						'content'               => __( 'Add new', 'vg_sheet_editor' ),
						'icon'                  => 'fa fa-plus',
						'extra_html_attributes' => 'data-remodal-target="modal-add-new-row-custom-table"',
						'footer_callback'       => array( $this, 'render_add_new_row_modal' ),
					),
					$post_type
				);
			}
		}

		function render_add_new_row_modal( $post_type ) {
			?>
			<!--Add new row modal-->
			<div class="remodal custom-table-add-new-row" data-remodal-id="modal-add-new-row-custom-table" data-remodal-options="closeOnOutsideClick: false">
		
				<div class="modal-content">
					<h3><?php esc_html_e( 'Add new rows', 'vg_sheet_editor' ); ?></h3>
					<p><?php esc_html_e( 'Some custom tables are usually configured to save rows only if all the required values are entered. So we are unable to add empty rows for you to edit. The only way to create new rows is using our import feature, this way we create the row with all the values at once.', 'vg_sheet_editor' ); ?></p>
					<p><?php esc_html_e( 'You need to create a CSV file with the columns and values (keep the ID empty in the CSV) and import the file using our plugin.', 'vg_sheet_editor' ); ?></p>
				</div>
				<br>
				<button data-remodal-action="confirm" class="remodal-confirm"><?php esc_html_e( 'OK', 'vg_sheet_editor' ); ?></button>
			</div>
			<?php
		}


		function customize_search_form( $post_type ) {
			if ( ! in_array( $post_type, $this->custom_tables, true ) ) {
				return;
			}
			?>
			<style>
				.wpse-advanced-filters-toggle {
					display: none;
				}
				.advanced-filters {
					display: block !important;
				}
			</style>
			<?php
		}

		function filter_rows_query_meta( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $settings['table_name'];
			if ( ! in_array( $post_type, $this->custom_tables, true ) ) {
				return $sql;
			}

			if ( empty( $args['wpse_original_filters'] ) || empty( $args['wpse_original_filters']['meta_query'] ) ) {
				return $sql;
			}
			$line_meta_query = wp_list_filter(
				$args['wpse_original_filters']['meta_query'],
				array(
					'source' => 'meta',
				)
			);
			if ( empty( $line_meta_query ) ) {
				return $sql;
			}
			$meta_table_name = $this->_get_meta_table_name( $post_type );
			$mq_sql          = VGSE()->helpers->get_current_provider()->get_meta_query_sql( $post_type, $meta_table_name, $line_meta_query );

			// Add left join
			$sql        = str_replace( ' as t ', ' as t ' . $mq_sql['join'], $sql );
			$meta_where = preg_replace( '/^AND /', '', trim( $mq_sql['where'] ) );

			// Add where
			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $meta_where;
			} else {
				$where = ' AND ' . $meta_where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}

		function filter_rows_query_post_data( $sql, $args, $settings ) {
			$post_type = $settings['table_name'];
			if ( ! in_array( $post_type, $this->custom_tables, true ) ) {
				return $sql;
			}

			if ( empty( $args['wpse_original_filters'] ) || empty( $args['wpse_original_filters']['meta_query'] ) ) {
				return $sql;
			}
			$table_data_filters = wp_list_filter(
				$args['wpse_original_filters']['meta_query'],
				array(
					'source' => 'post_data',
				)
			);
			if ( empty( $table_data_filters ) ) {
				return $sql;
			}

			// Replace the ID field key with the real primary key for the search
			$primary_column_key = VGSE()->helpers->get_current_provider()->get_post_data_table_id_key( $post_type );
			foreach ( $table_data_filters as $index => $table_data_filter ) {
				if ( $table_data_filter['key'] === 'ID' ) {
					$table_data_filters[ $index ]['key'] = $primary_column_key;
				}
			}

			$raw_where = WP_Sheet_Editor_Advanced_Filters::get_instance()->_build_sql_wheres_for_data_table( $table_data_filters, 't' );
			if ( empty( $raw_where ) ) {
				return $sql;
			}

			$where = implode( ' AND ', $raw_where );
			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}

		function add_fields_to_advanced_filters( $all_fields, $post_type ) {
			if ( ! in_array( $post_type, $this->custom_tables, true ) ) {
				return $all_fields;
			}

			$columns                 = VGSE()->helpers->get_current_provider()->get_arg( 'columns', $post_type );
			$all_fields['post_data'] = array_keys( $columns );
			return $all_fields;
		}

		/**
		 * Register toolbar items
		 *
		 * @param  WP_Sheet_Editor_Factory $editor
		 * @return void
		 */
		function _register_columns( $editor ) {
			$post_types    = $editor->args['enabled_post_types'];
			$custom_tables = array_intersect( $post_types, $this->custom_tables );
			if ( empty( $custom_tables ) ) {
				return;
			}

			if ( method_exists( $editor->provider, 'prefetch_tables_structure' ) ) {
				$editor->provider->prefetch_tables_structure( $custom_tables );
			}

			foreach ( $custom_tables as $post_type ) {
				$columns = $editor->provider->get_arg( 'columns', $post_type );
				if ( empty( $columns ) ) {
					continue;
				}
				$primary_column_key = $editor->provider->get_post_data_table_id_key( $post_type );
				foreach ( $columns as $column_key => $column ) {
					// Don't register the primary key column because the bootstrap
					// automatically registers the ID column
					if ( $column_key === $primary_column_key ) {
						continue;
					}
					if ( ! empty( $column['sample_values'] ) && function_exists( 'WPSE_JSON_Fields_Obj' ) ) {
						$column['sample_values'] = WPSE_JSON_Fields_Obj()->maybe_convert_json_to_array( $column['sample_values'], $column_key, $post_type );
					}
					$first_value = ! empty( $column['sample_values'] ) ? current( $column['sample_values'] ) : null;
					if ( empty( $first_value ) || ! is_array( $first_value ) ) {
						$editor->args['columns']->register_item(
							$column_key,
							$post_type,
							array(
								'data_type'           => 'post_data',
								'column_width'        => 150,
								'title'               => VGSE()->helpers->convert_key_to_label( $column_key ),
								'type'                => '',
								'supports_formulas'   => true,
								'allow_to_hide'       => true,
								'allow_to_rename'     => true,
								'allow_custom_format' => true,
							)
						);
					} elseif ( class_exists( 'WPSE_Custom_Tables_Serialized_Fields' ) ) {
						new WPSE_Custom_Tables_Serialized_Fields(
							array(
								'sample_field_key'   => $column_key,
								'sample_field'       => $first_value,
								'allowed_post_types' => array( $post_type ),
								'wpse_source'        => 'custom_table_controller',
								'column_settings'    => array(
									'allow_custom_format' => true,
								),
							)
						);
					}
				}
				$editor->args['columns']->register_item(
					'wpse_status',
					$post_type,
					array(
						'data_type'         => 'post_data', //String (post_data,post_meta|meta_data)
						'column_width'      => 150, //int (Ancho de la columna)
						'title'             => __( 'Status', vgse_custom_tables()->textname ), //String (Titulo de la columna)
						'type'              => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
						'supports_formulas' => true,
						'allow_to_hide'     => false,
						'allow_to_save'     => true,
						'allow_to_rename'   => true,
						'default_value'     => 'active',
						'formatted'         => array(
							'editor'        => 'select',
							'selectOptions' => array(
								'active',
								'delete',
							),
						),
					)
				);
			}

			if ( function_exists( 'WPSE_JSON_Fields_Obj' ) ) {
				WPSE_JSON_Fields_Obj()->save_json_keys( array() );
			}
		}

		function deactivate_acf_fields( $fields, $post_type ) {
			if ( in_array( $post_type, $this->custom_tables, true ) ) {
				$fields = array();
			}
			return $fields;
		}

		function set_default_provider( $provider_class_key, $provider ) {
			if ( in_array( $provider, $this->custom_tables, true ) ) {
				$provider_class_key = 'custom_table';
			}

			return $provider_class_key;
		}

		function get_columns() {
		}

		function _get_label( $label ) {
			$label       = VGSE()->helpers->convert_key_to_label( $label );
			$label_words = explode( ' ', $label );
			$out         = array();
			foreach ( $label_words as $word ) {
				if ( strlen( $word ) === 2 ) {
					$word = strtoupper( $word );
				}
				$out[] = $word;
			}
			return implode( ' ', $out );
		}

		function get_custom_tables_and_labels() {
			$out = array(
				'post_types' => $this->custom_tables,
				'labels'     => array(),
			);

			foreach ( $this->custom_tables as $table ) {
				$out['labels'][] = $this->_get_label( $table );
			}

			return $out;
		}

		function _get_meta_table_structure( $meta_table ) {
			global $wpdb;
			$columns = $wpdb->get_results( "SHOW COLUMNS FROM $meta_table", ARRAY_A );
			$out     = array(
				'id'         => null,
				'meta_key'   => null,
				'meta_value' => null,
			);

			foreach ( $columns as $column ) {
				if ( $column['Field'] === 'meta_key' ) {
					$out['meta_key'] = 'meta_key';
				} elseif ( $column['Field'] === 'meta_value' ) {
					$out['meta_value'] = 'meta_value';
				} elseif ( preg_match( '/_id$/', $column['Field'] ) && $column['Extra'] !== 'auto_increment' ) {
					$out['id'] = $column['Field'];
				}
			}
			return $out;
		}

		function get_custom_tables() {
			global $wpdb;
			$tables = $wpdb->get_col( 'SHOW TABLES' );

			// Remove custom table that have their own plugins
			$tables = array_diff( $tables, array( $wpdb->prefix . 'wc_orders' ) );

			$out         = array();
			$core_tables = array(
				$wpdb->prefix . 'yoast_indexable',
				$wpdb->prefix . 'yoast_indexable_hierarchy',
				$wpdb->prefix . 'yoast_migrations',
				$wpdb->prefix . 'yoast_primary_term',
				$wpdb->prefix . 'yoast_seo_links',
				$wpdb->prefix . 'yoast_seo_meta',
				$wpdb->prefix . 'woocommerce_sessions',
				$wpdb->prefix . 'woocommerce_payment_tokenmeta',
				$wpdb->prefix . 'woocommerce_payment_tokens',
				$wpdb->prefix . 'term_relationships',
				$wpdb->prefix . 'term_taxonomy',
				$wpdb->prefix . 'termmeta',
				$wpdb->prefix . 'terms',
				$wpdb->prefix . 'postmeta',
				$wpdb->prefix . 'posts',
				$wpdb->prefix . 'links',
				$wpdb->prefix . 'options',
				$wpdb->prefix . 'commentmeta',
				$wpdb->prefix . 'comments',
			);

			$this->predefined_meta_schemas = apply_filters(
				'vg_sheet_editor/custom_tables/predefined_meta_schema',
				array(
					$wpdb->prefix . 'my_warehouses' => array(
						'meta_table_name' => $wpdb->prefix . 'warehouse_meta',
						'id'              => 'warehouse_id',
						'meta_key'        => 'key',
						'meta_value'      => 'value',
					),
					$wpdb->prefix . 'bp_groups'     => array(
						'meta_table_name' => $wpdb->prefix . 'bp_groups_groupmeta',
						'id'              => 'group_id',
						'meta_key'        => 'meta_key',
						'meta_value'      => 'meta_value',
					),
					$wpdb->prefix . 'wcfm_marketplace_withdraw_request' => array(
						'meta_table_name' => $wpdb->prefix . 'wcfm_marketplace_withdraw_request_meta',
						'id'              => 'withdraw_id',
						'meta_key'        => 'key',
						'meta_value'      => 'value',
					),
				),
				$tables,
				$core_tables
			);
			$predefined_meta_tables        = $this->predefined_meta_schemas ? wp_list_pluck( $this->predefined_meta_schemas, 'meta_table_name' ) : array();

			// We can't use VGSE()->options[] because the options haven't initialized at this point
			$options            = get_option( VGSE()->options_key );
			$allowed_keywords   = empty( $options['custom_tables_keywords'] ) ? array() : array_map( 'trim', explode( ',', $options['custom_tables_keywords'] ) );
			$whitelisted_tables = empty( $options['custom_tables_whitelisted'] ) ? array() : array_map( 'trim', explode( ',', $options['custom_tables_whitelisted'] ) );
			foreach ( $tables as $table ) {
				$is_table_whitelisted = ! empty( $whitelisted_tables ) && in_array( $table, $whitelisted_tables, true );
				// Exclude tables that don't share the site prefix
				if ( strpos( $table, $wpdb->prefix ) === false && ! $is_table_whitelisted ) {
					continue;
				}
				// Excluded tables that have their own sheet editor plugins
				if ( in_array( $table, $core_tables, true ) ) {
					continue;
				}

				if ( $allowed_keywords ) {
					$disallowed_keyword = true;
					foreach ( $allowed_keywords as $allowed_keyword ) {
						if ( strpos( $table, $allowed_keyword ) !== false ) {
							$disallowed_keyword = false;
							break;
						}
					}
					if ( $disallowed_keyword ) {
						continue;
					}
				}

				if ( $predefined_meta_tables && in_array( $table, $predefined_meta_tables, true ) ) {
					$parent_table_key            = array_search( $table, $predefined_meta_tables, true );
					$this->meta_tables[ $table ] = $this->predefined_meta_schemas[ $parent_table_key ];
					continue;
				}

				// Exclude meta tables
				$without_meta = preg_replace( '/meta$/', '', $table );
				if ( $without_meta !== $table && ( in_array( $without_meta, $tables, true ) || in_array( $without_meta . 's', $tables, true ) ) ) {
					$meta_table_data = array_filter( $this->_get_meta_table_structure( $table ) );
					if ( count( $meta_table_data ) === 3 ) {
						$this->meta_tables[ $table ] = $meta_table_data;
					}
					continue;
				}
				$out[] = $table;
			}

			return array_unique( $out );
		}
	}

	$GLOBALS['wpse_custom_tables_sheet'] = new WPSE_Custom_Tables_Sheet();
}
