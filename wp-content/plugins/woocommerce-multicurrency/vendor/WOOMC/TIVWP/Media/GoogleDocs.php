<?php
/**
 * GoogleDocs
 *
 * @since 2.2.1
 *
 * Copyright (c) 2024, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\Media;

use WOOMC\Dependencies\TIVWP\HTML;

/**
 * Class GoogleDocs
 *
 * @since 2.2.1
 */
class GoogleDocs extends AbstractMedia {

	/**
	 * Override is_my_url().
	 *
	 * @since        2.2.1
	 * @inheritDoc
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public static function is_my_url( string $url ): bool {
		return str_starts_with( $url, 'https://docs.google.com/' );
	}

	/**
	 * Default style for the embed HTML.
	 *
	 * @since        2.2.1
	 *
	 * @return string
	 */
	public function get_css(): string {
		return parent::get_css() . 'aspect-ratio: 1/1;';
	}

	/**
	 * Override get_embed_html().
	 *
	 * @since        2.2.1
	 *
	 * @return string The HTML.
	 */
	public function get_embed_html(): string {

		$this->load_js();

		return HTML::make_tag(
			'div',
			array(
				'class'      => 'tivwp-media',
				'data-class' => $this->get_css_class(),
				'data-css'   => $this->get_css(),
				'data-type'  => $this->get_type(),
				'data-url'   => $this->get_sanitized_url(),
			),
			$this->msg_loading()
		);
	}
}
