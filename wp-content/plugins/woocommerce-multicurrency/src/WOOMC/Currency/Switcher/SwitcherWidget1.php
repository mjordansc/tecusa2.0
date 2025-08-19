<?php
/**
 * Currency switcher widget.
 *
 * @since 2.12.0-beta.1
 */

namespace WOOMC\Currency\Switcher;

/**
 * Class Widget
 */
class SwitcherWidget1 extends AbstractSwitcherWidget {

	/**
	 * Initialize widget settings.
	 */
	protected function init_settings() {
		$this->settings = SwitcherShortcode1::widget_settings();
	}
}
