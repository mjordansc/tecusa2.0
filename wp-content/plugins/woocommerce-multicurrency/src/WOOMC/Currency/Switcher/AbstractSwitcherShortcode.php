<?php
// Copyright (c) 2023, TIV.NET INC. All Rights Reserved.

namespace WOOMC\Currency\Switcher;

use WOOMC\Abstracts\Hookable;
use WOOMC\API;
use WOOMC\App;
use WOOMC\Currency\Detector;
use WOOMC\DAO\Factory;
use WOOMC\Dependencies\TIVWP\Env;
use WOOMC\Dict;
use WOOMC\Frontend\Controller as FrontendController;
use WOOMC\Scripting;

/**
 * Class AbstractSwitcherShortcode
 *
 * @since 4.0.0
 */
abstract class AbstractSwitcherShortcode extends Hookable {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected const TAG = '';

	/**
	 * Switcher type: '1', '2'.
	 *
	 * @var string
	 */
	protected const TYPE = '';

	/**
	 * Default format, if not passed.
	 *
	 * @var string
	 */
	protected const DEFAULT_FORMAT = '{{code}}: {{name}} ({{symbol}})';

	/**
	 * Parameters.
	 *
	 * @var array{type:string, format:string, flag:string, active_currency:string, currencies:string[], inactive_currencies:string}
	 */
	protected $params = array();

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_shortcode( static::TAG, array( $this, 'process_shortcode' ) );
	}

	/**
	 * Make option text.
	 *
	 * @param string $currency Currency code.
	 *
	 * @return string
	 */
	protected function make_option_text( $currency ) {

		$currency_names = API::currency_names();

		return str_replace(
			array(
				'{{code}}',
				'{{name}}',
				'{{symbol}}',
			),
			array(
				$currency,
				$currency_names[ $currency ],
				\get_woocommerce_currency_symbol( $currency ),
			),
			$this->params['format']
		);
	}

	/**
	 * Make flag CSS class.
	 *
	 * @param string $currency Currency code.
	 *
	 * @return string
	 */
	protected function make_flag_css_class( $currency ) {
		if ( $this->params['flag'] ) {
			$country_alpha2 = Dict::currency_to_country_alpha_2( $currency );

			return implode( ' ', array(
				'fi',
				'fi-' . strtolower( $country_alpha2 ),
				'currency-flag',
				'currency-flag-' . strtolower( $currency ),
			) );
		} else {
			return 'currency-no-flag';
		}
	}

	/**
	 * Method widget_settings.
	 *
	 * @since 4.1.0
	 * @return array
	 */
	public static function widget_settings(): array {
		return array(
			'tag'                  => static::TAG,
			'id_base'              => static::TAG . '-widget',
			'classname'            => 'widget-' . static::TAG,
			'default_format'       => static::DEFAULT_FORMAT,
			'name'                 => implode( ' ',
				array(
					\_x( 'Currency Switcher', 'Widget', 'woocommerce-multicurrency' ),
					\__( 'Type', 'woocommerce' ),
					static::TYPE,
				) ),
			'description'          => \_x( 'Drop-down currency selector.', 'Widget', 'woocommerce-multicurrency' ),
			'title'                => \_x( 'Currency', 'Widget', 'woocommerce-multicurrency' ),
			'admin_format_label'   => \_x( 'Display format:', 'Widget', 'woocommerce-multicurrency' ),
			'admin_format_example' => \_x( 'Example:', 'Widget', 'woocommerce-multicurrency' ),
			'admin_flag_label'     => \_x( 'Show flag', 'Widget', 'woocommerce-multicurrency' ),
		);
	}

	/**
	 * Process shortcode.
	 *
	 * @param string[] $params The shortcode attributes.
	 *
	 * @return string
	 */
	public function process_shortcode( $params ) {
		$this->setup_parameters( $params );

		if ( empty( $this->params['enabled'] ) ) {
			return '';
		}

		if ( '2' === $this->params['type'] ) {
			$this->enqueue_scripts_type_2();

			return $this->render_type_2();
		} else {
			$this->enqueue_scripts_type_1();

			return $this->render_type_1();
		}
	}

	/**
	 * Setup shortcode parameters.
	 *
	 * @param string[]|string $params The shortcode attributes.
	 */
	protected function setup_parameters( $params ) {

		// Defaults if not passed.
		$this->params = \shortcode_atts(
			array(
				'type'       => static::TYPE,
				'format'     => static::DEFAULT_FORMAT,
				'flag'       => '0',
				'currencies' => API::enabled_currencies(),
				'enabled'    => true,
			),
			$params,
			static::TAG
		);

		/**
		 * Filter woocommerce_multicurrency_switcher_params
		 *
		 * @since 4.3.0
		 *
		 * @param array $params The switcher parameters.
		 */
		$this->params = \apply_filters( 'woocommerce_multicurrency_switcher_params', $this->params );

		// This must not go through any filter!
		$this->params['active_currency'] = API::active_currency();

		/*
		 * Legacy (pre-4.3.0) filters
		 */

		/**
		 * Filter woocommerce_multicurrency_switcher_format
		 *
		 * @since 2.12.0
		 *
		 * @param string $format Switcher format.
		 */
		$this->params['format'] = \apply_filters( 'woocommerce_multicurrency_switcher_format', $this->params['format'] );
		/**
		 * Filter woocommerce_multicurrency_switcher_flag
		 *
		 * @since 2.12.0
		 *
		 * @param string $flag Flag '0'|'1'.
		 */
		$this->params['flag'] = \apply_filters( 'woocommerce_multicurrency_switcher_flag', $this->params['flag'] );
		/**
		 * Filter the list of currencies displayed in the dropdown.
		 *
		 * @since 2.12.0
		 *
		 * @param string[] $currencies List of currencies.
		 */
		$this->params['currencies'] = \apply_filters( 'woocommerce_multicurrency_switcher_currencies', $this->params['currencies'] );

		/*
		 * Sanitize
		 */

		// Type must be valid. TODO: this code is not abstract!
		if ( ! in_array( (int) $this->params['type'], range( 1, 2 ), true ) ) {
			$this->params['type'] = static::TYPE;
		}

		if ( ! is_string( $this->params['format'] ) ) {
			$this->params['format'] = static::DEFAULT_FORMAT;
		}

		$this->params['flag'] = (int) $this->params['flag'] ? '1' : '0';

		// No flag and empty format - set format to default to avoid empty dropdown.
		if ( ! $this->params['flag'] && empty( $this->params['format'] ) ) {
			$this->params['format'] = static::DEFAULT_FORMAT;
		}

		if ( ! is_array( $this->params['currencies'] ) ) {
			$this->params['currencies'] = API::enabled_currencies();
		}
		// In case the active currency is not there after the filter.
		if ( ! in_array( $this->params['active_currency'], $this->params['currencies'], true ) ) {
			$this->params['currencies'][] = $this->params['active_currency'];
			sort( $this->params['currencies'] );
		}

		// Make sure we only have enabled currencies.
		$this->params['currencies'] = array_intersect( $this->params['currencies'], API::enabled_currencies() );

		// Remove active currency to get the list of inactive, after filters.
		$this->params['inactive_currencies'] = array_diff( $this->params['currencies'], (array) $this->params['active_currency'] );
	}

	/**
	 * Returns true if hidden by Switcher Conditions.
	 *
	 * @since 2.16.2
	 *
	 * @return bool
	 */
	protected function is_hidden() {
		return Factory::getDao()->isSwitcherConditionsEnabled() && SwitcherConditions::is_hide_conditions();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.12.0-beta.1
	 * @since 2.16.1-rc.1 Do not do anything if hide_conditions.
	 */
	protected function enqueue_scripts_type_1() {
		static $already_done = false;
		if ( $already_done || $this->is_hidden() ) {
			return;
		}
		$already_done = true;

		$url_assets = App::instance()->plugin_dir_url() . 'assets';

		$url_css = $url_assets . '/css/currency-switcher.min.css';
		// Script
		$url_js = $url_assets . '/js/currency-switcher.min.js';

		\wp_enqueue_style( 'woomc-currency-switcher', $url_css, array(), WOOCOMMERCE_MULTICURRENCY_VERSION );

		Scripting::enqueue_script(
			'woomc-currency-switcher',
			$url_js,
			array(),
			WOOCOMMERCE_MULTICURRENCY_VERSION,
			true,
			20
		);

		/**
		 * Try to make the browser forgetting that we reloaded using a POST.
		 * Otherwise, if refresh the page (F5), browser prompts for form re-submission.
		 */
		if ( Env::is_parameter_in_http_post( Detector::GET_FORCED_CURRENCY ) ) {
			//@formatter:off
			?>
			<script id="woomc-forget-http-post-js">
				if (window.history.replaceState) {
					window.history.replaceState(null, null, window.location.href);
				}
			</script>
			<?php
			//@formatter:on
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.1.2
	 * @since 2.5.1 Enqueued only if shortcode (or widget) is on the page.
	 * @since 2.6.3-rc.2 Non-related settings moved to {@see FrontendController}.
	 * @since 3.2.1-0 Loading Flags CSS moved to Currency\Controller.
	 * @since 4.3.0-3 Loading Flags CSS moved to frontend JS.
	 */
	protected function enqueue_scripts_type_2() {
		static $already_done = false;
		if ( $already_done || $this->is_hidden() ) {
			return;
		}
		$already_done = true;

		$url_assets = App::instance()->plugin_dir_url() . 'assets';

		// Styles for jQuery-UI selectmenu.
		$url_css = $url_assets . '/css/currency-selector.min.css?ver=' . WOOCOMMERCE_MULTICURRENCY_VERSION;
		// Script
		$url_js = $url_assets . '/js/currency-selector.min.js';

		Scripting::enqueue_script(
			'woomc-currency-selector',
			$url_js,
			array(
				'jquery',
				'jquery-ui-selectmenu',
			),
			WOOCOMMERCE_MULTICURRENCY_VERSION,
			true,
			20
		);

		$woomc_currency_selector_data = array(
			'currencySelectorDOM' => '.woocommerce-currency-selector',
			'url'                 => array(
				'currencySelectorCSS' => $url_css,
			),
		);

		Scripting::enqueue_data_script( 'woomc_currency_selector', $woomc_currency_selector_data );

		// Make sure UI styles are loaded - copied from WC code.
		\wp_enqueue_style( 'jquery-ui-style', \WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), \WC()->version );
	}

	/**
	 * Method render_type_1.
	 *
	 * @since 4.1.0
	 * @return string
	 */
	protected function render_type_1() {
		ob_start();

		?>
		<span class="<?php echo \esc_attr( SwitcherShortcode1::TAG ); ?>"
				tabindex="0"
				role="navigation"
				aria-label="<?php echo \esc_attr_x( 'Currency Switcher', 'Widget', 'woocommerce-multicurrency' ); ?>">
			<?php
			$option_text    = $this->make_option_text( $this->params['active_currency'] );
			$css_class      = 'selector currency-' . $this->params['active_currency'];
			$flag_css_class = $this->make_flag_css_class( $this->params['active_currency'] );
			$post_action    = FrontendController::sanitized_current_url();
			?>
			<div class="<?php echo \esc_attr( $css_class ); ?>">
				<span class="option-wrap">
					<span class="<?php echo \esc_attr( $flag_css_class ); ?>"></span>
					<span class="option-text"><?php echo \esc_html( $option_text ); ?></span>
				</span>
				<span class="chevron-down">^</span>
			</div>
			<div class="dropdown-content">
			<?php foreach ( $this->params['inactive_currencies'] as $currency ) : ?>
				<?php
				$option_text    = $this->make_option_text( $currency );
				$css_class      = 'currency-' . $currency;
				$flag_css_class = $this->make_flag_css_class( $currency );
				?>
				<form method="post" action="<?php echo \esc_url( $post_action ); ?>"
						class="woomc-switcher <?php echo \esc_attr( $css_class ); ?>">
				<input type="hidden" name="<?php echo \esc_attr( Detector::GET_FORCED_CURRENCY ); ?>"
						value="<?php echo \esc_attr( $currency ); ?>">
				<button class="option-submit">
					<span class="<?php echo \esc_attr( $flag_css_class ); ?>"></span>
					<span class="option-text"><?php echo \esc_html( $option_text ); ?></span>
				</button>
			</form>
			<?php endforeach; ?>
				</div>
			</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Method render_type_2.
	 *
	 * @since 4.1.0
	 * @return string
	 */
	protected function render_type_2() {
		ob_start();
		?>
		<span class="<?php echo \esc_attr( SwitcherShortcode2::TAG ); ?>-wrap" role="navigation">
			<select class="<?php echo \esc_attr( SwitcherShortcode2::TAG ); ?>"
					data-flag="<?php echo (int) $this->params['flag']; ?>"
					aria-label="<?php echo \esc_attr_x( 'Currency Switcher', 'Widget', 'woocommerce-multicurrency' ); ?>"
					disabled
					style="opacity:.2">
				<?php foreach ( $this->params['currencies'] as $currency ) : ?>
					<?php
					$option_text = $this->make_option_text( $currency );
					?>
					<option value="<?php echo \esc_attr( $currency ); ?>"<?php \selected( $currency, $this->params['active_currency'] ); ?>><?php echo \esc_html( $option_text ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
		<?php
		return ob_get_clean();
	}
}
