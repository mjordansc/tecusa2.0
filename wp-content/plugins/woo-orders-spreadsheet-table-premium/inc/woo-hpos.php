<?php

if ( ! class_exists( 'WPSE_WC_Orders_Custom_Table_Sheet' ) ) {

	class WPSE_WC_Orders_Custom_Table_Sheet extends WPSE_WooCommerce_Orders_Sheet {
		public $post_type         = null;
		public $user_id_to_emails = array();
		public $operational_data  = array();
		public $address_data      = array();

		public function __construct() {
			global $wpdb;

			if ( ! class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) || ! \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				return;
			}
			$this->post_type = $wpdb->prefix . 'wc_orders';
			WPSE_Sheet_Factory::__construct(
				array(
					'fs_object'       => wpsewco_fs(),
					'post_type'       => array( $this->post_type ),
					'post_type_label' => array( __( 'Orders', 'woocommerce' ) ),
					'bootstrap_class' => 'WPSE_Custom_Tables_Spreadsheet_Bootstrap',
					'remove_columns'  => $this->get_removed_column_keys(),
				)
			);
			$this->set_hooks();
			$this->hpos_hooks();
		}

		public function hpos_hooks() {
			add_filter( 'vg_sheet_editor/provider/default_provider_key', array( $this, 'set_default_provider' ), 10, 2 );
			add_filter( 'vg_sheet_editor/advanced_filters/all_fields_groups', array( $this, 'add_fields_to_advanced_filters' ), 10, 2 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'filter_rows_query_post_data' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'filter_rows_query_user_data' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'filter_rows_query_address_data' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'filter_rows_query_operational_data' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'filter_rows_query_by_meta' ), 10, 3 );
			add_filter( 'vgse_sheet_editor/provider/custom_table/meta_table_name', array( $this, 'use_different_meta_table' ), 10, 2 );
			add_filter( 'vgse_sheet_editor/provider/custom_table/meta_table_post_id_key', array( $this, 'use_different_meta_id_column' ), 10, 2 );
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, '_register_columns' ), 1 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'search_by_product_id_custom_tables' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'search_by_variation_id_custom_tables' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'search_by_variation_attributes_custom_tables' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'search_by_product_type_custom_table' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'search_by_product_taxonomy_custom_table' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'search_by_order_notes_custom_table' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'add_advanced_line_item_meta_search_query_custom_tables' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/get_rows_sql', array( $this, 'only_include_order_rows' ), 10, 3 );
			add_filter( 'vg_sheet_editor/provider/custom_table/delete_row_handler', array( $this, 'custom_delete_row' ), 10, 3 );
			add_filter( 'vg_sheet_editor/load_rows/preload_data', array( $this, 'preload_data' ), 10, 5 );
			add_filter( 'vgse_sheet_editor/provider/custom_table/edit_capability/' . $this->post_type, array( $this, 'change_sheet_permissions' ) );
			add_filter( 'vgse_sheet_editor/provider/custom_table/read_capability/' . $this->post_type, array( $this, 'change_sheet_permissions' ) );
			add_filter( 'vgse_sheet_editor/provider/custom_table/delete_capability/' . $this->post_type, array( $this, 'change_sheet_permissions' ) );
		}

		function change_sheet_permissions( $capability ) {
			return 'manage_woocommerce';
		}

		function preload_data( $data, $posts, $wp_query_args, $settings, $spreadsheet_columns ) {
			global $wpdb;
			if ( $wp_query_args['post_type'] !== $this->post_type ) {
				return $data;
			}
			$ids                       = wp_list_pluck( $posts, 'ID' );
			$ids                       = array_filter( array_map( 'intval', $ids ) );
			$ids_in_query_placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

			$operational_columns = wp_list_filter(
				$spreadsheet_columns,
				array(
					'wc_operational_data' => true,
				)
			);
			if ( ! empty( $operational_columns ) ) {

				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE order_id IN ($ids_in_query_placeholders)", array_merge( array( $wpdb->prefix . 'wc_order_operational_data' ), $ids ) ) );
				foreach ( $results as $result ) {
					$this->operational_data[ (int) $result->order_id ] = $result;
				}
			}
			$address_columns = wp_list_filter(
				$spreadsheet_columns,
				array(
					'wc_is_address_column' => true,
				)
			);
			if ( ! empty( $address_columns ) ) {

				if ( ! isset( $this->address_data['shipping'] ) ) {
					$this->address_data['shipping'] = array();
				}
				if ( ! isset( $this->address_data['billing'] ) ) {
					$this->address_data['billing'] = array();
				}
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE order_id IN ($ids_in_query_placeholders)", array_merge( array( $wpdb->prefix . 'wc_order_addresses' ), $ids ) ) );
				foreach ( $results as $result ) {
					$this->address_data[ $result->address_type ][ (int) $result->order_id ] = $result;
				}
			}

			return $data;
		}
		function save_order_status( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			if ( strpos( $data_to_save, 'wc-' ) === 0 ) {
				$order     = wc_get_order( $post_id );
				$wc_status = str_replace( 'wc-', '', $data_to_save );
				$order->update_status( $wc_status, '', true );
			} else {
				VGSE()->helpers->get_current_provider()->update_item_data(
					array(
						'ID'          => $post_id,
						'wpse_status' => $data_to_save,
					)
				);
			}
		}
		function save_order_user_id( $order_id, $cell_key, $user_id, $post_type, $cell_args, $spreadsheet_columns ) {
			global $wpdb;
			if ( empty( $user_id ) ) {
				return;
			}

			$customer_id = $wpdb->get_var( $wpdb->prepare( 'SELECT customer_id FROM %i WHERE user_id = %d', $wpdb->prefix . 'wc_customer_lookup', $user_id ) );
			if ( ! $customer_id ) {
				return;
			}
			$wpdb->update(
				$this->post_type,
				array(
					'customer_id' => $customer_id,
				),
				array(
					'id' => $order_id,
				)
			);
		}
		public function custom_delete_row( $handled, $id, $table_name ) {
			if ( $table_name === $this->post_type ) {
				$order = wc_get_order( $id );
				if ( $order ) {
					$temp_order_obj = (object) array( 'ID' => $id );
					if ( VGSE()->get_option( 'wc_orders_delete_user_account_on_delete_order' ) && $user_id = $this->get_order_user_id( $temp_order_obj, null, null ) ) {
						$customer = new WC_Customer( $user_id );
						if ( $customer && (int) $customer->get_order_count() < 2 ) {
							$customer->delete( true );
							wp_delete_user( $user_id );
						}
					}
					$order->delete( true );
					$handled = true;
				}
			}
			return $handled;
		}
		public function only_include_order_rows( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}

			$where = " t.type = 'shop_order' ";

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );

			return $sql;
		}
		public function add_advanced_line_item_meta_search_query_custom_tables( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}

			if ( empty( $args['wpse_original_filters'] ) || empty( $args['wpse_original_filters']['meta_query'] ) || ! is_array( $args['wpse_original_filters']['meta_query'] ) ) {
				return $sql;
			}
			$line_meta_query = WP_Sheet_Editor_Advanced_Filters::get_instance()->_parse_meta_query_args( $args['wpse_original_filters']['meta_query'], 'line_item_meta' );
			if ( empty( $line_meta_query ) ) {
				return $sql;
			}

			$query_args = array( 'meta_query' => $line_meta_query );
			$meta_query = new WP_Meta_Query();
			$meta_query->parse_query_vars( $query_args );
			$mq_sql = $meta_query->get_sql(
				'order_item',
				$wpdb->prefix . 'woocommerce_order_items',
				'order_item_id',
				null
			);

			$items_table  = "{$wpdb->prefix}woocommerce_order_items";
			$subquery_sql = "SELECT order_id FROM $items_table " . $mq_sql['join'] . " WHERE order_item_type = 'line_item'  " . $mq_sql['where'];

			$where = " t.id IN ($subquery_sql) ";

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );

			return $sql;
		}
		public function search_by_order_notes_custom_table( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}
			$wheres = array();
			if ( ! empty( $args['wpse_order_notes'] ) ) {
				$keywords = array_map( 'trim', explode( ';', $args['wpse_order_notes'] ) );
				$checks   = array();
				foreach ( $keywords as $single_keyword ) {
					$checks[] = "comment_content LIKE '%" . esc_sql( $single_keyword ) . "%' ";
				}
				$wheres[] = " t.id IN (SELECT comment_post_ID FROM $wpdb->comments WHERE comment_type = 'order_note' AND (" . implode( ' OR ', $checks ) . ') ) ';
			}
			if ( ! empty( $args['wpse_order_notes_not'] ) ) {
				$keywords = array_map( 'trim', explode( ';', $args['wpse_order_notes_not'] ) );
				$checks   = array();
				foreach ( $keywords as $single_keyword ) {
					$checks[] = "comment_content LIKE '%" . esc_sql( $single_keyword ) . "%' ";
				}
				$wheres[] = " t.id NOT IN (SELECT comment_post_ID FROM $wpdb->comments WHERE comment_type = 'order_note' AND (" . implode( ' OR ', $checks ) . ') ) ';
			}

			$where = IMPLODE( ' AND ', array_filter( $wheres ) );
			if ( empty( $where ) ) {
				return $sql;
			}
			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}
		public function search_by_product_type_custom_table( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type || empty( $args['wpse_order_product_type'] ) ) {
				return $sql;
			}

			$subquery_sql = $wpdb->prepare(
				"SELECT DISTINCT o.id
			FROM %i o
			INNER JOIN %i oi
				ON oi.order_id = o.id
			INNER JOIN %i oim
				ON oi.order_item_id = oim.order_item_id
			INNER JOIN %i tr
				ON oim.meta_value = tr.object_id
			INNER JOIN %i tt
				ON tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN %i t
				ON tt.term_id = t.term_id
			WHERE o.type = 'shop_order'
			AND oim.meta_key = '_product_id'
			AND tt.taxonomy = 'product_type'
			AND t.slug = %s",
				$this->post_type,
				$wpdb->prefix . 'woocommerce_order_items',
				$wpdb->prefix . 'woocommerce_order_itemmeta',
				$wpdb->prefix . 'term_relationships',
				$wpdb->prefix . 'term_taxonomy',
				$wpdb->prefix . 'terms',
				$args['wpse_order_product_type']
			);

			$where = " t.id IN ($subquery_sql) ";

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}
		public function search_by_product_taxonomy_custom_table( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type || empty( $args['wpse_order_product_taxonomy'] ) ) {
				return $sql;
			}

			$slugs                       = $args['wpse_order_product_taxonomy']['taxonomy_terms'];
			$slugs_in_query_placeholders = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );

			$subquery_sql = $wpdb->prepare(
				"SELECT DISTINCT o.id
			FROM %i o
			INNER JOIN %i oi
				ON oi.order_id = o.id
			INNER JOIN %i oim
				ON oi.order_item_id = oim.order_item_id
			INNER JOIN %i tr
				ON oim.meta_value = tr.object_id
			INNER JOIN %i tt
				ON tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN %i t
				ON tt.term_id = t.term_id
			WHERE o.type = 'shop_order'
			AND oim.meta_key = '_product_id'
				AND tt.taxonomy = %s
				AND t.slug IN ($slugs_in_query_placeholders)",
				array_merge(
					array(
						$this->post_type,
						$wpdb->prefix . 'woocommerce_order_items',
						$wpdb->prefix . 'woocommerce_order_itemmeta',
						$wpdb->prefix . 'term_relationships',
						$wpdb->prefix . 'term_taxonomy',
						$wpdb->prefix . 'terms',
						$args['wpse_order_product_taxonomy']['taxonomy_key'],
					),
					$slugs
				)
			);

			$where = " t.id IN ($subquery_sql) ";

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}
		public function search_by_product_id_custom_tables( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type || empty( $args['wpse_order_products'] ) ) {
				return $sql;
			}

			$product_id_sql = $this->get_sql_query_by_product( $args['wpse_order_products']['product_ids'], $args['wpse_order_products']['operator'] );
			$where          = " t.id IN ($product_id_sql) ";

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}
		public function search_by_variation_id_custom_tables( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type || empty( $args['wpse_order_variation_ids'] ) ) {
				return $sql;
			}

			$product_id_sql = $this->get_sql_query_by_product( $args['wpse_order_variation_ids'], 'OR', '_variation_id' );
			$where          = " t.id IN ($product_id_sql) ";

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}
		public function search_by_variation_attributes_custom_tables( $sql, $args, $settings ) {
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type || empty( $args['wpse_order_variation_attributes'] ) ) {
				return $sql;
			}

			$product_id_sql = $this->get_search_by_variation_attributes_sql( $args['wpse_order_variation_attributes'] );
			$where          = " t.id IN ($product_id_sql) ";

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}
		function get_address_field_value( $post, $column_key, $cell_args ) {
			global $wpdb;
			$address_type = strpos( $column_key, 'billing' ) !== false ? 'billing' : 'shipping';
			$field_key    = $cell_args['wc_order_field_key'];

			if ( isset( $this->address_data[ $address_type ] ) && isset( $this->address_data[ $address_type ][ $post->ID ] ) ) {
				$value = $this->address_data[ $address_type ][ $post->ID ]->$field_key;
				return $value;
			}
			$value = $wpdb->get_var( $wpdb->prepare( 'SELECT %i FROM %i WHERE address_type = %s AND order_id = %d', $field_key, $wpdb->prefix . 'wc_order_addresses', $address_type, $post->ID ) );
			if ( empty( $value ) ) {
				$value = '';
			}
			return $value;
		}
		function get_order_status( $post, $column_key, $cell_args ) {
			$value = VGSE()->helpers->get_current_provider()->get_item_data( $post->ID, 'status' );
			return $value;
		}
		function get_order_user_id( $post, $column_key, $cell_args ) {
			global $wpdb;
			$value = $wpdb->get_var( $wpdb->prepare( 'SELECT c.user_id FROM %i o LEFT JOIN %i c ON c.customer_id = o.customer_id WHERE o.id = %d', $this->post_type, $wpdb->prefix . 'wc_customer_lookup', $post->ID ) );
			return $value;
		}
		function save_address_field_value( $order_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			global $wpdb;
			if ( empty( $data_to_save ) ) {
				return;
			}
			$order       = wc_get_order( $order_id );
			$method_name = 'set' . $cell_key;
			if ( method_exists( $order, $method_name ) ) {
				$order->$method_name( $data_to_save );
				$order->save();
			} else {
				$result = $wpdb->update(
					$wpdb->prefix . 'wc_order_addresses',
					array(
						$cell_args['wc_order_field_key'] => sanitize_text_field( $data_to_save ),
					),
					array(
						'order_id'     => $order_id,
						'address_type' => strpos( $cell_key, 'billing' ) !== false ? 'billing' : 'shipping',
					),
					array( '%s' ),
					array( '%d', '%s' )
				);
			}

			if ( strpos( $cell_key, 'billing_email' ) !== false ) {
				$wpdb->update(
					$this->post_type,
					array(
						'billing_email' => sanitize_text_field( $data_to_save ),
					),
					array(
						'id' => $order_id,
					)
				);
			}
		}
		/**
		 * Register toolbar items
		 *
		 * @param  WP_Sheet_Editor_Factory $editor
		 * @return void
		 */
		function _register_columns( $editor ) {
			$post_type = $this->post_type;
			if ( $editor->args['provider'] !== $post_type ) {
				return;
			}

			$editor->args['columns']->register_item(
				'open_wp_editor',
				$post_type,
				array(
					'data_type'                => 'post_data',
					'title'                    => __( 'Order Details', vgse_woocommerce_orders()->textname ),
					'column_width'             => 130,
					'type'                     => 'external_button',
					'supports_formulas'        => false,
					'allow_to_save'            => false,
					'external_button_template' => admin_url( 'admin.php?page=wc-orders&action=edit&id={ID}' ),
				)
			);

			$address_fields = array(
				'first_name',
				'last_name',
				'company',
				'address_1',
				'address_2',
				'city',
				'state',
				'postcode',
				'country',
				'email',
				'phone',
			);
			$address_types  = array( 'billing', 'shipping' );
			foreach ( $address_types as $address_type ) {
				foreach ( $address_fields as $field_key ) {
					$args = array(
						'get_value_callback'   => array( $this, 'get_address_field_value' ),
						'save_value_callback'  => array( $this, 'save_address_field_value' ),
						'wc_order_field_key'   => $field_key,
						'wc_is_address_column' => true,
					);
					if ( $field_key === 'email' ) {
						$args['value_type'] = 'email';
					}
					if ( $field_key === 'country' ) {
						$args['formatted'] = array(
							'editor'        => 'select',
							'selectOptions' => WC()->countries->countries,
						);
					}
					$editor->args['columns']->register_item( '_' . $address_type . '_' . $field_key, $post_type, $args, true );
				}
			}

			$editor->args['columns']->register_item(
				'currency',
				$post_type,
				array(
					'data_type'         => 'post_data',
					'column_width'      => 150,
					'title'             => __( 'Currency', 'woocommerce' ),
					'supports_formulas' => true,
					'formatted'         => array(
						'editor'        => 'select',
						'selectOptions' => get_woocommerce_currencies(),
					),
				)
			);
			$editor->args['columns']->register_item(
				'payment_method_title',
				$post_type,
				array(
					'data_type'                  => 'post_data',
					'column_width'               => 150,
					'title'                      => __( 'Payment method', 'woocommerce' ),
					'supports_formulas'          => true,
					'formatted'                  => array(
						'editor'        => 'select',
						'selectOptions' => array_values( $this->get_payment_gateways() ),
					),
					'prepare_value_for_database' => array( $this, 'prepare_payment_method_for_database' ),
					'allow_custom_format'        => true,
				)
			);
			$editor->args['columns']->register_item(
				'_shipping_method_title',
				$post_type,
				array(
					'formatted'          => array(
						'editor'        => 'select',
						'selectOptions' => array_values( $this->get_shipping_methods() ),
					),
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_shipping_method' ),
				),
				true
			);
			$editor->args['columns']->register_item(
				'customer_id',
				$post_type,
				array(
					'data_type'                  => 'post_data',
					'column_width'               => 150,
					'title'                      => __( 'Customer', 'woocommerce' ),
					'supports_formulas'          => true,
					'prepare_value_for_database' => array( $this, 'prepare_customer_for_database' ),
					'prepare_value_for_display'  => array( $this, 'prepare_customer_for_display' ),
				)
			);
			$editor->args['columns']->register_item(
				'user_id',
				$post_type,
				array(
					'data_type'           => 'post_data',
					'column_width'        => 150,
					'title'               => __( 'User ID', 'woocommerce' ),
					'supports_formulas'   => true,
					'get_value_callback'  => array( $this, 'get_order_user_id' ),
					'save_value_callback' => array( $this, 'save_order_user_id' ),
				)
			);
			$editor->args['columns']->register_item(
				'date_created_gmt',
				$post_type,
				array(
					'data_type'                  => 'post_data',
					'column_width'               => 150,
					'title'                      => __( 'Date created', 'woocommerce' ),
					'supports_formulas'          => true,
					'value_type'                 => 'date',
					'formatted'                  => array(
						'editor'           => 'wp_datetime',
						'type'             => 'date',
						'dateFormatPhp'    => 'Y-m-d H:i:s',
						'correctFormat'    => true,
						'defaultDate'      => date( 'Y-m-d H:i:s' ),
						'datePickerConfig' => array(
							'firstDay'       => 0,
							'showWeekNumber' => true,
							'numberOfMonths' => 1,
							'yearRange'      => array( 1900, (int) date( 'Y' ) + 20 ),
						),
					),
					'prepare_value_for_database' => array( $this, 'prepare_gmt_date_for_database' ),
					'prepare_value_for_display'  => array( $this, 'prepare_gmt_date_for_display' ),
				)
			);
			$editor->args['columns']->register_item(
				'date_updated_gmt',
				$post_type,
				array(
					'data_type'                  => 'post_data',
					'column_width'               => 150,
					'title'                      => __( 'Date updated', 'woocommerce' ),
					'supports_formulas'          => true,
					'value_type'                 => 'date',
					'formatted'                  => array(
						'editor'           => 'wp_datetime',
						'type'             => 'date',
						'dateFormatPhp'    => 'Y-m-d H:i:s',
						'correctFormat'    => true,
						'defaultDate'      => date( 'Y-m-d H:i:s' ),
						'datePickerConfig' => array(
							'firstDay'       => 0,
							'showWeekNumber' => true,
							'numberOfMonths' => 1,
							'yearRange'      => array( 1900, (int) date( 'Y' ) + 20 ),
						),
					),
					'prepare_value_for_database' => array( $this, 'prepare_gmt_date_for_database' ),
					'prepare_value_for_display'  => array( $this, 'prepare_gmt_date_for_display' ),
				)
			);

			$automatic_columns_keys = array(
				'tax_amount',
				'total_amount',
				'transaction_id',
				'ip_address',
				'user_agent',
				'customer_note',
			);
			foreach ( $automatic_columns_keys as $column_key ) {
				$column_args = array(
					'data_type'           => 'post_data',
					'column_width'        => 150,
					'title'               => VGSE()->helpers->convert_key_to_label( $column_key ),
					'supports_formulas'   => true,
					'allow_custom_format' => true,
				);
				if ( preg_match( '/_amount$/', $column_key ) ) {
					$column_args['prepare_value_for_display'] = array( $this, 'prepare_currency_amount_for_display_hpos' );
				}
				$editor->args['columns']->register_item(
					$column_key,
					$post_type,
					$column_args
				);
			}

			$editor->args['columns']->register_item(
				'wpse_status',
				$post_type,
				array(
					'data_type'           => 'post_data',
					'column_width'        => 150,
					'title'               => __( 'Status', 'vg_sheet_editor_givewp' ),
					'supports_formulas'   => true,
					'allow_to_hide'       => false,
					'allow_to_save'       => true,
					'formatted'           => array(
						'editor'        => 'select',
						'selectOptions' => array_merge(
							wc_get_order_statuses(),
							array_combine(
								array(
									'delete',
								),
								array(
									__( 'Delete completely', 'vg_sheet_editor' ),
								)
							)
						),
					),
					'get_value_callback'  => array( $this, 'get_order_status' ),
					'save_value_callback' => array( $this, 'save_order_status' ),
				)
			);

			$operational_data_keys = array(
				'prices_include_tax',
				'cart_hash',
				'new_order_email_sent',
				'order_key',
				'shipping_tax_amount',
				'shipping_total_amount',
				'discount_tax_amount',
				'discount_total_amount',
				'date_paid_gmt',
				'date_completed_gmt',
			);
			foreach ( $operational_data_keys as $data_key ) {
				$column_args = array(
					'title'                    => VGSE()->helpers->convert_key_to_label( $data_key ),
					'supports_formulas'        => true,
					'allow_to_save'            => true,
					'get_value_callback'       => array( $this, 'get_operational_field_value' ),
					'save_value_callback'      => array( $this, 'save_operational_field_value' ),
					'wc_operational_field_key' => $data_key,
					'wc_operational_data'      => true,
				);
				if ( preg_match( '/_amount$/', $data_key ) ) {
					$column_args['prepare_value_for_display'] = array( $this, 'prepare_currency_amount_for_display_hpos' );
				}

				if ( str_ends_with( $data_key, '_gmt' ) ) {
					$column_args = array_merge(
						$column_args,
						array(
							'title'                      => VGSE()->helpers->convert_key_to_label( str_replace( '_gmt', '', $data_key ) ),
							'value_type'                 => 'date',
							'formatted'                  => array(
								'editor'           => 'wp_datetime',
								'type'             => 'date',
								'dateFormatPhp'    => 'Y-m-d H:i:s',
								'correctFormat'    => true,
								'defaultDate'      => date( 'Y-m-d H:i:s' ),
								'datePickerConfig' => array(
									'firstDay'       => 0,
									'showWeekNumber' => true,
									'numberOfMonths' => 1,
									'yearRange'      => array( 1900, (int) date( 'Y' ) + 20 ),
								),
							),
							'prepare_value_for_database' => array( $this, 'prepare_gmt_date_for_database' ),
							'prepare_value_for_display'  => array( $this, 'prepare_gmt_date_for_display' ),
						)
					);

				}
				$editor->args['columns']->register_item(
					$data_key,
					$post_type,
					$column_args
				);
			}

			$editor->args['columns']->register_item(
				'returning_customer',
				$post_type,
				array(
					'data_type'           => 'post_data',
					'title'               => __( 'Returning Customer', 'woocommerce' ),
					'supports_formulas'   => true,
					'allow_to_save'       => true,
					'formatted'           => array(
						'type'              => 'checkbox',
						'checkedTemplate'   => '1',
						'uncheckedTemplate' => '',
					),
					'default_value'       => '',
					'get_value_callback'  => array( $this, 'get_returning_customer_value' ),
					'save_value_callback' => array( $this, 'save_returning_customer_value' ),
				)
			);
		}
		function get_returning_customer_value( $post, $column_key, $cell_args ) {
			global $wpdb;
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT returning_customer FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d", $post->ID ) );
			return ! empty( $value ) ? 1 : '';
		}

		function save_returning_customer_value( $order_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'wc_order_stats',
				array( 'returning_customer' => (int) $data_to_save ),
				array( 'order_id' => $order_id )
			);
		}
		function get_operational_field_value( $post, $column_key, $cell_args ) {
			global $wpdb;

			if ( isset( $this->operational_data[ (int) $post->ID ] ) ) {
				return $this->operational_data[ (int) $post->ID ]->$column_key;
			}
			$table = $wpdb->prefix . 'wc_order_operational_data';

			$value = $wpdb->get_var( $wpdb->prepare( 'SELECT %i FROM %i WHERE order_id = %d', $cell_args['wc_operational_field_key'], $table, $post->ID ) );

			return $value;
		}
		function save_operational_field_value( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			global $wpdb;
			$table = $wpdb->prefix . 'wc_order_operational_data';

			$wpdb->update(
				$table,
				array(
					$cell_args['wc_operational_field_key'] => $data_to_save,
				),
				array( 'order_id' => $post_id )
			);
		}


		function prepare_gmt_date_for_display( $value, $post, $cell_key, $cell_args ) {
			if ( ! empty( $value ) ) {
				$value = get_date_from_gmt( $value );
			}
			return $value;
		}

		function prepare_customer_for_display( $value, $post, $cell_key, $cell_args ) {
			global $wpdb;
			if ( ! empty( $value ) && is_numeric( $value ) ) {
				if ( isset( $this->user_id_to_emails[ $value ] ) ) {
					$email = $this->user_id_to_emails[ $value ];
				} else {
					$email                             = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM $wpdb->users WHERE ID = %d", $value ) );
					$this->user_id_to_emails[ $value ] = $email;
				}

				$value = $email ? $email : '';
			} else {
				$value = '';
			}
			return $value;
		}
		function prepare_customer_for_database( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			if ( ! empty( $data_to_save ) ) {
				$data_to_save = (int) email_exists( $data_to_save );
			}
			return $data_to_save;
		}
		function prepare_payment_method_for_database( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			global $wpdb;
			if ( ! empty( $data_to_save ) ) {
				$payment_gateways = $this->get_payment_gateways();
				$key              = isset( $payment_gateways[ $data_to_save ] ) ? $data_to_save : array_search( $data_to_save, $payment_gateways );

				if ( $key !== false ) {
					$wpdb->update(
						$this->post_type,
						array(
							'payment_method' => $key,
						),
						array(
							'id' => $post_id,
						)
					);
				}
			}
			return $data_to_save;
		}
		function prepare_gmt_date_for_database( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			if ( ! empty( $data_to_save ) ) {
				$data_to_save = get_gmt_from_date( $data_to_save );
			}
			return $data_to_save;
		}

		function use_different_meta_id_column( $id_column, $post_type ) {
			if ( $post_type !== $this->post_type ) {
				return $id_column;
			}

			return 'order_id';
		}

		function use_different_meta_table( $table_name, $post_type ) {
			global $wpdb;
			if ( $post_type !== $this->post_type ) {
				return $table_name;
			}

			return $wpdb->prefix . 'wc_orders_meta';
		}

		public function filter_rows_query_post_data( $sql, $args, $settings ) {
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}

			if ( empty( $args['wpse_original_filters'] ) || empty( $args['wpse_original_filters']['meta_query'] ) ) {
				return $sql;
			}
			$table_data_filters = wp_list_filter(
				$args['wpse_original_filters']['meta_query'],
				array(
					'source' => 'orders_data',
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

			// Replace the wpse_status column used in the filter with the real status column used by wc in the db
			$where = str_replace( 'wpse_status', 'status', $where );
			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}

		public function filter_rows_query_user_data( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}

			if ( empty( $args['wpse_original_filters'] ) || empty( $args['wpse_original_filters']['meta_query'] ) ) {
				return $sql;
			}
			$table_data_filters = wp_list_filter(
				$args['wpse_original_filters']['meta_query'],
				array(
					'source' => 'wp_user',
				)
			);
			if ( empty( $table_data_filters ) ) {
				return $sql;
			}

			$raw_where = WP_Sheet_Editor_Advanced_Filters::get_instance()->_build_sql_wheres_for_data_table( $table_data_filters, 'c' );
			if ( empty( $raw_where ) ) {
				return $sql;
			}

			$raw_where = str_replace( array( "user_id = ''", "user_id != ''" ), array( 'user_id IS NULL', 'user_id IS NOT NULL' ), $raw_where );

			$new_sql = str_replace( 'as t', 'as t LEFT JOIN ' . $wpdb->prefix . 'wc_customer_lookup as c ON t.customer_id = c.customer_id', $sql );
			$where   = implode( ' AND ', $raw_where );
			if ( strpos( $new_sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $new_sql );
			return $sql;
		}

		public function filter_rows_query_by_meta( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}

			if ( empty( $args['meta_query'] ) ) {
				return $sql;
			}
			$table_data_filters = wp_list_filter(
				$args['meta_query'],
				array(
					'source' => 'meta',
				)
			);
			if ( empty( $table_data_filters ) ) {
				return $sql;
			}

			$meta_sql = VGSE()->helpers->get_current_provider()->get_meta_query_sql( $post_type, $wpdb->prefix . 'wc_orders_meta', $table_data_filters );
			if ( ! is_array( $meta_sql ) ) {
				return $sql;
			}

			$sql   = str_replace( 'FROM ' . $wpdb->prefix . 'wc_orders as t', 'FROM ' . $wpdb->prefix . 'wc_orders as t' . $meta_sql['join'] . ' ', $sql );
			$where = preg_replace( '/^ AND/', '', $meta_sql['where'] );

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}

		public function filter_rows_query_address_data( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}

			if ( empty( $args['wpse_original_filters'] ) || empty( $args['wpse_original_filters']['meta_query'] ) ) {
				return $sql;
			}
			$table_data_filters = wp_list_filter(
				$args['wpse_original_filters']['meta_query'],
				array(
					'source' => 'wc_order_address',
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

			$shipping_filters = array();
			$billing_filters  = array();
			foreach ( $table_data_filters as $index => $filter ) {
				if ( strpos( $filter['key'], 'billing_' ) === 0 ) {
					$filter['key']     = str_replace( 'billing_', '', $filter['key'] );
					$billing_filters[] = $filter;
				}
				if ( strpos( $filter['key'], 'shipping_' ) === 0 ) {
					$filter['key']      = str_replace( 'shipping_', '', $filter['key'] );
					$shipping_filters[] = $filter;
				}
			}

			$shipping_where = WP_Sheet_Editor_Advanced_Filters::get_instance()->_build_sql_wheres_for_data_table( $shipping_filters, 'sa' );
			$billing_where  = WP_Sheet_Editor_Advanced_Filters::get_instance()->_build_sql_wheres_for_data_table( $billing_filters, 'ba' );
			$wheres         = array();

			if ( ! empty( $billing_where ) ) {
				$sql = str_replace( 'FROM ' . $wpdb->prefix . 'wc_orders as t', 'FROM ' . $wpdb->prefix . 'wc_orders as t LEFT JOIN ' . $wpdb->prefix . 'wc_order_addresses ba ON (t.id = ba.order_id AND ba.address_type = \'billing\')', $sql );

				$wheres[] = implode( ' AND ', $billing_where );
			}

			if ( ! empty( $shipping_where ) ) {
				$sql = str_replace( 'FROM ' . $wpdb->prefix . 'wc_orders as t', 'FROM ' . $wpdb->prefix . 'wc_orders as t LEFT JOIN ' . $wpdb->prefix . 'wc_order_addresses sa ON (t.id = sa.order_id AND sa.address_type = \'shipping\')', $sql );

				$wheres[] = implode( ' AND ', $shipping_where );
			}
			$where = implode( ' AND ', $wheres );

			if ( empty( $where ) ) {
				return $sql;
			}

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}
		public function filter_rows_query_operational_data( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}

			if ( empty( $args['wpse_original_filters'] ) || empty( $args['wpse_original_filters']['meta_query'] ) ) {
				return $sql;
			}
			$table_data_filters = wp_list_filter(
				$args['wpse_original_filters']['meta_query'],
				array(
					'source' => 'wc_order_operational_fields',
				)
			);
			if ( empty( $table_data_filters ) ) {
				return $sql;
			}

			$operational_where = WP_Sheet_Editor_Advanced_Filters::get_instance()->_build_sql_wheres_for_data_table( $table_data_filters, 'od' );
			$where             = '';

			if ( ! empty( $operational_where ) ) {
				$sql = str_replace( 'FROM ' . $wpdb->prefix . 'wc_orders as t', 'FROM ' . $wpdb->prefix . 'wc_orders as t LEFT JOIN ' . $wpdb->prefix . 'wc_order_operational_data od ON (t.id = od.order_id)', $sql );

				$where = implode( ' AND ', $operational_where );
			}

			if ( empty( $where ) ) {
				return $sql;
			}

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );
			return $sql;
		}

		public function add_fields_to_advanced_filters( $all_fields, $post_type ) {
			global $wpdb;
			if ( $post_type !== $this->post_type ) {
				return $all_fields;
			}

			$table_name                = $wpdb->prefix . 'wc_orders';
			$data_fields               = wp_list_pluck( $wpdb->get_results( "SHOW COLUMNS FROM {$table_name};" ), 'Field' );
			$data_fields[]             = 'wpse_status';
			$all_fields['orders_data'] = array_diff( $data_fields, array( 'status' ) );

			$raw_address_fields = array_diff( wp_list_pluck( $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}wc_order_addresses;" ), 'Field' ), array( 'id', 'order_id', 'address_type' ) );
			$address_fields     = array();
			foreach ( $raw_address_fields as $index => $address_field ) {
				$address_fields[] = 'billing_' . $address_field;
				$address_fields[] = 'shipping_' . $address_field;
			}
			$all_fields['wc_order_address'] = $address_fields;

			$all_fields['wp_user'] = array( 'user_id' );

			$raw_operational_fields                     = array_diff( wp_list_pluck( $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}wc_order_operational_data;" ), 'Field' ), array( 'id', 'order_id' ) );
			$all_fields['wc_order_operational_fields']  = $raw_operational_fields;
			$meta_fields_replaced_by_operational_fields = array(
				'_created_via',
				'_order_version',
				'_prices_include_tax',
				'_recorded_coupon_usage_counts',
				'_download_permissions_granted',
				'_cart_hash',
				'_new_order_email_sent',
				'_order_key',
				'_order_stock_reduced',
				'_date_paid',
				'_date_completed',
				'_order_shipping_tax',
				'_order_shipping',
				'_cart_discount_tax',
				'_cart_discount',
				'_recorded_sales',
				'_paid_date',
				'_completed_date',
			);
			$all_fields['meta']                         = array_diff( $all_fields['meta'], $meta_fields_replaced_by_operational_fields );

			return $all_fields;
		}


		public function set_default_provider( $provider_class_key, $post_type ) {
			if ( $post_type === $this->post_type ) {
				$provider_class_key = 'custom_table';
			}
			return $provider_class_key;
		}
	}

	add_action(
		'plugins_loaded',
		function () {
			new WPSE_WC_Orders_Custom_Table_Sheet();
		}
	);

}
