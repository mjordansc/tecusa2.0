<?php
// Copyright (c) 2023, TIV.NET INC. All Rights Reserved.

namespace WOOMC\Deprecated;

/**
 * Class Deprecated_Filters
 *
 * @since 4.1.0
 */
class Deprecated_Filters extends \WC_Deprecated_Filter_Hooks {

	/**
	 * Array of deprecated hooks we need to handle.
	 * Format of 'new' => 'old'.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $deprecated_hooks = array(
		'woocommerce_multicurrency_shortcode_format'     => 'woocommerce_multicurrency_switcher_format',
		'woocommerce_multicurrency_shortcode_flag'       => 'woocommerce_multicurrency_switcher_flag',
		'woocommerce_multicurrency_shortcode_currencies' => 'woocommerce_multicurrency_switcher_currencies',
	);

	/**
	 * Array of versions on each hook has been deprecated.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	protected $deprecated_version = array(
		'woocommerce_multicurrency_shortcode_format'     => '4.1.0',
		'woocommerce_multicurrency_shortcode_flag'       => '4.1.0',
		'woocommerce_multicurrency_shortcode_currencies' => '4.1.0',
	);
}
