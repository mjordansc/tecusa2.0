<?php
/**
 * WPPlayer
 *
 * @since 2.4.0
 *
 * Copyright (c) 2024, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\Media;

/**
 * Class WPPlayer
 *
 * @since 2.4.0
 * @noinspection PhpUnused
 */
class WPPlayer {

	/**
	 * Method is_active.
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	public static function is_active(): bool {
		return true;
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
		 * Filter tivwp_wp_player_is_ok_to_use
		 *
		 * @since 2.4.0
		 *
		 * @param bool $is_ok_to_use Default = false
		 *
		 * @return bool
		 */
		$is_ok_to_use = \apply_filters( 'tivwp_wp_player_is_ok_to_use', false );

		// To make sure the filter returns a bool.
		return true === $is_ok_to_use;
	}

	/**
	 * Get Player HTML.
	 *
	 * @since        2.4.0
	 *
	 * @param string $url            The media URL.
	 * @param array  $url_parameters Optional URL parameters to add to the URL.
	 *
	 * @return string
	 * @noinspection PhpUnused
	 */
	public static function get_html( string $url, array $url_parameters = array() ): string {

		if ( count( $url_parameters ) > 0 ) {
			\add_filter( 'wp_video_shortcode', function ( $output ) use ( $url_parameters ) {
				$url_parameters_string = \build_query( $url_parameters );

				return str_replace( '_=1', '_=1&' . $url_parameters_string, $output );
			} );
		}

		$html = \wp_video_shortcode( array(
			'src' => $url,
		) );

		return is_string( $html ) ? $html : '';
	}
}
