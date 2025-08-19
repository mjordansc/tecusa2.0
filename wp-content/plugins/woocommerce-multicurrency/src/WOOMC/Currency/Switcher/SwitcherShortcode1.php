<?php
/**
 * Currency Switcher shortcode.
 *
 * @since 2.12.0-beta.1
 * @example
 *  [TAG format="{{code}}: {{name}} ({{symbol}})"]
 */

namespace WOOMC\Currency\Switcher;

/**
 * Class Shortcode
 */
class SwitcherShortcode1 extends AbstractSwitcherShortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'woocommerce-currency-switcher';

	/**
	 * Switcher type: '1', '2'.
	 *
	 * @var string
	 */
	protected const TYPE = '1';
}
