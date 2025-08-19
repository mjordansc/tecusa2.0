<?php
/**
 * Session Functions
 *
 * @since 5.2.0
 *
 * @package WooCommerce_Point_Of_Sale/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get session.
 *
 * @since 5.2.0
 *
 * @param int|string|WC_POS_Session $session Session ID, slug or object.
 *
 * @throws Exception If session cannot be read/found and $data parameter of WC_POS_Session class constructor is set.
 * @return WC_POS_Session|null
 */
function wc_pos_get_session( $session ) {
	$session_object = new WC_POS_Session( $session );
	return 0 !== $session_object->get_id() ? $session_object : null;
}

function wc_pos_get_session_totals( $session_id, $user_id = 0, $skip_query = false ) {
	global $wpdb;

	$totals = [
		'orders_count'               => 0,
		'total'                      => 0,
		'refunds_total'              => 0,
		'tax_total'                  => 0,
		'shipping_tax_total'         => 0,
		'shipping_total'             => 0,
		'pending_orders_count'       => 0,
		'pending_total'              => 0,
		'pending_tax_total'          => 0,
		'pending_shipping_tax_total' => 0,
		'pending_shipping_total'     => 0,
		'payments'                   => [],
	];

	if ( $skip_query ) {
		return $totals;
	}

	$session = wc_pos_get_session( $session_id );

	if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
		return $totals;
	}

	$now         = time();
	$date_opened = is_null( $session->get_date_opened() ) ? $now : $session->get_date_opened()->getTimestamp();
	$date_closed = is_null( $session->get_date_closed() ) ? $now : $session->get_date_closed()->getTimestamp();

	$wpdb->query( 'SET SESSION SQL_BIG_SELECTS = 1' );

	if ( wc_pos_custom_orders_table_usage_is_enabled() ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
				wc_orders.payment_method AS payment_method,
				wc_orders.payment_method_title AS payment_method_title,
				COUNT(wc_orders.id) AS orders_count,
				COALESCE(SUM(wc_orders.total_amount), 0) AS total,
				COALESCE(ABS(SUM(wc_orders_refunds.total_amount)), 0) AS refunds_total,
				COALESCE(ABS(SUM(wc_orders.tax_amount)), 0) AS tax_total,
				COALESCE(ABS(SUM(wc_order_od.shipping_tax_amount)), 0) AS shipping_tax_total,
				COALESCE(ABS(SUM(wc_order_od.shipping_total_amount)), 0) AS shipping_total
				FROM {$wpdb->prefix}wc_orders wc_orders

				INNER JOIN {$wpdb->prefix}wc_orders_meta meta_register_id ON meta_register_id.order_id = wc_orders.id AND meta_register_id.meta_key = 'wc_pos_register_id' AND meta_register_id.meta_value = %d
				INNER JOIN {$wpdb->prefix}wc_orders_meta meta_served_by ON meta_served_by.order_id = wc_orders.id AND meta_served_by.meta_key = 'wc_pos_served_by' AND (%d = 0 OR meta_served_by.meta_value = %d)
				LEFT JOIN {$wpdb->prefix}wc_order_operational_data wc_order_od ON  wc_order_od.order_id = wc_orders.id
				LEFT JOIN {$wpdb->prefix}wc_orders wc_orders_refunds ON wc_orders_refunds.parent_order_id = wc_orders.id AND wc_orders_refunds.type = 'shop_order_refund'

				WHERE wc_orders.type = 'shop_order'
				AND (
					(wc_orders.date_created_gmt >= %s AND wc_orders.date_created_gmt < %s) OR (wc_order_od.date_paid_gmt >= %s AND wc_order_od.date_paid_gmt < %s)
				)
				GROUP BY wc_orders.payment_method, wc_orders.payment_method_title;
				",
				$session->get_register_id(),
				$user_id,
				$user_id,
				gmdate( WC_POS_SQL_DATETIME_FORMAT, $date_opened ),
				gmdate( WC_POS_SQL_DATETIME_FORMAT, $date_closed ),
				gmdate( WC_POS_SQL_DATETIME_FORMAT, $date_opened ),
				gmdate( WC_POS_SQL_DATETIME_FORMAT, $date_closed )
			)
		);
	} else {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm_payment_method.meta_value AS payment_method,
				pm_payment_method_title.meta_value AS payment_method_title,
				COUNT(p.ID) AS orders_count,
				COALESCE(SUM(pm_total.meta_value + 0), 0) AS total,
				COALESCE(ABS(SUM(pm_refunds_total.meta_value + 0)), 0) AS refunds_total,
				COALESCE(ABS(SUM(pm_tax_total.meta_value + 0)), 0) AS tax_total,
				COALESCE(ABS(SUM(pm_shipping_tax_total.meta_value + 0)), 0) AS shipping_tax_total,
				COALESCE(ABS(SUM(pm_shipping_total.meta_value + 0)), 0) AS shipping_total
				FROM {$wpdb->posts} p
				
				INNER JOIN {$wpdb->postmeta} pm_register ON pm_register.post_id = p.ID AND pm_register.meta_key = 'wc_pos_register_id' AND pm_register.meta_value = %d
				INNER JOIN {$wpdb->postmeta} pm_served_by ON pm_served_by.post_id = p.ID AND pm_served_by.meta_key = 'wc_pos_served_by' AND (0 = %d OR pm_served_by.meta_value = %d)
				
				LEFT JOIN {$wpdb->postmeta} pm_payment_method ON pm_payment_method.post_id = p.ID AND pm_payment_method.meta_key = '_payment_method'
				LEFT JOIN {$wpdb->postmeta} pm_payment_method_title ON pm_payment_method_title.post_id = p.ID AND pm_payment_method_title.meta_key = '_payment_method_title'
				LEFT JOIN {$wpdb->postmeta} pm_total ON pm_total.post_id = p.ID AND pm_total.meta_key = '_order_total'
				LEFT JOIN {$wpdb->postmeta} pm_tax_total ON pm_tax_total.post_id = p.ID AND pm_tax_total.meta_key = '_order_tax'
				LEFT JOIN {$wpdb->postmeta} pm_shipping_tax_total ON pm_shipping_tax_total.post_id = p.ID AND pm_shipping_tax_total.meta_key = '_order_shipping_tax'
				LEFT JOIN {$wpdb->postmeta} pm_shipping_total ON pm_shipping_total.post_id = p.ID AND pm_shipping_total.meta_key = '_order_shipping'
				LEFT JOIN {$wpdb->postmeta} pm_date_paid ON pm_date_paid.post_id = p.ID AND pm_date_paid.meta_key = '_date_paid'
				
				LEFT JOIN {$wpdb->posts} p_refund ON p_refund.post_parent = p.ID AND p_refund.post_type = 'shop_order_refund'
				LEFT JOIN {$wpdb->postmeta} pm_refunds_total ON pm_refunds_total.post_id = p_refund.ID AND pm_refunds_total.meta_key = '_order_total'
				
				WHERE p.post_type = 'shop_order' 
				AND (
					(p.post_date_gmt >= %s AND p.post_date_gmt < %s) OR (pm_date_paid.meta_value >= %d AND pm_date_paid.meta_value < %d)
				)
				GROUP BY pm_payment_method.meta_value;
				",
				$session->get_register_id(),
				$user_id,
				$user_id,
				gmdate( WC_POS_SQL_DATETIME_FORMAT, $date_opened ),
				gmdate( WC_POS_SQL_DATETIME_FORMAT, $date_closed ),
				$date_opened,
				$date_closed
			)
		);
	}

	if ( $results ) {
		foreach ( $results as $result ) {
			$totals['payments'][ $result->payment_method ] = [
				'title'              => $result->payment_method_title,
				'orders_count'       => (float) $result->orders_count,
				'total'              => (float) $result->total,
				'refunds_total'      => (float) $result->refunds_total,
				'tax_total'          => (float) $result->tax_total,
				'shipping_tax_total' => (float) $result->shipping_tax_total,
				'shipping_total'     => (float) $result->shipping_total,
			];

			if ( $result->payment_method ) {
				$totals['orders_count']       += (float) $result->orders_count;
				$totals['total']              += (float) $result->total;
				$totals['refunds_total']      += (float) $result->refunds_total;
				$totals['tax_total']          += (float) $result->tax_total;
				$totals['shipping_tax_total'] += (float) $result->shipping_tax_total;
				$totals['shipping_total']     += (float) $result->shipping_total;
			} else {
				$totals['pending_orders_count']       += (float) $result->orders_count;
				$totals['pending_total']              += (float) $result->total;
				$totals['pending_tax_total']          += (float) $result->tax_total;
				$totals['pending_shipping_tax_total'] += (float) $result->shipping_tax_total;
				$totals['pending_shipping_total']     += (float) $result->shipping_total;
			}
		}
	}

	return $totals;
}

function wc_pos_get_session_orders( $session_id ) {
	$session = wc_pos_get_session( $session_id );

	if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
		return [];
	}

	$now         = gmdate( WC_POS_SQL_DATETIME_FORMAT );
	$date_opened = is_null( $session->get_date_opened() ) ? $now : $session->get_date_opened()->date( WC_POS_SQL_DATETIME_FORMAT );
	$date_closed = is_null( $session->get_date_closed() ) ? $now : $session->get_date_closed()->date( WC_POS_SQL_DATETIME_FORMAT );

	// Query arguments
	$args = [
		'date_query' => [
			'after'     => $date_opened,
			'before'    => $date_closed,
			'inclusive' => true,
		],
		'meta_query' => [
			[
				'key'     => 'wc_pos_register_id',
				'value'   => strval( $session->get_register_id() ),
				'compare' => '=',
			],
		],
	];

	return wc_get_orders( $args );
}

/**
 * Returns the session details.
 *
 * @since 5.2.0
 *
 * @param $session_id Session ID.
 * @return array Session details.
 */
function wc_pos_get_session_details( $session_id ) {
	$details = [];
	$session = wc_pos_get_session( $session_id );

	if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
		return $details;
	}

	// Essential metadata stored in Session::session_data to prevent data loss when deleting registers, outlets, or users.
	$session_data               = $session->get_session_data();
	$session_data['register']   = empty( $session_data['register'] ) ? __( 'Deleted Register', 'woocommerce-point-of-sale' ) : $session_data['register'];
	$session_data['outlet']     = empty( $session_data['outlet'] ) ? __( 'Deleted Outlet', 'woocommerce-point-of-sale' ) : $session_data['outlet'];
	$session_data['open_first'] = empty( $session_data['open_first'] ) ? __( 'Deleted User', 'woocommerce-point-of-sale' ) : $session_data['open_first'];
	$session_data['open_last']  = empty( $session_data['open_last'] ) ? __( 'Deleted User', 'woocommerce-point-of-sale' ) : $session_data['open_last'];

	$register        = wc_pos_get_register( $session->get_register_id() );
	$outlet          = wc_pos_get_outlet( $session->get_outlet_id() );
	$open_first_user = get_user_by( 'id', $session->get_open_first() );
	$open_last_user  = get_user_by( 'id', $session->get_open_last() );

	$details['register']  = $register ? $register->get_name() : $session_data['register'];
	$details['outlet']    = $outlet ? $outlet->get_name() : $session['outlet'];
	$details['opened_by'] = $open_first_user ? $open_first_user->display_name : $session_data['open_first'];
	$details['closed_by'] = $open_last_user ? $open_last_user->display_name : $session_data['open_last'];

	return $details;
}
