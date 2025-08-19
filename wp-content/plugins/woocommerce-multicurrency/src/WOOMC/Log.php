<?php
/**
 * Logging to WooCommerce Status->Logs
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC;

use WOOMC\Dependencies\TIVWP\Logger\Log as TIVWPLog;
use WOOMC\DAO\Factory;

/**
 * WC_Logger wrapper.
 */
class Log extends TIVWPLog {

	/**
	 * Log source.
	 *
	 * @return string
	 */
	protected static function source(): string {
		return 'WooCommerce-Multicurrency';
	}

	/**
	 * Log level defined in the application settings.
	 *
	 * @since 2.5.4 Made public.
	 *
	 * @return string
	 */
	public static function threshold(): string {
		return Factory::getDao()->getLogLevel();
	}
}
