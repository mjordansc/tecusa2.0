<?php
/**
 * Embed YouTube.
 *
 * @since   1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\Media;

use WOOMC\Dependencies\TIVWP\HTML;
use WOOMC\Dependencies\TIVWP\Logger\Log;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Dependencies\TIVWP\Utils;

/**
 * Class YouTube
 *
 * @since   1.0.0
 */
class YouTube extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @since   1.0.0
	 *
	 * @var string
	 */
	public const TYPE = 'youtube';

	/**
	 * YouTube short URL domain.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	public const SHORT_URL_DOMAIN = 'youtu.be';

	/**
	 * URL for embedding.
	 *
	 * @since 1.1.0
	 * @since 1.12.0 Removed 'no-cookie' to support '?start='; Use as URL, not domain.
	 *
	 * @var string
	 */
	protected const EMBED_URL = 'https://www.youtube.com/embed/';

	/**
	 * Is this a short YouTube URL?
	 *
	 * @since 1.1.0
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	protected static function is_short_url( string $url ): bool {
		return false !== stripos( $url, self::SHORT_URL_DOMAIN );
	}

	/**
	 * Is this a YouTube Shorts URL?
	 *
	 * @since 2.4.0
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	protected static function is_youtube_shorts_url( string $url ): bool {
		return false !== stripos( $url, '/shorts/' );
	}

	/**
	 * Is this my type of the URL?
	 *
	 * @since        1.0.0
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public static function is_my_url( string $url ): bool {
		return false !== stripos( $url, self::TYPE ) || self::is_short_url( $url );
	}

	/**
	 * If the URL is a playlist one, normalize it and return. If not - return ''.
	 *
	 * @since 4.5.1
	 *
	 * <code>
	 *    // Examples:
	 *    // https://www.youtube.com/embed/videoseries?list=PLAYLIST_ID
	 *    // https://www.youtube.com/watch?v=NNN&list=PLAYLIST_ID&ab_channel=NNN
	 *    // https://www.youtube.com/watch?v=NNN&list=PLAYLIST_ID&index=2&ab_channel=NNN
	 * </code>
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function try_playlist_url( string $url ): string {
		// Parse the URL
		$parsed_url = \wp_parse_url( $url );

		// Check if there is a query component in the URL
		if ( ! empty( $parsed_url['query'] ) ) {

			// Parse the query string into an associative array
			parse_str( $parsed_url['query'], $query_params );

			// Check if 'list' parameter exists
			if ( isset( $query_params['list'] ) ) {
				// Return the embedded YouTube playlist URL
				return self::EMBED_URL . 'videoseries?list=' . \esc_attr( $query_params['list'] );
			}
		}

		return '';
	}

	/**
	 * Method get_player_url.
	 *
	 * @since   2.4.0 Moved from get_sanitized_url
	 * @since   1.0.0
	 * @since   1.1.0 Handle YouTube short URLs.
	 * @since   1.12.0 Handle start parameter and "shorts" videos.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected static function get_player_url( string $url ): string {

		// Make is HTTPS always.
		$url = str_replace( 'http:', 'https:', $url );

		// Check if the source URL is already in the standard form.
		if (
			preg_match( '#^' . preg_quote( self::EMBED_URL, '#' ) . '\d+#i', $url )
		) {
			return $url;
		}

		$maybe_its_a_playlist = self::try_playlist_url( $url );
		if ( ! empty( $maybe_its_a_playlist ) ) {
			return $maybe_its_a_playlist;
		}

		$parsed_url = \wp_parse_url( $url );
		$path       = $parsed_url['path'];
		$params     = array();
		$video_id   = '';

		if ( isset( $parsed_url['query'] ) ) {
			$query = $parsed_url['query'];
			parse_str( $query, $params );

			if ( isset( $params['t'] ) ) {
				// In embeds, '?t=' becomes '?start='.
				$params['start'] = $params['t'];
				unset( $params['t'] );
			}

			if ( isset( $params['index'] ) ) {
				// Remove 'index' from playlist.
				// https://www.youtube.com/watch?v=XXXXX&list=XXXXX&index=3
				unset( $params['index'] );
			}
		}

		if ( isset( $params['v'] ) ) {
			// https://www.youtube.com/watch?v=6UHkpR_fDHo&t=30
			$video_id = $params['v'];
			unset( $params['v'] );
		} elseif ( preg_match( '/^\/(embed|shorts)\/(.+)/', $path, $matches ) ) {
			// https://www.youtube.com/embed/6UHkpR_fDHo?start=30
			// https://www.youtube.com/shorts/-DqaBgnMra0
			$video_id = $matches[2];
		} elseif ( self::is_short_url( $url ) ) {
			// https://youtu.be/6UHkpR_fDHo
			$video_id = ltrim( $path, '/' );
		}

		// Return the original URL if it doesn't match the expected pattern.
		return $video_id ? self::EMBED_URL . $video_id : $url;
	}

	/**
	 * Return sanitized URL.
	 *
	 * @since        1.0.0
	 *
	 * @return string
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function get_sanitized_url(): string {

		$url = $this->getUrl();
		$url = filter_var( $url, FILTER_SANITIZE_URL );
		$url = filter_var( $url, FILTER_VALIDATE_URL );
		if ( ! $url ) {
			Log::error( new Message( array( 'Invalid media URL', $url ) ) );

			return '';
		}

		$url = self::get_player_url( $url );

		$params = $this->get_url_parameters();

		return \add_query_arg( $params, $url );
	}

	/**
	 * YouTube's parameters
	 * https://developers.google.com/youtube/player_parameters
	 * $params['modestbranding'] = '1'; // Deprecated on August 15, 2023
	 * $params['controls']    = '0'; // Not sure about this
	 *
	 * @since        2.4.0
	 * @return string[]
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	protected function get_url_parameters(): array {
		$default_url_parameters = array(
			'playsinline' => '0',
			'rel'         => '0', // related videos will come from the same channel as the video that was just played
		);

		/**
		 * Filter to adjust the YouTube URL parameters.
		 *
		 * @since   1.12.1
		 *
		 * @param string[] $default_url_parameters The parameters.
		 */
		$url_parameters = \apply_filters( 'tivwp_youtube_url_parameters', $default_url_parameters );

		if ( ! is_array( $url_parameters ) ) {
			$url_parameters = $default_url_parameters;
		}

		return $url_parameters;
	}

	/**
	 * Default style for the embed HTML.
	 *
	 * @since   1.0.0
	 * @return string
	 */
	public function get_css(): string {
		return parent::get_css() . 'height:100%;position:absolute;top:0;left:0;';
	}

	/**
	 * Returns 'allow' parameters string.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	protected function get_allow(): string {
		$default_allow = array(
			'accelerometer',
			'autoplay',
			'clipboard-write',
			'encrypted-media',
			'gyroscope',
			'picture-in-picture',
			'web-share',
		);

		/**
		 * Filter to adjust the YouTube 'allow' parameters.
		 *
		 * @since   2.4.0
		 *
		 * @param string[] $default_allow The allow parameters.
		 */
		$allow = \apply_filters( 'tivwp_youtube_allow_parameters', $default_allow );

		if ( ! Utils::is_array_of_strings( $allow ) ) {
			$allow = $default_allow;
		}

		return implode( '; ', $allow );
	}

	/**
	 * Method get_paywall_player_html.
	 *
	 * @since   2.4.0 Moved to a separate method
	 * @since   1.6.0 Added `fitvidsignore class.
	 * @return string
	 */
	protected function get_paywall_player_html() {
		$this->load_js();

		$attributes = array(
			'class'      => 'tivwp-media fitvidsignore',
			'style'      => 'padding:56.25% 0 0 0;position:relative',
			'data-class' => $this->get_css_class(),
			'data-type'  => $this->get_type(),
			'data-css'   => $this->get_css(),
			'data-url'   => $this->get_sanitized_url(),
		);

		$allow = $this->get_allow();
		if ( $allow ) {
			$attributes['data-allow'] = $allow;
		}

		return HTML::make_tag(
			'div',
			$attributes,
			$this->msg_loading()
		);
	}

	/**
	 * Do we have to force the Paywall player?
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	protected function is_force_paywall_player(): bool {
		return self::is_youtube_shorts_url( $this->getUrl() );
	}

	/**
	 * Generate embed HTML.
	 *
	 * @since   1.0.0
	 * @return string The HTML.
	 */
	public function get_embed_html(): string {

		if ( $this->is_force_paywall_player() ) {
			return $this->get_paywall_player_html();
		}

		if ( $this->is_wp_player_enabled() ) {
			return $this->get_wp_player_html();
		}

		if ( $this->is_presto_player_enabled() ) {
			return $this->get_presto_player_html();
		}

		return $this->get_paywall_player_html();
	}
}
