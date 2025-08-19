<?php
/**
 * PrestoPlayer
 *
 * @since 2.4.0
 *
 * Copyright (c) 2024, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\Media;

/**
 * Class PrestoPlayer
 *
 * @since        2.4.0
 * @noinspection PhpUnused
 */
class PrestoPlayer {

	/**
	 * URL of the plugin on WordPress.
	 */
	public const WP_PLUGIN_URL = 'https://wordpress.org/plugins/presto-player/';

	/**
	 * Var preset_name.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	protected static $preset_name = 'simple';

	/**
	 * Method getPresetName.
	 *
	 * @since        2.4.0
	 * @return string
	 * @noinspection PhpUnused
	 */
	public static function getPresetName(): string {
		return self::$preset_name;
	}

	/**
	 * Method setPresetName.
	 *
	 * @since        2.4.0
	 *
	 * @param string $preset_name
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public static function setPresetName( string $preset_name ): void {
		self::$preset_name = $preset_name;
	}

	/**
	 * Method is_active.
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	public static function is_active(): bool {
		return class_exists( '\PrestoPlayer\Models\Preset' );
	}

	/**
	 * Method is_ok_to_use.
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	public static function is_ok_to_use(): bool {

		if ( ! self::is_active() ) {
			return false;
		}

		/**
		 * Filter tivwp_presto_player_is_ok_to_use
		 *
		 * @since 2.4.0
		 *
		 * @param bool $is_ok_to_use Default = false
		 *
		 * @return bool
		 */
		$is_ok_to_use = \apply_filters( 'tivwp_presto_player_is_ok_to_use', false );

		// To make sure the filter returns a bool.
		return true === $is_ok_to_use;
	}

	/**
	 * Get Presto Player preset ID if available.
	 *
	 * @since 2.4.0
	 * @return int
	 */
	public static function get_preset_id(): int {

		if ( ! self::is_ok_to_use() ) {
			// Presto Player not active or its use disabled.
			return 0;
		}

		try {
			/**
			 * Ignore recommendation.
			 *
			 * @noinspection PhpFullyQualifiedNameUsageInspection
			 *
			 * Ignore non-existing PrestoPlayer because we check it in the "if".
			 * @noinspection PhpUndefinedNamespaceInspection
			 * @noinspection PhpUndefinedClassInspection
			 */
			$preset    = new \PrestoPlayer\Models\Preset();
			$presets   = $preset->fetch( array( 'slug' => self::$preset_name ) );
			$preset_id = $presets->data[0]->id;
		} catch ( \Exception $e ) {
			$preset_id = 0;
		}

		return $preset_id;
	}

	/**
	 * Get Player HTML.
	 *
	 * @since        2.4.0
	 *
	 * @param string $url The media URL.
	 *
	 * @return string
	 * @noinspection PhpUnused
	 */
	public static function get_html( string $url ): string {

		$presto_player_preset_id = self::get_preset_id();
		if ( ! $presto_player_preset_id ) {
			return '';
		}

		$html = \do_shortcode(
			'[' .
			implode( ' ',
				array(
					'presto_player',
					'src="' . $url . '"',
					'preset=' . $presto_player_preset_id,
				)
			) .
			']'
		);

		return is_string( $html ) ? $html : '';
	}
}
