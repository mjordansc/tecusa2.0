<?php
/**
 * Customizer.
 *
 * @since 3.2.4-2
 * Copyright (c) 2023, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin\Appearance;

use WOOMC\Abstracts\Hookable;

/**
 * Class Customizer
 *
 * @since 3.2.4-2
 */
class Customizer extends Hookable {

	/**
	 * Setup actions and filters.
	 *
	 * @since 3.2.4-2
	 * @return void
	 */
	public function setup_hooks() {

		// Include custom items to customizer nav menu settings.
		\add_filter( 'customize_nav_menu_available_item_types', array(
			$this,
			'register_customize_nav_menu_item_types',
		) );
		\add_filter( 'customize_nav_menu_available_items', array( $this, 'register_customize_nav_menu_items' ), 10, 4 );
	}

	/**
	 * Register custom menu item types.
	 *
	 * @since 3.2.4-2
	 *
	 * @param array $item_types The item types.
	 *
	 * @return array
	 */
	public function register_customize_nav_menu_item_types( $item_types ) {
		$item_types[] = array(
			'title'      => \__( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' ),
			'type_label' => \__( 'Multi-currency', 'woocommerce-multicurrency' ),
			'type'       => 'woomc',
			'object'     => 'woomc_nav',
		);

		return $item_types;
	}

	/**
	 * Register custom menu items.
	 *
	 * @since        3.2.4-2
	 *
	 * @param array  $items       The items to register.
	 * @param string $item_type   The type of the item (currency-switcher, currency-selector, rate-display).
	 * @param string $item_object The object of the item (woomc-currency-switcher_nav, woomc-currency-selector_nav, woomc-rate-display_nav).
	 * @param int    $page        The page of the item
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function register_customize_nav_menu_items( $items = array(), $item_type = '', $item_object = '', $page = 0 ) {
		// We don't paginate because we only have one page of items
		if ( 0 < $page ) {
			return $items;
		}
		if ( 'woomc_nav' === $item_object ) {
			$custom_items = Menus::items();
			foreach ( $custom_items as $key => $custom_item ) {
				$items[] = array(
					'id'          => 'woomc-' . $key,
					'title'       => $custom_item['menu-item-title'],
					'type_label'  => \__( 'Custom Link' ),
					'url'         => Menus::MENU_ITEM_URL,
					'classes'     => Menus::MAIN_CSS_CLASS . ' ' . $key,
					'description' => $custom_item['menu-item-description'],
				);
			}
		}

		return $items;
	}
}
