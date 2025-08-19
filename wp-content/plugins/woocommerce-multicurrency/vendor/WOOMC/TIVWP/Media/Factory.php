<?php
/**
 * Media Factory.
 *
 * @since        1.0.0
 * @noinspection PhpUnused
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\Media;

/**
 * Class Factory
 *
 * @since        1.0.0
 */
class Factory {

	/**
	 * URL query parameter to force the media type.
	 *
	 * @since 2.2.1
	 *
	 * @var string
	 */
	private const PARAM_FORCED_TYPE = 'tivwp_type';

	/**
	 * Method get_url_forced_type.
	 *
	 * @since 2.2.1
	 *
	 * @param string $url URL to check.
	 *
	 * @return string
	 */
	private static function get_url_forced_type( string $url ): string {
		$url_query = \wp_parse_url( $url, PHP_URL_QUERY );
		if ( is_string( $url_query ) && $url_query ) {
			parse_str( $url_query, $query_params );
			if ( isset( $query_params[ self::PARAM_FORCED_TYPE ] ) ) {
				return \sanitize_text_field( $query_params[ self::PARAM_FORCED_TYPE ] );
			}
		}

		return '';
	}

	/**
	 * Get a media object.
	 *
	 * @since        1.0.0
	 *
	 * @param string $url The URL.
	 * @param string $id  The media ID.
	 *
	 * @return AbstractMedia
	 */
	public static function get_media( string $url, string $id = '' ) {

		// 1. Check if the media type is forced by the URL parameter.

		$url_forced_type = self::get_url_forced_type( $url );
		if ( $url_forced_type ) {
			$url = \remove_query_arg( self::PARAM_FORCED_TYPE, $url );

			$classes_supporting_forced_type = array(
				PDF::TYPE  => PDF::class,
				GImg::TYPE => GImg::class,
			);

			foreach ( $classes_supporting_forced_type as $type => $class_name ) {
				if ( $type === $url_forced_type ) {
					// If a match is found, return a new instance of the corresponding class
					return new $class_name( $url, $id );
				}
			}
		}

		// 2. Let each class decide if the URL is theirs. Sorted by assumed use frequency.

		$all_media_classes = array(
			YouTube::class,
			Vimeo::class,
			Video::class,
			PDF::class,
			GoogleDocs::class,
			Audio::class,
			Image::class,
			Cloudflare::class,
			TED::class,
			Issuu::class,
		);

		foreach ( $all_media_classes as $media_class_name ) {
			$method = array( $media_class_name, 'is_my_url' );
			if ( $method( $url ) ) {
				return new $media_class_name( $url, $id );
			}
		}

		// Fallback.
		return new Generic( $url, $id );
	}
}
