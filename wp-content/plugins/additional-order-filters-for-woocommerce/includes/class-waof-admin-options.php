<?php

/**
 * Additional Order Filters for WooCommerce / Admin options
 *
 * @package   Additional Order Filters for WooCommerce
 * @author    Anton Bond
 * @license   GPL-2.0+
 * @since     1.11
 */

defined( 'ABSPATH' ) || exit;

class AOF_Woo_Additional_Order_Filters_Admin_Options {

	function __construct() {
		add_action( 'admin_menu', [$this, 'woaf_register_admin_menu_page'] );
		add_action( 'admin_menu', [$this, 'woaf_add_plugin_settings_page'] );
	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function woaf_register_admin_menu_page() {
		add_menu_page( 'Additional Filters', __( 'Filters of Orders', 'woaf-plugin' ), 'manage_options', 'additional-order-filters-woocommerce', false, 'dashicons-list-view', 58);
	}

	function woaf_add_plugin_settings_page() {
		add_submenu_page( 'additional-order-filters-woocommerce', __( 'Default Additional Order Filters', 'woaf-plugin' ), __( 'Default Additional Order Filters', 'woaf-plugin' ), 'manage_options', 'additional-order-filters-woocommerce', array( $this, 'woaf_show_default_filters_settings' ) );

		add_submenu_page( 'additional-order-filters-woocommerce', __( 'Custom Additional Order Filters', 'woaf-plugin' ), __( 'Custom Additional Order Filters', 'woaf-plugin' ), 'manage_options', 'custom-additional-order-filters', array( $this, 'woaf_show_custom_filters_settings' ) );
	}

	function woaf_saving_default_filters_settings() {
		if ( isset($_POST['ant_waof_save_settings']) ) {
			if ( function_exists('check_admin_referer') ) {
				check_admin_referer('ant_waof_save_settings');
			}
			if ( !empty($_POST['filters']) ) {
				$enabled_filters = $_POST['filters'];

				$filters = [];
				foreach ($enabled_filters as $filter) {
					$filters[] = sanitize_text_field( $filter );
				}
			}

			if ( !empty($_POST['woaf_per_column']) ) {
				if ( is_numeric($_POST['woaf_per_column']) ) {
					sanitize_text_field( update_option( 'woaf_per_column', $_POST['woaf_per_column'] ) );
				}
			}

			if ( !empty($filters) )
				update_option( 'ant_additional_order_enabled_filters', serialize( $filters ) );
			else
				update_option( 'ant_additional_order_enabled_filters', '' );

			return true;
		}
	}

	function woaf_show_default_filters_settings() {
		$save_settings = $this->woaf_saving_default_filters_settings();

		$output = '<div class="wrap">';
		$output .= '<h1>'.get_admin_page_title().'</h1>';
		$output .= '<p>'.__( 'Active filters:', 'woaf-plugin' ).'</p>';

		$output .= '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'utf-8').'?page=additional-order-filters-woocommerce&update=true" method="POST" id="ant_waof_save_settings">';
		if ( function_exists('wp_nonce_field') ) {
			$output .= wp_nonce_field('ant_waof_save_settings');
		}

		$filters         = $this->woaf_get_defaul_filters();
		$enabled_filters = $this->woaf_enabled_additional_filters();
		$per_column      = ( get_option( 'woaf_per_column' ) ) ? get_option( 'woaf_per_column' ) : '4' ;

		if ( !empty($filters) ) {
			$output .= "<ul class='waof_enebled_filters'>";
			foreach ($filters as $filter) {
				$output .= "<li>";
				if ( !empty($enabled_filters) && in_array( $filter['id'], $enabled_filters ) ) {
					$output .= "<input type='checkbox' id='".$filter['id']."' name='filters[]' checked value='".$filter['id']."'>";
				} else {
					$output .= "<input type='checkbox' id='".$filter['id']."' name='filters[]' value='".$filter['id']."'>";
				}
				$output .= "<label for='".$filter['id']."'>".$filter['name']."</label>";
				if ( isset($filter['desc']) ) {
					$output .= "<p class='description'>".$filter['desc']."</p>";
				}
				$output .= "</li>";
			}
			$output .= "</ul>";
		}

		$output .= '<div class="select_buttons"><input id="select_all_filters" class="button" value="'.__( 'Select all', 'woaf-plugin' ).'" type="button"><input id="deselect_all_filters" class="button" value="'.__( 'Deselect all', 'woaf-plugin' ).'" type="button"></div>';
		$output .= '<div class="option_block">';
		$output .= '<label for="woaf_per_column">'.__( 'Number of filters in the column:', 'woaf-plugin' ).'</label>';
		$output .= '<input type="number" name="woaf_per_column" id="woaf_per_column" min="2" max="7" required="" value="'.$per_column.'">';
		$output .= '</div>';
		if ( $save_settings ) {
			$output .= '<p class="set_saved">Settings saved</p>';
		}
		$output .= '<input name="ant_waof_save_settings" id="submit" class="button button-primary" value="'.__( 'Save Changes', 'woaf-plugin' ).'" type="submit">';
		$output .= '</form>';
		$output .= '</div>'; // .wrap

		echo $output;
	}

	function woaf_show_custom_filters_settings() {
		$save_settings = $this->woaf_saving_custom_filters_settings();

		$filters = $this->woaf_get_custom_filters();

		$output = '<div class="wrap">';
		$output .= '<h1>'.get_admin_page_title().'</h1>';
			$output .= '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'utf-8').'?page=custom-additional-order-filters&update=true" name="woaf-сustom-additional-order-filters" id="woaf-сustom-additional-order-filters" method="POST">';
				if ( function_exists('wp_nonce_field') ) {
					$output .= wp_nonce_field('waof_save_custom_filters_settings');
				}
				$output .= '<table class="widefat table-custom-filters">';
					$output .= '<thead>';
						$output .= '<tr>';
							$output .= '<th>'.__('Name of filter', 'woaf-plugin').'</th>';
							$output .= '<th class="text-center">'.__('Statement', 'woaf-plugin').'</th>';
							$output .= '<th>'.__('Name of field', 'woaf-plugin').'</th>';
							$output .= '<th class="text-center">'.__('Remove', 'woaf-plugin').'</th>';
						$output .= '</tr>';
					$output .= '</thead>';
					if ( !empty($filters) && is_array($filters) ) {
						$statements = $this->get_custom_field_statements();
						$i = 0;
						foreach ($filters as $count => $filter) {
							$output .= '<tr>';
								$output .= '<td><input type="text" data-name="filter-name" name="filter_rows['.$i.'][filter-name]" value="' . esc_attr( $filter['filter-name'] ) . '" placeholder="Filter name"></td>';
								$output .= '<td class="text-center"><select data-name="filter-statement" name="filter_rows['.$i.'][filter-statement]">';
									foreach ( $statements as $key => $stat ) {
										$selected = ( $filter['filter-statement'] == $key ) ? ' selected="selected"' : '';
										$output .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $stat ) . '</option>';
									}
								$output .= '</select></td>';
								$output .= '<td><select class="select2" data-name="filter-field" name="filter_rows['.$i.'][filter-field]" id="filter_rows['.$i.'][filter-field]">';

									if ( $filter['filter-field'] ) {
										$output .= '<option value="' . esc_attr( $filter['filter-field'] ) . '" selected="selected">' . esc_html( $filter['filter-field'] ) . '</option>';
									}

									$output .= '</select></td>';
								$output .= '<td class="text-center"><a href="#" class="remove_row"><span class="dashicons dashicons-no"></span></a></td>';
							$output .= '</tr>';

							$i++;
						}
					} else {
						$output .= '<tbody>
										<tr>
											<td class="woaf-custom-filter-blank-state" colspan="4"><p>'.__('No custom filters have been added.', 'woaf-plugin').'</p></td>
										</tr>
									</tbody>';
					}
					$output .= '<tfoot>
									<tr>
										<td colspan="4">
											<button type="submit" name="save" class="button button-primary woaf-save-custom-filters" value="woaf-save-custom-filters">'.__('Save custom filters', 'woaf-plugin').'</button>
											<a class="button button-secondary woaf-add-custom-filter" href="#">'.__('Add custom filter', 'woaf-plugin').'</a>
										</td>
									</tr>
								</tfoot>';
				$output .= '</table>';
			$output .= '</form>';

		$output .= '</div>'; // .wrap

		$order_keys_json = $this->get_existing_order_keys_json();

		if ( $order_keys_json ) {
			$output .= '<script>var order_keys_json = '.json_encode($order_keys_json).' ;</script>';
		}

		echo $output;
	}

	function woaf_get_defaul_filters() {
		$filters = array();
		$filters[0]['name'] = __( 'Order Statuses', 'woaf-plugin' );
		$filters[0]['id']   = 'order_statuses';

		$filters[1]['name'] = __( 'Payment Method', 'woaf-plugin' );
		$filters[1]['id']   = 'payment_method';

		$filters[2]['name'] =  __( 'Customer Group', 'woaf-plugin' );
		$filters[2]['id']   = 'customer_group';

		$filters[3]['name'] = __( 'Shipping Method', 'woaf-plugin' );
		$filters[3]['id']   = 'shipping_method';

		$filters[4]['name'] = __( 'Customer Email', 'woaf-plugin' );
		$filters[4]['id']   = 'customer_email';

		$filters[5]['name'] = __( 'Customer First Name', 'woaf-plugin' );
		$filters[5]['id']   = 'customer_first_name';

		$filters[6]['name'] = __( 'Customer Last Name', 'woaf-plugin' );
		$filters[6]['id']   = 'customer_last_name';

		$filters[7]['name'] = __( 'Customer Billing Address', 'woaf-plugin' );
		$filters[7]['id']   = 'customer_billing_address';

		$filters[8]['name'] = __( 'Customer Billing Country', 'woaf-plugin' );
		$filters[8]['id']   = 'billing_country';

		$filters[9]['name'] = __( 'Customer Phone', 'woaf-plugin' );
		$filters[9]['id']   = 'customer_phone';

		$filters[10]['name'] = __( 'Track Number', 'woaf-plugin' );
		$filters[10]['desc'] = __( 'This filter requires <a href="https://woocommerce.com/products/shipment-tracking/" target="_blank">Shipment Tracking</a> plugin.', 'woaf-plugin' );
		$filters[10]['id']   = 'track_number';

		$filters[11]['name'] = __( 'Search by SKU Number', 'woaf-plugin' );
		$filters[11]['id']   = 'search_by_sku';

		$filters[12]['name'] = __( 'Orders by Date Range', 'woaf-plugin' );
		$filters[12]['id']   = 'orders_by_date_range';

		$filters[13]['name'] = __( 'Order Total', 'woaf-plugin' );
		$filters[13]['id']   = 'filter_order_total';

		return $filters;
	}

	public static function woaf_get_custom_filters() {
		$custom_filters = get_option('woaf_custom_filters');
		if ( empty($custom_filters) )
			return;
		//remove extra data
		unset($custom_filters['ID']);
		unset($custom_filters['filter']);

		return $custom_filters;
	}

	function woaf_saving_custom_filters_settings() {
		if ( isset($_POST['save']) && $_POST['save'] == 'woaf-save-custom-filters' ) {
			if ( function_exists('waof_save_custom_filters_settings') ) {
				check_admin_referer('waof_save_custom_filters_settings');
			}
			if ( !empty($_POST['filter_rows']) ) {
				$filter_rows = sanitize_post( $_POST['filter_rows'], 'db' );
				update_option( 'woaf_custom_filters', $filter_rows );
			} else {
				update_option( 'woaf_custom_filters', '' );
			}
		}
	}

	public static function woaf_get_filters() {
		$filters = [];

		$filters[0]['name'] = __( 'Order Statuses', 'woaf-plugin' );
		$filters[0]['id']   = 'order_statuses';

		$filters[1]['name'] = __( 'Payment Method', 'woaf-plugin' );
		$filters[1]['id']   = 'payment_method';

		$filters[2]['name'] =  __( 'Customer Group', 'woaf-plugin' );
		$filters[2]['id']   = 'customer_group';

		$filters[3]['name'] = __( 'Shipping Method', 'woaf-plugin' );
		$filters[3]['id']   = 'shipping_method';

		$filters[4]['name'] = __( 'Customer Email', 'woaf-plugin' );
		$filters[4]['id']   = 'customer_email';

		$filters[5]['name'] = __( 'Customer First Name', 'woaf-plugin' );
		$filters[5]['id']   = 'customer_first_name';

		$filters[6]['name'] = __( 'Customer Last Name', 'woaf-plugin' );
		$filters[6]['id']   = 'customer_last_name';

		$filters[7]['name'] = __( 'Customer Billing Address', 'woaf-plugin' );
		$filters[7]['id']   = 'customer_billing_address';

		$filters[8]['name'] = __( 'Customer Billing Country', 'woaf-plugin' );
		$filters[8]['id']   = 'billing_country';

		$filters[9]['name'] = __( 'Customer Phone', 'woaf-plugin' );
		$filters[9]['id']   = 'customer_phone';

		$filters[10]['name'] = __( 'Track Number', 'woaf-plugin' );
		$filters[10]['desc'] = __( 'This filter requires <a href="https://woocommerce.com/products/shipment-tracking/" target="_blank">Shipment Tracking</a> plugin.', 'woaf-plugin' );
		$filters[10]['id']   = 'track_number';

		$filters[11]['name'] = __( 'Search by SKU Number', 'woaf-plugin' );
		$filters[11]['id']   = 'search_by_sku';

		$filters[12]['name'] = __( 'Orders by Date Range', 'woaf-plugin' );
		$filters[12]['id']   = 'orders_by_date_range';

		$filters[13]['name'] = __( 'Order Total', 'woaf-plugin' );
		$filters[13]['id']   = 'filter_order_total';

		return $filters;
	}

	public static function woaf_enabled_additional_filters() {
		$enabled_filters = get_option( 'ant_additional_order_enabled_filters' );
		if ( !empty( $enabled_filters ) ) {
			$enabled_filters = unserialize( $enabled_filters );
		}
		return $enabled_filters;
	}

	function get_custom_field_statements() {
		$statements= array(
			'equal' => '=',
			'like'  => 'like'
		);

		return $statements;
	}

	function get_existing_order_keys_json() {
		$last_order = $this->get_last_order_id();

		$fields_of_order = get_post_custom($last_order);
		$json = [];

		if ( is_array($fields_of_order) ) {
			foreach ($fields_of_order as $key => $field) {
				$json[] = $key;
			}
		}

		return $json;
	}

	function get_last_order_id(){
		global $wpdb;
		$statuses = array_keys(wc_get_order_statuses());
		$statuses = implode( "','", $statuses );

		// Getting last Order ID (max value)
		$results = $wpdb->get_col( "
			SELECT MAX(ID) FROM {$wpdb->prefix}posts
			WHERE post_type LIKE 'shop_order'
			AND post_status IN ('$statuses')
		" );
		return reset($results);
	}
}

new AOF_Woo_Additional_Order_Filters_Admin_Options();