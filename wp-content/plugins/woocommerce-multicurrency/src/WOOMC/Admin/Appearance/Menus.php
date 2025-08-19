<?php
/**
 * Manage menus.
 *
 * @since 2.11.0
 * Copyright (c) 2021, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin\Appearance;

use WOOMC\Abstracts\Hookable;

/**
 * Class Menus
 *
 * @since 2.11.0
 */
class Menus extends Hookable {

	/**
	 * A fictitious menu URL serves as a flag to replace with a shortcode output.
	 *
	 * @since 2.11.0
	 * @var string
	 */
	const MENU_ITEM_URL = '#woomc-shortcode';

	/**
	 * The CSS class added to the menus by default.
	 *
	 * @since 2.11.0
	 * @var string
	 */
	const MAIN_CSS_CLASS = 'woomc-shortcode';

	/**
	 * Setup actions and filters.
	 *
	 * @since 2.11.0
	 * @return void
	 */
	public function setup_hooks() {

		\add_action( 'admin_head-nav-menus.php', array( $this, 'action__add_meta_box' ) );
	}

	/**
	 * Add custom nav meta box.
	 *
	 * @since 2.11.0
	 * @return void
	 */
	public function action__add_meta_box() {
		\add_meta_box(
			'add-multicurrency',
			\__( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' ),
			array( $this, 'callback__nav_menu_links' ),
			'nav-menus',
			'side',
			'low'
		);
	}

	/**
	 * Menu items.
	 *
	 * @since 3.2.4-2
	 * @return string[][]
	 */
	public static function items() {
		return array(
			'currency-switcher' => array(
				'menu-item-title'       => \_x( 'Currency Switcher', 'Widget', 'woocommerce-multicurrency' ) . ' 1 (' . \_x( 'Mouse over', 'Widget', 'woocommerce-multicurrency' ) . ')',
				'menu-item-description' => '[woocommerce-currency-switcher type=1 flag=1 format="{{code}} ({{symbol}})"]',
			),
			'currency-selector' => array(
				'menu-item-title'       => \_x( 'Currency Switcher', 'Widget', 'woocommerce-multicurrency' ) . ' 2 (' . \_x( 'Click', 'Widget', 'woocommerce-multicurrency' ) . ')',
				'menu-item-description' => '[woocommerce-currency-switcher type=2 flag=1 format="{{code}} ({{symbol}})"]',
			),
			'rate-display'      => array(
				'menu-item-title'       => \__( 'Rate', 'woocommerce-multicurrency' ),
				'menu-item-description' => '[woomc-convert value="1" currency="DEFAULT"] = [woomc-convert value="1"]',
			),
		);
	}

	/**
	 * Output menu links.
	 *
	 * @since        2.11.0
	 * @since        3.2.1-0 Selector is no longer marked as "deprecated".
	 *
	 * @noinspection PhpArrayWriteIsNotUsedInspection
	 */
	public function callback__nav_menu_links() {

		$items = self::items();
		array_walk( $items, function ( &$item, $key ) {
			$item['menu-item-type']    = 'custom';
			$item['menu-item-url']     = self::MENU_ITEM_URL;
			$item['menu-item-classes'] = implode( ' ', array( self::MAIN_CSS_CLASS, $key ) );
		} );

		require_once __DIR__ . '/template-menus.php';
	}
}
