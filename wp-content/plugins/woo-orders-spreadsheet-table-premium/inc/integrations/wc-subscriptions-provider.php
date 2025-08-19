<?php defined( 'ABSPATH' ) || exit;

class VGSE_Provider_Wc_subscriptions_hpos extends VGSE_Provider_Custom_table {

	protected static $instance = false;
	public $key                = 'wc_subscriptions_hpos';

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}
	function get_table_name( $post_type = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_orders';
		return $table_name;
	}
	function get_sheet_key( $post_type = null ) {
		global $wpdb;
		return $wpdb->prefix . 'wc_orders_subscriptions';
	}
}
