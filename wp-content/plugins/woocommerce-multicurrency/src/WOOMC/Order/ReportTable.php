<?php
/**
 * Orders Report Table
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Order;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\NumberUtil;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Log;
use WOOMC\Rate;

if ( ! class_exists( 'WP_List_Table' ) ) {
	/**
	 * Path.
	 *
	 * @noinspection PhpIncludeInspection
	 */
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class ReportTable
 *
 * @package WOOMC\Order
 */
class ReportTable extends \WP_List_Table {

	/**
	 * Nonce name.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const NONCE_NAME = 'woomc_nonce_name_orders';

	/**
	 * Nonce action.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const NONCE_ACTION = 'woomc_nonce_action_orders';

	/**
	 * The Order post type.
	 *
	 * @var string
	 */
	const POST_TYPE = 'shop_order';

	/**
	 * Prefix for the converted field names.
	 *
	 * @var string
	 */
	const PREFIX_CONVERTED = 'converted__';

	/**
	 * Marker for the converted fields headings.
	 */
	const MARKER = '*';

	/**
	 * Rows per page - min.
	 *
	 * @var int
	 */
	const PER_PAGE_MIN = 1;

	/**
	 * Rows per page - max.
	 *
	 * @var int
	 */
	const PER_PAGE_MAX = 1000;

	/**
	 * Rows per page - default.
	 *
	 * @var int
	 */
	const PER_PAGE_DEFAULT = 20;

	/**
	 * Max items.
	 *
	 * @var int
	 */
	protected $max_items = 0;

	/**
	 * DI.
	 *
	 * @var Rate\Storage
	 */
	protected $rate_storage;

	/**
	 * Store currency - now. Used as default if not in the order meta.
	 *
	 * @var string
	 */
	protected $store_currency_now;

	/**
	 * Rows per page.
	 *
	 * @var int
	 */
	protected $per_page = self::PER_PAGE_DEFAULT;

	/**
	 * Totals.
	 *
	 * @var float[]
	 */
	protected $totals = array();

	/**
	 * To access the array in {@see get_columns}
	 *
	 * @var string[]
	 */
	protected $columns = array();

	/**
	 * Constructor.
	 *
	 * @param Rate\Storage $rate_storage Rate Storage instance.
	 */
	public function __construct( $rate_storage ) {

		$this->rate_storage       = $rate_storage;
		$this->store_currency_now = \get_option( 'woocommerce_currency' );

		$requested_per_page = (int) Env::get_http_get_parameter( 'per_page' );
		if ( $requested_per_page ) {
			$this->per_page = min( max( self::PER_PAGE_MIN, $requested_per_page ), self::PER_PAGE_MAX );
		}

		parent::__construct(
			array(
				'singular' => 'order',
				'plural'   => 'orders',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Output the report.
	 */
	public function output_report() {

		$this->prepare_items();

		?>
		<div id="poststuff" class="woocommerce-reports-wide">
			<?php $this->display(); ?>
		</div>

		<h2><?php \esc_html_e( 'This page totals:', 'woocommerce-multicurrency' ); ?></h2>
		<table class="widefat striped fixed" style="max-width: 30em">
			<?php foreach ( $this->totals as $column_name => $total ) { ?>
				<tr>
					<th class="alignleft"><?php echo \esc_html( $this->columns[ $column_name ] ); ?></th>
					<td class="alignright"><?php echo \wp_kses_post( \wc_price( $total ) ); ?></td>
				</tr>
			<?php } ?>
		</table>
		<?php
	}

	/**
	 * Get column value.
	 *
	 * @param \WC_Order $item        Item (order object).
	 * @param string    $column_name Column name.
	 */
	public function column_default( $item, $column_name ) {

		$values = $this->calculate( $item );

		if ( self::PREFIX_CONVERTED === substr( $column_name, 0, strlen( self::PREFIX_CONVERTED ) ) ) {
			// Calculated value.
			$actual_column = substr( $column_name, strlen( self::PREFIX_CONVERTED ) );
			$value         = (float) $values[ $actual_column ] * (float) $values['rate'];

			// Totals on this page.
			if ( isset( $this->totals[ $column_name ] ) ) {
				$this->totals[ $column_name ] += $value;
			} else {
				$this->totals[ $column_name ] = $value;
			}

			// Round for display
			$value = NumberUtil::round( $value, \wc_get_price_decimals() );

		} else {
			// Regular value.
			$value = $values[ $column_name ];
		}

		if ( 'rate' === $column_name ) {
			$value = NumberUtil::round( $value, 4 );
		}

		echo \esc_html( $value );
	}

	/**
	 * Calculate the table row values.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return array
	 */
	protected function calculate( $order ) {

		if ( ! $order instanceof \WC_Order ) {
			Log::error( new \Exception( 'Received not an order object' ) );

			return array();
		}

		// Cache: this method is called for each column in the row.
		static $cache = array();

		$order_id = $order->get_id();

		if ( isset( $cache[ $order_id ] ) ) {
			return $cache[ $order_id ];
		}

		$store_currency = $order->get_meta( Meta::PREFIX . 'store_currency', true, 'edit' );
		if ( ! $store_currency ) {
			$store_currency = $this->store_currency_now;
		}

		$values = array(
			'ID'                 => $order->get_id(),
			'date'               => \wc_format_datetime( $order->get_date_created() ),
			'status'             => \wc_get_order_status_name( $order->get_status() ),
			'order_currency'     => $order->get_currency(),
			'store_currency'     => $store_currency,
			'order_total'        => $order->get_total( 'edit' ),
			'order_tax'          => $order->get_total_tax( 'edit' ),
			'order_shipping'     => $order->get_shipping_total( 'edit' ),
			'order_shipping_tax' => $order->get_shipping_tax( 'edit' ),
		);

		$values['rate'] = (float) ( $order->get_meta( Meta::PREFIX . 'rate' ) );
		if ( ! $values['rate'] ) {
			$values['rate'] = $this->rate_storage->get_rate( $values['store_currency'], $values['order_currency'] );
		}

		$cache[ $order_id ] = $values;

		return $values;
	}


	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		$this->columns = array(
			'ID'                                          => __( 'Order', 'woocommerce' ),
			'date'                                        => __( 'Date', 'woocommerce' ),
			'status'                                      => __( 'Status', 'woocommerce' ),
			'order_currency'                              => __( 'Currency', 'woocommerce' ),
			'order_total'                                 => __( 'Order Total', 'woocommerce' ),
			'order_tax'                                   => __( 'Tax amount', 'woocommerce' ),
			'order_shipping'                              => __( 'Shipping', 'woocommerce' ),
			'order_shipping_tax'                          => __( 'Shipping tax amount', 'woocommerce' ),
			'store_currency'                              => __( 'Store Currency', 'woocommerce-multicurrency' ),
			'rate'                                        => __( 'Rate', 'woocommerce-multicurrency' ),
			self::PREFIX_CONVERTED . 'order_total'        => __( 'Order Total', 'woocommerce' ) . self::MARKER,
			self::PREFIX_CONVERTED . 'order_tax'          => __( 'Tax amount', 'woocommerce' ) . self::MARKER,
			self::PREFIX_CONVERTED . 'order_shipping'     => __( 'Shipping', 'woocommerce' ) . self::MARKER,
			self::PREFIX_CONVERTED . 'order_shipping_tax' => __( 'Shipping tax amount', 'woocommerce' ) . self::MARKER,
		);

		return $this->columns;
	}

	/**
	 * Prepare customer list items.
	 */
	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$current_page          = \absint( $this->get_pagenum() );

		$this->get_items( $current_page, $this->per_page );

		/**
		 * Pagination.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $this->max_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $this->max_items / $this->per_page ),
			)
		);
	}

	/**
	 * Retrieve the items from DB.
	 *
	 * @param int $current_page Current page.
	 * @param int $per_page     Results per page.
	 */
	public function get_items( $current_page, $per_page ) {

		if ( isset( $_GET[ self::NONCE_NAME ] ) ) {
			\check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );
		}

		// https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
		$args = array(
			'paginate' => true,
			'orderby'  => 'date',
			'order'    => 'DESC',
			'limit'    => $per_page,
			'paged'    => $current_page,
		);

		$requested_status = Env::get_http_get_parameter( 'order_status' );
		if ( $requested_status ) {
			$args['status'] = $requested_status;
		}

		$year_month = Env::get_http_get_parameter( 'm' );
		// Ensure the input is a valid 'YYYYMM' format
		if ( preg_match( '/^\d{6}$/', $year_month ) === 1 ) {
			// Extract year and month from the input
			$year  = substr( $year_month, 0, 4 );
			$month = substr( $year_month, 4, 2 );

			// Calculate the start and end dates for the month
			$start_date = "{$year}-{$month}-01 00:00:00";
			$end_date   = gmdate( 'Y-m-t 23:59:59', strtotime( $start_date . ' UTC' ) );

			$args['date_query'] = array(
				'after'     => $start_date,
				'before'    => $end_date,
				'inclusive' => true,
			);
		}

		$results         = \wc_get_orders( $args );
		$this->max_items = $results->total;
		$this->items     = $results->orders;
	}

	/**
	 * Extra navigation controls.
	 *
	 * @param string $which Top or bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {

			?>
			<form>
				<input
						type="hidden" name="page"
						value="<?php echo \esc_attr( ReportPage::MENU_SLUG_ORDERS_REPORT ); ?>"/>
				<div class="alignleft actions">
					<?php $this->months_filter(); ?>
				</div>
				<div class="alignleft actions">
					<label for="order_status">
						<?php \esc_html_e( 'Status:' ); ?>
					</label>
				</div>
				<div class="alignleft actions">
					<?php $this->status_dropdown(); ?>
				</div>
				<div class="alignleft actions">
					<label for="per_page">
						<?php \esc_html_e( 'Number of items per page:' ); ?>
					</label>
				</div>
				<div class="alignleft actions">
					<input
							type="number" step="1" min="<?php echo \esc_attr( self::PER_PAGE_MIN ); ?>"
							max="<?php echo \esc_attr( self::PER_PAGE_MAX ); ?>" id="per_page" name="per_page"
							value="<?php echo \esc_attr( $this->per_page ); ?>">
				</div>
				<div class="alignleft actions">
					<?php \wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
					<?php \submit_button( \__( 'Filter', 'woocommerce' ), 'large', false, false ); ?>
				</div>
			</form>
			<?php
		}
	}

	/**
	 * Displays the order status dropdown filter
	 */
	public function status_dropdown() {

		$statuses         = \wc_get_order_statuses();
		$requested_status = Env::get_http_get_parameter( 'order_status' );
		?>

		<select id="order_status" name="order_status" class="wc-enhanced-select">
			<option value="">
				<?php \esc_html_e( 'All', 'woocommerce' ); ?>
			</option>
			<?php
			foreach ( $statuses as $status => $status_name ) {
				echo '<option value="' . \esc_attr( $status ) . '" ' . \selected( $status, $requested_status, false ) . '>' . \esc_html( $status_name ) . '</option>';
			}
			?>
		</select>
		<?php
	}

	/**
	 * Render the months filter dropdown.
	 * A clone of {@see \Automattic\WooCommerce\Internal\Admin\Orders\ListTable::months_filter}
	 *
	 * @since 4.4.1
	 * @return void
	 */
	private function months_filter() {

		global $wp_locale;
		global $wpdb;

		$orders_table = \esc_sql( OrdersTableDataStore::get_orders_table_name() );
		$utc_offset   = \wc_timezone_offset();

		$_fn = 'get_results';

		$order_dates = $wpdb->$_fn(
			"
				SELECT DISTINCT YEAR( t.date_created_local ) AS year,
								MONTH( t.date_created_local ) AS month
				FROM ( SELECT DATE_ADD( date_created_gmt, INTERVAL $utc_offset SECOND ) AS date_created_local FROM $orders_table WHERE status != 'trash' ) t
				ORDER BY year DESC, month DESC
			"
		);

		$m = max( (int) Env::get_http_get_parameter( 'm' ), 0 );
		echo '<select name="m" id="filter-by-date">';
		echo '<option ' . \selected( $m, 0, false ) . ' value="0">' . \esc_html__( 'All dates', 'woocommerce' ) . '</option>';

		foreach ( $order_dates as $date ) {
			$month           = \zeroise( $date->month, 2 );
			$month_year_text = sprintf(
			/* translators: 1: Month name, 2: 4-digit year. */
				\esc_html_x( '%1$s %2$d', 'order dates dropdown', 'woocommerce' ),
				$wp_locale->get_month( $month ),
				$date->year
			);

			/**
			 * Ignore %1$s warning
			 *
			 * @noinspection HtmlUnknownAttribute
			 */
			printf(
				'<option %1$s value="%2$s">%3$s</option>\n',
				\selected( $m, $date->year . $month, false ),
				\esc_attr( $date->year . $month ),
				\esc_html( $month_year_text )
			);
		}

		echo '</select>';
	}
}
