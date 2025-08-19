<?php
/**
 * Currency Switcher shortcode.
 *
 * @since 1.0.0
 * @example
 *  [TAG format="{{code}}: {{name}} ({{symbol}})"]
 */

namespace WOOMC\Currency\Switcher;

/**
 * Class Shortcode
 */
class SwitcherShortcode2 extends AbstractSwitcherShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'woocommerce-currency-selector';

	/**
	 * Switcher type: '1', '2'.
	 *
	 * @var string
	 */
	protected const TYPE = '2';
}
