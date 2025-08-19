<?php
/**
 * AdminUtils
 *
 * @since 2.10.0
 *
 * Copyright (c) 2025, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\WC\Admin;

use WOOMC\Dependencies\TIVWP\TIVWP;
use WOOMC\Dependencies\TIVWP\Utils;

/**
 * Class AdminUtils
 *
 * @since 2.10.0
 */
class AdminUtils {

	/**
	 * Generic method enqueue_js.
	 *
	 * @since 2.10.0
	 *
	 * @param string $handle Script ID.
	 * @param bool   $args   Arguments for enqueue_script
	 * @param array  $deps   Dependencies for enqueue_script
	 *
	 * @return void
	 */
	protected static function enqueue_js( string $handle, bool $args = true, $deps = array( 'jquery' ) ): void {

		$ext_js = '.min.js';
		$ver    = TIVWP::ver();
		\wp_enqueue_script(
			$handle,
			\plugin_dir_url( __FILE__ ) . $handle . $ext_js,
			$deps,
			$ver,
			$args
		);
	}

	/**
	 * Style "external" links with an arrow SVG.
	 *
	 * @since 2.10.0
	 * @return void
	 */
	public static function style_external_links(): void {
		self::enqueue_js( 'tivwp-external-link' );
	}

	/**
	 * Show tivwp_wc_about HTML on the current tab.
	 *
	 * @since 2.10.0
	 *
	 * @param string $current_tab The current tab must be passed (get it from globals).
	 *
	 * @return void
	 */
	public static function show_tivwp_wc_about( string $current_tab ): void {

		// Show it after the "Save changes" button on the current tab.
		// See [...]woocommerce/includes/admin/views/html-admin-settings.php
		\add_action( 'woocommerce_after_settings_' . $current_tab, static function () {

			$file_path = Utils::locate_localized_file( __DIR__ . '/about/about_{{locale}}.inc.php' );
			if ( $file_path && is_readable( $file_path ) ) {
				echo '<style>.tivwp-wc-about{border-top:1px solid #ccc;padding:1em 0 2em 30px;}</style>';
				include $file_path;
			}
		} );
	}

	/**
	 * Style setting comment "tivwp-wc-admin-comment".
	 *
	 * @since 2.10.0
	 * @return void
	 */
	public static function style_setting_comment(): void {
		\add_action( 'admin_footer', static function () {
			echo '<style>.tivwp-wc-admin-comment{padding:.5em 0 .5em 2em;opacity:0.8;max-width:50em;}</style>';
		} );
	}

	/**
	 * Hide woocommerce_save_button.
	 *
	 * @since 2.10.0
	 * @return void
	 */
	public static function hide_woocommerce_save_button(): void {
		\add_action( 'admin_footer', static function () {
			echo '<style>.woocommerce-save-button{display:none!important;}</style>';
		} );
	}
}
