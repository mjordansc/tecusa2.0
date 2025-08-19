<?php
/**
 * Display shortcode in menus.
 *
 * @since 2.11.0
 * Copyright (c) 2021, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Shortcode;

use WOOMC\Abstracts\Hookable;
use WOOMC\Admin\Appearance\Menus;

/**
 * Class InMenus
 *
 * @package WOOMC\Shortcode
 */
class InMenus extends Hookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_filter( 'walker_nav_menu_start_el', array( $this, 'filter__walker_nav_menu_start_el' ), 0, 2 );
	}

	/**
	 * Filters a menu item's starting output.
	 * If this is our shortcode, replace the menu with the shortcode output.
	 *
	 * @param string   $item_output The menu item's starting HTML output.
	 * @param \WP_Post $item        Menu item data object.
	 */
	public function filter__walker_nav_menu_start_el( $item_output, $item ) {

		if (
			$item instanceof \WP_Post
			&& ! empty( $item->url ) && Menus::MENU_ITEM_URL === $item->url
			&& ! empty( $item->description )
		) {
			$item_output = \do_shortcode( $item->description );
		}

		return $item_output;
	}
}
