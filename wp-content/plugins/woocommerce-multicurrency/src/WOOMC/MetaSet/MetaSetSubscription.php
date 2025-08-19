<?php
/**
 * MetaSetSubscription
 *
 * @since  4.4.11
 */

namespace WOOMC\MetaSet;

use WOOMC\Dependencies\TIVWP\HTML;
use WOOMC\Dependencies\TIVWP\WC\Metabox\MetaboxEngine;

/**
 * Class
 *
 * @since  4.4.11
 */
class MetaSetSubscription extends AbstractCurrencyMetaSet {

	/**
	 * Implement get_type().
	 *
	 * @since 4.4.11
	 * @inheritDoc
	 */
	public function get_type(): string {
		return 'subscription';
	}

	/**
	 * Method get_screens.
	 *
	 * @since        4.4.11
	 * @return string[]
	 * @noinspection PhpMemberCanBePulledUpInspection
	 */
	public static function get_screens(): array {
		return array( 'shop_subscription' );
	}

	/**
	 * Implement additional_fields().
	 *
	 * @inheritDoc
	 */
	protected function additional_fields(): array {
		return array(
			array(
				'type'  => MetaboxEngine::get_metabox_field_type( 'DESCRIPTION_ONLY' ),
				'id'    => 'woomc_cross_changes',
				'label' => \__( 'Cascaded changes?', 'woocommerce-multicurrency' ),
				'desc'  => implode(
					'<br>',
					array(
						HTML::bold( \__( 'None' ) ),
						\__( 'If there are related orders, their currencies will not change!', 'woocommerce-multicurrency' ),
					)
				),
			),
		);
	}
}
