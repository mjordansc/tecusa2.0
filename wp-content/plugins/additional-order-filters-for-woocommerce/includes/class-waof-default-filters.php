<?php

/**
 * Additional Order Filters for WooCommerce / Defaul filters
 * This class should called only when HPOS is disabled
 *
 * @package   Additional Order Filters for WooCommerce
 * @author    Anton Bond facebook.com/antonbondarevych
 * @license   GPL-2.0+
 * @since     1.11
 */

defined( 'ABSPATH' ) || exit;

class AOF_Woo_Additional_Order_Default_Filters {

	private static $filter_search  = array( '*', ', ', ',' );
	private static $filter_replace = array( '%', '|', '|' );

	protected $woaf_enabled_additional_filters;
	protected $woaf_default_filters;
	protected $woaf_custom_filters;

	function __construct()
	{
		add_action( 'admin_menu', array( $this,'woaf_add_plugin_settings_page' ) );

		$this->woaf_enabled_additional_filters = AOF_Woo_Additional_Order_Filters_Admin_Options::woaf_enabled_additional_filters();
		$this->woaf_default_filters = AOF_Woo_Additional_Order_Filters_Admin_Options::woaf_get_filters();
		$this->woaf_custom_filters = AOF_Woo_Additional_Order_Filters_Admin_Options::woaf_get_custom_filters();
	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function woaf_add_plugin_settings_page() {
		add_action( 'admin_notices', array( $this, 'waof_woocommerce_settings_check' ) );
		add_action( 'views_edit-shop_order', array( $this, 'woaf_show_button' ), 10 );
		add_action( 'restrict_manage_posts', array( $this, 'woaf_show_filters' ), 15 );
		add_action( 'posts_where', array( $this, 'woaf_where_plugin_functions' ) );
		add_filter( 'pre_get_posts', array( $this, 'woaf_filter_date_range' ) );
		add_filter( 'plugin_action_links_'.plugin_basename( __FILE__ ).'', array( $this, 'waof_plugin_add_settings_link' ) );
	}

	function waof_woocommerce_settings_check() {
		global $typenow;
		$enabled_filters = $this->woaf_enabled_additional_filters;

		if( $typenow == 'shop_order' && empty($enabled_filters) ) {
			$notice = '<div class="notice notice-warning">';
				$notice .= '<p>Enable additional order filters on <a href="admin.php?page=additional-order-filters-woocommerce">settings page</a> to use them</p>';
			$notice .= '</div>';
			echo $notice;
		}
	}

	function waof_plugin_add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=additional-order-filters">' . __( 'Settings', 'woaf-plugin' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	function woaf_show_button( $views ) {
		$enabled_filters = $this->woaf_enabled_additional_filters;

		if ( !empty( $enabled_filters ) )
			echo '<a href="" onclick="event.preventDefault()" id="woaf_show_filters" class="button action">'.__( 'Additional Filters', 'woaf-plugin' ).'</a>';

		return $views;
	}

	function woaf_show_filters() {
		$post_type = sanitize_text_field( $_GET['post_type'] );
		if (!isset($_GET['post_type']) || $post_type !='shop_order') {
			return false;
		}

		$output = '';
		$filters = AOF_Woo_Additional_Order_Filters_Admin_Options::woaf_get_filters();
		$enabled_filters = $this->woaf_enabled_additional_filters;

		if ( !empty($filters) && !empty($enabled_filters) ) {
			$output .= '<div class="woaf_special_order_filter_wrapper">';
			$opened = ( isset( $_COOKIE["woaf_additional_order_filter"] ) && $_COOKIE["woaf_additional_order_filter"] == 'open' ) ? 'opened' : '';

			$output .= "<div class='woaf_special_order_filter $opened'>";
			$per_column = get_option( 'woaf_per_column' );
			$per_column = ($per_column) ? $per_column : '4';
			foreach (array_chunk($filters, $per_column, true) as $filter) {
				$output .= '<div class="inline_block">';
				foreach ($filter as $filter) {
					if ( !empty($enabled_filters) && in_array( $filter['id'], $enabled_filters ) ) {
						if ( $filter['id'] == 'order_statuses' ) :
							$output .= '<div class="order_block_wrapper">';
							$output .= '<label for="order_statuses">'.$filter["name"].'</label>';
							$output .= '<select id="order_statuses" class="order_statuses_select" name="post_status[]" multiple="multiple">';
								$orders_statuses = wc_get_order_statuses();

								$selected = ( isset($_GET['post_status']) ) ? (array)$_GET['post_status'] : array();

								foreach ($orders_statuses as $k => $v) {
									$select = ( isset($selected) && in_array($k, $selected) ) ? " selected" : "";
									$output .= '<option value="'.$k.'" name="post_status" '.$select.'>'.$v.'</option>';
								}
							$output .= "</select>";
							$output .= "</div>";
						endif;
						if ( $filter['id'] == 'payment_method' ) :
							$selected = ( isset($_GET['payment_customer_filter']) ) ? sanitize_text_field($_GET['payment_customer_filter']) : '';
							$gateways = WC()->payment_gateways->payment_gateways();
							$output .= '<div class="order_block_wrapper">';
							$output .= '<label for="payment_customer_filter">'.$filter["name"].'</label>';
							$output .= '<select name="payment_customer_filter" id="payment_customer_filter">';
								$output .= '<option value=""></option>';
									foreach ($gateways as $gateway) {
										$title     = $gateway->title;
										$method_id = $gateway->id;
										if ( $selected == $method_id ) {
											$output .= '<option value="'.$method_id.'" selected>'.$title.'</option>';
										} else {
											$output .= '<option value="'.$method_id.'">'.$title.'</option>';
										}
									}
							$output .= '</select>';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'customer_group' ) :
							$selected = ( isset($_GET['nonregistered_users_filter']) ) ? sanitize_text_field($_GET['nonregistered_users_filter']) : '';
							if ( $selected == 'nonregistered_users' ) $selected = 'selected';
							$selected_reg = ( $selected == 'registered_users' ) ? 'selected' : '';
							$output .= '<div class="order_block_wrapper">';
							$output .= '<label for="nonregistered_users_filter">'.$filter["name"].'</label>';
							$output .= '<select name="nonregistered_users_filter" id="nonregistered_users_filter">';
							$output .= '<option value=""></option>';
							$output .= "<option value=\"nonregistered_users\" $selected >Nonregistered Users</option>";
							$output .= "<option value=\"registered_users\" $selected_reg>Registered Users</option>";
							$output .= '</select>';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'shipping_method' ) :
							$output .= '<div class="order_block_wrapper">';
								$shipping_method_filter = (isset( $_GET['shipping_method_filter'] )) ? sanitize_text_field($_GET['shipping_method_filter']) : '';
							$output .= '<label for="shipping_method_filter">'.$filter["name"].'</label>';
							$output .= '<input type="text" value="'.$shipping_method_filter.'" name="shipping_method_filter" id="shipping_method_filter">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'customer_email' ) :
							$output .= '<div class="order_block_wrapper">';
								$user_email_search = (isset( $_GET['user_email_search'] )) ? sanitize_text_field($_GET['user_email_search']) : '';
							$output .= '<label for="user_email_search">'.$filter["name"].'</label>';
							$output .= '<input type="text" value="'.$user_email_search.'" name="user_email_search" id="user_email_search">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'customer_first_name' ) :
							$output .= '<div class="order_block_wrapper">';
								$user_billing_first_name = (isset( $_GET['user_billing_first_name'] )) ? sanitize_text_field($_GET['user_billing_first_name']) : '';
							$output .= '<label for="user_billing_first_name">'.$filter["name"].'</label>';
							$output .= '<input type="text" value="'.$user_billing_first_name.'" name="user_billing_first_name" id="user_billing_first_name">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'customer_last_name' ) :
							$output .= '<div class="order_block_wrapper">';
								$user_billing_last_name = (isset( $_GET['user_billing_last_name'] )) ? sanitize_text_field($_GET['user_billing_last_name']) : '';
							$output .= '<label for="user_billing_last_name">'.$filter["name"].'</label>';
							$output .= '<input type="text" value="'.$user_billing_last_name.'" name="user_billing_last_name" id="user_billing_last_name">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'customer_billing_address' ) :
							$output .= '<div class="order_block_wrapper">';
								$user_billing_address = (isset( $_GET['user_billing_address'] )) ? sanitize_text_field($_GET['user_billing_address']) : '';
							$output .= '<label for="user_billing_address">'.$filter["name"].'</label>';
							$output .= '<input type="text" value="'.$user_billing_address.'" name="user_billing_address" id="user_billing_address">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'billing_country' ) :
							$output .= '<div class="order_block_wrapper">';
								$user_billing_country = (isset( $_GET['user_billing_country'] )) ? sanitize_text_field($_GET['user_billing_country']) : '';
								$output .= '<label for="order_statuses">'.$filter["name"].'</label>';
								$output .= '<select id="user_billing_country" class="order_statuses_select" name="user_billing_country[]" multiple="multiple">';
									$woo_countries = new WC_Countries();
									$countries     = $woo_countries->__get('countries');

									$selected = ( isset($_GET['user_billing_country']) ) ? (array)$_GET['user_billing_country'] : array();

									foreach ($countries as $k => $v) {
										$select = ( isset($selected) && in_array($k, $selected) ) ? " selected" : "";
										$output .= '<option value="'.$k.'" name="user_billing_country" '.$select.'>'.$v.'</option>';
									}
								$output .= "</select>";
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'customer_phone' ) :
							$output .= '<div class="order_block_wrapper">';
								$user_phone = (isset( $_GET['user_phone'] )) ? sanitize_text_field($_GET['user_phone']) : '';
							$output .= '<label for="user_phone">'.$filter["name"].'</label>';
							$output .= '<input type="text" value="'.$user_phone.'" name="user_phone" id="user_phone">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'track_number' ) :
							$output .= '<div class="order_block_wrapper">';
								$shpping_track_number = (isset( $_GET['shpping_track_number'] )) ? sanitize_text_field($_GET['shpping_track_number']) : '';
							$output .= '<label for="shpping_track_number">'.$filter["name"].'</label>';
							$output .= '<input type="text" value="'.$shpping_track_number.'" name="shpping_track_number" id="shpping_track_number">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'search_by_sku' ) :
							$output .= '<div class="order_block_wrapper">';
								$woaf_filter_search_sku = (isset( $_GET['woaf_filter_search_sku'] )) ? sanitize_text_field($_GET['woaf_filter_search_sku']) : '';
							$output .= '<label for="woaf_filter_search_sku">'.$filter["name"].'</label>';
							$output .= '<input type="text" value="'.$woaf_filter_search_sku.'" name="woaf_filter_search_sku" id="woaf_filter_search_sku">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'orders_by_date_range' ) :
							$output .= '<div class="order_block_wrapper date_range">';
							$output .= '<label for="woaf_filter_start_date">'.$filter["name"].'</label>';
							$from = ( isset($_GET['woaf_filter_start_date']) ) ? sanitize_text_field( $_GET['woaf_filter_start_date'] ) : '';
							$to   = ( isset($_GET['woaf_filter_end_date']) ) ? sanitize_text_field( $_GET['woaf_filter_end_date'] ) : '';
							$output .= '<input type="text" id="woaf_filter_start_date" name="woaf_filter_start_date" value="'.$from.'" placeholder="'.__( 'Start date', 'woaf-plugin' ).'">';
							$output .= '<input type="text" id="woaf_filter_end_date" value="'.$to.'" name="woaf_filter_end_date" placeholder="'.__( 'End date', 'woaf-plugin' ).'">';
							$output .= '</div>';
						endif;
						if ( $filter['id'] == 'filter_order_total' ) :
							$output .= '<div class="order_block_wrapper order_total">';
							$order_total_start = (isset( $_GET['order_total_start'] )) ? sanitize_text_field($_GET['order_total_start']) : '';
							$order_total_end = (isset( $_GET['order_total_end'] )) ? sanitize_text_field($_GET['order_total_end']) : '';
							$output .= '<label for="order_total_start">'.$filter["name"].'';
								$output .= '<div class="inline">';
									$output .= '<label for="order_total_start">'.__( 'from:', 'woaf-plugin' ).'</label>';
									$output .= '<input type="number" min="0" value="'.$order_total_start.'" id="order_total_start" name="order_total_start">';
								$output .= '</div>';
								$output .= '<div class="inline">';
									$output .= '<label for="order_total_end">'.__( 'to:', 'woaf-plugin' ).'</label>';
									$output .= '<input type="number" min="1" value="'.$order_total_end.'" id="order_total_end" name="order_total_end">';
								$output .= '</div>';
							$output .= '</label>';
							$output .= '</div>';
						endif;
					}
				}
				$output .= '</div>';
			}

			//start collect custom users filters
			if ( is_array($this->woaf_custom_filters) && !empty($this->woaf_custom_filters) ) {
				$output .= '<div class="woaf_custom_orders_filters">';
					$output .= '<h2>'.__( 'Custom Filters', 'woaf-plugin' ).'</h2>';
					$count = 0;
					foreach (array_chunk($this->woaf_custom_filters, $per_column, true) as $filter) {
						$output .= '<div class="inline_block">';
						foreach ($filter as $filter) {
							$output .= '<div class="order_block_wrapper">';
								$filter_search = (isset( $_GET[$filter['filter-field']] )) ? sanitize_text_field($_GET[$filter['filter-field']]) : '';
							$output .= '<label for="user-filter-'.$filter['filter-field'].'-'.$count.'">'.$filter["filter-name"].'</label>';
							$output .= '<input type="text" value="'.$filter_search.'" name="'.$filter['filter-field'].'" id="user-filter-'.$filter['filter-field'].'-'.$count.'">';
							$output .= '</div>';

							$count++;
						}
						$output .= '</div>'; // .inline_block
					}
				$output .= '</div>'; // .woaf_custom_orders_filters
			}

			$output .= '<div class="filter_buttons">';
				$output .= '<input name="filter_action" class="button" value="'.__( 'Apply Filters', 'woaf-plugin' ).'" type="submit">';
				$output .= '<input name="filter_clear" class="button" value="'.__( 'Clear', 'woaf-plugin' ).'" id="filter_clear" type="button">';
			$output .= '</div>';
			$output .= '<div class="cledarfix"></div>';

			$output .= '</div>'; // .woaf_special_order_filter
			$output .= '</div>'; // .woaf_special_order_filter_wrapper
		}

		echo $output;
	}

	function woaf_where_plugin_functions( $where ) {
		global $typenow, $wpdb;

		if( 'shop_order' == $typenow ) {
			if ( isset( $_GET['nonregistered_users_filter'] ) && !empty( $_GET['nonregistered_users_filter'] ) ) { // search by user email
				$filter = trim( sanitize_text_field($_GET['nonregistered_users_filter']) );
				$filter = str_replace("*", "%", $filter);
				$filter = $wpdb->_escape($filter);
				if ( !empty( $filter ) && $filter == 'nonregistered_users' ) :
					$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_customer_user' AND meta_value = '0' OR meta_key = '_customer_user' AND meta_value = '' ) ";
				endif;
				if ( !empty( $filter ) && $filter == 'registered_users' ) :
					$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_customer_user' AND meta_value > '0' )";
				endif;
			}
			if ( isset( $_GET['post_status'] ) && !empty( $_GET['post_status'] ) && is_array($_GET['post_status']) ) { // search by order statuses
				$filter = '';
				$count = count( $_GET['post_status'] );
				foreach ($_GET['post_status'] as $k => $status) {
					$last = ( $k + 1 == $count ) ? "" : ", ";
					$filter .= "'". trim( sanitize_text_field($status) ) . "'$last";
				}
				if ( !empty( $filter )  ) {
					$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->posts .".ID FROM ".$wpdb->posts ." WHERE `post_status` IN (".$filter."))";
				}
			}
			if ( isset( $_GET['user_email_search'] ) && !empty( $_GET['user_email_search'] ) ) { // search by user email
				$filter = trim( sanitize_text_field($_GET['user_email_search']) );
				$filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
				$filter = $wpdb->_escape($filter);
				$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_billing_email' AND meta_value REGEXP '" . $filter . "' )";
			}
			if ( isset( $_GET['user_billing_first_name'] ) && !empty( $_GET['user_billing_first_name'] ) ) { // search by billing first name
				$filter = trim( sanitize_text_field($_GET['user_billing_first_name']) );
				$filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
				$filter = $wpdb->_escape($filter);
				$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_billing_first_name' AND meta_value REGEXP '" . $filter . "' )";
			}
			if ( isset( $_GET['user_billing_last_name'] ) && !empty( $_GET['user_billing_last_name'] ) ) { // search by billing last name
				$filter  = trim( sanitize_text_field($_GET['user_billing_last_name']) );
				$filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
				$filter  = $wpdb->_escape($filter);
				$where  .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_billing_last_name' AND meta_value REGEXP '" . $filter . "' )";
			}
			if ( isset( $_GET['user_billing_address'] ) && !empty( $_GET['user_billing_address'] ) ) { // search by billing address
				$filter = trim( sanitize_text_field($_GET['user_billing_address']) );
				$filter = str_replace("*", "%", $filter);
				$filter = $wpdb->_escape($filter);
				$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_billing_address_1' AND meta_value LIKE '%" . $filter . "%' )";
			}
			if ( isset( $_GET['user_billing_country'] ) && !empty( $_GET['user_billing_country'] ) ) { // search by billing country
				$filter = '';
				$count = count( $_GET['user_billing_country'] );
				foreach ($_GET['user_billing_country'] as $k => $country) {
					$suffix = ( $k + 1 == $count ) ? "" : " OR meta_value = ";
					$country_code  = "'". trim( sanitize_text_field($country) ) ."'";
					$filter       .= $country_code.$suffix;
				}
				$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_billing_country' AND meta_value = ".$filter." )";
			}
			if ( isset( $_GET['user_phone'] ) && !empty( $_GET['user_phone'] ) ) { // search by billing phone or shipping phone
				$filter  = trim( sanitize_text_field($_GET['user_phone']) );
				$filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
				$filter  = $wpdb->_escape($filter);
				$where  .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_billing_phone' AND meta_value REGEXP '" . $filter . "' OR meta_key = '_shipping_phone' AND meta_value REGEXP '" . $filter . "' )";
			}
			if ( isset( $_GET['order_total_start'] ) && !empty( $_GET['order_total_start'] ) || isset( $_GET['order_total_end'] ) && !empty( $_GET['order_total_end'] ) ) { // search by total
				$start = $_GET['order_total_start'];
				$end   = $_GET['order_total_end'];

				if ( is_numeric( $start ) || is_numeric( $end ) ) {
					$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_order_total'";
					if ( is_numeric( $start ) ){
						$where .= " AND meta_value >= " . sprintf("%.2f", $start);
					}
					if ( is_numeric( $end ) ){
						$where .= " AND meta_value <= " . sprintf("%.2f", $end) ;
					}
					$where .= " ) ";
				}
			}
			if ( isset( $_GET['shipping_method_filter'] ) && !empty( $_GET['shipping_method_filter'] ) ) { // search by shipping
				$filter  = trim( sanitize_text_field($_GET['shipping_method_filter']) );
				$filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
				$filter  = $wpdb->_escape($filter);
				$where  .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->prefix."woocommerce_order_items.order_id FROM ".$wpdb->prefix."woocommerce_order_items WHERE order_item_type = 'shipping' AND order_item_name REGEXP '" . $filter . "' )";
			}
			if ( isset( $_GET['payment_customer_filter'] ) && !empty( $_GET['payment_customer_filter'] ) ) { // search by payment method
				$filter = trim( sanitize_text_field($_GET['payment_customer_filter']) );
				$filter = str_replace("*", "%", $filter);
				$filter = $wpdb->_escape($filter);
				$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_payment_method' AND meta_value LIKE '%" . $filter . "%' )";
			}
			if ( isset( $_GET['shpping_track_number'] ) && !empty( $_GET['shpping_track_number'] ) ) { // search by track number
				$filter  = trim( sanitize_text_field($_GET['shpping_track_number']) );
				$filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
				$filter  = $wpdb->_escape($filter);
				if ( is_plugin_active( 'woocommerce-shipment-tracking/shipment-tracking.php' ) ) {
					$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_wc_shipment_tracking_items' AND meta_value REGEXP '\"tracking_number\";s:" . $filter . "%' )";
				} elseif ( is_plugin_active( 'woo-shipment-tracking-order-tracking/woocommerce-shipment-tracking.php' ) ) {
					$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = 'wf_wc_shipment_source' AND meta_value REGEXP '\"shipment_id_cs\";s:" . $filter . "%' )";
				}
			}
			if ( isset( $_GET['woaf_filter_search_sku'] ) && !empty( $_GET['woaf_filter_search_sku'] ) ) { // search by SKU
				$filter  = trim($_GET['woaf_filter_search_sku']);
				$filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
				$filter  = $wpdb->_escape($filter);

				$where .= " AND ($wpdb->posts.ID IN(
				SELECT $wpdb->posts.ID FROM $wpdb->posts
				INNER JOIN " . $wpdb->prefix . "woocommerce_order_items ON $wpdb->posts.ID = " . $wpdb->prefix . "woocommerce_order_items.order_id
				INNER JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta ON " . $wpdb->prefix . "woocommerce_order_items.order_item_id = " . $wpdb->prefix . "woocommerce_order_itemmeta.order_item_id
				INNER JOIN $wpdb->postmeta ON " . $wpdb->prefix . "woocommerce_order_itemmeta.meta_value = $wpdb->postmeta.post_id
				WHERE $wpdb->posts.post_type = 'shop_order'
				AND " . $wpdb->prefix . "woocommerce_order_items.order_item_type = 'line_item'
				AND " . $wpdb->prefix . "woocommerce_order_itemmeta.meta_key = '_product_id'
				AND $wpdb->postmeta.meta_key = '_sku'
				AND $wpdb->postmeta.meta_value REGEXP '" . $filter . "') )";
			}

			//handle custom user filters
			if ( is_array($this->woaf_custom_filters) && !empty($this->woaf_custom_filters) ) {
				foreach ($this->woaf_custom_filters as $user_filter) {
					if ( isset( $_GET[$user_filter['filter-field']] ) && !empty( $_GET[$user_filter['filter-field']] ) ) {
						$filter = trim( sanitize_text_field( $_GET[$user_filter['filter-field']]) );
						$filter = str_replace(self::$filter_search, self::$filter_replace, $filter);
						$filter = $wpdb->_escape($filter);

						$statement = $this->woaf_get_correct_filter_statement( $user_filter['filter-statement'] );

						$where .= " AND $wpdb->posts.ID IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '" . $user_filter['filter-field'] . "' AND meta_value $statement '" . $filter . "' )";
					}
				}
			}
		}
		return $where;
	}

	function woaf_filter_date_range( $wp_query ) {
		if (
			is_admin()
			&& $wp_query->is_main_query()
			&& isset($_GET['post_type']) && sanitize_text_field($_GET['post_type']) =='shop_order' 
			&& ! empty( $_GET['woaf_filter_start_date'] )
			&& ! empty( $_GET['woaf_filter_end_date'] )
		) {
			$from = explode( '/', sanitize_text_field( $_GET['woaf_filter_start_date'] ) );
			$to   = explode( '/', sanitize_text_field( $_GET['woaf_filter_end_date'] ) );

			$from = array_map( 'intval', $from );
			$to   = array_map( 'intval', $to );

			if ( 3 === count( $to )	&& 3 === count( $from ) ) {
				list( $year_from, $month_from, $day_from ) = $from;
				list( $year_to, $month_to, $day_to )       = $to;
			} else {
				return $wp_query;
			}
			$wp_query->set(
				'date_query',
				array(
					'after' => array(
						'year'  => $year_from,
						'month' => $month_from,
						'day'   => $day_from,
					),
					'before' => array(
						'year'  => $year_to,
						'month' => $month_to,
						'day'   => $day_to,
					),
					'inclusive' => apply_filters( 'woo_orders_filterby_date_range_query_is_inclusive', true ),
					'column'    => apply_filters( 'woo_orders_filterby_date_query_column', 'post_date' ),
				)
			);
		}
		return $wp_query;
	}

	public function woaf_get_correct_filter_statement( $statement ) {
		switch ($statement) {
			case 'equal':
				$statement = 'LIKE';
				break;
			case 'like':
				$statement = 'REGEXP';
				break;
		}
		return $statement;
	}
}

new AOF_Woo_Additional_Order_Default_Filters();