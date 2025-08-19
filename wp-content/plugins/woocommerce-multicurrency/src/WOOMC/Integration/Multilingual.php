<?php
/**
 * Multilingual plugins' integration.
 *
 * @since 2.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

/**
 * Class Multilingual.
 *
 * @since 2.1.0
 */
class Multilingual {

	/**
	 * Supported plugins.
	 *
	 * @var array
	 */
	protected static $supported_plugins = array(
		'WPGlobus' => array(
			'url' => 'https://wordpress.org/plugins/wpglobus/',
		),
		'Polylang' => array(
			'url' => 'https://wordpress.org/plugins/polylang/',
		),
	);

	/**
	 * True if one of the supported multilingual plugins is active.
	 *
	 * @since 2.9.4-rc.1 Moved from Locale class.
	 *
	 * @return bool
	 */
	public static function is_multilingual() {
		return class_exists( 'Polylang', false ) || class_exists( 'WPGlobus', false );
	}

	/**
	 * Getter for Supported plugins.
	 *
	 * @return array
	 */
	public static function getSupportedPlugins() {
		return self::$supported_plugins;
	}

	/**
	 * Return the list of supported plugins in the "<ul>" HTML format.
	 *
	 * @return string
	 */
	public static function supported_plugins_as_ul() {

		$supported = array();

		foreach ( \wp_list_pluck( self::getSupportedPlugins(), 'url' ) as $name => $url ) {
			$supported[] = '- <a href="' . $url . '">' . $name . '</a>';
		}

		return '<ul><li>' . implode( '</li><li>', $supported ) . '</li></ul>';
	}
}
