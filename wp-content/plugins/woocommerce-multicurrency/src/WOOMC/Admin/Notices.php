<?php
/**
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin;

use WOOMC\App;

/**
 * Class Notices
 *
 * @since   1.10.0
 *
 * @package WOOMC\Admin
 */
class Notices {

	/**
	 * Public access to the __CLASS__.
	 *
	 * @return string
	 */
	public static function get_class() {
		return __CLASS__;
	}

	/**
	 * Plugin activated.
	 *
	 * @since 1.15.0 Do not display this notice if requirements not met.
	 *
	 * @param string $url_settings      The settings URL.
	 * @param string $url_documentation The documentation URL.
	 */
	public static function activation( $url_settings, $url_documentation ) {
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
				printf(/* translators: %s - WooCommerce Multi-currency. */
					\esc_html__( 'The %s extension is active. Please configure its settings.', 'woocommerce-multicurrency' ),
					\esc_html__( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' )
				);
				?>
			</p>
			<p>
				<a href="<?php echo \esc_url( $url_settings ); ?>" class="button button-primary">
					<?php \esc_html_e( 'Settings' ); ?>
				</a>
				<a href="<?php echo \esc_url( $url_documentation ); ?>" class="button">
					<?php \esc_html_e( 'Docs', 'woocommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Method get_wrapper_error.
	 *
	 * @since 3.2.4-2
	 *
	 * @param string $msg Text to wrap in "error".
	 *
	 * @return string
	 */
	protected static function get_wrapper_error( $msg ) {
		return '<div class="error"><p>' . $msg . '</p></div>';
	}

	/**
	 * Method get_txt_warning.
	 *
	 * @since 3.2.4-2
	 * @return string
	 */
	public static function get_txt_warning() {
		return \__( 'Warning', 'woocommerce' ) . ': ';
	}

	/**
	 * Method get_error_wcpay.
	 *
	 * @since 3.2.4-2
	 * @return string
	 */
	public static function get_error_wcpay() {
		$msg = implode( '', array(
			self::get_txt_warning(),
			sprintf( 'Disable the multi-currency feature in %s to prevent incorrect currency conversion and payment processing issues.',
				'<strong>' . \__( 'WooCommerce Payments', 'woocommerce-payments' ) . '</strong>'
			),
			' ',
			'<a class="tivwp-external-link" href="' . App::URL_WOO . 'document/multi-currency/compatibility-and-integration/#section-4">',
			\__( 'Learn more', 'woocommerce' ),
			'</a>',
		) );

		return self::get_wrapper_error( $msg );
	}

	/**
	 * Method get_error_incompatible_plugins.
	 *
	 * @since 3.2.4-2
	 *
	 * @param string[] $plugin_names Names of the incompatible plugins.
	 *
	 * @return string
	 */
	public static function get_error_incompatible_plugins( $plugin_names ) {
		$msg = implode( '', array(
			self::get_txt_warning(),
			\_n( 'This plugin is', 'These plugins are', count( $plugin_names ), 'woocommerce-multicurrency' ) . ' ' .
			\__( 'not compatible with Multi-currency by TIV.NET and may cause incorrect payment processing', 'woocommerce-multicurrency' ) . ':<br><strong>' .
			implode( ', ', $plugin_names ) .
			'</strong>',
		) );

		return self::get_wrapper_error( $msg );
	}
}
