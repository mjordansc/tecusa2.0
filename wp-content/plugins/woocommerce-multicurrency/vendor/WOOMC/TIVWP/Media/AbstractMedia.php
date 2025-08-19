<?php
/**
 * Abstract media class
 *
 * @since        1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 * @noinspection PhpUnused
 */

namespace WOOMC\Dependencies\TIVWP\Media;

use WOOMC\Dependencies\TIVWP\TIVWP;

/**
 * Class AbstractMedia
 */
abstract class AbstractMedia {

	/**
	 * Type of the media.
	 */
	const TYPE = '';

	/**
	 * My URL extensions.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	const EXT = array();

	/**
	 * Media ID.
	 * Lowercase alphanumeric characters, dashes and underscores are allowed.
	 *
	 * @var string
	 */
	protected $id = 'tivwp';

	/**
	 * Getter: ID.
	 *
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * Media URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Media constructor.
	 *
	 * @param string $url The URL.
	 * @param string $id  The media ID.
	 */
	public function __construct( string $url, string $id = '' ) {
		$this->setUrl( $url );

		$id = \sanitize_key( $id );
		if ( $id ) {
			$this->id = $id;
		}
	}

	/**
	 * Return type of the media.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return static::TYPE;
	}

	/**
	 * Return sanitized URL.
	 *
	 * @return string
	 */
	public function get_sanitized_url(): string {
		return $this->url;
	}

	/**
	 * Default style for the embed HTML.
	 *
	 * @since        1.0.0
	 * @since        1.12.1 Added z-index.
	 * @since        1.12.2 Set z-index to one below WP admin bar.
	 * @return string
	 */
	public function get_css(): string {
		return 'border:none;width:100%;z-index:99998;';
	}

	/**
	 * Generate style HTML. Only if embed's ID is passed.
	 *
	 * @return string
	 */
	public function get_style_html(): string {
		$html = '';
		if ( $this->getId() ) {
			$html = '<style>';

			$html .= '#' . $this->getId() . '{' . $this->get_css() . '}';
			$html .= '</style>';
		}

		return $html;
	}

	/**
	 * Generate embed HTML.
	 *
	 * @return string The HTML.
	 */
	abstract public function get_embed_html(): string;

	/**
	 * Getter.
	 *
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}

	/**
	 * Setter.
	 *
	 * @param string $url The URL.
	 */
	public function setUrl( string $url ) {
		$this->url = $url;
	}

	/**
	 * Is this my type of the URL?
	 *
	 * @since 1.1.0 Return {@see is_my_extension} by default.
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	public static function is_my_url( string $url ): bool {
		return static::is_my_extension( $url );
	}

	/**
	 * Is this my file extension?
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	public static function is_my_extension( string $url ): bool {
		$url_path = \wp_parse_url( $url, PHP_URL_PATH );
		$pathinfo = pathinfo( $url_path );

		return isset( $pathinfo['extension'] ) && in_array( strtolower( $pathinfo['extension'] ), static::EXT, true );
	}

	/**
	 * List of CSS classes.
	 *
	 * @return string[]
	 */
	protected function get_css_classes(): array {
		return array(
			$this->id . '-media',
			$this->id . '-media-type-' . $this->get_type(),
			'intrinsic-ignore',
		);
	}

	/**
	 * CSS class for the HTML tag.
	 *
	 * @return string
	 */
	protected function get_css_class(): string {
		return implode( ' ', $this->get_css_classes() );
	}

	/**
	 * CSS selector to use in JS.
	 *
	 * @return string
	 */
	protected function get_css_selector(): string {
		return '.' . implode( '.', $this->get_css_classes() );
	}

	/**
	 * Load media JS.
	 *
	 * @since 1.12.1
	 * @return void
	 */
	public function load_js() {
		\wp_enqueue_script(
			'tivwp-media',
			\plugin_dir_url( __FILE__ ) . 'tivwp-media.min.js',
			array(),
			TIVWP::ver(),
			true
		);
	}

	/**
	 * Text to show while iframe is loading.
	 *
	 * @since 1.12.1
	 * @return string
	 */
	public function msg_loading(): string {
		return 'Loading...';
	}

	/**
	 * Optional video URL parameters.
	 *
	 * @since 2.4.0
	 * @return string[]
	 */
	protected function get_url_parameters(): array {
		return array();
	}

	/**
	 * Does Presto Player exists and its use enabled?
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	protected function is_presto_player_enabled(): bool {
		return PrestoPlayer::is_ok_to_use();
	}

	/**
	 * Get Presto Player HTML.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	protected function get_presto_player_html(): string {
		return PrestoPlayer::get_html( $this->getUrl() );
	}

	/**
	 * Does WP Player exists and its use enabled?
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	protected function is_wp_player_enabled(): bool {
		return WPPlayer::is_ok_to_use();
	}

	/**
	 * Get WP Player HTML.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	protected function get_wp_player_html(): string {
		return WPPlayer::get_html( $this->getUrl(), $this->get_url_parameters() );
	}
}
