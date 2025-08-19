<?php
/**
 * Currency switcher widget.
 *
 * @since 1.0.0
 */

namespace WOOMC\Currency\Switcher;

/**
 * Class Widget
 */
class SwitcherWidget2 extends AbstractSwitcherWidget {

	/**
	 * Initialize widget settings.
	 */
	protected function init_settings() {
		$this->settings = SwitcherShortcode2::widget_settings();
	}
}
