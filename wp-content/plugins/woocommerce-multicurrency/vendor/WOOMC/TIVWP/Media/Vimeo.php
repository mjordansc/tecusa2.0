<?php
/**
 * Embed Vimeo.
 *
 * @since   1.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\Media;

use WOOMC\Dependencies\TIVWP\HTML;
use WOOMC\Dependencies\TIVWP\Logger\Log;
use WOOMC\Dependencies\TIVWP\Logger\Message;
use WOOMC\Dependencies\TIVWP\Utils;

/**
 * Class Vimeo
 *
 * @since   1.1.0
 */
class Vimeo extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @since   1.1.0
	 *
	 * @var string
	 */
	public const TYPE = 'vimeo';

	/**
	 * URL for embedding.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	protected const EMBED_URL = 'https://player.vimeo.com/video/';

	/**
	 * Is this my type of the URL?
	 *
	 * @since        1.1.0
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public static function is_my_url( string $url ): bool {
		return false !== stripos( $url, self::TYPE );
	}

	/**
	 * Method get_player_url.
	 *
	 * @since 1.1.0
	 *
	 * Replace https://vimeo.com/424357917
	 *  with https://player.vimeo.com/video/424357917
	 *
	 * @since 1.1.1 Regex adjusted to match only URLs with trailing \d+
	 *         so the URLs like `https://vimeo.com/event/244910/embed` are not changed.
	 * @since 2.1.3 Moved to the get_player_url method.
	 * @since 2.1.3 Handle URLs like https://vimeo.com/652527858/600a14d99f?share=copy
	 *        The second part becomes ?h=...
	 * @since 2.1.6 Fix after 2.1.3 handle URLs like https://player.vimeo.com/video/649084438?h=f3797f156b
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
			|| preg_match( '#^https://vimeo\.com/event/\d+/embed#i', $url )
		) {
			return $url;
		}

		// Remove query parameters from the URL
		$url_without_query = preg_replace( '#\?.*#', '', $url );

		// Check if the source URL is a Vimeo URL with optional hash.
		if ( preg_match( '#^(https?://)?(www\.)?vimeo\.com/(\d+)(/[a-f0-9]+)?(\?.*)?$#i', $url_without_query, $matches ) ) {
			$video_id      = $matches[3];
			$hash          = isset( $matches[4] ) ? trim( $matches[4], '/' ) : null;
			$standard_form = self::EMBED_URL . $video_id;

			if ( $hash ) {
				$standard_form .= "?h=$hash";
			}

			return $standard_form;
		}

		// Return the original URL if it doesn't match the expected pattern.
		return $url;
	}

	/**
	 * Return sanitized URL.
	 *
	 * @since        1.1.0
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
	 * Vimeo URL parameters
	 * https://help.vimeo.com/hc/en-us/articles/12426260232977-Player-parameters-overview
	 * https://vimeo.zendesk.com/hc/en-us/articles/360001494447-Using-Player-Parameters
	 * quality = [240p, 360p, 540p, 720p, 1080p, 2k, 4k, auto]
	 *
	 * @since 2.4.0
	 * @return string[]
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	protected function get_url_parameters(): array {
		$default_url_parameters = array(
			'autopause'   => '1',
			'autoplay'    => '0',
			'background'  => '0',
			'badge'       => '0',
			'byline'      => '0',
			'color'       => '00adef',
			'controls'    => '1',
			'dnt'         => '0',
			'fun'         => '0',
			'loop'        => '0',
			'muted'       => '0',
			'playsinline' => '0',
			'portrait'    => '0',
			'quality'     => 'auto',
			'speed'       => '0',
			'title'       => '0',
			'transparent' => '1',
		);

		/**
		 * Filter to adjust the Vimeo URL parameters.
		 *
		 * @since   1.1.0
		 *
		 * @param string[] $default_url_parameters The parameters.
		 */
		$url_parameters = \apply_filters( 'tivwp_vimeo_url_parameters', $default_url_parameters );

		if ( ! is_array( $url_parameters ) ) {
			$url_parameters = $default_url_parameters;
		}

		return $url_parameters;
	}

	/**
	 * Default style for the embed HTML.
	 *
	 * @since   1.1.0
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
			'fullscreen',
			'gyroscope',
			'picture-in-picture',
		);

		/**
		 * Filter to adjust the Vimeo 'allow' parameters.
		 *
		 * @since   2.4.0
		 *
		 * @param string[] $default_allow The allow parameters.
		 */
		$allow = \apply_filters( 'tivwp_vimeo_allow_parameters', $default_allow );

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
		return false;
	}

	/**
	 * Generate embed HTML.
	 *
	 * @since   1.1.0
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
