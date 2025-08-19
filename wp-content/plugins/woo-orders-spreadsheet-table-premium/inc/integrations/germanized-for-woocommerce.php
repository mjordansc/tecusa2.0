<?php

if (!class_exists('WPSE_Orders_Germanized_For_WooCommerce')) {

	class WPSE_Orders_Germanized_For_WooCommerce {

		static private $instance = false;
		var $post_type = 'shop_order';

		private function __construct() {
			
		}

		function init() {
			if (!class_exists('WooCommerce_Germanized_Pro')) {
				return;
			}

			add_action('vg_sheet_editor/editor/register_columns', array($this, 'register_columns'));
		}

		/**
		 * Register toolbar items
		 */
		function register_columns($editor) {
			$post_type = $this->post_type;
			if (!in_array($editor->args['provider'], array($post_type))) {
				return;
			}

			$editor->args['columns']->register_item('_germanized_invoices', $post_type, array(
				'get_value_callback' => array($this, 'get_invoices'),
				'title' => __('Germanized : Invoice numbers', vgse_woocommerce_orders()->textname),
				'allow_to_save' => false,
				'column_width' => 200
					), true);
			$editor->args['columns']->register_item('_germanized_invoice_date', $post_type, array(
				'title' => __('Germanized : Invoice date', vgse_woocommerce_orders()->textname),
				'get_value_callback' => array($this, 'get_invoice_date'),
				'allow_to_save' => false,
				'column_width' => 200
					), true);
			$editor->args['columns']->register_item('_germanized_invoice_delivery_status', $post_type, array(
				'title' => __('Germanized : Invoice delivery status', vgse_woocommerce_orders()->textname),
				'get_value_callback' => array($this, 'get_invoice_delivery_status'),
				'allow_to_save' => false,
				'column_width' => 250
					), true);
			$editor->args['columns']->register_item('_germanized_invoice_pdf', $post_type, array(
				'title' => __('Germanized : Invoice PDF', vgse_woocommerce_orders()->textname),
				'get_value_callback' => array($this, 'get_invoice_pdf'),
				'allow_to_save' => false,
				'column_width' => 200
					), true);

			$editor->args['columns']->register_item('_germanized_invoice_status', $post_type, array(
				'get_value_callback' => array($this, 'get_invoice_status'),
				'title' => __('Germanized : Invoice status', vgse_woocommerce_orders()->textname),
				'formatted' => array('editor' => 'select', 'selectOptions' => wc_gzdp_get_invoice_statuses()),
				'allow_to_save' => false,
				'column_width' => 200
					), true);
		}

		function _get_first_invoice($order_id) {

			$order = wc_get_order($order_id);
			$invoices = wc_gzdp_get_invoices_by_order($order);
			return current($invoices);
		}

		function get_invoice_pdf($post, $cell_key, $cell_args) {
			$invoice = $this->_get_first_invoice($post->ID);
			$value = ( is_object($invoice) && $invoice->has_attachment() ) ? $invoice->get_pdf_url() : '';
			return $value;
		}

		function get_invoice_delivery_status($post, $cell_key, $cell_args) {
			$invoice = $this->_get_first_invoice($post->ID);
			$value = ( is_object($invoice) && $invoice->is_delivered() ? sprintf(__('Delivered @ %s', 'woocommerce-germanized-pro'), esc_html($invoice->get_delivery_date())) : __('Not yet delivered', 'woocommerce-germanized-pro') );
			return $value;
		}

		function get_invoice_status($post, $cell_key, $cell_args) {
			$invoice = $this->_get_first_invoice($post->ID);
			$value = '';
			if (is_object($invoice)) {
				$value = ( $invoice->get_status() == "auto-draft" ? wc_gzdp_get_default_invoice_status() : $invoice->get_status() );
			}
			return $value;
		}

		function get_invoice_date($post, $cell_key, $cell_args) {
			$invoice = $this->_get_first_invoice($post->ID);
			return (is_object($invoice)) ? $invoice->get_date('Y-m-d') : '';
		}

		function get_invoices($post, $cell_key, $cell_args) {


			$order = wc_get_order($post);
			$invoices = wc_gzdp_get_invoices_by_order($order);
			$values = array();
			if (!empty($invoices)) {
				foreach ($invoices as $id => $invoice) {
					$values[] = $invoice->get_title();
				}
			}
			return implode(', ', $values);
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPSE_Orders_Germanized_For_WooCommerce::$instance) {
				WPSE_Orders_Germanized_For_WooCommerce::$instance = new WPSE_Orders_Germanized_For_WooCommerce();
				WPSE_Orders_Germanized_For_WooCommerce::$instance->init();
			}
			return WPSE_Orders_Germanized_For_WooCommerce::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPSE_Orders_Germanized_For_WooCommerce_Obj')) {

	function WPSE_Orders_Germanized_For_WooCommerce_Obj() {
		return WPSE_Orders_Germanized_For_WooCommerce::get_instance();
	}

}
WPSE_Orders_Germanized_For_WooCommerce_Obj();
