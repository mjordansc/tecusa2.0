<?php
// Copyright (c) 2024, TIV.NET INC. All Rights Reserved.

namespace WOOMC;

use WOOMC\Dependencies\TIVWP\Scripting as TIVWPScripting;

/**
 * Class Scripting
 *
 * @since 4.2.0
 */
class Scripting extends TIVWPScripting {

	/**
	 * Init.
	 *
	 * @since 4.2.0
	 * @return void
	 */
	public static function init(): void {
		if ( DAO\Factory::getDao()->isLazyLoadJS() ) {
			self::set_mode( self::MODES['LAZY'] );
		} else {
			self::set_mode( self::MODES['WP'] );
		}
		parent::setup_hooks();
	}

	/**
	 * Method is_lazy_mode.
	 *
	 * @since 4.2.0
	 * @return bool
	 */
	public static function is_lazy_mode(): bool {
		return self::MODES['LAZY'] === self::$mode;
	}
}
