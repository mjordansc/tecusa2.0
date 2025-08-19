<?php defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Sheet_Editor_Custom_Columns' ) ) {

	/**
	 * This class enables the autofill cells features.
	 * Also known as fillHandle in handsontable arguments.
	 */
	class WP_Sheet_Editor_Custom_Columns {

		private static $instance                        = null;
		public $key                                     = 'vg_sheet_editor_custom_columns';
		public $found_columns                           = array();
		public $automatic_column_post_types_initialized = array();
		public $serialized_field_templates              = array();

		private function __construct() {
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new WP_Sheet_Editor_Custom_Columns();
				self::$instance->init();
			}
			return self::$instance;
		}

		public function init() {
			// Don't initialize if the columns manager is not available, we need to handle the column formats
			if ( function_exists( 'vgse_columns_manager_init' ) ) {
				add_action( 'admin_menu', array( $this, 'register_menu_page' ), 99 );
				add_action( 'vg_sheet_editor/after_enqueue_assets', array( $this, 'register_frontend_assets' ) );
				add_action( 'wp_ajax_vgse_save_columns', array( $this, 'save_columns' ) );
				// We use priority 40 to overwrite any column through the UI
				add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_columns' ), 40 );
			}

			add_action( 'wp_ajax_vgse_rename_meta_key', array( $this, 'rename_meta_key' ) );
			add_action( 'wp_ajax_vgse_delete_meta_key', array( $this, 'delete_meta_key' ) );

			add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_toolbar_items' ) );
			// CORE columns are registered automatically without the hook.
			// Here we use priority 8 to register the automatic columns early and other
			// modules/plugins will just overwrite them
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_columns_automatically' ), 8 );

			$this->maybe_migrate_from_old_option_to_new();
		}
		/**
		 * Allow letters, numbers, spaces, and ()
		 * @param string $input
		 * @deprecated Use VGSE()->helpers->convert_key_to_label( $input ) instead
		 * @return string
		 */
		public function _convert_key_to_label( $input ) {
			return VGSE()->helpers->convert_key_to_label( $input );
		}

		/**
		 * Register spreadsheet columns
		 */
		public function register_columns_automatically( $editor ) {
			$post_type = $editor->args['provider'];

			// Only do this once per post type
			if ( in_array( $post_type, $this->automatic_column_post_types_initialized ) ) {
				return;
			}

			if ( function_exists( 'WPSE_Profiler_Obj' ) ) {
				WPSE_Profiler_Obj()->record( 'Start ' . __FUNCTION__ );
			}
			$this->automatic_column_post_types_initialized[] = $post_type;
			$transient_key                                   = 'vgse_detected_fields_' . $post_type;
			$columns_detected                                = get_transient( $transient_key );

			// Clear cache on demand
			if ( method_exists( VGSE()->helpers, 'can_rescan_db_fields' ) && VGSE()->helpers->can_rescan_db_fields( $post_type ) ) {
				$columns_detected = false;
				// Increase columns limit every time we rescan
				if ( empty( $_GET['wpse_dont_increase_limit'] ) ) {
					VGSE()->update_option( 'be_columns_limit', VGSE()->helpers->get_columns_limit() + 300 );
				}
			}

			if ( empty( $columns_detected ) ) {
				// We will process meta keys limited to the columns limit+200
				// we can't limit to the columns limit because sometimes columns are blacklisted and we miss good columns
				$meta_keys = apply_filters( 'vg_sheet_editor/custom_columns/all_meta_keys', VGSE()->helpers->get_all_meta_keys( $post_type, VGSE()->helpers->get_columns_limit() + 200 ), $post_type, $editor );

				$this->found_columns[ $post_type ] = array();

				$columns_detected             = array(
					'serialized' => array(),
					'normal'     => array(),
				);
				$post_types_for_sample_values = apply_filters( 'vg_sheet_editor/custom_columns/post_type_for_sample_values', array( $post_type ), $meta_keys, $editor );
				foreach ( $meta_keys as $meta_key ) {
					// Fields with numbers as keys are not compatible because PHP
					// messes up the number indexes when arrays are merged
					if ( is_numeric( $meta_key ) ) {
						continue;
					}

					$blacklisted = $editor->args['columns']->is_column_blacklisted( $meta_key, $post_type );
					if ( $blacklisted ) {
						$editor->args['columns']->add_rejection( $meta_key, 'blacklisted_by_pattern : ' . $blacklisted, $post_type );
						continue;
					}

					$label                                       = VGSE()->helpers->convert_key_to_label( $meta_key );
					$this->found_columns[ $post_type ][ $label ] = $meta_key;

					$detected_type = $this->detect_column_type( $meta_key, $editor, $post_types_for_sample_values );

					if ( empty( $detected_type ) ) {
						continue;
					}
					if ( $detected_type['type'] === 'serialized' ) {
						$columns_detected['serialized'][ $meta_key ] = array(
							'sample_field_key'    => $meta_key,
							'sample_field'        => $detected_type['sample_field'],
							'column_width'        => 175,
							'column_title_prefix' => $label, // to remove the field key from the column title
							'level'               => ( $detected_type['is_single_level'] ) ? 3 : count( $detected_type['sample_field'] ),
							'allowed_post_types'  => array( $post_type ),
							'is_single_level'     => $detected_type['is_single_level'],
							'allow_in_wc_product_variations' => false,
							'wpse_source'         => 'custom_columns',
							'column_settings'     => array(
								'allow_custom_format' => true,
							),
							'detected_type'       => $detected_type,
						);
					} elseif ( $detected_type['type'] === 'infinite_serialized' ) {
						$columns_detected['infinite_serialized'][ $meta_key ] = array_merge(
							$detected_type,
							array(
								'sample_field_key'   => $meta_key,
								'allowed_post_types' => array( $post_type ),
								'allow_in_wc_product_variations' => false,
								'wpse_source'        => 'custom_columns',
								'column_settings'    => array(
									'allow_custom_format' => true,
								),
								'detected_type'      => $detected_type,
							)
						);
					} else {
						if ( $editor->args['columns']->has_item( $meta_key, $post_type ) ) {
							continue;
						}
						$column_settings = array(
							'data_type'           => 'meta_data',
							'unformatted'         => array( 'data' => $meta_key ),
							'title'               => $label,
							'type'                => '',
							'supports_formulas'   => true,
							'formatted'           => array( 'data' => $meta_key ),
							'allow_to_hide'       => true,
							'allow_to_rename'     => true,
							'allow_to_save'       => true,
							'allow_custom_format' => true,
							'detected_type'       => $detected_type,
						);
						if ( $detected_type['type'] === 'checkbox' ) {
							$column_settings['formatted']['type']              = 'checkbox';
							$column_settings['formatted']['checkedTemplate']   = $detected_type['positive_value'];
							$column_settings['formatted']['uncheckedTemplate'] = $detected_type['negative_value'];
							$column_settings['default_value']                  = $detected_type['negative_value'];
						}

						$columns_detected['normal'][ $meta_key ] = $column_settings;
					}
				}

				$columns_detected = apply_filters( 'vg_sheet_editor/custom_columns/columns_detected_settings_before_cache', $columns_detected, $post_type );
				$total_rows       = (int) $editor->provider->get_total( $post_type );
				// If the spreadsheet has < 200 rows in total, we refresh the automatic columns more often
				$cache_expiration = VGSE()->helpers->columns_cache_expiration( $total_rows );
				set_transient( $transient_key, $columns_detected, $cache_expiration );
				update_option( $transient_key . '_updated', current_time( 'timestamp' ) );
			}
			$columns_detected = apply_filters( 'vg_sheet_editor/custom_columns/columns_detected_settings', $columns_detected, $post_type );
			if ( function_exists( 'WPSE_Profiler_Obj' ) ) {
				WPSE_Profiler_Obj()->record( 'Before registering serialized field ' . __FUNCTION__ );
			}
			if ( ! empty( $columns_detected['normal'] ) ) {
				foreach ( $columns_detected['normal'] as $column_key => $column_settings ) {
					$editor->args['columns']->register_item( $column_key, $post_type, $column_settings );
				}
			}
			if ( ! empty( $columns_detected['serialized'] ) && method_exists( $editor->args['columns'], 'columns_limit_reached' ) && ! $editor->args['columns']->columns_limit_reached( $post_type ) ) {
				foreach ( $columns_detected['serialized'] as $column_key => $column_settings ) {
					new WP_Sheet_Editor_Serialized_Field( $column_settings );
				}
			} else {
				foreach ( $columns_detected['serialized'] as $column_key => $column_settings ) {
					$editor->args['columns']->add_rejection( $column_key, 'columns_limit_reached', $post_type );
				}
			}
			if ( ! empty( $columns_detected['infinite_serialized'] ) && method_exists( $editor->args['columns'], 'columns_limit_reached' ) && ! $editor->args['columns']->columns_limit_reached( $post_type ) ) {
				foreach ( $columns_detected['infinite_serialized'] as $column_key => $column_settings ) {
					new WP_Sheet_Editor_Infinite_Serialized_Field( $column_settings );
				}
			} else {
				foreach ( $columns_detected['serialized'] as $column_key => $column_settings ) {
					$editor->args['columns']->add_rejection( $column_key, 'columns_limit_reached', $post_type );
				}
			}

			if ( function_exists( 'WPSE_Profiler_Obj' ) ) {
				WPSE_Profiler_Obj()->record( 'End ' . __FUNCTION__ );
			}
		}

		public function _is_not_object( $value ) {
			return ! is_object( $value );
		}

		public function detect_column_type( $meta_key, $editor, $post_types_for_sample_values = array() ) {
			$values = array();
			// If we have multiple post types to check, we'll use the sample values from the first post type that has values
			foreach ( $post_types_for_sample_values as $post_type ) {
				$post_type_values = array_map( 'maybe_unserialize', $editor->provider->get_meta_field_unique_values( $meta_key, $post_type ) );
				$non_empty_values = VGSE()->helpers->array_remove_empty( $post_type_values );
				if ( $post_type === 'product' && empty( $non_empty_values ) ) {
					$post_type_values = array_map( 'maybe_unserialize', $editor->provider->get_meta_field_unique_values( $meta_key, 'product_variation' ) );
				}
				if ( ! empty( $post_type_values ) ) {
					$values = $post_type_values;
					break;
				}
			}
			$values_without_objects = array_filter( $values, array( $this, '_is_not_object' ) );

			// Don't register columns that have objects as values
			if ( count( $values ) > count( $values_without_objects ) ) {
				return false;
			}

			$out                                = array(
				'type'           => 'text',
				'positive_value' => '',
				'negative_value' => '',
				'sample_values'  => $values,
			);
			$positive_values                    = array();
			$negative_values                    = array();
			$forced_infinite_serialized_handler = isset( VGSE()->options['keys_for_infinite_serialized_handler'] ) ? array_map( 'trim', explode( ',', VGSE()->options['keys_for_infinite_serialized_handler'] ) ) : array();

			if ( ! empty( VGSE()->options['serialized_field_post_templates'] ) && empty( $this->serialized_field_templates ) ) {
				$serialized_fields = array_map( 'trim', explode( ',', VGSE()->options['serialized_field_post_templates'] ) );
				foreach ( $serialized_fields as $serialized_field ) {
					$template_parts = array_map( 'trim', explode( ':', $serialized_field ) );
					if ( count( $template_parts ) !== 2 ) {
						continue;
					}

					$this->serialized_field_templates[ current( $template_parts ) ] = (int) end( $template_parts );
				}
			}
			$serialized_field_templates = $this->serialized_field_templates;
			if ( isset( $serialized_field_templates[ $meta_key ] ) ) {
				$template_serialized_value = $editor->provider->get_item_meta( $serialized_field_templates[ $meta_key ], $meta_key, true, 'read' );
			}
			foreach ( $values as $value_index => $value ) {

				if ( is_array( $value ) ) {
					if ( ! empty( VGSE()->options['be_disable_serialized_columns'] ) || ! apply_filters( 'vg_sheet_editor/serialized_addon/is_enabled', true ) ) {
						continue;
					}
					$array_level = VGSE()->helpers->array_depth( $value );
					if ( ! empty( $value ) ) {

						if ( ! empty( $template_serialized_value ) ) {
							$value = $template_serialized_value;
						} else {

							// If we have multiple array samples, merge 4 samples so we have
							// a more complete array sample that probably includes all the possible subfields
							if ( $array_level > 1 && count( $values ) > 1 ) {
								if ( isset( $values[2] ) && isset( $values[3] ) && is_array( $values[2] ) && is_array( $values[3] ) ) {
									$values[2] = array_merge( $values[3], $values[2] );
								}
								if ( isset( $values[1] ) && isset( $values[2] ) && is_array( $values[1] ) && is_array( $values[2] ) ) {
									$values[1] = array_merge( $values[2], $values[1] );
								}
								if ( isset( $values[1] ) && is_array( $values[1] ) ) {
									$value = array_merge( $values[1], $value );
								}
							}
						}

						if ( $array_level < 3 && VGSE()->helpers->array_depth_uniform( $value ) && ! in_array( $meta_key, $forced_infinite_serialized_handler, true ) ) {
							$out['type']            = 'serialized';
							$out['is_single_level'] = $array_level === 1;
							if ( $array_level === 1 ) {
								$out['sample_field'] = ( is_numeric( implode( '', array_keys( $value ) ) ) ) ? array( '' ) : array_fill_keys( array_keys( $value ), '' );
							} else {
								$out['sample_field'] = array();
								foreach ( $value as $row ) {
									if ( is_array( $row ) ) {
										$out['sample_field'][] = array_fill_keys( array_keys( $row ), '' );
									}
								}
							}
						} else {
							$out['type']                = 'infinite_serialized';
							$out['serialization_level'] = $array_level;
							$out['sample_field']        = $value;
						}
						break;
					} else {
						$out = array();
					}
				} else {
					if ( in_array( $value, array( '1', 1, 'yes', true, 'true', 'on' ), true ) ) {
						$positive_values[] = $value;
					} elseif ( in_array( $value, array( '0', 0, 'no', false, 'false', null, 'null', 'off', '' ), true ) ) {
						$negative_values[] = (string) $value;
					}
				}
			}

			if ( count( $positive_values ) === 1 ) {
				$out['type']           = 'checkbox';
				$out['positive_value'] = current( $positive_values );
				$out['negative_value'] = ( empty( $negative_values ) ) ? '0' : current( $negative_values );
			}
			return apply_filters( 'vg_sheet_editor/custom_columns/column_type', $out, $meta_key, $editor );
		}

		/**
		 * @deprecated Use VGSE()->helpers->array_depth_uniform instead
		 */
		public function _array_depth_uniform( array $array ) {
			return VGSE()->helpers->array_depth_uniform( $array );
		}

		/**
		 * @deprecated Use VGSE()->helpers->array_depth instead
		 */
		public function _array_depth( array $array ) {
			return VGSE()->helpers->array_depth( $array );
		}

		/**
		 * Register toolbar item
		 */
		public function register_toolbar_items( $editor ) {

			if ( ! VGSE()->helpers->user_can_manage_options() ) {
				return;
			}
			$post_types = $editor->args['enabled_post_types'];
			foreach ( $post_types as $post_type ) {
				$editor->args['toolbars']->register_item(
					'add_columns',
					array(
						'type'              => 'button',
						'content'           => __( 'Add columns for custom fields', 'vg_sheet_editor' ),
						'icon'              => 'fa fa-plus',
						'url'               => admin_url( 'admin.php?page=' . $this->key ),
						'toolbar_key'       => 'secondary',
						'allow_in_frontend' => false,
						'parent'            => 'settings',
					),
					$post_type
				);
			}
		}

		public function maybe_migrate_from_old_option_to_new() {
			if ( ! VGSE()->helpers->user_can_manage_options() ) {
				return;
			}
			$old_columns = get_option( $this->key );
			if ( empty( $old_columns ) ) {
				return;
			}

			$new_columns = array();
			foreach ( $old_columns as $column_settings ) {
				if ( ! is_array( $column_settings['post_types'] ) ) {
					$column_settings['post_types'] = array( $column_settings['post_types'] );
				}
				$new_column = array(
					'provider'     => $column_settings['post_types'],
					'key'          => $column_settings['key'],
					'title'        => $column_settings['name'],
					'is_read_only' => $column_settings['read_only'] === 'yes',
					'data_type'    => $column_settings['data_source'],
					'field_type'   => 'text',
				);
				if ( $column_settings['cell_type'] === 'boton_gallery' || $column_settings['cell_type'] === 'boton_gallery_multiple' ) {
					$new_column['field_type']           = 'file_upload';
					$new_column['allow_multiple_files'] = $column_settings['cell_type'] === 'boton_gallery_multiple' ? 'yes' : '';
					$new_column['file_saved_format']    = 'id';
				}
				$new_columns[ $column_settings['key'] ] = $new_column;
			}

			$existing_columns = get_option( 'vgse_custom_columns_new' );
			if ( ! empty( $existing_columns ) ) {
				$existing_keys = wp_list_pluck( $existing_columns, 'title', 'key' );
				$new_columns   = array_diff_key( $new_columns, $existing_keys );
				$new_columns   = array_merge( $existing_columns, array_values( $new_columns ) );
			}

			update_option( 'vgse_custom_columns_new', array_values( $new_columns ), false );
			delete_option( $this->key );
		}

		public function register_columns( $editor ) {
			$columns = get_option( 'vgse_custom_columns_new', array() );

			if ( empty( $columns ) ) {
				return;
			}

			foreach ( $columns as $column_index => $column_settings ) {
				$matching_post_types = array_intersect( $column_settings['provider'], $editor->args['enabled_post_types'] );
				if ( ! $matching_post_types ) {
					continue;
				}
				$column_settings = array_merge( $this->get_default_column_values(), $column_settings );
				foreach ( $matching_post_types as $post_type ) {
					$column_args = array(
						'data_type'           => $column_settings['data_type'],
						'title'               => $column_settings['title'],
						'type'                => '',
						'supports_formulas'   => empty( $column_settings['is_read_only'] ),
						'allow_to_hide'       => true,
						'allow_to_save'       => true,
						'allow_to_rename'     => true,
						'skip_columns_limit'  => true,
						'skip_blacklist'      => true,
						'allow_custom_format' => true,
					);

					$editor->args['columns']->register_item( $column_settings['key'], $post_type, $column_args );
				}
			}
		}

		public function make_columns_visible( $columns ) {
			if ( ! class_exists( 'WP_Sheet_Editor_Columns_Visibility' ) ) {
				return;
			}
			$columns_visibility = WP_Sheet_Editor_Columns_Visibility::get_instance();
			$columns_visibility->change_columns_status( $columns );
		}

		public function delete_meta_key() {
			if ( empty( $_POST['post_type'] ) || empty( $_POST['column_key'] ) ) {
				wp_send_json_error( __( 'Missing post type or column_key' ) );
			}
			if ( ! VGSE()->helpers->verify_nonce_from_request() || ! VGSE()->helpers->user_can_manage_options() ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to do this action. Please reload the page or log in again.', 'vg_sheet_editor' ) ) );
			}

			$post_type = VGSE()->helpers->sanitize_table_key( $_POST['post_type'] );

			if ( is_string( $_POST['column_key'] ) ) {
				$column_keys = array( sanitize_text_field( $_POST['column_key'] ) );
			} else {
				$column_keys = array_map( 'sanitize_text_field', $_POST['column_key'] );
			}
			foreach ( $column_keys as $column_key ) {
				$result = VGSE()->helpers->get_current_provider()->delete_meta_key( $column_key, $post_type );
			}

			wp_send_json_success(
				array(
					'message' => __( 'The meta field was deleted successfully', 'vg_sheet_editor' ),
				)
			);
		}

		public function rename_meta_key() {

			if ( empty( $_POST['post_type'] ) || empty( $_POST['old_column_key'] ) || empty( $_POST['new_column_key'] ) || $_POST['old_column_key'] === $_POST['new_column_key'] ) {
				wp_send_json_error( __( 'Missing post type, old_column_key, or new_column_key; or the old and new key are the same.' ) );
			}
			if ( ! VGSE()->helpers->verify_nonce_from_request() || ! VGSE()->helpers->user_can_manage_options() ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to do this action. Please reload the page or log in again.', 'vg_sheet_editor' ) ) );
			}

			$post_type      = VGSE()->helpers->sanitize_table_key( $_POST['post_type'] );
			$old_column_key = sanitize_text_field( $_POST['old_column_key'] );
			$new_column_key = sanitize_text_field( $_POST['new_column_key'] );
			$result         = VGSE()->helpers->get_current_provider()->rename_meta_key( $old_column_key, $new_column_key, $post_type );

			if ( is_numeric( $result ) ) {
				wp_send_json_success(
					array(
						'label'   => VGSE()->helpers->convert_key_to_label( $new_column_key ),
						'key'     => $new_column_key,
						'message' => __( 'The meta key was renamed successfully', 'vg_sheet_editor' ),
					)
				);
			} else {
				wp_send_json_error( array( 'message' => __( 'The meta key couldnt be renamed.', 'vg_sheet_editor' ) ) );
			}
		}

		public function save_columns() {
			if ( ! VGSE()->helpers->verify_nonce_from_request() || ! VGSE()->helpers->user_can_manage_options() || ! isset( $_POST['columns'] ) ) {
				wp_send_json_error( __( 'You are not allowed to do this action. Please reload the page or log in again.' ) );
			}

			if ( empty( $_POST['columns'] ) ) {
				update_option( 'vgse_custom_columns_new', array(), false );
			} else {
				if ( is_string( $_POST['columns'] ) && strpos( $_POST['columns'], '[' ) === 0 ) {
					$_POST['columns'] = json_decode( wp_unslash( $_POST['columns'] ), true );
				}
				$default_column_values     = $this->get_default_column_values();
				$columns                   = array();
				$columns_manager_settings  = array();
				$columns_manager_to_enable = array();
				foreach ( $_POST['columns'] as $dirty_column_args ) {
					if ( empty( $dirty_column_args['title'] ) || empty( $dirty_column_args['key'] ) || empty( $dirty_column_args['data_type'] ) || empty( $dirty_column_args['provider'] ) ) {
						continue;
					}
					$clean_column = array();
					foreach ( array_intersect_key( $dirty_column_args, $default_column_values ) as $key => $value ) {
						if ( is_string( $value ) && $value === '' ) {
							continue;
						}
						// use sanitize_textarea_field to preserve new lines used in the column formatting settings
						if ( is_string( $value ) ) {
							$clean_column[ $key ] = sanitize_textarea_field( $value );
						} elseif ( is_array( $value ) ) {
							$clean_column[ $key ] = array_map( 'sanitize_textarea_field', $value );
						} elseif ( is_int( $value ) || is_bool( $value ) ) {
							$clean_column[ $key ] = $value;
						}
					}

					foreach ( $clean_column['provider'] as $post_type ) {
						if ( ! isset( $columns_manager_settings[ $post_type ] ) ) {
							$columns_manager_settings[ $post_type ] = array();
						}
						$columns_manager_settings[ $post_type ][ $clean_column['key'] ] = $clean_column;
					}
					$columns[]                   = $clean_column;
					$columns_manager_to_enable[] = array(
						'status'     => 'enabled',
						'post_types' => $clean_column['provider'],
						'key'        => $clean_column['key'],
						'name'       => $clean_column['title'],
					);
				}
				foreach ( $columns_manager_settings as $post_type => $post_type_column_formats ) {
					foreach ( $post_type_column_formats as $column_index => $column_format ) {
						$post_type_column_formats[ $column_index ]['is_read_only'] = $column_format['is_read_only'] ? 'yes' : 'no';
					}
					vgse_columns_manager_init()->save_column_settings( $post_type, $post_type_column_formats );
				}
				update_option( 'vgse_custom_columns_new', $columns, false );

				$this->make_columns_visible( $columns_manager_to_enable );
			}
			wp_send_json_success( __( 'Changes saved', 'vg_sheet_editor' ) );
		}

		public function get_default_column_values() {
			$out = array_merge(
				array(
					'title'               => '',
					'key'                 => '',
					'data_type'           => 'post_data',
					'provider'            => array(),
					'is_read_only'        => false,
					'allow_custom_format' => true,
				),
				vgse_columns_manager_init()->format_column_settings( array() )
			);
			return $out;
		}

		public function register_frontend_assets() {
			wp_enqueue_script( $this->key . '-init', plugins_url( '/', __FILE__ ) . 'assets/js/init.js', array( 'jquery' ), VGSE()->version, true );

			$default_column_values = $this->get_default_column_values();
			$raw_columns           = get_option( 'vgse_custom_columns_new', array() );
			$columns               = array();
			foreach ( $raw_columns as $column ) {
				$columns[] = array_merge( $default_column_values, $column );
			}

			wp_localize_script(
				$this->key . '-init',
				$this->key,
				apply_filters(
					'vg_sheet_editor/custom_columns/js_data',
					array(
						'nonce'                 => wp_create_nonce( 'bep-nonce' ),
						'custom_columns'        => $columns,
						'default_column_values' => $default_column_values,
					)
				)
			);
			wp_enqueue_style( $this->key . '-styles', plugins_url( '/', __FILE__ ) . 'assets/css/styles.css' );
		}

		public function register_menu_page() {
			add_submenu_page( 'vg_sheet_editor_setup', __( 'Custom columns', 'vg_sheet_editor' ), __( 'Custom columns', 'vg_sheet_editor' ), 'manage_options', $this->key, array( $this, 'render_settings_page' ) );
		}

		public function render_settings_page() {
			require 'views/settings-page.php';
		}

		public function __set( $name, $value ) {
			$this->$name = $value;
		}

		public function __get( $name ) {
			return $this->$name;
		}

	}

	add_action( 'vg_sheet_editor/initialized', 'vgse_custom_columns_init' );

	function vgse_custom_columns_init() {
		return WP_Sheet_Editor_Custom_Columns::get_instance();
	}
}
