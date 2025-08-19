<?php defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WPSE_WooCommerce_Orders_Sheet' ) ) {

	class WPSE_WooCommerce_Orders_Sheet extends WPSE_Sheet_Factory {

		public $post_type        = 'shop_order';
		public $columns          = array();
		public $orders_tax_rates = array();

		function __construct() {

			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				return;
			}
			$allowed_columns = array();

			if ( ! wpsewco_fs()->can_use_premium_code__premium_only() ) {
				$allowed_columns = array(
					'ID',
					'post_title',
					'post_status',
				);
			}

			parent::__construct(
				array(
					'fs_object'          => wpsewco_fs(),
					'post_type'          => array( $this->post_type ),
					'post_type_label'    => array( __( 'Orders', 'woocommerce' ) ),
					'serialized_columns' => array(), // column keys
					'columns'            => array(),
					'allowed_columns'    => $allowed_columns,
					'remove_columns'     => $this->get_removed_column_keys(),
				)
			);
			$this->set_hooks();
			$this->post_hooks();
		}

		function get_removed_column_keys() {
			return array(
				'view_post',
				'post_name',
				'post_content',
				'comment_status',
				'menu_order',
				'post_type',
				'_billing_title',
				'_shipping_title',
				'_cart_hash',
				'_order_version',
				'_billing_address_index',
				'_shipping_address_index',
				'_shipping_method',
				'_payment_method',
				'_order_stock_reduced',
				'_date_completed',
				'_date_paid',
				'_order_number',
				'_wp_desired_post_slug',
				'_wp_trash_meta_comments_status',
				'_wp_trash_meta_status',
				'_wp_trash_meta_time',
			);
		}

		function post_hooks() {
			add_filter( 'posts_clauses', array( $this, 'search_by_product_id' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'search_by_product_type' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'search_by_product_taxonomy' ), 10, 2 );
			add_filter( 'posts_clauses', array( $this, 'search_by_order_notes' ), 10, 2 );
			add_filter( 'vg_sheet_editor/provider/post/statuses', array( $this, 'set_order_statuses' ), 10, 2 );
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_post_columns' ) );
			add_filter( 'posts_clauses', array( $this, 'add_advanced_line_item_meta_search_query' ), 10, 2 );
			add_action( 'before_delete_post', array( $this, 'maybe_delete_user_before_deleting_order' ), 10, 2 );
		}
		function set_hooks() {
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_columns' ) );
			add_filter( 'vg_sheet_editor/filters/after_fields', array( $this, 'add_filters_fields' ), 10, 2 );
			add_filter( 'vg_sheet_editor/load_rows/wp_query_args', array( $this, 'filter_posts' ), 11, 2 );
			add_filter( 'vg_sheet_editor/filters/sanitize_request_filters', array( $this, 'register_custom_filters' ), 10, 2 );
			add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_toolbars' ) );
			add_filter( 'vg_sheet_editor/handsontable/custom_args', array( $this, 'handsontable_settings' ), 10, 2 );
			add_action( 'vg_sheet_editor/editor_page/after_content', array( $this, 'render_js' ) );
			add_filter( 'vg_sheet_editor/advanced_filters/all_fields_groups', array( $this, 'add_line_item_meta_to_advanced_filters' ), 10, 2 );
			// Run WC webhooks
			add_action( 'vg_sheet_editor/save_rows/after_saving_post', array( $this, 'after_save_order' ), 10, 4 );
			add_action( 'vg_sheet_editor/formulas/execute_formula/after_execution_on_field', array( $this, 'after_save_order_formula' ), 10, 6 );
			add_filter( 'vg_sheet_editor/formulas/quick_actions', array( $this, 'add_quick_bulk_actions' ), 10, 2 );
			add_filter( 'vg_sheet_editor/formulas/form_settings', array( $this, 'formulas_add_custom_edit_types' ), 10, 2 );
			add_filter( 'vg_sheet_editor/formulas/sql_execution/can_execute', array( $this, 'disable_sql_formulas_for_custom_bulk_actions' ), 10, 6 );
			add_filter( 'vg_sheet_editor/formulas/execute_formula/custom_formula_handler_executed', array( $this, 'execute_formula_recalculate_taxes' ), 10, 7 );
			add_filter( 'vg_sheet_editor/formulas/execute_formula/custom_formula_handler_executed', array( $this, 'execute_formula_resend_email' ), 10, 7 );
			add_filter( 'vg_sheet_editor/formulas/execute_formula/custom_formula_handler_executed', array( $this, 'execute_formula_replace_product' ), 10, 7 );
			add_filter( 'vg_sheet_editor/formulas/execute_formula/custom_formula_handler_executed', array( $this, 'execute_formula_regenerate_download_permissions' ), 10, 7 );
			add_filter( 'vg_sheet_editor/formulas/execute_formula/custom_formula_handler_executed', array( $this, 'execute_formula_order_notes' ), 10, 7 );
			add_filter( 'vg_sheet_editor/formulas/execute_formula/custom_formula_handler_executed', array( $this, 'execute_formula_price_edits' ), 10, 7 );
			add_action( 'vg_sheet_editor/export/before_response', array( $this, 'render_extra_export_options' ) );
			add_filter( 'vg_sheet_editor/load_rows/full_output', array( $this, 'separate_line_items_after_export' ), 20, 4 );
			add_action( 'vg_sheet_editor/filters/after_advanced_fields_section', array( $this, 'render_advanced_filters' ) );
			add_filter( 'vg_sheet_editor/options_page/options', array( $this, 'add_settings_page_options' ) );
			add_filter( 'vg_sheet_editor/after_enqueue_assets', array( $this, 'enqueue_assets' ) );
			add_filter( 'vg_sheet_editor/load_rows/preload_data', array( $this, 'preload_tax_rates' ), 10, 5 );
			add_filter( 'vg_sheet_editor/rest/export_rows_args', array( $this, 'filter_rest_export_rows_args' ) );
			add_filter( 'vg_sheet_editor/automations/extra_fields_for_sync_settings', array( $this, 'register_sync_settings_fields' ) );
		}
		public function register_sync_settings_fields( $fields ) {
			$fields['exports'][] = 'line_items_separate_rows';
			return $fields;
		}
		/**
		 * Filters the export rows arguments to include line items separate rows.
		 *
		 * @param array $args The existing arguments.
		 *
		 * @return array The modified arguments with line items separate rows.
		 */
		public function filter_rest_export_rows_args( $args ) {
			$args['line_items_separate_rows'] = array(
				'type'     => 'boolean',
				'required' => false,
			);

			return $args;
		}

		function preload_tax_rates( $data, $posts, $wp_query_args, $settings, $spreadsheet_columns ) {
			global $wpdb;
			if ( $wp_query_args['post_type'] !== $this->post_type ) {
				return $data;
			}
			$tax_rate_columns = array();
			// Filter $spreadsheet_columns that contain tax_rate in the key.
			foreach ( $spreadsheet_columns as $key => $value ) {
				if ( strpos( $key, 'tax_rate' ) === false ) {
					continue;
				}
				$tax_rate_columns[] = str_replace( 'tax_rate', '', $key );
			}
			if ( empty( $tax_rate_columns ) ) {
				return $data;
			}
			$ids                       = wp_list_pluck( $posts, 'ID' );
			$ids                       = array_filter( array_map( 'intval', $ids ) );
			$ids_in_query_placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

			foreach ( $tax_rate_columns as $tax_rate ) {
				if ( ! isset( $this->orders_tax_rates[ $tax_rate ] ) ) {
					$this->orders_tax_rates[ $tax_rate ] = array();
				}
				$sql      = "SELECT i.order_id, SUM(m.meta_value) AS total_tax_amount
FROM {$wpdb->prefix}woocommerce_order_items i
INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta m
ON i.order_item_id = m.order_item_id
WHERE i.order_item_type = 'tax'
AND i.order_item_name LIKE %s
AND m.meta_key = 'tax_amount'
AND i.order_id IN ($ids_in_query_placeholders)
GROUP BY i.order_id";
				$prepared = $wpdb->prepare( $sql, array_merge( array( '%' . $wpdb->esc_like( $tax_rate ) . '%' ), $ids ) );
				$results  = $wpdb->get_results( $prepared );

				foreach ( $results as $result ) {
					$this->orders_tax_rates[ $tax_rate ][ (int) $result->order_id ] = $result->total_tax_amount;
				}
				foreach ( $ids as $id ) {
					if ( ! isset( $this->orders_tax_rates[ $tax_rate ][ $id ] ) ) {
						$this->orders_tax_rates[ $tax_rate ][ $id ] = 0;
					}
				}
			}
			return $data;
		}
		function enqueue_assets() {
			$current_post = VGSE()->helpers->get_provider_from_query_string();

			if ( $current_post !== $this->post_type ) {
				return;
			}

			wp_enqueue_script( 'wp-sheet-editor-wc-attributes', plugins_url( '/assets/js/init.js', vgse_woocommerce_orders()->plugin_file ), array( 'jquery' ), VGSE()->version );
		}

		function maybe_delete_user_before_deleting_order( $order_id, $post ) {
			if ( $post->post_type !== 'shop_order' || ! VGSE()->get_option( 'wc_orders_delete_user_account_on_delete_order' ) ) {
				return;
			}

			$user_id = (int) get_post_meta( $order_id, '_customer_user', true );
			if ( ! $user_id ) {
				return;
			}

			$customer = new WC_Customer( $user_id );
			if ( $customer && $customer->get_order_count() < 2 ) {
				$customer->delete( true );
				wp_delete_user( $user_id );
			}
		}
		/**
		 * Add fields to options page
		 * @param array $sections
		 * @return array
		 */
		function add_settings_page_options( $sections ) {
			$sections['wc_orders'] = array(
				'icon'   => 'el-icon-cogs',
				'title'  => __( 'WooCommerce Orders', 'vg_sheet_editor' ),
				'fields' => array(
					array(
						'id'    => 'wc_orders_export_line_items_product_meta_keys',
						'type'  => 'text',
						'title' => __( 'Export these meta fields of each product when we export orders with line items as rows', 'vg_sheet_editor' ),
						'desc'  => __( 'Enter multiple meta keys separated with commas.', 'vg_sheet_editor' ),
					),
					array(
						'id'    => 'wc_orders_export_line_items_product_columns_excluded',
						'type'  => 'text',
						'title' => __( 'Exclude these columns when exporting line items in separate lines', 'vg_sheet_editor' ),
						'desc'  => __( 'Enter the column names separated with commas.', 'vg_sheet_editor' ),
					),
					array(
						'id'    => 'wc_orders_delete_user_account_on_delete_order',
						'type'  => 'switch',
						'title' => __( 'Delete the user account after deleting the only order of the user?', 'vg_sheet_editor' ),
						'desc'  => __( 'By default, we delete the order but the customer account will remain. Activate this option if you want to delete the user account automatically if the user has zero orders left after deleting an order.', 'vg_sheet_editor' ),
					),
					array(
						'id'    => 'wc_orders_format_prices',
						'type'  => 'switch',
						'title' => __( 'Add formatting to the totals?', 'vg_sheet_editor' ),
						'desc'  => __( 'By default, we show the totals as raw numbers (for example: 10.95) in case you want to do math calculations using our bulk editor. You can enable this option if you want to see the prices with the formatting that you configured in the WooCommerce settings (currency, custom decimal separator, etc), which might be useful if you want to generate reports in Excel/Google Sheets.', 'vg_sheet_editor' ),
					),
				),
			);
			return $sections;
		}

		function render_advanced_filters( $post_type ) {
			if ( $post_type !== $this->post_type ) {
				return;
			}
			?>

			<li class="variation-id--in">
				<label><?php _e( 'Find orders containing these variation IDs:', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php _e( 'Enter IDs separated by commas, spaces, new lines, or tabs. You can use ID ranges like 20-50 as a shortcut.', 'vg_sheet_editor' ); ?>">( ? )</a></label>
				<textarea name="variation_id__in"></textarea>
			</li>
			<li class="variation-attributes--in">
				<label><?php _e( 'Find orders containing these variation attributes:', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php _e( 'Enter the attribute name and value separated with a colon (:), you can enter multiple attributes separated with commas. For example: Color: blue, Size: small.', 'vg_sheet_editor' ); ?>">( ? )</a></label>
				<textarea name="variation_attributes__in"></textarea>
			</li>
			<?php
		}

		function separate_line_items_after_export( $out, $wp_query_args, $spreadsheet_columns, $clean_data ) {
			if ( empty( $out['export_complete'] ) || $wp_query_args['post_type'] !== $this->post_type || empty( $clean_data['line_items_separate_rows'] ) ) {
				return $out;
			}

			$file_path = WPSE_CSV_API_Obj()->exports_dir . $out['export_file_name'] . '.csv';
			if ( ! file_exists( $file_path ) ) {
				return $out;
			}

			$first_lines = WPSE_CSV_API_Obj()->get_rows( $file_path, ',', false, 1 );
			if ( empty( $first_lines['rows'] ) ) {
				return $out;
			}
			// Exit if the export doesn't include the line items column
			$sorted_column_keys = array_map( 'trim', explode( ',', $clean_data['custom_enabled_columns'] ) );
			if ( ! in_array( 'wpse_order_line_items', $sorted_column_keys, true ) ) {
				return $out;
			}

			$new_file_path = WPSE_CSV_API_Obj()->exports_dir . $out['export_file_name'] . '-sorted.csv';

			// Modify the CSV data using a while loop in batches of 100 rows to prevent memory leaks
			$position                = 0;
			$csv_data                = WPSE_CSV_API_Obj()->get_rows( $file_path, ',', false, 100, $position );
			$all_column_keys         = array();
			$one_variable_product    = new WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'tax_query'      => array(
						array(
							'taxonomy' => 'product_type',
							'field'    => 'slug',
							'terms'    => 'variable',
						),
					),
				)
			);
			$count_variable_products = (int) $one_variable_product->found_posts;
			$columns_to_exclude      = array_filter( array_map( 'trim', explode( ',', VGSE()->get_option( 'wc_orders_export_line_items_product_columns_excluded', '' ) ) ) );

			while ( $csv_data['rows'] ) {
				if ( empty( $csv_data['rows'] ) ) {
					break;
				}
				$new_data = array();
				foreach ( $csv_data['rows'] as $row_index => $row ) {
					$line_items_parts = explode( '==json:line_items==', $row[ __( 'Line items', vgse_woocommerce_orders()->textname ) ] );
					$row[ __( 'Line items', vgse_woocommerce_orders()->textname ) ] = current( $line_items_parts );
					$line_items = json_decode( end( $line_items_parts ), true );
					foreach ( $line_items as $line_item ) {
						$row[ __( 'Product title', vgse_woocommerce_orders()->textname ) ] = trim( $line_item['name'] );
						$row[ __( 'Quantity', vgse_woocommerce_orders()->textname ) ]      = (int) $line_item['quantity'];
						$row[ __( 'Sku', vgse_woocommerce_orders()->textname ) ]           = trim( $line_item['sku'] );
						$row[ __( 'Price', vgse_woocommerce_orders()->textname ) ]         = trim( $line_item['subtotal'] );
						$row[ __( 'Product ID', vgse_woocommerce_orders()->textname ) ]    = intval( $line_item['product_id'] );

						// Include variation ID column only if the store has variable products
						if ( $count_variable_products ) {
							$row[ __( 'Variation ID', vgse_woocommerce_orders()->textname ) ] = trim( $line_item['variation_id'] );
						}

						foreach ( $line_item['meta'] as $label => $value ) {
							$row[ __( 'Product', 'woocommerce' ) . ': ' . $label ] = $value;
						}

						$all_column_keys = array_merge( $all_column_keys, array_keys( $row ) );
						$base_columns    = array_fill_keys( $all_column_keys, '' );
						$row             = array_merge( $base_columns, $row );

						if ( ! empty( $columns_to_exclude ) ) {
							$row = array_diff_key( $row, array_flip( $columns_to_exclude ) );
						}
						$new_data[] = $row;
					}
				}

				WPSE_CSV_API_Obj()->_array_to_csv( $new_data, $new_file_path, implode( ',', array_keys( $row ) ) );
				$position = $csv_data['file_position'];
				$csv_data = WPSE_CSV_API_Obj()->get_rows( $file_path, ',', false, 100, $position );
			}

			// If the csv data was sorted successfully, replace the old file with the new file
			unlink( $file_path );
			rename( $new_file_path, $file_path );

			return $out;
		}

		function render_extra_export_options( $post_type ) {
			if ( $post_type !== $this->post_type ) {
				return;
			}
			?>
			<div class="field-wrap">
				<label><input type="checkbox" name="wpse_line_items_separate_rows" class="wpse_line_items_separate_rows"/> <?php _e( 'Export every product (line item) as separate rows?', 'vg_sheet_editor' ); ?></label>
			</div>
			<?php
		}

		function execute_formula_recalculate_taxes( $results, $post_id, $spreadsheet_column, $formula, $post_type, $spreadsheet_columns, $raw_form_data ) {

			if ( $post_type !== $this->post_type || $raw_form_data['action_name'] !== 'wc_orders_recalculate_taxes' ) {
				return $results;
			}

			$this->recalculate_taxes( $post_id );

			// Return any modified value so the progress text shows the number of updated orders
			$out = array(
				'initial_data'  => 'before',
				'modified_data' => 'after',
			);
			return $out;
		}

		function execute_formula_resend_email( $results, $post_id, $spreadsheet_column, $formula, $post_type, $spreadsheet_columns, $raw_form_data ) {

			if ( $post_type !== $this->post_type || $raw_form_data['action_name'] !== 'wc_orders_send_email' ) {
				return $results;
			}

			$action           = $raw_form_data['formula_data'][0];
			$order            = wc_get_order( $post_id );
			$email_status_map = array(
				'WC_Email_Cancelled_Order'           => 'cancelled',
				'WC_Email_Failed_Order'              => 'failed',
				'WC_Email_Customer_Failed_Order'     => 'failed',
				'WC_Email_Customer_On_Hold_Order'    => 'on-hold',
				'WC_Email_Customer_Processing_Order' => 'processing',
				'WC_Email_Customer_Completed_Order'  => 'completed',
				'WC_Email_Customer_Refunded_Order'   => 'refunded',
			);

			// Check if the order has the status related to the email, otherwise don't send the email
			if ( isset( $email_status_map[ $action ] ) && $order->get_status() !== $email_status_map[ $action ] ) {
				return;
			}
			if ( 'send_order_details' === $action ) {
				/**
				 * Fires before an order email is resent.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

				// Send the customer invoice email.
				WC()->payment_gateways();
				WC()->shipping();
				WC()->mailer()->customer_invoice( $order );

				// Note the event.
				$order->add_order_note( __( 'Order details manually sent to customer.', 'woocommerce' ), false, true );

				/**
				 * Fires after an order email has been resent.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );

			} elseif ( 'send_order_details_admin' === $action ) {

				do_action( 'woocommerce_before_resend_order_emails', $order, 'new_order' );

				WC()->payment_gateways();
				WC()->shipping();
				add_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );
				WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order, true );
				remove_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );

				do_action( 'woocommerce_after_resend_order_email', $order, 'new_order' );

			} elseif ( isset( WC()->mailer()->emails[ $action ] ) ) {
				WC()->mailer()->emails[ $action ]->trigger( $order->get_id() );
			}

			// Return any modified value so the progress text shows the number of updated orders
			$out = array(
				'initial_data'  => 'before',
				'modified_data' => 'after',
			);
			return $out;
		}

		function execute_formula_replace_product( $results, $post_id, $spreadsheet_column, $formula, $post_type, $spreadsheet_columns, $raw_form_data ) {

			if ( $post_type !== $this->post_type || $raw_form_data['action_name'] !== 'wc_orders_replace_product' ) {
				return $results;
			}
			$old_product_id   = (int) $raw_form_data['formula_data'][0];
			$old_variation_id = (int) $raw_form_data['formula_data'][1];
			$new_product_id   = (int) $raw_form_data['formula_data'][2];
			$new_variation_id = (int) $raw_form_data['formula_data'][3];

			$this->replace_product( $post_id, $old_product_id, $old_variation_id, $new_product_id, $new_variation_id );

			// Return any modified value so the progress text shows the number of updated orders
			$out = array(
				'initial_data'  => 'before',
				'modified_data' => 'after',
			);
			return $out;
		}

		function _order_contains_product( $order_id, $product_id, $variation_id ) {
			global $wpdb;
			$order_item = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT im.meta_value 'product_id', im2.meta_value 'variation_id', im.order_item_id FROM %i im
LEFT JOIN %i oi 
ON oi.order_item_type = 'line_item' AND oi.order_item_id = im.order_item_id AND im.meta_key = '_product_id'
LEFT JOIN %i im2 
ON im2.order_item_id = im.order_item_id AND im2.meta_key = '_variation_id'
WHERE oi.order_id = %d 
AND im.meta_value = %d
AND im2.meta_value = %d",
					$wpdb->prefix . 'woocommerce_order_itemmeta',
					$wpdb->prefix . 'woocommerce_order_items',
					$wpdb->prefix . 'woocommerce_order_itemmeta',
					$order_id,
					$product_id,
					$variation_id
				)
			);
			return $order_item;
		}

		function replace_product( $order_id, $old_product_id, $old_variation_id, $new_product_id, $new_variation_id ) {
			global $wpdb;
			if ( ! $old_product_id || ! $new_product_id ) {
				return;
			}
			$order_item = $this->_order_contains_product( $order_id, $old_product_id, $old_variation_id );
			if ( ! $order_item ) {
				return;
			}
			$old_price = get_post_meta( $old_variation_id ? $old_variation_id : $old_product_id, '_price', true );
			$new_price = get_post_meta( $new_variation_id ? $new_variation_id : $new_product_id, '_price', true );

			$wpdb->update(
				$wpdb->prefix . 'woocommerce_order_itemmeta',
				array(
					'meta_value' => (int) $new_product_id,
				),
				array(
					'order_item_id' => $order_item->order_item_id,
					'meta_key'      => '_product_id',
				)
			);
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_order_itemmeta',
				array(
					'meta_value' => (int) $new_variation_id,
				),
				array(
					'order_item_id' => $order_item->order_item_id,
					'meta_key'      => '_variation_id',
				)
			);
			$wpdb->update(
				$wpdb->prefix . 'wc_order_product_lookup',
				array(
					'product_id'   => (int) $new_product_id,
					'variation_id' => (int) $new_variation_id,
				),
				array(
					'order_id'     => $order_id,
					'product_id'   => $old_product_id,
					'variation_id' => $old_variation_id,
				)
			);
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_order_items',
				array(
					'order_item_name' => get_the_title( $new_product_id ),
				),
				array(
					'order_id'      => $order_id,
					'order_item_id' => $order_item->order_item_id,
				)
			);

			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return;
			}
			$order->add_order_note( sprintf( __( 'Replaced product ID %1$d with product ID %2$d', 'asdf' ), $old_product_id, $new_product_id ) );
			$order->save();

			if ( $old_price !== $new_price ) {
				$item = new WC_Order_Item_Product( $order_item->order_item_id );

				$product_or_variation = wc_get_product( $new_variation_id ? $new_variation_id : $new_product_id );
				$item->set_subtotal( $product_or_variation->get_price() );
				$item->set_total( $product_or_variation->get_price() );
				$item->calculate_taxes();
				$item->save();
				$order->calculate_totals();
			}
		}

		function execute_formula_regenerate_download_permissions( $results, $post_id, $spreadsheet_column, $formula, $post_type, $spreadsheet_columns, $raw_form_data ) {

			if ( $post_type !== $this->post_type || $raw_form_data['action_name'] !== 'wc_orders_regenerate_download_permissions' ) {
				return $results;
			}

			$data_store = WC_Data_Store::load( 'customer-download' );
			$data_store->delete_by_order_id( $post_id );
			wc_downloadable_product_permissions( $post_id, true );

			// Return any modified value so the progress text shows the number of updated orders
			$out = array(
				'initial_data'  => 'before',
				'modified_data' => 'after',
			);
			return $out;
		}

		function execute_formula_order_notes( $results, $post_id, $spreadsheet_column, $formula, $post_type, $spreadsheet_columns, $raw_form_data ) {
			global $wpdb;

			if ( $post_type !== $this->post_type || ! in_array( $raw_form_data['action_name'], array( 'clear_value', 'wc_orders_add_note' ), true ) ) {
				return $results;
			}
			$out = array(
				'initial_data'  => false,
				'modified_data' => true,
			);
			if ( $raw_form_data['action_name'] === 'wc_orders_add_note' ) {
				$note             = wp_kses_post( trim( wp_unslash( $raw_form_data['formula_data'][0] ) ) );
				$note_type        = wc_clean( wp_unslash( $raw_form_data['formula_data'][1] ) );
				$is_customer_note = ( 'customer' === $note_type ) ? 1 : 0;

				$placeholders_regex = '/\$([a-zA-Z0-9_\-]+)\$/';
				$placeholders_found = preg_match_all( $placeholders_regex, $note, $columns_matched );
				if ( $placeholders_found && method_exists( vgse_formulas_init(), '_replace_placeholders' ) ) {
					$note = vgse_formulas_init()->_replace_placeholders( $note, '', $post_id, $post_type, $spreadsheet_column );
				}
				if ( $note ) {
					$order = wc_get_order( $post_id );
					$order->add_order_note( $note, $is_customer_note, true );
				} else {
					$out['modified_data'] = false;
				}
			} elseif ( $raw_form_data['action_name'] === 'clear_value' ) {
				$notes = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_type = 'order_note' ", $post_id ) );
				if ( $notes ) {
					foreach ( $notes as $note_id ) {
						wp_delete_comment( $note_id, true );
					}
				} else {
					$out['modified_data'] = false;
				}
			}
			return $out;
		}

		function execute_formula_price_edits( $results, $post_id, $spreadsheet_column, $formula, $post_type, $spreadsheet_columns, $raw_form_data ) {
			global $wpdb;

			if ( $post_type !== $this->post_type || ! in_array( $raw_form_data['action_name'], array( 'wc_orders_increase_product_prices_by_percentage', 'wc_orders_increase_product_prices_by_number', 'wc_orders_decrease_product_prices_by_percentage', 'wc_orders_decrease_product_prices_by_number' ), true ) ) {
				return $results;
			}
			$out   = array(
				'initial_data'  => false,
				'modified_data' => true,
			);
			$order = wc_get_order( $post_id );
			if ( $order->is_editable() ) {
				$change      = (float) $raw_form_data['formula_data'][0];
				$action_name = $raw_form_data['action_name'];
				$was_updated = false;

				foreach ( $order->get_items() as $item_id => $item ) {
					// The new line item price
					$current_subtotal = $item->get_subtotal();
					$current_total    = $item->get_total();

					if ( $action_name === 'wc_orders_increase_product_prices_by_percentage' ) {
						$new_subtotal = $current_subtotal + ( $current_subtotal * $change / 100 );
						$new_total    = $current_total + ( $current_total * $change / 100 );
					} elseif ( $action_name === 'wc_orders_decrease_product_prices_by_percentage' ) {
						$new_subtotal = $current_subtotal - ( $current_subtotal * $change / 100 );
						$new_total    = $current_total - ( $current_total * $change / 100 );
					} elseif ( $action_name === 'wc_orders_increase_product_prices_by_number' ) {
						$new_subtotal = $current_subtotal + $change;
						$new_total    = $current_total + $change;
					} elseif ( $action_name === 'wc_orders_decrease_product_prices_by_number' ) {
						$new_subtotal = $current_subtotal - $change;
						$new_total    = $current_total - $change;
					}
					// Set the new price
					if ( $new_subtotal !== $current_subtotal && $new_total !== $current_total ) {
						$item->set_subtotal( $new_subtotal );
						$item->set_total( $new_total );
						$item->calculate_taxes();
						$item->save();
						$was_updated = true;
					}
				}
				if ( $was_updated ) {
					$order->calculate_totals();
				} else {
					$out['modified_data'] = false;
				}
			} else {
				$out['modified_data'] = false;
			}
			return $out;
		}

		function disable_sql_formulas_for_custom_bulk_actions( $allowed, $formula, $column, $post_type, $spreadsheet_columns, $raw_form_data ) {
			if ( $post_type === $this->post_type && in_array( $raw_form_data['action_name'], array( 'wc_orders_recalculate_taxes', 'wc_orders_add_note', 'wc_orders_regenerate_download_permissions', 'wc_orders_replace_product', 'wc_orders_send_email' ), true ) ) {
				$allowed = false;
			}

			return $allowed;
		}

		function formulas_add_custom_edit_types( $form_builder_args, $post_type ) {
			if ( $post_type !== $this->post_type ) {
				return $form_builder_args;
			}

			$form_builder_args['columns_actions']['text']['wc_orders_regenerate_download_permissions'] = 'default';
			$form_builder_args['default_actions']['wc_orders_regenerate_download_permissions']         = array(
				'label'               => __( 'Regenerate download permissions', vgse_woocommerce_orders()->textname ),
				'description'         => '',
				'fields_relationship' => 'AND',
				'jsCallback'          => 'vgseWcAdvancedOrdersActionsFormula',
				'disallow_preview'    => true,
				'allowed_column_keys' => array( 'wpse_status', 'post_status' ),
				'input_fields'        =>
				array(
					array(
						'tag' => 'textarea',
					),
				),
			);

			$form_builder_args['columns_actions']['text']['wc_orders_recalculate_taxes']                     = 'default';
			$form_builder_args['default_actions']['wc_orders_recalculate_taxes']                             = array(
				'label'               => __( 'Recalculate taxes', vgse_woocommerce_orders()->textname ),
				'description'         => '',
				'fields_relationship' => 'AND',
				'jsCallback'          => 'vgseWcAdvancedOrdersActionsFormula',
				'allowed_column_keys' => array( 'wpse_status', 'post_status' ),
				'disallow_preview'    => true,
				'input_fields'        =>
				array(
					array(
						'tag' => 'textarea',
					),
				),
			);
			$form_builder_args['columns_actions']['text']['wc_orders_send_email']                            = 'default';
			$form_builder_args['default_actions']['wc_orders_send_email']                                    = array(
				'label'               => __( 'Resend a WooCommerce email', vgse_woocommerce_orders()->textname ),
				'description'         => '',
				'fields_relationship' => 'AND',
				'jsCallback'          => 'vgseWcAdvancedOrdersActionsFormula',
				'allowed_column_keys' => array( 'wpse_status', 'post_status' ),
				'disallow_preview'    => true,
				'input_fields'        =>
				array(
					array(
						'label'       => __( 'Type of email', 'vg_sheet_editor' ),
						'tag'         => 'select',
						'options'     => '<option value="send_order_details">' . esc_html__( 'Send order details to customer', 'woocommerce' ) . '</option>' .
						'<option value="send_order_details_admin">' . esc_html__( 'Resend new order notification', 'woocommerce' ) . '</option>' .
						'<option value="WC_Email_Cancelled_Order">' . esc_html__( 'Send cancellation notification to admin', 'woocommerce' ) . '</option>' .
						'<option value="WC_Email_Failed_Order">' . esc_html__( 'Notify administrator of failed order', 'woocommerce' ) . '</option>' .
						'<option value="WC_Email_Customer_Failed_Order">' . esc_html__( 'Notify customer about failed order', 'woocommerce' ) . '</option>' .
						'<option value="WC_Email_Customer_On_Hold_Order">' . esc_html__( 'Notify customer that order is on hold', 'woocommerce' ) . '</option>' .
						'<option value="WC_Email_Customer_Processing_Order">' . esc_html__( 'Notify customer that order is paid and processing', 'woocommerce' ) . '</option>' .
						'<option value="WC_Email_Customer_Completed_Order">' . esc_html__( 'Notify customer that order has been completed or shipped', 'woocommerce' ) . '</option>' .
						'<option value="WC_Email_Customer_Refunded_Order">' . esc_html__( 'Notify customer about refunded order', 'woocommerce' ) . '</option>',
						'description' => __( 'Note, we will send the email if the order has the related status. For example, the "order cancelled" email will only be sent if the order is cancelled.', 'vg_sheet_editor' ),
					),
				),
			);
			$form_builder_args['columns_actions']['text']['wc_orders_replace_product']                       = 'default';
			$form_builder_args['default_actions']['wc_orders_replace_product']                               = array(
				'label'               => __( 'Replace product', vgse_woocommerce_orders()->textname ),
				'description'         => '',
				'fields_relationship' => 'AND',
				'jsCallback'          => 'vgseWcAdvancedOrdersActionsFormula',
				'allowed_column_keys' => array( 'wpse_status', 'post_status' ),
				'disallow_preview'    => true,
				'input_fields'        =>
				array(
					array(
						'tag'        => 'input',
						'html_attrs' => array(
							'type'        => 'number',
							'step'        => '1',
							'placeholder' => __( 'Enter the product ID', 'vg_sheet_editor' ),
						),
						'label'      => __( 'Replace the product', 'vg_sheet_editor' ),
					),
					array(
						'tag'        => 'input',
						'html_attrs' => array(
							'type' => 'number',
							'step' => '1',
						),
						'label'      => __( 'Old variation ID (optional)', 'vg_sheet_editor' ),
					),
					array(
						'tag'        => 'input',
						'html_attrs' => array(
							'type'        => 'number',
							'step'        => '1',
							'placeholder' => __( 'Enter the new product ID', 'vg_sheet_editor' ),
						),
						'label'      => __( 'With this new product', 'vg_sheet_editor' ),
					),
					array(
						'tag'        => 'input',
						'html_attrs' => array(
							'type' => 'number',
							'step' => '1',
						),
						'label'      => __( 'New variation ID (optional)', 'vg_sheet_editor' ),
					),
				),
			);
			$form_builder_args['columns_actions']['text']['wc_orders_increase_product_prices_by_percentage'] = 'default';
			$form_builder_args['default_actions']['wc_orders_increase_product_prices_by_percentage']         = array(
				'label'                  => __( 'Increase product prices by percentage', 'vg_sheet_editor' ),
				'description'            => __( 'Increase the existing value by a percentage.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
				'fields_relationship'    => 'AND',
				'jsCallback'             => 'vgseGenerateIncreasePercentageFormula',
				'allowed_column_keys'    => array( 'wpse_status', 'post_status' ),
				'disallowed_column_keys' => array(),
				'disallow_preview'       => true,
				'input_fields'           =>
				array(
					array(
						'tag'         => 'input',
						'html_attrs'  => array(
							'type' => 'number',
							'step' => '0.01',
						),
						'label'       => __( 'Increase by', 'vg_sheet_editor' ),
						'description' => __( 'Enter the percentage number.', 'vg_sheet_editor' ),
					),
				),
			);
			$form_builder_args['columns_actions']['text']['wc_orders_increase_product_prices_by_number']     = 'default';
			$form_builder_args['default_actions']['wc_orders_increase_product_prices_by_number']             = array(
				'label'                  => __( 'Increase product prices by number', 'vg_sheet_editor' ),
				'description'            => __( 'Increase the existing value by a number.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
				'fields_relationship'    => 'AND',
				'jsCallback'             => 'vgseGenerateIncreaseFormula',
				'allowed_column_keys'    => array( 'wpse_status', 'post_status' ),
				'disallow_preview'       => true,
				'disallowed_column_keys' => array(),
				'input_fields'           =>
				array(
					array(
						'tag'         => 'input',
						'html_attrs'  => array(
							'type' => 'number',
							'step' => '0.01',
						),
						'label'       => __( 'Increase by', 'vg_sheet_editor' ),
						'description' => __( 'Enter the number.', 'vg_sheet_editor' ),
					),
				),
			);
			$form_builder_args['columns_actions']['text']['wc_orders_decrease_product_prices_by_percentage'] = 'default';
			$form_builder_args['default_actions']['wc_orders_decrease_product_prices_by_percentage']         = array(
				'label'                  => __( 'Decrease product prices by percentage', 'vg_sheet_editor' ),
				'description'            => __( 'Decrease the existing value by a percentage.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
				'fields_relationship'    => 'AND',
				'jsCallback'             => 'vgseGenerateDecreasePercentageFormula',
				'allowed_column_keys'    => array( 'wpse_status', 'post_status' ),
				'disallow_preview'       => true,
				'disallowed_column_keys' => array(),
				'input_fields'           =>
				array(
					array(
						'tag'         => 'input',
						'html_attrs'  => array(
							'type' => 'number',
							'step' => '0.01',
						),
						'label'       => __( 'Decrease by', 'vg_sheet_editor' ),
						'description' => __( 'Enter the percentage number.', 'vg_sheet_editor' ),
					),
				),
			);
			$form_builder_args['columns_actions']['text']['wc_orders_decrease_product_prices_by_number']     = 'default';
			$form_builder_args['default_actions']['wc_orders_decrease_product_prices_by_number']             = array(
				'label'                  => __( 'Decrease product prices by number', 'vg_sheet_editor' ),
				'description'            => __( 'Decrease the existing value by a number.<br>The result is rounded to the 2 nearest decimals. I.e. 3.845602 becomes 3.85', 'vg_sheet_editor' ),
				'fields_relationship'    => 'AND',
				'jsCallback'             => 'vgseGenerateDecreaseFormula',
				'allowed_column_keys'    => array( 'wpse_status', 'post_status' ),
				'disallow_preview'       => true,
				'disallowed_column_keys' => array(),
				'input_fields'           =>
				array(
					array(
						'tag'         => 'input',
						'html_attrs'  => array(
							'type' => 'number',
							'step' => '0.01',
						),
						'label'       => __( 'Decrease by', 'vg_sheet_editor' ),
						'description' => __( 'Enter the number.', 'vg_sheet_editor' ),
					),
				),
			);

			$form_builder_args['columns_actions']['text']['wc_orders_add_note'] = 'default';
			$form_builder_args['default_actions']['wc_orders_add_note']         = array(
				'label'               => __( 'Add note', vgse_woocommerce_orders()->textname ),
				'description'         => '',
				'fields_relationship' => 'AND',
				'jsCallback'          => 'vgseWcAdvancedOrdersActionsFormula',
				'allowed_column_keys' => array( 'wpse_order_notes' ),
				'disallow_preview'    => true,
				'input_fields'        =>
				array(
					array(
						'label' => __( 'Add note', 'woocommerce' ),
						'tag'   => 'textarea',
					),
					array(
						'label'   => __( 'Note type', 'woocommerce' ),
						'tag'     => 'select',
						'options' => '<option value="">' . __( 'Private note', 'woocommerce' ) . '</option>' . '<option value="customer">' . __( 'Note to customer', 'woocommerce' ) . '</option>',
					),
				),
			);

			return $form_builder_args;
		}

		function recalculate_taxes( $order_id ) {
			$order = wc_get_order( $order_id );
			$this->avatax_estimate_order_tax( $order_id );
			$order->calculate_totals( true );

			/* $tax_based_on = get_option('woocommerce_tax_based_on');
				if ('base' === $tax_based_on) {
				$country = WC()->countries->get_base_country();
				$state = WC()->countries->get_base_state();
				$postcode = WC()->countries->get_base_postcode();
				$city = WC()->countries->get_base_city();
				} elseif ('billing' === $tax_based_on) {
				$country = $order->get_billing_country();
				$state = $order->get_billing_state();
				$postcode = $order->get_billing_postcode();
				$city = $order->get_billing_city();
				} else {
				$country = $order->get_shipping_country();
				$state = $order->get_shipping_state();
				$postcode = $order->get_shipping_postcode();
				$city = $order->get_shipping_city();
				}

				$calculate_tax_args = array(
				'country' => $country,
				'state' => $state,
				'postcode' => $postcode,
				'city' => $city
				);
				$order->calculate_taxes($calculate_tax_args); */
		}

		/**
		 * Forked from AvaTax to remove the doing_action line and $_POST references
		 *
		 * @since 1.0.0
		 *
		 * @param int $order_id the order ID
		 * @throws WC_Data_Exception
		 */
		public function avatax_estimate_order_tax( $order_id ) {
			if ( ! function_exists( 'wc_avatax' ) ) {
				return;
			}
			// If tax calculation is turned off, bail
			if ( ! wc_avatax()->get_tax_handler()->is_available() ) {
				return;
			}

			$order               = wc_get_order( $order_id );
			$avatax_tax_included = wc_avatax()->wc_avatax_utilities()->get_order_meta( $order->get_id(), '_wc_avatax_tax_included', true );
			if ( $avatax_tax_included == '' ) {

				$tax_included = get_option( 'woocommerce_prices_include_tax', 'no' );
				wc_avatax()->wc_avatax_utilities()->add_order_meta( $order->get_id(), '_wc_avatax_tax_included', $tax_included );
			}
			// If order couldn't be fetched, bail
			if ( ! $order ) {
				return;
			}

			if ( $order->has_shipping_address() ) {
				$country_code = $order->get_shipping_country( 'edit' );
				$state        = $order->get_shipping_state( 'edit' );
			} else {
				$country_code = $order->get_billing_country( 'edit' );
				$state        = $order->get_billing_state( 'edit' );
			}

			// check that the destination is taxable
			if ( ! wc_avatax()->get_tax_handler()->is_location_taxable( $country_code, $state ) ) {
				return;
			}

			wc_avatax()->get_order_handler()->estimate_tax( $order );
		}

		function add_quick_bulk_actions( $actions, $post_type ) {
			if ( $post_type !== $this->post_type ) {
				return $actions;
			}

			$actions['increase_product_prices_by_percent']              = array(
				'label'                     => __( 'Increase product prices by percentage', vgse_woocommerce_orders()->textname ),
				'columns'                   => array( 'wpse_status', 'post_status' ),
				'allow_to_select_column'    => false,
				'type_of_edit'              => 'wc_orders_increase_product_prices_by_percentage',
				'wp_handler'                => false,
				'hide_slow_execution_field' => true,
			);
			$actions['increase_product_prices_by_number']               = array(
				'label'                     => __( 'Increase product prices by number', vgse_woocommerce_orders()->textname ),
				'columns'                   => array( 'wpse_status', 'post_status' ),
				'allow_to_select_column'    => false,
				'type_of_edit'              => 'wc_orders_increase_product_prices_by_number',
				'wp_handler'                => false,
				'hide_slow_execution_field' => true,
			);
			$actions['wc_orders_decrease_product_prices_by_percentage'] = array(
				'label'                     => __( 'Decrease product prices by percentage', vgse_woocommerce_orders()->textname ),
				'columns'                   => array( 'wpse_status', 'post_status' ),
				'allow_to_select_column'    => false,
				'type_of_edit'              => 'wc_orders_decrease_product_prices_by_percentage',
				'wp_handler'                => false,
				'hide_slow_execution_field' => true,
			);
			$actions['decrease_product_prices_by_number']               = array(
				'label'                     => __( 'Decrease product prices by number', vgse_woocommerce_orders()->textname ),
				'columns'                   => array( 'wpse_status', 'post_status' ),
				'allow_to_select_column'    => false,
				'type_of_edit'              => 'wc_orders_decrease_product_prices_by_number',
				'wp_handler'                => false,
				'hide_slow_execution_field' => true,
			);
			$actions['recalculate_taxes']                               = array(
				'label'                  => __( 'Recalculate taxes', vgse_woocommerce_orders()->textname ),
				'columns'                => array( 'wpse_status', 'post_status' ),
				'allow_to_select_column' => false,
				'type_of_edit'           => 'wc_orders_recalculate_taxes',
				'values'                 => array( 'wc-pending' ),
				'wp_handler'             => false,
			);
			$actions['send_email']                                      = array(
				'label'                  => __( 'Resend WooCommerce email', vgse_woocommerce_orders()->textname ),
				'columns'                => array( 'wpse_status', 'post_status' ),
				'allow_to_select_column' => false,
				'type_of_edit'           => 'wc_orders_send_email',
				'wp_handler'             => false,
			);
			$actions['replace_product']                                 = array(
				'label'                  => __( 'Replace product', vgse_woocommerce_orders()->textname ),
				'columns'                => array( 'wpse_status', 'post_status' ),
				'allow_to_select_column' => false,
				'type_of_edit'           => 'wc_orders_replace_product',
				'wp_handler'             => false,
			);
			$temp_column_value = 'wc-pending';
			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$temp_column_key = 'wpse_status';
			} else {
				$temp_column_key = 'post_status';
			}
			$actions['regenerate_download_permissions'] = array(
				'label'                  => __( 'Regenerate download permissions', vgse_woocommerce_orders()->textname ),
				'columns'                => array( $temp_column_key ),
				'allow_to_select_column' => false,
				'type_of_edit'           => 'wc_orders_regenerate_download_permissions',
				'values'                 => array( $temp_column_value ),
				'wp_handler'             => false,
			);
			$actions['add_note']                        = array(
				'label'                     => __( 'Add note', vgse_woocommerce_orders()->textname ),
				'columns'                   => array( 'wpse_order_notes' ),
				'allow_to_select_column'    => false,
				'type_of_edit'              => 'wc_orders_add_note',
				'hide_slow_execution_field' => true,
				'wp_handler'                => false,
			);
			$actions['delete_notes']                    = array(
				'label'                     => __( 'Delete all the notes from the selected orders', vgse_woocommerce_orders()->textname ),
				'columns'                   => array( 'wpse_order_notes' ),
				'allow_to_select_column'    => false,
				'type_of_edit'              => 'clear_value',
				'values'                    => array( '.' ),
				'hide_slow_execution_field' => true,
				'wp_handler'                => false,
			);

			return $actions;
		}

		function after_save_order_formula( $post_id, $initial_data, $modified_data, $column, $formula, $post_type ) {
			if ( $post_type !== $this->post_type ) {
				return;
			}

			$this->after_save_order( $post_id, null, null, $post_type );
		}

		function after_save_order( $post_id, $item, $data, $post_type ) {
			if ( $post_type !== $this->post_type ) {
				return;
			}

			$order = wc_get_order( $post_id );
			if ( ! $order ) {
				return;
			}
			do_action( 'woocommerce_update_order', $order->get_id(), $order );
		}

		function add_advanced_line_item_meta_search_query( $clauses, $wp_query ) {
			global $wpdb;
			if ( empty( $wp_query->query['wpse_original_filters'] ) || empty( $wp_query->query['wpse_original_filters']['meta_query'] ) || ! is_array( $wp_query->query['wpse_original_filters']['meta_query'] ) ) {
				return $clauses;
			}
			$line_meta_query = WP_Sheet_Editor_Advanced_Filters::get_instance()->_parse_meta_query_args( $wp_query->query['wpse_original_filters']['meta_query'], 'line_item_meta' );
			if ( empty( $line_meta_query ) ) {
				return $clauses;
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

			$items_table = "{$wpdb->prefix}woocommerce_order_items";
			$sql         = "SELECT order_id FROM $items_table " . $mq_sql['join'] . " WHERE order_item_type = 'line_item'  " . $mq_sql['where'];

			$clauses['where'] .= " AND $wpdb->posts.ID IN ($sql) ";

			return $clauses;
		}

		function add_line_item_meta_to_advanced_filters( $all_fields, $post_type ) {
			global $wpdb;

			if ( ! in_array( $post_type, array( $this->post_type, $wpdb->prefix . 'wc_orders' ) ) ) {
				return $all_fields;
			}
			$line_item_fields = $this->get_custom_line_item_meta();
			if ( ! empty( $line_item_fields ) ) {
				$all_fields['line_item_meta'] = $line_item_fields;
			}

			return $all_fields;
		}

		function get_custom_line_item_meta() {
			global $wpdb;
			$transient_key = 'vgse_orders_line_items_meta';
			$meta          = get_transient( $transient_key );

			if ( empty( $meta ) || method_exists( VGSE()->helpers, 'can_rescan_db_fields' ) && VGSE()->helpers->can_rescan_db_fields( $this->post_type ) ) {
				$wc_core_meta = array_merge(
					wc_get_attribute_taxonomy_names(),
					array(
						'_billing_address_1',
						'_billing_address_2',
						'_billing_city',
						'_billing_company',
						'_billing_country',
						'_billing_email',
						'_billing_first_name',
						'_billing_last_name',
						'_billing_phone',
						'_billing_postcode',
						'_billing_state',
						'_line_subtotal',
						'_line_subtotal_tax',
						'_line_tax',
						'_line_tax_data',
						'_line_total',
						'_payment_method',
						'_product_id',
						'_qty',
						'_shipping_address_1',
						'_shipping_address_2',
						'_shipping_city',
						'_shipping_company',
						'_shipping_country',
						'_shipping_first_name',
						'_shipping_last_name',
						'_shipping_postcode',
						'_shipping_state',
						'_tax_class',
						'_variation_id',
					)
				);

				$custom_keys = $wpdb->get_col( "SELECT meta.meta_key FROM {$wpdb->prefix}woocommerce_order_itemmeta meta LEFT JOIN {$wpdb->prefix}woocommerce_order_items item ON item.order_item_id = meta.order_item_id WHERE item.order_item_type = 'line_item' GROUP BY meta.meta_key LIMIT 200; " );
				$meta        = array_diff( $custom_keys, $wc_core_meta );
				set_transient( $transient_key, $meta, WEEK_IN_SECONDS );
			}
			return $meta;
		}

		function render_js( $post_type ) {
			if ( $post_type !== $this->post_type ) {
				return;
			}
			?>
			<script>
				jQuery(document).ready(function () {
					jQuery('#enable_edition').change(function (e) {
						hot.updateSettings({
							readOnly: !hot.getSettings().readOnly
						});
					});
				});
				function vgseWcAdvancedOrdersActionsFormula(data) {
					console.log(data);
					if (!data.actionSettings.fields_relationship) {
						data.actionSettings.fields_relationship = 'AND';
					}
					if (!data.firstFieldValue) {
						return false;
					}
					return '=REPLACE(""$current_value$"",""' + data.firstFieldValue + '"")';
				}
			</script>
			<?php
		}

		function handsontable_settings( $handsontable_settings, $post_type ) {
			if ( $post_type === $this->post_type ) {
				$handsontable_settings['readOnly'] = true;
			}
			return $handsontable_settings;
		}

		function set_order_statuses( $statuses, $post_type ) {
			if ( $post_type === $this->post_type ) {
				$wc_statuses       = wc_get_order_statuses();
				$original_statuses = isset( $statuses['trash'] ) ? array(
					'trash' => $statuses['trash'],
				) : array();
				$statuses          = array_merge( $wc_statuses, $original_statuses );
			}
			return $statuses;
		}

		function get_sql_query_by_product( $product_ids, $operator = 'AND', $meta_key = '_product_id' ) {
			global $wpdb;
			$t_order_items    = $wpdb->prefix . 'woocommerce_order_items';
			$t_order_itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';

			// Build join query, select meta_value
			$query  = "SELECT $t_order_items.order_id FROM";
			$query .= " $t_order_items LEFT JOIN $t_order_itemmeta";
			$query .= " on $t_order_itemmeta.order_item_id=$t_order_items.order_item_id";

			// Build where clause, where order_id = $t_posts.ID
			$query .= " WHERE $t_order_items.order_item_type='line_item'";
			$query .= " AND $t_order_itemmeta.meta_key='" . esc_sql( $meta_key ) . "'";
			$query .= " AND $t_order_itemmeta.meta_value IN (" . implode( ',', $product_ids ) . ') ';

			return $query;
		}

		function search_by_order_notes( $clauses, $wp_query ) {
			global $wpdb;
			if ( ! empty( $wp_query->query['wpse_order_notes'] ) ) {
				$keywords = array_map( 'trim', explode( ';', $wp_query->query['wpse_order_notes'] ) );
				$checks   = array();
				foreach ( $keywords as $single_keyword ) {
					$checks[] = "comment_content LIKE '%" . esc_sql( $single_keyword ) . "%' ";
				}
				$clauses['where'] .= " AND $wpdb->posts.ID IN (SELECT comment_post_ID FROM $wpdb->comments WHERE comment_type = 'order_note' AND (" . implode( ' OR ', $checks ) . ') ) ';
			}
			if ( ! empty( $wp_query->query['wpse_order_notes_not'] ) ) {
				$keywords = array_map( 'trim', explode( ';', $wp_query->query['wpse_order_notes_not'] ) );
				$checks   = array();
				foreach ( $keywords as $single_keyword ) {
					$checks[] = "comment_content LIKE '%" . esc_sql( $single_keyword ) . "%' ";
				}
				$clauses['where'] .= " AND $wpdb->posts.ID NOT IN (SELECT comment_post_ID FROM $wpdb->comments WHERE comment_type = 'order_note' AND (" . implode( ' OR ', $checks ) . ') ) ';
			}
			return $clauses;
		}

		function get_search_by_variation_attributes_sql( $attributes_index ) {
			global $wpdb;
			$t_order_items    = $wpdb->prefix . 'woocommerce_order_items';
			$t_order_itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';

			$select        = "SELECT oi.order_id FROM $t_order_items oi";
			$join          = array();
			$where         = array();
			$prepared_data = array();
			$index         = 1;
			foreach ( $attributes_index as $attribute_slug => $attribute_value ) {
				$join[]          = "LEFT JOIN $t_order_itemmeta om$index 
				ON om$index.order_item_id = oi.order_item_id";
				$where[]         = "om$index.meta_key = %s AND om$index.meta_value = %s";
				$prepared_data[] = $attribute_slug;
				$prepared_data[] = $attribute_value;
				++$index;
			}
			$sql = $select . ' ' . implode( PHP_EOL, $join ) . ' WHERE ' . implode( ' AND ', $where );

			$product_id_sql = $wpdb->prepare( $sql, $prepared_data );
			return $product_id_sql;
		}

		function search_by_product_id( $clauses, $wp_query ) {
			global $wpdb;
			if ( ! empty( $wp_query->query['wpse_order_products'] ) ) {
				$product_id_sql    = $this->get_sql_query_by_product( $wp_query->query['wpse_order_products']['product_ids'], $wp_query->query['wpse_order_products']['operator'] );
				$clauses['where'] .= " AND $wpdb->posts.ID IN ($product_id_sql) ";
			}
			if ( ! empty( $wp_query->query['wpse_order_variation_ids'] ) ) {
				$product_id_sql    = $this->get_sql_query_by_product( $wp_query->query['wpse_order_variation_ids'], 'OR', '_variation_id' );
				$clauses['where'] .= " AND $wpdb->posts.ID IN ($product_id_sql) ";
			}
			if ( ! empty( $wp_query->query['wpse_order_variation_attributes'] ) ) {
				$product_id_sql    = $this->get_search_by_variation_attributes_sql( $wp_query->query['wpse_order_variation_attributes'] );
				$clauses['where'] .= " AND $wpdb->posts.ID IN ($product_id_sql) ";
			}
			return $clauses;
		}
		function search_by_product_type( $clauses, $wp_query ) {
			global $wpdb;
			if ( ! empty( $wp_query->query['wpse_order_product_type'] ) ) {
				$sql = $wpdb->prepare(
					"SELECT DISTINCT o.ID
				FROM %i o
				INNER JOIN %i oi
					ON oi.order_id = o.ID
				INNER JOIN %i oim
					ON oi.order_item_id = oim.order_item_id
				INNER JOIN %i tr
					ON oim.meta_value = tr.object_id
				INNER JOIN %i tt
					ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN %i t
					ON tt.term_id = t.term_id
				WHERE o.post_type = 'shop_order'
				AND oim.meta_key = '_product_id'
				AND tt.taxonomy = 'product_type'
				AND t.slug = %s",
					$wpdb->posts,
					$wpdb->prefix . 'woocommerce_order_items',
					$wpdb->prefix . 'woocommerce_order_itemmeta',
					$wpdb->prefix . 'term_relationships',
					$wpdb->prefix . 'term_taxonomy',
					$wpdb->prefix . 'terms',
					$wp_query->query['wpse_order_product_type']
				);

				$clauses['where'] .= " AND $wpdb->posts.ID IN ($sql) ";
			}
			return $clauses;
		}
		function search_by_product_taxonomy( $clauses, $wp_query ) {
			global $wpdb;
			if ( ! empty( $wp_query->query['wpse_order_product_taxonomy'] ) ) {
				$slugs                       = $wp_query->query['wpse_order_product_taxonomy']['taxonomy_terms'];
				$slugs_in_query_placeholders = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );
				$sql                         = $wpdb->prepare(
					"SELECT DISTINCT o.ID
				FROM %i o
				INNER JOIN %i oi
					ON oi.order_id = o.ID
				INNER JOIN %i oim
					ON oi.order_item_id = oim.order_item_id
				INNER JOIN %i tr
					ON oim.meta_value = tr.object_id
				INNER JOIN %i tt
					ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN %i t
					ON tt.term_id = t.term_id
				WHERE o.post_type = 'shop_order'
				AND oim.meta_key = '_product_id'
				AND tt.taxonomy = %s
				AND t.slug IN ($slugs_in_query_placeholders)",
					array_merge(
						array(
							$wpdb->posts,
							$wpdb->prefix . 'woocommerce_order_items',
							$wpdb->prefix . 'woocommerce_order_itemmeta',
							$wpdb->prefix . 'term_relationships',
							$wpdb->prefix . 'term_taxonomy',
							$wpdb->prefix . 'terms',
							$wp_query->query['wpse_order_product_taxonomy']['taxonomy_key'],
						),
						$slugs
					)
				);

				$clauses['where'] .= " AND $wpdb->posts.ID IN ($sql) ";
			}
			return $clauses;
		}

		function register_custom_filters( $sanitized_filters, $dirty_filters ) {

			if ( isset( $dirty_filters['products'] ) ) {
				$sanitized_filters['products'] = array_map( 'sanitize_text_field', $dirty_filters['products'] );
			}
			if ( isset( $dirty_filters['customers'] ) ) {
				$sanitized_filters['customers'] = array_map( 'intval', $dirty_filters['customers'] );
			}
			if ( isset( $dirty_filters['order_notes'] ) ) {
				$sanitized_filters['order_notes'] = sanitize_text_field( $dirty_filters['order_notes'] );
			}
			if ( isset( $dirty_filters['product_type'] ) ) {
				$sanitized_filters['product_type'] = sanitize_text_field( $dirty_filters['product_type'] );
			}
			if ( isset( $dirty_filters['product_taxonomy'] ) ) {
				$sanitized_filters['product_taxonomy'] = sanitize_text_field( $dirty_filters['product_taxonomy'] );
			}
			if ( isset( $dirty_filters['product_taxonomy_terms'] ) ) {
				$sanitized_filters['product_taxonomy_terms'] = array_filter( array_map( 'sanitize_text_field', $dirty_filters['product_taxonomy_terms'] ) );
			}
			if ( isset( $dirty_filters['order_notes_not'] ) ) {
				$sanitized_filters['order_notes_not'] = sanitize_text_field( $dirty_filters['order_notes_not'] );
			}
			return $sanitized_filters;
		}

		/**
		 * Apply filters to wp-query args
		 * @param array $query_args
		 * @param array $data
		 * @return array
		 */
		function filter_posts( $query_args, $data ) {
			global $wpdb;
			if ( $query_args['post_type'] !== $this->post_type ) {
				return $query_args;
			}

			if ( ! empty( $data['filters'] ) ) {
				$filters = WP_Sheet_Editor_Filters::get_instance()->get_raw_filters( $data );

				if ( ! empty( $filters['products'] ) && ! empty( $filters['products_operator'] ) ) {
					$product_ids                       = array_filter( array_map( array( VGSE()->helpers, '_get_post_id_from_search' ), array_map( 'sanitize_text_field', $filters['products'] ) ) );
					$query_args['wpse_order_products'] = array(
						'operator'    => $filters['products_operator'] === 'AND' ? 'AND' : 'OR',
						'product_ids' => $product_ids,
					);
				}
				if ( ! empty( $filters['product_type'] ) ) {
					$query_args['wpse_order_product_type'] = $filters['product_type'];
				}
				if ( ! empty( $filters['product_taxonomy'] ) && ! empty( $filters['product_taxonomy_terms'] ) ) {
					$query_args['wpse_order_product_taxonomy'] = array(
						'taxonomy_key'   => $filters['product_taxonomy'],
						'taxonomy_terms' => $filters['product_taxonomy_terms'],
					);
				}
				if ( ! empty( $filters['order_notes'] ) ) {
					$query_args['wpse_order_notes'] = $filters['order_notes'];
				}
				if ( ! empty( $filters['order_notes_not'] ) ) {
					$query_args['wpse_order_notes_not'] = $filters['order_notes_not'];
				}

				if ( ! empty( $filters['variation_id__in'] ) ) {
					$variation_ids_parts = preg_split( '/\r\n|\r|\n|\t|\s|,/', $filters['variation_id__in'] );
					$variation_ids       = array();
					foreach ( $variation_ids_parts as $variation_ids_part ) {
						if ( strpos( $variation_ids_part, '-' ) !== false ) {
							$range_parts = array_filter( explode( '-', $variation_ids_part ) );
							if ( count( $range_parts ) === 2 ) {
								$variation_ids = array_merge( $variation_ids, range( (int) $range_parts[0], (int) $range_parts[1] ) );
							}
						} else {
							$variation_ids[] = $variation_ids_part;
						}
					}
					$variation_ids                          = array_map( 'intval', $variation_ids );
					$query_args['wpse_order_variation_ids'] = $variation_ids;
				}
				if ( ! empty( $filters['variation_attributes__in'] ) ) {
					$variation_attributes_parts = preg_split( '/\r\n|\r|\n|\t|,/', $filters['variation_attributes__in'] );
					$variation_attributes_index = array();
					foreach ( $variation_attributes_parts as $variation_attributes_part ) {
						$attribute_parts = array_map( 'trim', explode( ':', $variation_attributes_part ) );
						if ( count( $attribute_parts ) !== 2 ) {
							continue;
						}
						$attribute_slug  = $attribute_parts[0];
						$attribute_value = $attribute_parts[1];
						if ( strpos( $attribute_slug, 'pa_' ) === 0 ) {
							$attribute_slug = $attribute_slug;
						} elseif ( $global_attribute = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocommerce_attribute_taxonomies WHERE attribute_label = %s', $attribute_slug ) ) ) {
							$attribute_slug = 'pa_' . $global_attribute->attribute_name;
						} else {
							$attribute_slug = sanitize_title( $attribute_slug );
						}
						if ( strpos( $attribute_slug, 'pa_' ) === 0 && ! empty( $attribute_value ) ) {
							$attribute_term = get_term_by( 'name', $attribute_value, $attribute_slug );
							if ( ! $attribute_term ) {
								$attribute_term = get_term_by( 'slug', $attribute_value, $attribute_slug );
							}
							if ( $attribute_term ) {
								$attribute_value = $attribute_term->slug;
							}
						}
						$variation_attributes_index[ $attribute_slug ] = $attribute_value;
					}
					$query_args['wpse_order_variation_attributes'] = $variation_attributes_index;
				}

				if ( ! empty( $filters['customers'] ) ) {
					if ( empty( $query_args['meta_query'] ) ) {
						$query_args['meta_query'] = array();
					}
					$query_args['meta_query'][] = array(
						'compare' => 'IN',
						'key'     => '_customer_user',
						'value'   => array_map( 'intval', $filters['customers'] ),
					);
				}
				if ( ! empty( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ) {
					$all_meta_query = json_encode( $query_args['meta_query'] );
					if ( strpos( $all_meta_query, '_customer_user' ) !== false ) {
						foreach ( $query_args['meta_query'] as $index => $meta ) {
							if ( isset( $meta[0] ) && $meta[0]['key'] === '_customer_user' && $meta[0]['value'] === '' ) {
								$query_args['meta_query'][ $index ][0]['value'] = '0';
							}
						}
					}
				}
			}

			return $query_args;
		}

		function add_filters_fields( $current_post_type, $filters ) {
			if ( $current_post_type !== $this->post_type ) {
				return;
			}

			$nonce = wp_create_nonce( 'bep-nonce' );
			?>
			<li>
				<label><?php _e( 'Find orders containing these products', vgse_woocommerce_orders()->textname ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php echo esc_attr( __( 'You enter product names, ID, or SKUs', vgse_woocommerce_orders()->textname ) ); ?>">( ? )</a></label>
				<select name="products_operator">
					<option value="AND"><?php _e( 'It must contain all the products selected', vgse_woocommerce_orders()->textname ); ?></option>
					<option value="OR"><?php _e( 'It must contain at least one product selected', vgse_woocommerce_orders()->textname ); ?></option>
				</select>
				<select name="products[]" data-remote="true" data-min-input-length="4" data-action="vgse_find_post_by_name" data-post-type="<?php echo apply_filters( 'vg_sheet_editor/woocommerce/product_post_type_key', 'product' ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>"  data-placeholder="<?php _e( 'Select product(s)...', vgse_woocommerce_orders()->textname ); ?> " class="select2 individual-product-selector" multiple>
					<option value=""></option>
				</select>
			</li>
			<li>
				<label><?php _e( 'Find orders by product type', vgse_woocommerce_orders()->textname ); ?></label>
				<select name="product_type" class="product-type-selector">
					<option value="">--</option>
					<?php
					$types = get_terms(
						array(
							'taxonomy'   => 'product_type',
							'hide_empty' => false,
						)
					);
					foreach ( $types as $type ) {
						?>
						<option value="<?php echo esc_attr( $type->slug ); ?>"><?php echo esc_html( $type->name ); ?></option>
						<?php
					}
					?>
				</select>
			</li>
			<li>
				<label><?php _e( 'Find orders by product taxonomy', vgse_woocommerce_orders()->textname ); ?></label>
				<select name="product_taxonomy" class="product-taxonomy-selector">
					<option value=""><?php _e( 'Select taxonomy', vgse_woocommerce_orders()->textname ); ?></option>
					<?php
					$taxonomies = get_object_taxonomies( 'product', 'objects' );
					foreach ( $taxonomies as $taxonomy ) {
						?>
						<option value="<?php echo esc_attr( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->label ); ?></option>
						<?php
					}
					?>
				</select><br>
				<select data-placeholder="<?php _e( 'Enter a category/tag name...', 'vg_sheet_editor' ); ?>" name="product_taxonomy_terms[]" class="select2"  multiple data-remote="true" data-action="vgse_search_taxonomy_terms" data-output-format="%slug%" data-global-search="1" data-min-input-length="4" data-taxonomies="">
			</li>
			<li>
				<label><?php _e( 'Registered customer', vgse_woocommerce_orders()->textname ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php echo esc_attr( __( 'Find orders made by specific customers', vgse_woocommerce_orders()->textname ) ); ?>">( ? )</a></label>									
				<select data-placeholder="<?php _e( 'Enter a username or email...', 'vg_sheet_editor' ); ?>" name="customers[]" class="select2"  multiple data-remote="true" data-action="vgse_find_users_by_keyword_for_select2" data-min-input-length="4"></select>
			</li>
			<li>
				<label><?php _e( 'Order notes', vgse_woocommerce_orders()->textname ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php echo esc_attr( __( 'Search by order notes.<br/>Search by multiple keywords separating keywords with a semicolon (;)' ) ); ?>">( ? )</a></label>
				<input type="text" name="order_notes" />
			</li>
			<li>
				<label><?php _e( 'Order notes don\'t contains these keywords', vgse_woocommerce_orders()->textname ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php echo esc_attr( __( 'Search orders where notes dont contains these keywords.<br/>Search by multiple keywords separating keywords with a semicolon (;)' ) ); ?>">( ? )</a></label>
				<input type="text" name="order_notes_not" />
			</li>
			<?php
		}

		function get_registered_customer_emails() {
			global $wpdb;
			$transient_key = 'vg_sheet_editor_users_emails';
			$emails        = get_transient( $transient_key );
			if ( ! $emails ) {
				$emails = $wpdb->get_col(
					"SELECT user_email 
FROM $wpdb->users INNER JOIN $wpdb->usermeta 
ON $wpdb->users.ID = $wpdb->usermeta.user_id 
WHERE $wpdb->usermeta.meta_key = '{$wpdb->prefix}capabilities' 
AND $wpdb->usermeta.meta_value <> '' 
ORDER BY $wpdb->users.user_email ASC"
				);
				set_transient( $transient_key, $emails, WEEK_IN_SECONDS );
			}
			return $emails;
		}

		function prepare_currency_amount_for_display_hpos( $value, $post, $cell_key, $cell_args ) {
			$value = WPSE_WooCommerce_Orders_Sheet::prepare_currency_amount_for_display( $value );
			return $value;
		}
		function register_post_columns( $editor ) {
			global $wpdb;
			$post_type = $this->post_type;
			if ( ! in_array( $editor->args['provider'], array( $post_type ) ) ) {
				return;
			}

			$editor->args['columns']->register_item(
				'_billing_email',
				$post_type,
				array(
					'data_type'         => 'meta_data',
					'value_type'        => 'email',
					'type'              => '',
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_rename'   => true,
					'allow_to_save'     => true,
				),
				true
			);
			$editor->args['columns']->register_item(
				'_shipping_email',
				$post_type,
				array(
					'data_type'         => 'meta_data',
					'value_type'        => 'email',
					'type'              => '',
					'supports_formulas' => true,
					'allow_to_hide'     => true,
					'allow_to_rename'   => true,
					'allow_to_save'     => true,
				),
				true
			);
			$editor->args['columns']->register_item(
				'post_status',
				$post_type,
				array(
					'save_value_callback' => array( $this, 'save_order_status' ),
				),
				true
			);
			$editor->args['columns']->register_item(
				'_customer_user',
				$post_type,
				array(
					'get_value_callback'  => array( $this, 'get_customer_user' ),
					'save_value_callback' => array( $this, 'save_customer_user' ),
					'formatted'           => array(
						'editor'        => 'select',
						'selectOptions' => $this->get_registered_customer_emails(),
					),
				),
				true
			);
			$editor->args['columns']->register_item(
				'post_date',
				$post_type,
				array(
					'is_locked'         => true,
					'lock_template_key' => 'enable_lock_cell_template',
					'column_width'      => 160,
				),
				true
			);

			$editor->args['columns']->register_item(
				'post_excerpt',
				$post_type,
				array(
					'title' => __( 'Order note', vgse_woocommerce_orders()->textname ),
				),
				true
			);

			$editor->args['columns']->register_item(
				'_shipping_country',
				$post_type,
				array(
					'data_type' => 'meta_data',
					'formatted' => array(
						'editor'        => 'select',
						'selectOptions' => WC()->countries->countries,
					),
				),
				true
			);
			$editor->args['columns']->register_item(
				'_billing_country',
				$post_type,
				array(
					'formatted' => array(
						'editor'        => 'select',
						'selectOptions' => WC()->countries->countries,
					),
				),
				true
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
				'_payment_method_title',
				$post_type,
				array(
					'data_type'           => 'meta_data',
					'formatted'           => array(
						'editor'        => 'select',
						'selectOptions' => array_values( $this->get_payment_gateways() ),
					),
					'save_value_callback' => array( $this, 'save_payment_gateway' ),
					'allow_custom_format' => true,
				),
				true
			);
			$editor->args['columns']->register_item(
				'_order_currency',
				$post_type,
				array(
					'formatted' => array(
						'editor'        => 'select',
						'selectOptions' => get_woocommerce_currencies(),
					),
				),
				true
			);

			$editor->args['columns']->register_item(
				'open_wp_editor',
				$post_type,
				array(
					'title'        => __( 'Order Details', vgse_woocommerce_orders()->textname ),
					'column_width' => 130,
				),
				true
			);
			$editor->args['columns']->register_item(
				'_created_via',
				$post_type,
				array(
					'formatted' => array(
						'editor'        => 'select',
						'selectOptions' => array(
							'checkout',
							'rest-api',
						),
					),
				),
				true
			);

			$editor->args['columns']->register_item(
				'is_vat_exempt',
				$post_type,
				array(
					'formatted' => array(
						'data'              => 'comment_status',
						'type'              => 'checkbox',
						'checkedTemplate'   => 'yes',
						'uncheckedTemplate' => 'no',
					),
				),
				true
			);
			$editor->args['columns']->register_item(
				'_prices_include_tax',
				$post_type,
				array(
					'formatted' => array(
						'data'              => 'comment_status',
						'type'              => 'checkbox',
						'checkedTemplate'   => 'yes',
						'uncheckedTemplate' => 'no',
					),
				),
				true
			);
			$total_amount_meta_keys = array(
				'_order_tax',
				'_order_total',
				'_order_shipping',
				'_order_shipping_tax',
				'_cart_discount_tax',
				'_cart_discount',
			);
			foreach ( $total_amount_meta_keys as $meta_key ) {
				$editor->args['columns']->register_item(
					$meta_key,
					$post_type,
					array(
						'prepare_value_for_display' => array( $this, 'prepare_currency_amount_for_display_hpos' ),
					),
					true
				);
			}

			$editor->args['columns']->register_item(
				'_completed_date',
				$post_type,
				array(
					'title'               => __( 'Completed date', 'woocommerce' ),
					'formatted'           => array(
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
					'save_value_callback' => array( $this, 'save_order_date' ),
					'is_locked'           => true,
					'lock_template_key'   => 'enable_lock_cell_template',
					'value_type'          => 'date',
					'allow_custom_format' => false,
					'timestamp_meta_key'  => '_date_completed',
				),
				true
			);
			$editor->args['columns']->register_item(
				'_paid_date',
				$post_type,
				array(
					'title'               => __( 'Paid date', 'woocommerce' ),
					'formatted'           => array(
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
					'save_value_callback' => array( $this, 'save_order_date' ),
					'is_locked'           => true,
					'lock_template_key'   => 'enable_lock_cell_template',
					'value_type'          => 'date',
					'allow_custom_format' => false,
					'timestamp_meta_key'  => '_date_paid',
				),
				true
			);
		}
		function save_order_date( $order_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {

			$order = wc_get_order( $order_id );
			if ( $cell_key === '_paid_date' ) {
				$order->set_date_paid( $data_to_save );
			} elseif ( $cell_key === '_completed_date' ) {
				$order->set_date_completed( $data_to_save );
			}
			$order->save();
		}
		/**
		 * Register toolbar items
		 */
		function register_columns( $editor ) {
			global $wpdb;
			$post_type = $this->post_type;
			if ( ! in_array( $editor->args['provider'], array( $post_type ) ) ) {
				return;
			}

			$editor->args['columns']->register_item(
				'wpse_order_subtotal',
				$post_type,
				array(
					'title'              => __( 'Subtotal', vgse_woocommerce_orders()->textname ),
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_order_subtotal' ),
				),
				true
			);
			$editor->args['columns']->register_item(
				'wpse_order_number',
				$post_type,
				array(
					'title'              => __( 'Order number', vgse_woocommerce_orders()->textname ),
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_order_number' ),
				),
				true
			);
			$editor->args['columns']->register_item(
				'wpse_order_total',
				$post_type,
				array(
					'title'              => __( 'Total', vgse_woocommerce_orders()->textname ),
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_order_total' ),
				),
				true
			);
			$editor->args['columns']->register_item(
				'wpse_order_total_quantity_count',
				$post_type,
				array(
					'title'              => __( 'Items quantity count', vgse_woocommerce_orders()->textname ),
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_order_item_count' ),
				),
				true
			);
			$editor->args['columns']->register_item(
				'wpse_order_total_items',
				$post_type,
				array(
					'title'              => __( 'Items count', vgse_woocommerce_orders()->textname ),
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_order_total_items' ),
				),
				true
			);

			$editor->args['columns']->register_item(
				'wpse_order_total_refunds',
				$post_type,
				array(
					'title'              => __( 'Refund amount', vgse_woocommerce_orders()->textname ),
					'is_locked'          => true,
					'get_value_callback' => array( $this, 'get_order_total_refunds' ),
				),
				true
			);

			// Add tax columns
			$tax_rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates LIMIT 300", ARRAY_A );
			foreach ( $tax_rates as $tax_rate ) {
				$editor->args['columns']->register_item(
					'tax_rate' . sanitize_title( $tax_rate['tax_rate_name'] ),
					$post_type,
					array(
						'title'              => sprintf( __( 'Tax rate: %s', vgse_woocommerce_orders()->textname ), $tax_rate['tax_rate_name'] ),
						'get_value_callback' => array( $this, 'get_tax_rate' ),
						'allow_to_save'      => false,
						'tax_rate_name'      => $tax_rate['tax_rate_name'],
					)
				);
			}
			$editor->args['columns']->register_item(
				'wpse_order_line_items',
				$post_type,
				array(
					'title'              => __( 'Line items', vgse_woocommerce_orders()->textname ),
					'get_value_callback' => array( $this, 'get_order_line_items' ),
					'column_width'       => 500,
					'allow_to_save'      => false,
					'is_locked'          => true,
				)
			);
			$editor->args['columns']->register_item(
				'wpse_order_notes',
				$post_type,
				array(
					'title'                   => __( 'Order notes', vgse_woocommerce_orders()->textname ),
					'get_value_callback'      => array( $this, 'get_order_notes' ),
					'column_width'            => 500,
					'allow_to_save'           => false,
					'is_locked'               => true,
					'supports_formulas'       => true,
					'supports_sql_formulas'   => false,
					'supported_formula_types' => array( 'wc_orders_add_note', 'clear_value' ),
				)
			);
		}

		function get_shipping_methods() {

			$shipping_methods = WC()->shipping()->get_shipping_methods();
			$active_methods   = array();
			foreach ( $shipping_methods as $id => $shipping_method ) {
				$active_methods[ $id ] = $shipping_method->method_title;
			}
			return $active_methods;
		}

		function get_payment_gateways() {

			$payment_gateways       = WC()->payment_gateways->payment_gateways();
			$payment_gateway_titles = array();
			foreach ( $payment_gateways as $key => $gateway ) {
				if ( $gateway->is_available() ) {
					$payment_gateway_titles[ $key ] = $gateway->get_title();
				}
			}
			return $payment_gateway_titles;
		}

		function save_payment_gateway( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			$payment_gateways = $this->get_payment_gateways();
			$key              = isset( $payment_gateways[ $data_to_save ] ) ? $data_to_save : array_search( $data_to_save, $payment_gateways );

			if ( $key !== false ) {
				update_post_meta( $post_id, '_payment_method_title', $payment_gateways[ $key ] );
				update_post_meta( $post_id, '_payment_method', $key );
			}
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
						'post_status' => $data_to_save,
					)
				);
			}
		}

		function save_customer_user( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			$user_id = email_exists( $data_to_save );
			update_post_meta( $post_id, '_customer_user', $user_id );
		}

		function get_shipping_method( $post, $column_key, $cell_args ) {
			$order = wc_get_order( $post->ID );
			$value = '';
			if ( ! $order ) {
				return $value;
			}
			$shipping = $order->get_items( 'shipping' );

			if ( ! empty( $shipping ) ) {
				foreach ( $shipping as $item_id => $shipping_item_obj ) {
					$value = $shipping_item_obj->get_method_title();
					break;
				}
			}
			return $value;
		}

		function get_order_notes( $post, $column_key, $cell_args ) {

			$notes  = wc_get_order_notes( array( 'order_id' => $post->ID ) );
			$values = array();

			foreach ( $notes as $note ) {
				$values[] = sprintf( __( 'Note: %1$s (Added by %2$s on %3$s, Visibility: %4$s)', vgse_woocommerce_orders()->textname ), wptexturize( wp_kses_post( $note->content ) ), $note->added_by, esc_html( sprintf( __( '%1$s at %2$s', 'woocommerce' ), $note->date_created->date_i18n( wc_date_format() ), $note->date_created->date_i18n( wc_time_format() ) ) ), $note->customer_note ? 'customer-note' : 'private-note' );
			}

			$out = implode( '. ' . PHP_EOL, $values );
			return $out;
		}

		function _get_line_items( $order ) {
			$out               = array();
			$items             = $order->get_items();
			$product_meta_keys = array_filter( array_map( 'trim', explode( ',', VGSE()->get_option( 'wc_orders_export_line_items_product_meta_keys', '' ) ) ) );
			foreach ( $items as $item_id => $item ) :
				$out[ $item_id ] = array(
					'sku'           => '',
					'name'          => '',
					'quantity'      => 0,
					'purchase_note' => '',
					'subtotal'      => '',
					'meta'          => array(),
					'product_id'    => 0,
					'variation_id'  => 0,
				);
				if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
					$product = $item->get_product();

					if ( is_object( $product ) ) {
						$out[ $item_id ]['sku']           = $product->get_sku();
						$out[ $item_id ]['purchase_note'] = $product->get_purchase_note();
					}
					$out[ $item_id ]['name']         = wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );
					$out[ $item_id ]['quantity']     = apply_filters( 'woocommerce_email_order_item_quantity', $item->get_quantity(), $item );
					$out[ $item_id ]['subtotal']     = html_entity_decode( wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ) );
					$out[ $item_id ]['product_id']   = intval( $item->get_product_id() );
					$out[ $item_id ]['variation_id'] = intval( $item->get_variation_id() );

					foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
						$out[ $item_id ]['meta'][ wp_kses_post( $meta->display_key ) ] = html_entity_decode( wp_strip_all_tags( trim( $meta->display_value ) ) );
					}
				}

				foreach ( $product_meta_keys as $meta_key ) {
					$out[ $item_id ]['meta'][ wp_kses_post( VGSE()->helpers->convert_key_to_label( $meta_key ) ) ] = html_entity_decode( wp_strip_all_tags( trim( get_post_meta( $item->get_product_id(), $meta_key, true ) ) ) );
				}
			endforeach;
			return $out;
		}

		function get_order_line_items( $post, $column_key, $cell_args ) {
			$order = wc_get_order( $post->ID );
			if ( ! $order ) {
				return '';
			}
			$lines = array_filter(
				explode(
					"\n",
					html_entity_decode(
						wp_strip_all_tags(
							wc_get_email_order_items(
								$order,
								array(
									'show_sku'      => true,
									'show_image'    => false,
									'plain_text'    => true,
									'sent_to_admin' => true,
								)
							)
						)
					)
				)
			);
			$out   = '';
			foreach ( $lines as $line ) {
				$out .= __( 'Product', 'woocommerce' ) . ': ' . $line . '. ' . PHP_EOL;
			}

			$separate_rows = ! empty( $_REQUEST['line_items_separate_rows'] ) || ( ! empty( $cell_args['request_settings'] ) && ! empty( $cell_args['request_settings']['line_items_separate_rows'] ) );

			if ( $separate_rows ) {
				$line_items = $this->_get_line_items( $order );
				$out       .= '==json:line_items==' . json_encode( $line_items );
			}
			return $out;
		}

		function get_tax_rate( $post, $column_key, $cell_args ) {
			global $wpdb;
			$tax_name = $cell_args['tax_rate_name'];

			if ( isset( $this->orders_tax_rates[ $tax_name ] ) && isset( $this->orders_tax_rates[ $tax_name ][ $post->ID ] ) ) {
				$value = self::prepare_currency_amount_for_display( $this->orders_tax_rates[ $tax_name ][ $post->ID ] );
				return $value;
			}
			$value = (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(m.meta_value) FROM {$wpdb->prefix}woocommerce_order_items i
INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta m 
ON i.order_item_id = m.order_item_id
WHERE i.order_item_type = 'tax' 
AND i.order_id = %d AND i.order_item_name LIKE %s 
AND m.meta_key = 'tax_amount'",
					$post->ID,
					'%' . $wpdb->esc_like( $tax_name ) . '%'
				)
			);

			$value = self::prepare_currency_amount_for_display( $value );
			return $value;
		}

		static function prepare_currency_amount_for_display( $value ) {
			if ( VGSE()->get_option( 'wc_orders_format_prices' ) && $value ) {
				$value = html_entity_decode( wp_strip_all_tags( wc_price( $value ) ) );
			}
			return $value;
		}

		function get_customer_user( $post, $column_key ) {
			$user_id = (int) get_post_meta( $post->ID, '_customer_user', true );
			$value   = '';
			if ( $user_id ) {
				$user  = get_userdata( $user_id );
				$value = $user->user_email;
			}
			return $value;
		}

		function get_order_total( $post, $column_key ) {
			$order = wc_get_order( $post->ID );
			if ( ! $order ) {
				return '';
			}
			$value = $order->get_total();
			$value = self::prepare_currency_amount_for_display( $value );
			return $value;
		}

		function get_order_item_count( $post, $column_key ) {
			$order = wc_get_order( $post->ID );
			if ( ! $order ) {
				return '';
			}
			$value = $order->get_item_count();
			return $value;
		}

		function get_order_total_items( $post, $column_key ) {
			$order = wc_get_order( $post->ID );
			if ( ! $order ) {
				return '';
			}
			$value = count( $order->get_items() );
			return $value;
		}

		function get_order_total_refunds( $post, $column_key ) {
			$order = wc_get_order( $post->ID );
			if ( ! $order ) {
				return '';
			}
			$value = $order->get_total_refunded();
			return $value;
		}

		function get_order_subtotal( $post, $column_key ) {
			$order = wc_get_order( $post->ID );
			if ( ! $order ) {
				return '';
			}
			$value = $order->get_subtotal();
			$value = self::prepare_currency_amount_for_display( $value );
			return $value;
		}

		function get_order_number( $post, $column_key ) {
			$order = wc_get_order( $post->ID );
			if ( ! $order ) {
				return '';
			}
			$value = $order->get_order_number();
			return $value;
		}

		function register_toolbars( $editor ) {
			$post_type = $this->post_type;
			if ( ! in_array( $editor->args['provider'], array( $post_type ) ) ) {
				return;
			}
			if ( WP_Sheet_Editor_Helpers::current_user_can( 'edit_shop_orders' ) ) {
				$editor->args['toolbars']->register_item(
					'enable_edition',
					array(
						'type'          => 'switch', // html | switch | button
						'content'       => __( 'Enable editing', vgse_woocommerce_orders()->textname ),
						'id'            => 'enable_edition',
						'toolbar_key'   => 'primary',
						'help_tooltip'  => __( 'By default all the cells are readonly for safety reasons, click here if you want to edit information.', vgse_woocommerce_orders()->textname ),
						'default_value' => false,
					),
					$post_type
				);
				$editor->args['toolbars']->register_item(
					'add_rows',
					array(
						'type'                  => 'button', // html | switch | button
						'icon'                  => 'fa fa-upload',
						'url'                   => admin_url( 'post-new.php?post_type=shop_order' ),
						'content'               => __( 'Create order', vgse_woocommerce_orders()->textname ),
						'extra_html_attributes' => ' target="_blank" ',
						'allow_in_frontend'     => false,
					),
					$post_type
				);
			}
		}
	}

	add_action(
		'plugins_loaded',
		function () {
			new WPSE_WooCommerce_Orders_Sheet();
		}
	);
}
