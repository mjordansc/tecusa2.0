<?php

if ( ! class_exists( 'WPSE_Orders_WooCommerce_Subscriptions' ) ) {

	class WPSE_Orders_WooCommerce_Subscriptions extends WPSE_WooCommerce_Orders_Sheet {

		var $post_type = 'shop_subscription';

		function __construct() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				return;
			}
			if ( ! class_exists( 'WC_Subscriptions_Admin' ) ) {
				return;
			}

			$allowed_columns = array();

			WPSE_Sheet_Factory::__construct(
				array(
					'fs_object'          => wpsewco_fs(),
					'post_type'          => array( $this->post_type ),
					'post_type_label'    => array( __( 'WooCommerce Subscriptions', 'woocommerce' ) ),
					'serialized_columns' => array(), // column keys
					'columns'            => array(),
					'allowed_columns'    => $allowed_columns,
					'remove_columns'     => array(
						'view_post',
						'post_name',
						'post_content',
						'comment_status',
						'menu_order',
						'post_type',
					), // column keys
				)
			);

			$this->set_hooks();
			$this->post_hooks();
			add_action( 'vg_sheet_editor/editor/register_columns', array( $this, 'register_subscription_columns' ) );
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
						'value_type' => 'date',
						'data_type' => 'meta_data',
						'supports_formulas' => true,
					),
					true
				);
			}

			foreach ( array( 'start', 'trial_end', 'end', 'next_payment' ) as $date_type ) {
				$editor->args['columns']->register_item(
					'_schedule_' . $date_type,
					$post_type,
					array(
						'value_type' => 'date',
						'data_type' => 'meta_data',
						'save_value_callback' => array( $this, 'save_subscription_date_with_api' ),
						'supports_formulas' => true,
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
		}

		function get_parent_order_id( $post, $column_key, $cell_args ) {
			return $post->post_parent;
		}

		function save_subscription_date_with_api( $post_id, $cell_key, $data_to_save, $post_type, $cell_args, $spreadsheet_columns ) {
			$dates_to_update                   = array();
			$date_type                         = str_replace( '_schedule_', '', $cell_key );
			$dates['start']                    = get_post_meta( $post_id, '_schedule_start', true );
			$date_type_key                     = ( 'start' === $date_type ) ? 'date_created' : $date_type;
			$dates_to_update[ $date_type_key ] = $data_to_save;

			$subscription          = wcs_get_subscription( $post_id );
			$subscription->update_dates( $dates_to_update );
		}

		function set_order_statuses( $statuses, $post_type ) {
			if ( $post_type === $this->post_type ) {
				$statuses = wcs_get_subscription_statuses();
			}
			return $statuses;
		}

	}

	// We initialize with priority 1 because the factory class initializes with priority 10
	add_action( 'vg_sheet_editor/initialized', 'vgse_init_wc_subscriptions_sheet', 1 );

	function vgse_init_wc_subscriptions_sheet() {
		new WPSE_Orders_WooCommerce_Subscriptions();
	}
}
