<?php
/**
 * Edit currency of orders and subscriptions.
 *
 * @since   1.8.0
 */

namespace WOOMC\Admin;

use WOOMC\Abstracts\Hookable;
use WOOMC\API;
use WOOMC\DAO\Factory;
use WOOMC\Log;
use WOOMC\Order\Meta;
use WOOMC\Rate\Storage;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Class EditCurrency
 */
class EditCurrency extends Hookable {

	/**
	 * Nonce name.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'woomc_nonce_name_currency';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'woomc_nonce_action_currency';

	/**
	 * List of the admin screens where currency editing is applicable.
	 *
	 * @var string[]
	 */
	protected $screen = array(
		'shop_order',
		'shop_subscription',
	);

	/**
	 * List of fields for the metabox.
	 *
	 * @var array[]
	 */
	protected $meta_fields = array();

	/**
	 * The Rate Storage instance.
	 *
	 * @since 2.9.4-rc.1
	 * @var  Storage
	 */
	protected $rate_storage;

	/**
	 * EditCurrency constructor.
	 *
	 * @since 2.9.4-rc.1 DI Rate Storage.
	 *
	 * @param Storage $rate_storage The Rate Storage instance.
	 */
	public function __construct( Storage $rate_storage ) {
		$this->rate_storage = $rate_storage;

		$this->meta_fields = array(
			array(
				'label'   => \__( 'Currency', 'woocommerce' ),
				'id'      => '_order_currency',
				'type'    => 'select',
				'default' => \get_woocommerce_currency(),
				'options' => Factory::getDao()->getEnabledCurrencies(),
			),
		);
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		\add_action( 'save_post', array( $this, 'save_fields' ) );
	}

	/**
	 * Add metaboxes.
	 *
	 * @since    1.8.0
	 * @since    2.16.4 HPOS support: use wc_get_page_screen_id.
	 * @internal action
	 */
	public function add_meta_boxes() {
		foreach ( $this->screen as $screen ) {

			$screen = \wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
				? \wc_get_page_screen_id( $screen )
				: $screen;

			\add_meta_box(
				'currency',
				\__( 'Multi-currency options', 'woocommerce-multicurrency' ),
				array( $this, 'meta_box_callback' ),
				$screen,
				'advanced',
				'high'
			);
		}
	}

	/**
	 * Metabox callback.
	 *
	 * @param \WP_Post $post The Post object.
	 *
	 * @internal callback.
	 */
	public function meta_box_callback( $post ) {
		\wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$this->field_generator( $post );
	}

	/**
	 * Metabox content.
	 *
	 * @since    1.8.0
	 * @since    2.16.4 HPOS support: use $order->get_currency().
	 *
	 * @param \WP_Post|\WC_Order $post_or_order_object The Post or Order object.
	 */
	protected function field_generator( $post_or_order_object ) {

		$order = ( $post_or_order_object instanceof \WP_Post ) ? \wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

		$output = '';
		foreach ( $this->meta_fields as $meta_field ) {
			$label = '<label for="' . $meta_field['id'] . '">' . $meta_field['label'] . '</label>';

			if ( '_order_currency' === $meta_field['id'] ) {
				$meta_value = $order->get_currency();
			} else {
				$meta_value = $order->get_meta( $meta_field['id'] );
			}

			if ( empty( $meta_value ) ) {
				$meta_value = $meta_field['default'];
			}
			$input = sprintf(
				'<select id="%s" name="%s">',
				$meta_field['id'],
				$meta_field['id']
			);
			foreach ( $meta_field['options'] as $key => $value ) {
				$meta_field_value = ! is_numeric( $key ) ? $key : $value;

				/**
				 * Inspection %s.
				 *
				 * @noinspection HtmlUnknownAttribute
				 */
				$input .= sprintf(
					'<option %s value="%s">%s</option>',
					$meta_value === $meta_field_value ? 'selected' : '',
					$meta_field_value,
					$value
				);
			}
			$input .= '</select>';

			$output .= $this->format_rows( $label, $input );
		}

		$output .= $this->format_rows( '<span class="dashicons dashicons-warning"></span>',
			implode(
				'<br>',
				array(
					\__( 'To change the currency of this order, select it from the dropdown and click Create/Update.', 'woocommerce-multicurrency' ),
					\__( 'Note: this will only change the currency, not the amounts!', 'woocommerce-multicurrency' ),
					\__( 'To change the amounts, remove the items and add them back.', 'woocommerce-multicurrency' ),
				)
			)
		);

		$allowed_tags = array(
			'option' => array(
				'selected' => true,
				'value'    => true,
			),
			'label'  => array( 'for' => true ),
			'select' => array(
				'id'   => true,
				'name' => true,
			),
			'tr'     => true,
			'th'     => true,
			'td'     => true,
			'br'     => true,
			'span'   => array( 'class' => true ),
		);

		echo '<table class="form-table"><tbody>' . \wp_kses( $output, $allowed_tags ) . '</tbody></table>';
	}

	/**
	 * Put values in a table row.
	 *
	 * @param string $th Content of the <th> tag.
	 * @param string $td Content of the <td> tag.
	 *
	 * @return string
	 */
	protected function format_rows( $th, $td ) {
		return '<tr><th>' . $th . '</th><td>' . $td . '</td></tr>';
	}

	/**
	 * Save the metabox fields.
	 *
	 * @since    1.8.0
	 * @since    2.16.4 HPOS support: use $order->update_meta_data().
	 *
	 * @param int $post_id Post ID.
	 *
	 * @internal action.
	 */
	public function save_fields( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! \wp_verify_nonce( \wc_clean( \wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		$order = \wc_get_order( $post_id );
		if ( ! $order ) {
			return;
		}

		foreach ( $this->meta_fields as $meta_field ) {
			if ( isset( $_POST[ $meta_field['id'] ] ) ) {
				$meta_value = \sanitize_text_field( $_POST[ $meta_field['id'] ] );

				/**
				 * If order currency changed, update the rate.
				 *
				 * @since    2.9.4-rc.1
				 * @since    2.16.4 HPOS support: use $order->update_meta_data().
				 */
				if ( '_order_currency' === $meta_field['id'] ) {
					try {
						$order->set_currency( $meta_value );
						// Update order rate meta.
						$rate = $this->rate_storage->get_rate( API::default_currency(), $meta_value );
						$order->update_meta_data( Meta::PREFIX . 'rate', $rate );

						// And save the default currency to know what rate was based on.
						$order->update_meta_data( Meta::PREFIX . 'store_currency', API::default_currency() );
					} catch ( \Exception $e ) {
						Log::error( $e );
					}
				} else {
					$order->update_meta_data( $meta_field['id'], $meta_value );

				}

				/**
				 * DO NOT!
				 * // $order->save();
				 */
			}
		}
	}
}
