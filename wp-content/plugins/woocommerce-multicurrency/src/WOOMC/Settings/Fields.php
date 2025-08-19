<?php
/**
 * Field definitions for the settings tab.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Settings;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WOOMC\Admin\Notices;
use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\DAO\IDAO;
use WOOMC\DAO\WP;
use WOOMC\Integration\Multilingual;
use WOOMC\Log;
use WOOMC\Price\Rounder;
use WOOMC\Rate\CurrentProvider;
use WOOMC\Rate\Provider\FixedRates;
use WOOMC\Rate\Providers;
use WOOMC\Rate\Storage;
use WOOMC\Rate\Updater;
use WOOMC\Rate\UpdateScheduler;

/**
 * Class Settings\Fields
 */
class Fields {

	/**
	 * Field sections prefix.
	 *
	 * @var string
	 */
	const SECTION_ID_PREFIX = 'woocommerce-multicurrency_';

	/**
	 * CSS class for credentials input fields. Needed for the show/hide JS.
	 *
	 * @var string
	 */
	const CSS_CLASS_CREDENTIALS_INPUT = 'rates_credentials_input';

	/**
	 * Currencies with rates.
	 *
	 * @var  array
	 */
	protected $currencies_with_rates;

	/**
	 * DAO.
	 *
	 * @var  IDAO
	 */
	protected $dao;

	/**
	 * Rate Storage instance.
	 *
	 * @var Storage
	 */
	protected $rate_storage;

	/**
	 * Fields constructor.
	 *
	 * @param Storage $rate_storage Rate Storage instance.
	 */
	public function __construct( Storage $rate_storage ) {
		$this->rate_storage = $rate_storage;
		$this->dao          = Factory::getDao();
	}

	/**
	 * Build all panel fields.
	 *
	 * @return array
	 */
	public function get_all() {

		/**
		 * This is not done in the constructor
		 * because we need the information updated after saving the settings.
		 */
		$this->currencies_with_rates = $this->rate_storage->woocommerce_currencies_with_rates();

		$all_fields = array();

		$this->section_intro( $all_fields );
		$this->section_rates_service( $all_fields );

		if ( $this->dao->getRatesProviderID() && ! $this->dao->getRatesRetrievalStatus() ) {
			// Provider is set but rates not retrieved.
			$this->section_rates_not_retrieved( $all_fields );
		}

		if ( count( $this->currencies_with_rates ) ) {
			if ( ! CurrentProvider::isFixedRates() ) {
				$this->section_rates_timestamp( $all_fields );
			}

			$this->section_enabled_currencies( $all_fields );

			if ( CurrentProvider::isFixedRates() && count( $this->dao->getEnabledCurrencies() ) > 1 ) {
				// Show the fixed rates section only if:
				// - provider is 'FixedRates'
				// - more than 1 currency is enabled (because 1 is always the default).
				$this->section_fixed_rates( $all_fields );
			}

			$this->section_currency_symbols( $all_fields );
			$this->section_price_conversion_settings( $all_fields );
			$this->section_price_formats( $all_fields );

			if ( App::instance()->isMultilingual() ) {
				$this->section_auto_currencies( $all_fields );
			} else {
				$this->section_auto_currencies_is_disabled( $all_fields );
			}
		}

		$this->section_general_settings( $all_fields );

		if ( App::instance()->isReadOnlySettings() ) {
			$this->section_save_is_disabled( $all_fields );
		}

		/**
		 * Filter woocommerce_multicurrency_settings_fields.
		 *
		 * @since 1.0.0
		 */
		return \apply_filters( 'woocommerce_multicurrency_settings_fields', $all_fields );
	}

	/**
	 * Section "intro".
	 *
	 * @since 1.0.0
	 * @since 3.2.4-1 Added: warning about incompatible plugins.
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_intro( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'intro';
		$section_title = implode( ' | ', array(
			\__( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' ),
			/* translators: %s: Plugin version number. */
			sprintf( \__( 'Version %s' ), WOOCOMMERCE_MULTICURRENCY_VERSION ),
			/* translators: %s: Plugin author name. */
			sprintf( \__( 'By %s' ), 'TIV.NET' ),
		) );
		$section_desc  = implode(
			' <br/>',
			array(
				'<div class="howto">' .
				\__( 'Thank you for installing the multi-currency extension! We appreciate your business!', 'woocommerce-multicurrency' ),
				sprintf( /* Translators: placeholders for HTML "a" tag linking 'here' to the Support page. */
					\__( 'Please configure the settings using the instructions below. Should you need help, please contact our technical support by clicking %1$shere%2$s.', 'woocommerce-multicurrency' ),
					'<a href="' . App::instance()->getUrlSupport() . '">',
					'</a>'
				),
				'</div>',
			)
		);

		$incompatible_plugins = array();
		if ( defined( 'WOOMULTI_CURRENCY_F_VERSION' ) ) {
			$incompatible_plugins[] = sprintf( '%s', \__( 'CURCY - Multi Currency for WooCommerce', 'woo-multi-currency' ) );
		}

		if ( ! empty( $incompatible_plugins ) ) {
			$section_desc .= Notices::get_error_incompatible_plugins( $incompatible_plugins );
		}

		if ( method_exists( '\WC_Payments_Features', 'is_customer_multi_currency_enabled' ) && \WC_Payments_Features::is_customer_multi_currency_enabled() ) {
			$section_desc .= Notices::get_error_wcpay();
		}

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "rates service".
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_rates_service( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'rates_service';
		$section_title = \__( 'Currency Exchange Rates', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				\__( 'To switch between currencies, your website needs to get the exchange rates from one of the service providers.', 'woocommerce-multicurrency' ),
				\__( 'Alternatively, you can select the FixedRates option and enter the rates manually.', 'woocommerce-multicurrency' ),
				'<i class="dashicons dashicons-media-document"></i> ' .
				sprintf( /* translators: %1$, %2$ are HTML tags linking "here" to documentation. */
					\__( 'Please read the instructions %1$shere%2$s.', 'woocommerce-multicurrency' ),
					'<a href="' . App::instance()->getUrlDocumentation() . '">',
					'</a>'
				),
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$providers                  = Providers::providers_id_title();
		$providers_credentials_name = Providers::providers_id_credentials_name();
		$provider_description       = Providers::providers_id_description();

		$fields[] =
			array(
				'title'    => \__( 'Service Provider', 'woocommerce-multicurrency' ),
				'desc'     => \__( 'Please choose one.', 'woocommerce-multicurrency' ),
				'id'       => $this->dao->key_rates_provider_id(),
				'css'      => 'min-width:350px;',
				'default'  => $this->dao->getRatesProviderID(),
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'desc_tip' => true,
				'options'  => $providers,
			);

		foreach ( $providers as $provider_id => $provider_name ) {
			if ( FixedRates::id() === $provider_id ) {
				continue;
			}
			$fields[] =
				array(
					'title'             => $providers_credentials_name[ $provider_id ],
					'id'                => $this->dao->key_rates_provider_credentials( $provider_id ),
					'type'              => 'text',
					'class'             => 'input-text regular-input ' . self::CSS_CLASS_CREDENTIALS_INPUT,
					'custom_attributes' => array(
						// Prevent browser from filling in this field automatically.
						'autocomplete'  => 'off',
						// Do not show LastPass password icon on this field.
						'data-lpignore' => 'true',
					),
					'desc'              => $provider_description[ $provider_id ],
				);
		}

		/**
		 * Cron interval options for rates update.
		 *
		 * @since 1.20.0
		 */

		// Get the existing schedules and filter out all but those we need. See `options` below.
		$cron_schedules = array_intersect_key(
			\wp_get_schedules(),
			array(
				UpdateScheduler::DEFAULT_SCHEDULE => '',
				UpdateScheduler::CUSTOM_SCHEDULE  => '',
				'hourly'                          => '',
				'daily'                           => '',
			)
		);

		$options = array();
		foreach ( $cron_schedules as $recurrence => $schedule ) {
			$options[ $recurrence ] = $schedule['display'];
		}

		$fields[] = array(
			'title'    => \esc_html__( 'Rates update schedule', 'woocommerce-multicurrency' ),
			'desc_tip' => \esc_html__( 'Not applicable to FixedRates', 'woocommerce-multicurrency' ),
			'id'       => $this->dao->key_rates_update_schedule(),
			'css'      => 'min-width:350px;',
			'default'  => $this->dao->getRatesUpdateSchedule(),
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'options'  => $options,
		);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Display notice that the rates were not retrieved.
	 *
	 * @since 1.15.0
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_rates_not_retrieved( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'rates_not_retrieved';
		$section_title = '';

		$desc_text = array(
			'<strong>' .
			\__( 'Currency exchange rates were not retrieved. Additional information may be available in the log.', 'woocommerce-multicurrency' ) .
			'</strong>',
		);

		$updater_error_message = Updater::get_error_message();
		if ( $updater_error_message ) {
			$desc_text[] =
				$this->format_timestamp( $updater_error_message['timestamp'] )
				. ': '
				. $updater_error_message['message'];
		}

		$section_desc =
			'<div class="error"><p><i class="dashicons dashicons-warning"></i> '
			. implode( '<br>', $desc_text ) .
			'</p></div>';

		$fields[] = array(
			'type'  => 'title',
			'id'    => $section_id,
			'title' => $section_title,
			'desc'  => $section_desc,
		);

		$fields[] = array(
			'type' => 'sectionend',
			'id'   => $section_id,
		);
	}

	/**
	 * Method format_timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @param string $timestamp Timestamp.
	 *
	 * @return string
	 */
	protected function format_timestamp( $timestamp ) {
		return \date_i18n( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), $timestamp ) . ' (' . \wc_timezone_string() . ')';
	}

	/**
	 * Display the date and time when the Provider updated the rates.
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_rates_timestamp( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'rates_timestamp';
		$section_title = '';

		$timestamp = $this->dao->getRatesTimestamp();

		$section_desc = $timestamp ?
			'<i class="dashicons dashicons-clock"></i> ' .
			\__( 'Rates updated on ', 'woocommerce-multicurrency' ) .
			'<code>' . $this->format_timestamp( $timestamp ) . '</code>'
			: '';

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "enabled currencies".
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_enabled_currencies( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'enabled_currencies';
		$section_title = \__( 'Enabled currencies', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				\__( 'Please specify all currencies you plan to use.', 'woocommerce-multicurrency' ),
				sprintf( /* Translators: %s - "Save changes" */
					\__( 'Then please click the [%s] button at the bottom to continue.', 'woocommerce-multicurrency' ),
					\__( 'Save Changes' )
				),
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		// Show currency codes, names and rates.
		$options = array();
		// Show currency codes and names.
		$options_no_rates = array();

		$rates = $this->rate_storage->getRates();

		foreach ( $this->currencies_with_rates as $currency_symbol => $currency_name ) {
			$options[ $currency_symbol ]          = $currency_symbol . ': ' . $currency_name;
			$options_no_rates[ $currency_symbol ] = $options[ $currency_symbol ];
			/**
			 * Changes here:
			 *
			 * @since 1.15.0 The rate is empty when adding a new currency with FixedRates provider.
			 * @since 2.12.2 Display low rates (BTC) using '%f' format to avoid things like '1.8e-5' scientific.
			 */
			if ( 'USD' !== $currency_symbol && ! empty( $rates[ $currency_symbol ] ) ) {
				$options[ $currency_symbol ] .= ' = USD/' . ( $rates[ $currency_symbol ] < 0.1 ? sprintf( '%.8f', $rates[ $currency_symbol ] ) : $rates[ $currency_symbol ] );
			}
		}

		$fields[] =
			array(
				'title'             => \__( 'Currencies', 'woocommerce-multicurrency' ),
				'id'                => $this->dao->key_enabled_currencies(),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => (array) $this->dao->getDefaultCurrency(),
				'desc'              => \__( 'Select all currencies that will be available on your website.', 'woocommerce-multicurrency' ),
				'options'           => $options,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => \esc_attr__( 'Select currencies', 'woocommerce-multicurrency' ),
					'required'         => 'required',
				),
			);

		$fields[] =
			array(
				'title'             => \__( 'Fallback currency', 'woocommerce-multicurrency' ),
				'id'                => $this->dao->key_fallback_currency(),
				'type'              => 'select',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => $this->dao->getDefaultCurrency(),
				'desc'              => \__( 'By default, show prices in this currency.', 'woocommerce-multicurrency' ),
				'options'           => $options_no_rates,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'required' => 'required',
				),
			);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * The Fixed Rates section.
	 *
	 * @since 1.15.0
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_fixed_rates( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'fixed_rates';
		$section_title = \__( 'Fixed Rates', 'woocommerce-multicurrency' );
		$section_desc  = \__( 'Enter the exchange rate of each currency relative to the US Dollar.', 'woocommerce-multicurrency' );

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		foreach ( $this->dao->getEnabledCurrencies() as $enabled_currency ) {
			if ( 'USD' === $enabled_currency ) {
				continue;
			}

			$title = \is_rtl()
				? '$1 USD ='
				: 'USD/' . $enabled_currency . ': &nbsp; $1 US = ';

			$fields[] =
				array(
					'title'             => $title,
					'type'              => 'number',
					'id'                => $this->dao->key_fixed_rate( $enabled_currency ),
					'desc'              => $enabled_currency,
					'default'           => 1.0,
					'placeholder'       => '1',
					'custom_attributes' => array(
						'min'  => 0.0000001,
						'step' => 'any',
					),
					'css'               => 'width: 10em; text-align: right',
				);
		}

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "Currency symbols".
	 *
	 * @since 1.1.0
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_currency_symbols( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'currency_symbols';
		$section_title = \__( 'Currency Symbols', 'woocommerce-multicurrency' );
		$section_desc  = \__( 'Change the currency symbol. Enter, for example, <code>C$</code> for Canadian Dollars.', 'woocommerce-multicurrency' );

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		foreach ( $this->dao->getEnabledCurrencies() as $enabled_currency ) {
			$fields[] =
				array(
					// Translators: placeholder for the currency symbol.
					'title' => sprintf( \__( '%s symbol', 'woocommerce-multicurrency' ), $enabled_currency ),
					'type'  => 'text',
					'id'    => $this->dao->key_currency_symbol( $enabled_currency ),
					'class' => 'input-text regular-input',
					'desc'  => \esc_html_x( 'Default', 'Settings placeholder', 'woocommerce-multicurrency' ) . ': ' . get_woocommerce_currency_symbol( $enabled_currency ),
				);
		}

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "price conversion settings".
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_price_conversion_settings( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'rounding_settings';
		$section_title = \__( 'Price Conversion Settings', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				\__( 'Fine-tune the prices after currency conversion.', 'woocommerce-multicurrency' ),
				'<div style="font-family: monospace"><strong>' .
				\__( 'Example', 'woocommerce-multicurrency' ) . ': ' .
				'</strong>' .
				\__( 'product price', 'woocommerce-multicurrency' ) .
				' &rarr; ' .
				\__( 'price after conversion', 'woocommerce-multicurrency' ) .
				' (' . \__( 'change the values below to recalculate', 'woocommerce-multicurrency' ) . ')' .
				'<br/>' .
				\__( 'Rate', 'woocommerce-multicurrency' ) . ': ' .
				'<span id="rate_example"></span><span id="rounding_calculator"></span>' .
				'</div>',
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$fields[] =
			array(
				'title'    => \__( 'Add a conversion fee (%)', 'woocommerce-multicurrency' ),
				'type'     => 'text',
				'id'       => $this->dao->key_fee_percent(),
				'class'    => 'input-text regular-input',
				'default'  => Rounder::DEFAULT_FEE_PERCENT,
				'desc'     => \__( 'Enter 2.5 to increase the converted price by 2.5%', 'woocommerce-multicurrency' ),
				'desc_tip' => false,
			);

		$fields[] =
			array(
				'title'    => \__( 'Round up to', 'woocommerce-multicurrency' ),
				'type'     => 'text',
				'id'       => $this->dao->key_round_to(),
				'class'    => 'input-text regular-input',
				'default'  => Rounder::DEFAULT_ROUND_TO,
				'desc'     => \__( 'Enter 10 to round 123.45 to 130', 'woocommerce-multicurrency' ),
				'desc_tip' => false,
			);

		$fields[] =
			array(
				'title'    => \__( 'Price charm', 'woocommerce-multicurrency' ),
				'type'     => 'text',
				'id'       => $this->dao->key_price_charm(),
				'class'    => 'input-text regular-input',
				'default'  => Rounder::DEFAULT_PRICE_CHARM,
				'desc'     => \__( 'Enter 0.01 to show 50 as 49.99', 'woocommerce-multicurrency' ),
				'desc_tip' => false,
			);

		$fields[] =
			array(
				'title'    => \__( 'Use adaptive rounding?', 'woocommerce-multicurrency' ),
				'type'     => 'checkbox',
				'id'       => $this->dao->key_automatic_rounding(),
				'default'  => false,
				'desc'     => implode( '<br>',
					array(
						\__( 'Check this box to make the rounding and charming dependent on the price.', 'woocommerce-multicurrency' ),
						\__( 'See the calculation example above.', 'woocommerce-multicurrency' ),
					)
				),
				'desc_tip' => false,
			);

		$fields[] = array(
			'id'      => $this->dao->key_raw_shipping_conversion(),
			'title'   => \__( '"Raw" shipping fees conversion', 'woocommerce-multicurrency' ),
			'desc'    => \__( 'Do not apply rounding, charming, and fee to the shipping fees.', 'woocommerce-multicurrency' ),
			'type'    => 'checkbox',
			'default' => 'no',
		);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "price formats".
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_price_formats( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'price_formats';
		$section_title = \__( 'Price formats', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				\__( 'Here you can change the way the prices are displayed, separately for each currency.', 'woocommerce-multicurrency' ) .
				// Translators: placeholder for the WooCommerce price format.
				sprintf( \__( 'The default format is <code>%s</code>', 'woocommerce-multicurrency' ), get_woocommerce_price_format() ),
				// Translators: placeholders will be displayed as-is.
				\__( 'The <code>%1$s</code> is the placeholder for the currency symbol. <code>%2$s</code> - for the amount.', 'woocommerce-multicurrency' ),
				// Translators: placeholders will be displayed as-is.
				\__( 'For example, if you want the currency symbol to go after the amount, you can use the <code>%2$s%1$s</code> format.', 'woocommerce-multicurrency' ),
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		foreach ( $this->dao->getEnabledCurrencies() as $enabled_currency ) {
			$fields[] =
				array(
					// Translators: placeholder for the currency symbol.
					'title'       => sprintf( \__( '%s price format', 'woocommerce-multicurrency' ), $enabled_currency ),
					'type'        => 'text',
					'id'          => $this->dao->key_price_format( $enabled_currency ),
					'class'       => 'input-text regular-input',
					'placeholder' => \esc_html_x( 'Default', 'Settings placeholder', 'woocommerce-multicurrency' ),
				);

		}
		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "auto-currencies" (if multilingual).
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_auto_currencies( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'auto_currencies';
		$section_title = \__( 'Link currency to language', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				\__( 'If you would like to set the currency <strong>automatically</strong> when the language is switched, please set the "Language - Currency" pairs below.', 'woocommerce-multicurrency' ),
				'<i class="dashicons dashicons-warning"></i> ' .
				\__( 'Note: our Currency Selector Widget can be used for the <strong>manual</strong> currency switching, independently of the language. In that case, the below settings will be ignored.', 'woocommerce-multicurrency' ),
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		// For "Enabled currencies only" drop-down boxes.
		$enabled_currency_code_options = array_intersect_key( $this->currencies_with_rates, array_flip( $this->dao->getEnabledCurrencies() ) );

		// Prepare the "nice" dropdown boxes.
		// foreach ( $this->currencies_with_rates as $code => $name ) {
		// $currency_code_options[ $code ] = $name . ' (' . \get_woocommerce_currency_symbol( $code ) . ')';
		// }
		foreach ( $enabled_currency_code_options as $code => $name ) {
			$enabled_currency_code_options[ $code ] = $code . ': ' . $name . ' (' . \get_woocommerce_currency_symbol( $code ) . ')';
		}

		/**
		 * Add the default option, which allows not to link any of the language (or all) to the currency.
		 *
		 * @since 1.4.0
		 * Previously, each language was linked to the shop currency by default, which prevented
		 * setting currency by the user's location.
		 */
		$enabled_currency_code_options     = array_reverse( $enabled_currency_code_options, true );
		$enabled_currency_code_options[''] = \esc_html__( 'Not linked', 'woocommerce-multicurrency' );
		$enabled_currency_code_options     = array_reverse( $enabled_currency_code_options, true );

		// Show the dropdown for each language (if WPGlobus is active).
		foreach ( App::instance()->getEnabledLanguages() as $language ) {

			$fields[] =
				array(
					'title'    => App::instance()->getEnLanguageName( $language ),
					// Translators: placeholder for the language name.
					'desc'     => sprintf( \__( 'Currency to use when the language is switched to %s.', 'woocommerce-multicurrency' ), App::instance()->getEnLanguageName( $language ) ),
					'id'       => $this->dao->key_language_to_currency( $language ),
					'css'      => 'min-width:350px;',
					'default'  => '',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'desc_tip' => true,
					'options'  => $enabled_currency_code_options,
				);
		}
		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Display notice that the Auto-currencies is disabled.
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_auto_currencies_is_disabled( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'auto_currencies_is_disabled';
		$section_title = \__( 'Link currency to language', 'woocommerce-multicurrency' );

		$section_desc =
			\__( 'To use this option, you need to install and activate one of the supported multilingual plugins:', 'woocommerce-multicurrency' ) .
			Multilingual::supported_plugins_as_ul();

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "General settings".
	 *
	 * @since 1.15.0
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_general_settings( array &$fields ) {

		$section_id    = self::SECTION_ID_PREFIX . 'general_settings';
		$section_title = \esc_html__( 'General options', 'woocommerce' );
		$section_desc  = '';

		$fields[] = array(
			'id'    => $section_id,
			'title' => $section_title,
			'desc'  => $section_desc,
			'type'  => 'title',
		);

		$_desc = array(
			\__( 'If checked, you can enter product prices for each currency.', 'woocommerce-multicurrency' ) . ' ' . __( '(Simple and variable products and subscriptions only)', 'woocommerce-multicurrency' ),
		);

		if ( FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			$_desc[] =
				'<p style="margin-top:1em"><span class="wp-ui-notification" style="padding:5px">' .
				'<span class="dashicons dashicons-warning"></span> ' .
				Notices::get_txt_warning() .
				sprintf( // Translators: %s placeholder for the New product editor
					\__( '"%s" feature must be disabled to enter the prices per currency.', 'woocommerce-multicurrency' ), \__( 'New product editor', 'woocommerce' ) ) .
				'</span></p>';
		}

		$fields[] = array(
			'id'      => $this->dao->key_allow_price_per_product(),
			'title'   => \__( 'Allow custom product pricing for extra currencies', 'woocommerce-multicurrency' ),
			'desc'    => implode( '<br/>', $_desc ),
			'type'    => 'checkbox',
			'default' => 'yes',
		);

		$fields[] = array(
			'id'      => $this->dao->key_switcher_conditions(),
			'title'   => \__( 'Disable currency switcher on account and payment pages', 'woocommerce-multicurrency' ),
			'desc'    => \__( 'If checked, the currency selector widgets and shortcodes will be hidden on Cart, Checkout and My Account pages.', 'woocommerce-multicurrency' ),
			'type'    => 'checkbox',
			'default' => 'no',
		);

		$fields[] = array(
			'id'      => $this->dao->key_lazy_load_js(),
			'title'   => \__( 'Lazy-load JS scripts', 'woocommerce-multicurrency' ),
			'desc'    => \__( 'Load scripts dynamically to speed-up the page load - recommended for UX and SEO.', 'woocommerce-multicurrency' ),
			'type'    => 'checkbox',
			'default' => 'yes',
		);

		$fields[] = array(
			'id'      => $this->dao->key_client_side_cookies(),
			'title'   => \__( 'Client-side cookies only', 'woocommerce-multicurrency' ),
			'desc'    => \__( 'Do not enable this unless asked by Support. Does not work with some page caching plugins!', 'woocommerce-multicurrency' ),
			'type'    => 'checkbox',
			'default' => 'no',
		);

		/*
		 * Log level.
		 */
		$link_view_logs =
			'<p style="font-style: normal; padding-left: 8px">' .
			'<a href="' . \admin_url( 'admin.php?page=wc-status&tab=logs' ) . '" target="_blank">' .
			\esc_html__( 'View logs', 'woocommerce-multicurrency' ) .
			'</a>' .
			'</p>';

		$fields[] = array(
			'title'    => \esc_html__( 'Log level', 'woocommerce-multicurrency' ),
			'desc'     => '<br>' . $link_view_logs,
			'id'       => $this->dao->key_log_level(),
			'css'      => 'min-width:350px;',
			'default'  => $this->dao->getLogLevel(),
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'desc_tip' => \esc_html__( 'What to write to the log file.', 'woocommerce-multicurrency' ),
			'options'  => array(
				Log::LOG_LEVEL_NONE   => \esc_html__( 'Nothing', 'woocommerce-multicurrency' ),
				\WC_Log_Levels::ERROR => \esc_html__( 'Error conditions', 'woocommerce-multicurrency' ),
				\WC_Log_Levels::INFO  => \esc_html__( 'Informational messages', 'woocommerce-multicurrency' ),
				\WC_Log_Levels::DEBUG => \esc_html__( 'Debug-level messages', 'woocommerce-multicurrency' ),
				Log::LOG_LEVEL_TRACE  => \esc_html__( 'Debug with tracing', 'woocommerce-multicurrency' ),
			),
		);

		$fields[] = array(
			'type' => 'sectionend',
			'id'   => $section_id,
		);
	}

	/**
	 * Display notice that the saving is disabled.
	 *
	 * @param array $fields Reference to the 'All Fields' array.
	 */
	protected function section_save_is_disabled( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'save_is_disabled';
		$section_title = '';
		$section_desc  =
			'<p><span class="wp-ui-notification" style="padding:5px;">' .
			'<span class="dashicons dashicons-lock"></span> ' .
			\__( 'Saving changes is not permitted.', 'woocommerce-multicurrency' ) .
			'</span></p>';

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * JS function to show/hide the credentials input fields.
	 *
	 * @since 1.20.0 Hide also the `rate update schedule` field.
	 */
	public function js_show_hide_credentials() {
		// @formatter:off
		?>
		<script>
			(function ($) {
				var $dropdown = $("#<?php echo \esc_js( $this->dao->key_rates_provider_id() ); ?>");
				var $allInputs = $(".<?php echo \esc_js( self::CSS_CLASS_CREDENTIALS_INPUT ); ?>").closest("tr");
				var showOnlyOneInput = function () {
					var $inputForSelectedProvider = $("#<?php echo \esc_js( $this->dao->key_rates_provider_credentials( '' ) ); ?>" + $dropdown.val()).closest("tr");
					var $rowRatesUpdateSchedule = $("#<?php echo \esc_js( $this->dao->key_rates_update_schedule() ); ?>").closest("tr");
					$allInputs.hide();
					$inputForSelectedProvider.show();
					if ("<?php echo \esc_js( FixedRates::id() ); ?>" === $dropdown.val()) {
						$rowRatesUpdateSchedule.hide();
					} else {
						$rowRatesUpdateSchedule.show();
					}
				};

				showOnlyOneInput();
				$dropdown.on("change", showOnlyOneInput);
			})(jQuery);
		</script>
		<?php
		// @formatter:on
	}

	/**
	 * JS function to disable saving settings.
	 */
	public function js_disable_save() {
		// @formatter:off
		?>
		<!--suppress JSDeprecatedSymbols -->
		<script>
			jQuery(function ($) {
				$("p.submit").hide();
				$("#mainform").submit(function (e) {
					e.preventDefault();
				});
			});
		</script>
		<?php
		// @formatter:on
	}

	/**
	 * Display a rounding example.
	 *
	 * @since  1.0.0
	 * @since  3.2.0-1 with auto-rounding.
	 */
	public function js_rounding_calculator() {
		// @formatter:off
		?>
		<script id="woomc-rounding_calculator-launcher">
			window.woomcRCConfig = {
				headerTitles: [
					'<?php echo \esc_js( \_x( 'Original', 'rounding_table', 'woocommerce-multicurrency' ) ); ?>',
					'<?php echo \esc_js( \_x( 'Converted', 'rounding_table', 'woocommerce-multicurrency' ) ); ?>',
					'<?php echo \esc_js( \_x( 'With fee', 'rounding_table', 'woocommerce-multicurrency' ) ); ?>',
					'<?php echo \esc_js( \_x( 'Rounded', 'rounding_table', 'woocommerce-multicurrency' ) ); ?>',
					'<?php echo \esc_js( \_x( 'With charm', 'rounding_table', 'woocommerce-multicurrency' ) ); ?>',
				],
				optionsPrefix: '<?php echo \esc_js( WP::OPTIONS_PREFIX ); ?>'
			}
		</script>
		<?php
		// @formatter:on
	}
}
