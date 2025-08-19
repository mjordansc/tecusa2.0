<?php

/**
 * Additional Order Filters for WooCommerce / Default filters for HPOS storage
 *
 * @package   Additional Order Filters for WooCommerce
 * @author    Anton Bond facebook.com/antonbondarevych
 * @license   GPL-2.0+
 * @since     1.20
 */

defined( 'ABSPATH' ) || exit;

class AOF_Woo_Additional_Order_Default_Filters_HPOS_Storage {

	private static $filter_search  = array( '*', ', ', ',' );
	private static $filter_replace = array( '%', '|', '|' );

	protected $woaf_enabled_additional_filters;
	protected $woaf_custom_filters;

    function __construct()
    {
        add_action( 'admin_menu', [$this, 'woaf_add_plugin_settings_page'] );

        $this->woaf_enabled_additional_filters = AOF_Woo_Additional_Order_Filters_Admin_Options::woaf_enabled_additional_filters();
        $this->woaf_custom_filters = AOF_Woo_Additional_Order_Filters_Admin_Options::woaf_get_custom_filters();
    }

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

    function woaf_add_plugin_settings_page()
    {
        add_action( 'woocommerce_order_list_table_extra_tablenav', [$this, 'woaf_show_filters_content'], 10, 2 );
        add_filter( 'woocommerce_order_list_table_prepare_items_query_args', [$this, 'woaf_filter_order_list_table_prepare_items_query_args'] );

        add_filter( 'woocommerce_orders_table_query_clauses', [$this, 'woaf_woocommerce_hpos_pre_query'], 10, 1 );
    }

    function woaf_show_filters_content( $order, $which )
    {
        if ( $which !== 'top')
            return;

        if ( ! $this->woaf_validate_if_hpos_orders_page() )
            return;

        $enabled_filters = $this->woaf_enabled_additional_filters;
        if ( empty( $enabled_filters ) )
            return;

        $output = '';

        $output .= '<div class="woaf_show_filters_button_wrapper">';
            $output .= '<a href="" onclick="event.preventDefault()" id="woaf_show_filters" class="button action">'.__( 'Additional Filters', 'woaf-plugin' ).'</a>';

        $output .= '</div>'; // .woaf_show_filters_button_wrapper

        $filters = AOF_Woo_Additional_Order_Filters_Admin_Options::woaf_get_filters();


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
                                $selected = ( isset($_GET['payment_customer_filter']) ) ? $this->woaf_sanitize_get_parameter($_GET['payment_customer_filter']) : '';
                                
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
                                
                                $selected = ( isset($_GET['nonregistered_users_filter']) ) ? $this->woaf_sanitize_get_parameter($_GET['nonregistered_users_filter']) : '';

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

                                $shipping_method_filter = (isset( $_GET['shipping_method_filter'] )) ? $this->woaf_sanitize_get_parameter($_GET['shipping_method_filter']) : '';

                                $output .= '<label for="shipping_method_filter">'.$filter["name"].'</label>';
                                $output .= '<input type="text" value="'.$shipping_method_filter.'" name="shipping_method_filter" id="shipping_method_filter">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'customer_email' ) :
                                $output .= '<div class="order_block_wrapper">';

                                $user_email_search = (isset( $_GET['user_email_search'] )) ? $this->woaf_sanitize_get_parameter($_GET['user_email_search']) : '';

                                $output .= '<label for="user_email_search">'.$filter["name"].'</label>';
                                $output .= '<input type="text" value="'.$user_email_search.'" name="user_email_search" id="user_email_search">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'customer_first_name' ) :
                                $output .= '<div class="order_block_wrapper">';
                                
                                $user_billing_first_name = (isset( $_GET['user_billing_first_name'] )) ? $this->woaf_sanitize_get_parameter($_GET['user_billing_first_name']) : '';

                                $output .= '<label for="user_billing_first_name">'.$filter["name"].'</label>';
                                $output .= '<input type="text" value="'.$user_billing_first_name.'" name="user_billing_first_name" id="user_billing_first_name">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'customer_last_name' ) :
                                $output .= '<div class="order_block_wrapper">';

                                $user_billing_last_name = (isset( $_GET['user_billing_last_name'] )) ? $this->woaf_sanitize_get_parameter($_GET['user_billing_last_name']) : '';

                                $output .= '<label for="user_billing_last_name">'.$filter["name"].'</label>';
                                $output .= '<input type="text" value="'.$user_billing_last_name.'" name="user_billing_last_name" id="user_billing_last_name">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'customer_billing_address' ) :
                                $output .= '<div class="order_block_wrapper">';
                                
                                $user_billing_address = (isset( $_GET['user_billing_address'] )) ? $this->woaf_sanitize_get_parameter($_GET['user_billing_address']) : '';

                                $output .= '<label for="user_billing_address">'.$filter["name"].'</label>';
                                $output .= '<input type="text" value="'.$user_billing_address.'" name="user_billing_address" id="user_billing_address">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'billing_country' ) :
                                $output .= '<div class="order_block_wrapper">';

                                    $user_billing_country = (isset( $_GET['user_billing_country'] )) ? $this->woaf_sanitize_get_parameter($_GET['user_billing_country']) : '';
                                    
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

                                $user_phone = (isset( $_GET['user_phone'] )) ? $this->woaf_sanitize_get_parameter($_GET['user_phone']) : '';

                                $output .= '<label for="user_phone">'.$filter["name"].'</label>';
                                $output .= '<input type="text" value="'.$user_phone.'" name="user_phone" id="user_phone">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'track_number' ) :
                                $output .= '<div class="order_block_wrapper">';

                                $shpping_track_number = (isset( $_GET['shpping_track_number'] )) ? $this->woaf_sanitize_get_parameter($_GET['shpping_track_number']) : '';

                                $output .= '<label for="shpping_track_number">'.$filter["name"].'</label>';
                                $output .= '<input type="text" value="'.$shpping_track_number.'" name="shpping_track_number" id="shpping_track_number">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'search_by_sku' ) :
                                $output .= '<div class="order_block_wrapper">';

                                $woaf_filter_search_sku = (isset( $_GET['woaf_filter_search_sku'] )) ? $this->woaf_sanitize_get_parameter($_GET['woaf_filter_search_sku']) : '';

                                $output .= '<label for="woaf_filter_search_sku">'.$filter["name"].'</label>';
                                $output .= '<input type="text" value="'.$woaf_filter_search_sku.'" name="woaf_filter_search_sku" id="woaf_filter_search_sku">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'orders_by_date_range' ) :
                                $output .= '<div class="order_block_wrapper date_range">';
                                $output .= '<label for="woaf_filter_start_date">'.$filter["name"].'</label>';
                                $from = ( isset($_GET['woaf_filter_start_date']) ) ? $this->woaf_sanitize_get_parameter( $_GET['woaf_filter_start_date'] ) : '';
                                $to   = ( isset($_GET['woaf_filter_end_date']) ) ? $this->woaf_sanitize_get_parameter( $_GET['woaf_filter_end_date'] ) : '';
                                $output .= '<input type="text" id="woaf_filter_start_date" name="woaf_filter_start_date" value="'.$from.'" placeholder="'.__( 'Start date', 'woaf-plugin' ).'">';
                                $output .= '<input type="text" id="woaf_filter_end_date" value="'.$to.'" name="woaf_filter_end_date" placeholder="'.__( 'End date', 'woaf-plugin' ).'">';
                                $output .= '</div>';
                            endif;
                            if ( $filter['id'] == 'filter_order_total' ) :
                                $output .= '<div class="order_block_wrapper order_total">';
                                $order_total_start = (isset( $_GET['order_total_start'] )) ? $this->woaf_sanitize_get_parameter($_GET['order_total_start']) : '';
                                $order_total_end = (isset( $_GET['order_total_end'] )) ? $this->woaf_sanitize_get_parameter($_GET['order_total_end']) : '';
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

                                $filter_search = (isset( $_GET[$filter['filter-field']] )) ? $this->woaf_sanitize_get_parameter($_GET[$filter['filter-field']]) : '';
                                    
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
            $output .= '</div>'; // .woaf_special_order_filter_wrapper

        }

        echo $output;
    }

    function woaf_filter_order_list_table_prepare_items_query_args($query_vars)
    {   
        if ( ! $this->woaf_validate_if_hpos_orders_page() )
            return $query_vars;

        $get = $_GET;

        //order status
        if ( isset($get['post_status']) && !empty($get['post_status']) && is_array($get['post_status']) ) {
            foreach ($get['post_status'] as $key => $value) {
                $query_vars['post_status'][] = $this->woaf_sanitize_get_parameter($value);
            }
        }

        //payment_customer_filter
        if ( isset($get['payment_customer_filter']) && !empty($get['payment_customer_filter']) ) {
            $query_vars['payment_method'] = $this->woaf_sanitize_get_parameter($get['payment_customer_filter']);
        }
    
        //user_email_search
        if ( isset($get['user_email_search']) && $get['user_email_search'] !== '' ) {
            $query_vars['billing_email'] = $this->woaf_sanitize_get_parameter($get['user_email_search']);
        }
    
        if ( isset($get['woaf_filter_start_date']) && isset($get['woaf_filter_end_date']) ) {
			$from = explode( '/', $this->woaf_sanitize_get_parameter( $_GET['woaf_filter_start_date'] ) );
			$to   = explode( '/', $this->woaf_sanitize_get_parameter( $_GET['woaf_filter_end_date'] ) );

			$from = array_map( 'intval', $from );
			$to   = array_map( 'intval', $to );

			if ( 3 === count( $to )	&& 3 === count( $from ) ) {
				list( $year_from, $month_from, $day_from ) = $from;
				list( $year_to, $month_to, $day_to )       = $to;

                $query_vars['date_query'] = 
                [
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
                    // 'column'    => apply_filters( 'woo_orders_filterby_date_query_column', 'post_date' ),
                ];
			}
        }

        //user_billing_first_name
        if ( isset($get['user_billing_first_name']) && $get['user_billing_first_name'] !== '' ) {
            $query_vars['billing_first_name'] = $this->woaf_sanitize_get_parameter($get['user_billing_first_name']);
        }

        //user_billing_last_name
        if ( isset($get['user_billing_last_name']) && $get['user_billing_last_name'] !== '' ) {
            $query_vars['billing_last_name'] = $this->woaf_sanitize_get_parameter($get['user_billing_last_name']);
        }

        //user_billing_address
        if ( isset($get['user_billing_address']) && $get['user_billing_address'] !== '' ) {
            $query_vars['_billing_address_1'] = $this->woaf_sanitize_get_parameter($get['user_billing_address']);
        }
        
		//handle custom user filters
        if ( is_array($this->woaf_custom_filters) && !empty($this->woaf_custom_filters) ) {
            $query_vars['meta_query'] = ['relation' => 'OR'];
            foreach ($this->woaf_custom_filters as $user_filter) {
                if ( isset( $get[$user_filter['filter-field']] ) && !empty( $get[$user_filter['filter-field']] ) ) {
                    $filter = $this->woaf_sanitize_get_parameter( $get[$user_filter['filter-field']]);
                    $filter = str_replace(self::$filter_search, self::$filter_replace, $filter);

                    $query_vars['meta_query'][] = [
                        'key'     => $user_filter['filter-field'],
                        'value'   => $filter,
                        'compare' => $user_filter['filter-statement'],
                    ];
                }
            }
        }

        return $query_vars;
    }

	function woaf_woocommerce_hpos_pre_query( $clauses ) {
        global $wpdb;
        if ( ! $this->woaf_validate_if_hpos_orders_page() )
            return $clauses;

        $get = $_GET;

        //order total range
        if ( isset( $get['order_total_start'] ) && !empty( $get['order_total_start'] ) || isset( $get['order_total_end'] ) && !empty( $get['order_total_end'] ) ) {
            $start =  $this->woaf_sanitize_get_parameter($get['order_total_start']);
            $end   =  $this->woaf_sanitize_get_parameter($get['order_total_end']);

            $start = " AND {$wpdb->prefix}wc_orders.total_amount >= '$start'";
            $end   = " AND wp_wc_orders.total_amount <= '$end'";
            $clauses['where'] .= $start.$end;
        }

        //Customer Group
        if ( isset($get['nonregistered_users_filter']) && $get['nonregistered_users_filter'] !== '' ) {
            if ( $get['nonregistered_users_filter'] == 'nonregistered_users' )
                $clauses['where'] .= " AND {$wpdb->prefix}wc_orders.customer_id = '0'";
            else
                $clauses['where'] .= " AND {$wpdb->prefix}wc_orders.customer_id > '0'";
        }

        // shipping_method_filter
        if ( isset($get['shipping_method_filter']) && $get['shipping_method_filter'] !== '' ) {
            $shipping_method = $this->woaf_sanitize_get_parameter($get['shipping_method_filter']);
            $clauses['where'] .= " AND {$wpdb->prefix}wc_orders.id IN (SELECT ".$wpdb->prefix."woocommerce_order_items.order_id FROM ".$wpdb->prefix."woocommerce_order_items WHERE order_item_type = 'shipping' AND order_item_name REGEXP '".$shipping_method."' )";
        }

        // search by track number
        if ( isset( $get['shpping_track_number'] ) && $get['shpping_track_number'] !== '' ) { 
            $filter  = trim( $this->woaf_sanitize_get_parameter($get['shpping_track_number']) );
            $filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
            $filter  = $wpdb->_escape($filter);
            if ( is_plugin_active( 'woocommerce-shipment-tracking/shipment-tracking.php' ) ) {
                $clauses['where'] .= " AND {$wpdb->prefix}wc_orders.id IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = '_wc_shipment_tracking_items' AND meta_value REGEXP '\"tracking_number\";s:" . $filter . "%' )";
            } elseif ( is_plugin_active( 'woo-shipment-tracking-order-tracking/woocommerce-shipment-tracking.php' ) ) {
                $clauses['where'] .= " AND {$wpdb->prefix}wc_orders.id IN (SELECT ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE meta_key = 'wf_wc_shipment_source' AND meta_value REGEXP '\"shipment_id_cs\";s:" . $filter . "%' )";
            }
        }

        // search by SKU
        if ( isset( $get['woaf_filter_search_sku'] ) && !empty( $get['woaf_filter_search_sku'] ) ) { 
            $filter  = trim($get['woaf_filter_search_sku']);
            $filter  = str_replace(self::$filter_search, self::$filter_replace, $filter);
            $filter  = $wpdb->_escape($filter);

            $clauses['where'] .= " AND ({$wpdb->prefix}wc_orders.id IN(
            SELECT {$wpdb->prefix}wc_orders.id FROM $wpdb->posts
            INNER JOIN " . $wpdb->prefix . "woocommerce_order_items ON {$wpdb->prefix}wc_orders.id = " . $wpdb->prefix . "woocommerce_order_items.order_id
            INNER JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta ON " . $wpdb->prefix . "woocommerce_order_items.order_item_id = " . $wpdb->prefix . "woocommerce_order_itemmeta.order_item_id
            INNER JOIN $wpdb->postmeta ON " . $wpdb->prefix . "woocommerce_order_itemmeta.meta_value = $wpdb->postmeta.post_id
            WHERE $wpdb->posts.post_type = 'shop_order'
            AND " . $wpdb->prefix . "woocommerce_order_items.order_item_type = 'line_item'
            AND " . $wpdb->prefix . "woocommerce_order_itemmeta.meta_key = '_product_id'
            AND $wpdb->postmeta.meta_key = '_sku'
            AND $wpdb->postmeta.meta_value REGEXP '" . $filter . "') )";
        }

        return $clauses;
	}

    function woaf_validate_if_hpos_orders_page()
    {
        global $pagenow;
        
        if ( (isset($_GET['action']) && $_GET['action'] === 'edit') )
            return false;
        elseif ( is_admin() && $pagenow == 'admin.php' && $_GET['page'] === 'wc-orders' )
            return true;
    }

    function woaf_sanitize_get_parameter($parameter)
    {
        return esc_attr( sanitize_text_field( $parameter ) );
    }

}

new AOF_Woo_Additional_Order_Default_Filters_HPOS_Storage();