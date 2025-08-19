<?php
/**
 * Integration: standard WooCommerce "Filter by price" widget.
 *
 * @todo  Doesn't take custom currency pricing into account: meta table and product filtering.
 *
 * @since 2.8.3
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Widget;

use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Integration\AbstractIntegration;

/**
 * Class PriceFilter
 *
 * @package WOOMC\Integration\Widget
 */
class PriceFilter extends AbstractIntegration {

	/**
	 * The post_clauses hook has a default priority=10.
	 * This is to run our hook before.
	 *
	 * @var int
	 */
	const PRIORITY_BEFORE = 9;

	/**
	 * The post_clauses hook has a default priority=10.
	 * This is to run our hook after.
	 *
	 * @var int
	 */
	const PRIORITY_AFTER = 11;

	/**
	 * Original query args values preserved here.
	 *
	 * @var int[]
	 */
	protected $original_min_max = array(
		'min_price' => 0,
		'max_price' => 0,
	);

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/*
		|--------------------------------------------------------------------------
		| Convert min-max values for the Widget.
		|--------------------------------------------------------------------------
		*/

		\add_filter(
			'woocommerce_price_filter_widget_max_amount',
			array( $this, 'filter__convert' )
		);

		\add_filter(
			'woocommerce_price_filter_widget_min_amount',
			array( $this, 'filter__convert' )
		);

		/*
		|--------------------------------------------------------------------------
		| Manipulate post_clauses to filter the products in shop correctly.
		| - convert min-max in $_GET and preserve the original values before,
		| - restore after.
		|--------------------------------------------------------------------------
		*/

		\add_filter(
			'posts_clauses',
			array( $this, 'convert_min_max_query_args' ),
			self::PRIORITY_BEFORE,
			2
		);

		\add_filter(
			'posts_clauses',
			array( $this, 'restore_min_max_query_args' ),
			self::PRIORITY_AFTER,
			2
		);
	}

	/**
	 * Temporarily convert _GETs.
	 *
	 * @see \WC_Query::price_filter_post_clauses()
	 *
	 * @param \WP_Query $wp_query WP_Query object.
	 *
	 * @param array     $args     Query args.
	 *
	 * @return array
	 */
	public function convert_min_max_query_args( $args, $wp_query ) {

		if ( ! $wp_query->is_main_query() ) {
			return $args;
		}

		foreach ( array_keys( $this->original_min_max ) as $query_arg ) {
			$value = Env::get_http_get_parameter( $query_arg );
			if ( $value ) {
				$this->original_min_max[ $query_arg ] = $value;

				$converted_value = $this->price_controller->convert_back_raw( $value );

				$_GET[ $query_arg ]     = $converted_value;
				$_REQUEST[ $query_arg ] = $converted_value;
			}
		}

		return $args;
	}

	/**
	 * Restore _GETs.
	 *
	 * @see          \WC_Query::price_filter_post_clauses()
	 *
	 * @param \WP_Query $wp_query WP_Query object.
	 *
	 * @param array     $args     Query args.
	 *
	 * @return array
	 */
	public function restore_min_max_query_args( $args, $wp_query ) {

		if ( ! $wp_query->is_main_query() ) {
			return $args;
		}

		foreach ( array_keys( $this->original_min_max ) as $query_arg ) {
			$original_value = $this->original_min_max[ $query_arg ];
			if ( $original_value ) {
				$_GET[ $query_arg ]     = $original_value;
				$_REQUEST[ $query_arg ] = $original_value;
			}
		}

		return $args;
	}
}
