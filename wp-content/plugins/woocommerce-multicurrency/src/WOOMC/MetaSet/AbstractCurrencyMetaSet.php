<?php
/**
 * Abstract Currency MetaSet
 *
 * @since 4.4.11
 */

namespace WOOMC\MetaSet;

use WOOMC\API;
use WOOMC\Dependencies\TIVWP\Abstracts\MetaSetInterface;
use WOOMC\Dependencies\TIVWP\HTML;
use WOOMC\Dependencies\TIVWP\Utils;
use WOOMC\Dependencies\TIVWP\WC\Metabox\MetaboxEngine;

/**
 * Class AbstractCurrencyMetaSet.
 *
 * @since 4.4.11
 */
abstract class AbstractCurrencyMetaSet implements MetaSetInterface {

	/**
	 * Var order_currency.
	 *
	 * @since 4.4.11
	 *
	 * @var string
	 */
	protected string $order_currency = '';

	/**
	 * Returns the MetaSet title.
	 *
	 * @since  3.0.0-rc.1
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Multi-currency options', 'woocommerce-multicurrency' );
	}

	/**
	 * Method init.
	 *
	 * @since 4.4.11
	 * @return void
	 */
	protected function init(): void {
		/**
		 * Global order.
		 *
		 * @var \WC_Order $theorder
		 */
		global $theorder;
		if ( $theorder instanceof \WC_Order ) {
			$this->order_currency = $theorder->get_currency( 'edit' );
		}
	}

	/**
	 * Method is_order_currency_enabled.
	 *
	 * @since 4.4.11
	 * @return bool
	 */
	protected function is_order_currency_enabled(): bool {
		return API::is_currency_enabled( $this->order_currency );
	}

	/**
	 * Method field_disabled_currency.
	 *
	 * @since 4.4.11
	 *
	 * @return array[]
	 */
	protected function field_disabled_currency(): array {
		return array(
			'type'  => MetaboxEngine::get_metabox_field_type( 'DESCRIPTION_ONLY' ),
			'id'    => 'woomc_disabled_currency',
			'label' => \__( 'Attention!', 'woocommerce-multicurrency' ),
			'desc'  => implode(
				'<br>',
				array(
					HTML::span_notification(
					// Translators: %s holds the currency code, e.g., USD, EUR.
						sprintf( \__( 'The currency %s is no longer enabled.', 'woocommerce-multicurrency' ), $this->order_currency )
					),
					\__( 'To add it back to the list of currencies, go to the Multi-currency settings.', 'woocommerce-multicurrency' ),
					HTML::icon_warning() .
					\__( 'If you click Update now, the currency will be changed. See the dropdown below.', 'woocommerce-multicurrency' ),
				)
			),
		);
	}

	/**
	 * Method field_select_currency.
	 *
	 * @since 4.4.11
	 * @return array
	 */
	protected function field_select_currency(): array {
		return array(
			'type'               => MetaboxEngine::get_metabox_field_type( 'SELECT' ),
			'label'              => \__( 'Currency', 'woocommerce' ),
			'id'                 => '_order_currency',
			'default'            => \get_woocommerce_currency(),
			'options'            => API::enabled_currencies(),
			'delete_empty'       => false,
			'desc'               => implode(
				'<br>',
				array(
					\__( 'To change the currency, select it from the dropdown and click Create/Update.', 'woocommerce-multicurrency' ),
					'',
					HTML::icon_warning() .
					\__( 'Note: this will only change the currency, not the amounts!', 'woocommerce-multicurrency' ),
					HTML::icon_info() .
					\__( 'To change the amounts, remove the items and add them back.', 'woocommerce-multicurrency' ),
				)
			),
			// Translators: %s - placeholder for currency.
			'order_note_updated' => \__( 'Multi-currency: currency set to %s', 'woocommerce-multicurrency' ),
		);
	}

	/**
	 * Method additional_fields.
	 *
	 * @since 4.4.11
	 * @return array
	 */
	protected function additional_fields(): array {
		return array();
	}

	/**
	 * Returns the MetaSet fields.
	 *
	 * @since  3.0.0-rc.1
	 *
	 * @return array[]
	 */
	public function get_meta_fields(): array {

		$this->init();

		$fields = array();

		if ( $this->order_currency && ! $this->is_order_currency_enabled() ) {
			$fields[] = $this->field_disabled_currency();
		}

		$fields[] = $this->field_select_currency();

		$additional_fields = $this->additional_fields();
		if ( Utils::is_not_empty_array( $additional_fields ) ) {
			foreach ( $additional_fields as $additional_field ) {
				$fields[] = $additional_field;
			}
		}

		return $fields;
	}
}
