<?php
/**
 * WooCommerce Tools abstract class.
 *
 * @since 1.2.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\WC;

use WOOMC\Dependencies\TIVWP\InterfaceHookable;

/**
 * Class AbstractTools
 *
 * @package WOOMC\Dependencies\TIVWP\WC
 */
abstract class AbstractTools implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_filter( 'woocommerce_debug_tools', array( $this, 'tools' ) );
	}

	/**
	 * Button(s) on the WooCommerce > Status > Tools page.
	 *
	 * @param array $tools All tools array.
	 *
	 * @return array
	 */
	abstract public function tools( array $tools ): array;
}
