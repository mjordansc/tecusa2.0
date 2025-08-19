<?php
/**
 * Admin menu setup.
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin;

use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\App;
use WOOMC\Settings\Panel;

/**
 * Class Menu
 *
 * @package WOOMC\Admin
 */
class Menu implements InterfaceHookable {

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	const SLUG_MAIN = 'woomc';

	/**
	 * Menu position.
	 *
	 * @var string
	 */
	const POSITION = 56;

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		\add_action( 'admin_menu', array( $this, 'action__setup_main_menu' ) );
		\add_action( 'admin_menu', array( $this, 'action__add_settings_menu' ), App::HOOK_PRIORITY_LATER );
		\add_action( 'admin_head', array( $this, 'action__admin_head' ) );

		/**
		 * WC-Admin Navigation.
		 * https://developer.woocommerce.com/2021/01/15/call-to-action-create-access-for-your-extension-in-the-new-woocommerce-navigation/
		 *
		 * @since 2.15.4 Do not need it.
		 */
		0 && \add_action( 'admin_menu', array( $this, 'register_navigation_items' ), App::HOOK_PRIORITY_EARLY );
	}

	/**
	 * Create main menu.
	 */
	public function action__setup_main_menu() {

		\add_menu_page(
			'',
			\__( 'Multi-currency', 'woocommerce-multicurrency' ),
			'manage_woocommerce',
			self::SLUG_MAIN,
			'',
			'dashicons-admin-site',
			self::POSITION
		);
	}

	/**
	 * Add pointer to the Woo Settings.
	 */
	public function action__add_settings_menu() {

		\add_submenu_page(
			self::SLUG_MAIN,
			'',
			self::icon_tag( 'dashicons-admin-settings' ) . \__( 'Settings', 'woocommerce' ),
			'manage_woocommerce',
			\add_query_arg(
				array(
					'page' => 'wc-settings',
					'tab'  => Panel::TAB_SLUG,
				),
				'admin.php'
			)
		);
	}

	/**
	 * Remove the non-existent main menu page.
	 */
	public function action__admin_head() {

		/**
		 * Global array of submenus.
		 *
		 * @var array $submenu
		 */
		global $submenu;

		if ( isset( $submenu[ self::SLUG_MAIN ] ) ) {
			unset( $submenu[ self::SLUG_MAIN ][0] );
		}
	}

	/**
	 * Generate HTML tag for submenu icons.
	 *
	 * @param string $dashicons_id Dashicon ID.
	 *
	 * @return string
	 */
	public static function icon_tag( $dashicons_id ) {
		return '<span class="wp-menu-image dashicons-before ' . $dashicons_id . '"><span> ';
	}

	/**
	 * Register the navigation items in the WooCommerce navigation.
	 *
	 * @since 2.9.4-rc.1
	 * @since 2.9.5 Check if WC Admin is active.
	 */
	public function register_navigation_items() {

		/**
		 * WC Admin can be disabled, for example, using this filter:
		 * <code>
		 * add_filter( 'woocommerce_admin_disabled', '__return_true' );
		 * </code>
		 */
		if ( ! WC()->is_wc_admin_active() ) {
			return;
		}

		if (
			! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu' ) ||
			! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Screen' )
		) {
			return;
		}

		// Add the settings link in the WooCommerce > Settings navigation list
		\wc_admin_connect_page(
			array(
				'id'         => Panel::TAB_SLUG,
				'title'      => \__( 'Multi-currency', 'woocommerce-multicurrency' ),
				'parent'     => 'settings',
				'capability' => 'manage_woocommerce',
				'nav_args'   => array(
					'url'    => \add_query_arg(
						array(
							'page' => 'wc-settings',
							'tab'  => Panel::TAB_SLUG,
						),
						'admin.php'
					),
					'parent' => 'woocommerce-settings',
				),
			)
		);
	}
}
