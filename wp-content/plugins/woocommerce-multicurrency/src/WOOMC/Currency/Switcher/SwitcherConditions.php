<?php
// Copyright (c) 2023, TIV.NET INC. All Rights Reserved.

namespace WOOMC\Currency\Switcher;

use WOOMC\Abstracts\Hookable;
use WOOMC\App;
use WOOMC\DAO\Factory;

/**
 * Class SwitcherConditions
 *
 * @package WOOMC\Currency
 */
class SwitcherConditions extends Hookable {

	/**
	 * Setup actions and filters.
	 *
	 * @since 2.12.0
	 *
	 * @return void
	 */
	public function setup_hooks() {

		if ( ! Factory::getDao()->isSwitcherConditionsEnabled() ) {
			return;
		}

		\add_filter( 'widget_display_callback',
			array( $this, 'filter__widget_display_callback' ),
			App::HOOK_PRIORITY_EARLY,
			3
		);

		\add_filter( 'pre_do_shortcode_tag',
			array( $this, 'filter__pre_do_shortcode_tag' ),
			App::HOOK_PRIORITY_LATE,
			4
		);
	}

	/**
	 * Do we need to hide?
	 *
	 * @since 2.12.0
	 * @since 2.16.1-rc.1 Converted to public static to use elsewhere.
	 * @return bool
	 */
	public static function is_hide_conditions() {

		$hide_conditions = (
			\is_cart()
			|| \is_checkout()
			|| \is_wc_endpoint_url( 'order-pay' )
			|| \is_account_page()
		);

		/**
		 * Filter the switcher hiding conditions.
		 *
		 * @since 2.12.0
		 *
		 * @param bool $hide_conditions The conditions.
		 */
		$hide_conditions = \apply_filters( 'woocommerce_multicurrency_switcher_conditions', $hide_conditions );

		return $hide_conditions;
	}

	/**
	 * Is this our switcher widget?
	 *
	 * @param \WP_Widget $widget The widget object.
	 *
	 * @return bool
	 */
	protected function is_switcher_widget( $widget ) {
		return (
			isset( $widget->id_base )
			&& (
				'woocommerce-currency-switcher-widget' === $widget->id_base
				|| 'woocommerce-currency-selector-widget' === $widget->id_base
			)
		);
	}

	/**
	 * Is this our switcher shortcode?
	 *
	 * @param string $tag Shortcode name.
	 *
	 * @return bool
	 */
	protected function is_switcher_shortcode( $tag ) {
		return (
			isset( $tag )
			&& (
				'woocommerce-currency-switcher' === $tag
				|| 'woocommerce-currency-selector' === $tag
			)
		);
	}

	/**
	 * Hide widget under certain conditions.
	 *
	 * @param array      $instance Instance settings.
	 * @param \WP_Widget $widget   The widget object.
	 * @param array      $args     Sidebar arguments.
	 *
	 * @return array|false
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__widget_display_callback( $instance, $widget, $args ) {

		if ( $this->is_switcher_widget( $widget ) && self::is_hide_conditions() ) {
			// Do not display.
			return false;
		}

		return $instance;
	}

	/**
	 * Filters whether to call a shortcode callback.
	 *
	 * Returning a non-false value from filter will short-circuit the
	 * shortcode generation process, returning that value instead.
	 *
	 * @see          \do_shortcode_tag()
	 *
	 * @param false|string $short_circuit_return Short-circuit return value. Either false or the value to replace the shortcode with.
	 * @param string       $tag                  Shortcode name.
	 * @param array|string $attr                 Shortcode attributes array or empty string.
	 * @param array        $m                    Regular expression match array.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__pre_do_shortcode_tag( $short_circuit_return, $tag, $attr, $m ) {

		if ( false !== $short_circuit_return ) {
			// A previous filter already set the `$$return`. We do not disturb.
			return $short_circuit_return;
		}

		if ( $this->is_switcher_shortcode( $tag ) && self::is_hide_conditions() ) {
			// Return empty output.
			$short_circuit_return = '';
		}

		return $short_circuit_return;
	}
}
