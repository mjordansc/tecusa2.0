<?php
/**
 * Custom Price controller
 *
 * @since 2.4.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin\CustomPrice;

use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\DAO\Factory;

/**
 * Class Controller.
 *
 * @since 2.4.0
 */
class Controller implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Bail out if price per product is not allowed in Settings.
		 */
		if ( ! Factory::getDao()->isAllowPricePerProduct() ) {
			return;
		}

		$simple = new Simple();
		$simple->setup_hooks();

		$variable = new Variable();
		$variable->setup_hooks();
	}
}
