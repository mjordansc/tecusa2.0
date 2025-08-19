<?php
/**
 * MetaSetOrder
 *
 * @since  3.0.0-rc.1
 */

namespace WOOMC\MetaSet;

use WOOMC\Dependencies\TIVWP\HTML;
use WOOMC\Dependencies\TIVWP\WC\Metabox\MetaboxEngine;

/**
 * Class
 *
 * @since  3.0.0-rc.1
 */
class MetaSetOrder extends AbstractCurrencyMetaSet {

	/**
	 * Implement get_type().
	 *
	 * @since 4.4.8
	 * @inheritDoc
	 */
	public function get_type(): string {
		return 'order';
	}

	/**
	 * Method get_screens.
	 *
	 * @since        4.4.11
	 * @return string[]
	 * @noinspection PhpMemberCanBePulledUpInspection
	 */
	public static function get_screens(): array {
		return array( 'shop_order' );
	}

	/**
	 * Implement additional_fields().
	 *
	 * @inheritDoc
	 */
	protected function additional_fields(): array {

		if ( ! class_exists( 'WC_Subscriptions', false ) ) {
			return array();
		}

		return array(
			array(
				'type'  => MetaboxEngine::get_metabox_field_type( 'DESCRIPTION_ONLY' ),
				'id'    => 'woomc_cross_changes',
				'label' => \__( 'Cascaded changes?', 'woocommerce-multicurrency' ),
				'desc'  => implode(
					'<br>',
					array(
						HTML::bold( \__( 'Yes' ) ),
						\__( 'If there are related subscriptions, their currencies will change accordingly!', 'woocommerce-multicurrency' ),
					)
				),
			),
		);
	}
}
