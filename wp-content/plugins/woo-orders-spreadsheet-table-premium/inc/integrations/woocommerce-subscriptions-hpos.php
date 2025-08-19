<?php

if ( ! class_exists( 'WPSE_Orders_WooCommerce_Subscriptions_HPOS' ) ) {

	class WPSE_Orders_WooCommerce_Subscriptions_HPOS extends WPSE_WC_Orders_Custom_Table_Sheet {

		public function __construct() {
			global $wpdb;

			if ( ! class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) || ! \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				return;
			}
			if ( ! class_exists( 'WC_Subscriptions_Admin' ) ) {
				return;
			}
			$this->post_type = $wpdb->prefix . 'wc_orders_subscriptions';
			WPSE_Sheet_Factory::__construct(
				array(
					'fs_object'       => wpsewco_fs(),
					'post_type'       => array( $this->post_type ),
					'post_type_label' => array( __( 'WooCommerce Subscriptions', 'woocommerce' ) ),
					'bootstrap_class' => 'WPSE_Custom_Tables_Spreadsheet_Bootstrap',
					'remove_columns'  => $this->get_removed_column_keys(),
				)
			);
			$this->set_hooks();
			$this->hpos_hooks();
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_subscription_columns' ) );
		}
		function get_order_user_id( $post, $column_key, $cell_args ) {
			global $wpdb;
			$value = $wpdb->get_var( $wpdb->prepare( 'SELECT c.user_id FROM %i o LEFT JOIN %i c ON c.customer_id = o.customer_id WHERE o.id = %d', $wpdb->prefix . 'wc_orders', $wpdb->prefix . 'wc_customer_lookup', $post->ID ) );
			return $value;
		}
		public function only_include_order_rows( $sql, $args, $settings ) {
			global $wpdb;
			$post_type = $args['post_type'];
			if ( $post_type !== $this->post_type ) {
				return $sql;
			}

			$where = " t.type = 'shop_subscription' ";

			if ( strpos( $sql, ' WHERE ' ) === false ) {
				$where = ' WHERE ' . $where;
			} else {
				$where = ' AND ' . $where;
			}
			$sql = str_replace( ' ORDER ', $where . ' ORDER ', $sql );

			return $sql;
		}
		public function set_default_provider( $provider_class_key, $post_type ) {
			if ( $post_type === $this->post_type ) {
				$provider_class_key = 'wc_subscriptions_hpos';
			}
			return $provider_class_key;
		}
		function get_removed_column_keys() {
			return array(
				'view_post',
				'post_name',
				'post_content',
				'comment_status',
				'menu_order',
				'post_type',
			);
		}

		function register_subscription_columns( $editor ) {
			$post_type = $this->post_type;
			if ( ! in_array( $editor->args['provider'], array( $post_type ) ) ) {
				return;
			}

			$date_column_keys = array( '_schedule_start', '_schedule_next_payment', '_schedule_end', '_schedule_payment_retry', '_schedule_trial_end' );
			foreach ( $date_column_keys as $date_column_key ) {
				$editor->args['columns']->register_item(
					$date_column_key,
					$post_type,
					array(
						'value_type'        => 'date',
						'data_type'         => 'meta_data',
						'supports_formulas' => true,
						'formatted'         => array(
							'editor'               => 'wp_datetime',
							'type'                 => 'date',
							'customDatabaseFormat' => 'Y-m-d H:i:s',
							'dateFormatPhp'        => 'Y-m-d H:i:s',
							'correctFormat'        => true,
							'defaultDate'          => '',
							'datePickerConfig'     => array(
								'firstDay'       => 0,
								'showWeekNumber' => true,
								'numberOfMonths' => 1,
							),
						),
					),
					true
				);
			}

			foreach ( array( 'start', 'trial_end', 'end', 'next_payment' ) as $date_type ) {
				$editor->args['columns']->register_item(
					'_schedule_' . $date_type,
					$post_type,
					array(
						'value_type'          => 'date',
						'data_type'           => 'meta_data',
						'save_value_callback' => array( $this, 'save_subscription_date_with_api' ),
						'supports_formulas'   => true,
						'formatted'         => array(
							'editor'               => 'wp_datetime',
							'type'                 => 'date',
							'customDatabaseFormat' => 'Y-m-d H:i:s',
							'dateFormatPhp'        => 'Y-m-d H:i:s',
							'correctFormat'        => true,
							'defaultDate'          => '',
							'datePickerConfig'     => array(
								'firstDay'       => 0,
								'showWeekNumber' => true,
								'numberOfMonths' => 1,
							),
						),
					),
					true
				);
			}

			$editor->args['columns']->register_item(
				'wpse_parent_order_id',
				$post_type,
				array(
					'title'              => __( 'Parent order ID', vgse_woocommerce_orders()->textname ),
					'get_value_callback' => array( $this, 'get_parent_order_id' ),
					'column_width'       => 100,
					'allow_to_save'      => false,
					'is_locked'          => true,
				)
			);

			$editor->args['columns']->register_item(
				'wpse_status',
				$post_type,
				array(
					'formatted' => array(
						'editor'        => 'select',
						'selectOptions' => array_merge(
							wc_get_order_statuses(),
							wcs_get_subscription_statuses(),
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
				),
				true
			);
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
			WHERE o.type = 'shop_subscription'
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
			WHERE o.type = 'shop_subscription'
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
		function get_parent_order_id( $post, $column_key, $cell_args ) {
			return $post->parent_order_id;
		}

		function save_subscription_date_with_api( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			$dates_to_update                   = array();
			$date_type                         = str_replace( '_schedule_', '', $cell_key );
			$dates['start']                    = get_post_meta( $post_id, '_schedule_start', true );
			$date_type_key                     = ( 'start' === $date_type ) ? 'date_created' : $date_type;
			$dates_to_update[ $date_type_key ] = $data_to_save;

			$subscription = wcs_get_subscription( $post_id );
			$subscription->update_dates( $dates_to_update );
		}

		function set_order_statuses( $statuses, $post_type ) {
			if ( $post_type === $this->post_type ) {
				$statuses = wcs_get_subscription_statuses();
			}
			return $statuses;
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

			parent::_register_columns( $editor );

			$editor->args['columns']->register_item(
				'open_wp_editor',
				$post_type,
				array(
					'title'                    => __( 'Subscription Details', vgse_woocommerce_orders()->textname ),
					'external_button_template' => admin_url( 'admin.php?page=wc-orders--shop_subscription&action=edit&id={ID}' ),
					'column_width'             => 180,
				),
				true
			);
		}

	}

	// We initialize with priority 1 because the factory class initializes with priority 10
	add_action( 'vg_sheet_editor/initialized', 'vgse_init_wc_subscriptions_sheet_hpos', 1 );

	function vgse_init_wc_subscriptions_sheet_hpos() {
		new WPSE_Orders_WooCommerce_Subscriptions_HPOS();
	}
}
