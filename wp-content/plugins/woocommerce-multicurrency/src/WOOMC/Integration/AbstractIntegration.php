<?php
/**
 * Abstract Integration Class.
 *
 * @since   1.18.0
 * @package WOOMC\Integration
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\Price;

/**
 * Class AbstractIntegration
 *
 * @package WOOMC\Integration
 */
abstract class AbstractIntegration implements InterfaceHookable {

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * Constructor.
	 *
	 * @param Price\Controller $price_controller The price controller.
	 */
	public function __construct( Price\Controller $price_controller ) {
		$this->price_controller = $price_controller;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	abstract public function setup_hooks();

	/**
	 * Pass-through to the Converter.
	 *
	 * @since 2.8.3
	 *
	 * @param string|int|float $value   The price.
	 * @param \WC_Product      $product The Product object. Reserved for future use.
	 * @param string           $to      Currency convert to. Default is the currently selected.
	 * @param string           $from    Currency convert from. Default is store base.
	 * @param bool             $reverse If this is a reverse conversion.
	 *
	 * @return float|int|string
	 */
	public function filter__convert(
		$value,
		$product = null,
		$to = '',
		$from = '',
		$reverse = false
	) {

		return $this->price_controller->convert( $value, $product, $to, $from, $reverse );
	}
}
