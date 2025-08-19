<?php
/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Cache;

use WOOMC\Dependencies\TIVWP\InterfaceHookable;
use WOOMC\App;
use WOOMC\Currency\Detector;

/**
 * Class WPSuperCache
 */
class WPSuperCache implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Do action 'wpsc_add_cookie'.
		 *
		 * @since 2.12.1
		 */
		\do_action( 'wpsc_add_cookie', Detector::COOKIE_FORCED_CURRENCY );

		\add_action( 'init', array( $this, 'no_cookie_no_cache' ), App::HOOK_PRIORITY_EARLY );
	}

	/**
	 * Do not cache pages for first visits (no cookie set).
	 * So, new visitors always get new pages and not cached.
	 *
	 * @since 2.12.1
	 */
	public function no_cookie_no_cache() {
		if ( empty( $_COOKIE[ Detector::COOKIE_FORCED_CURRENCY ] ) ) {
			global $cache_enabled;
			$cache_enabled = false;
		}
	}
}
