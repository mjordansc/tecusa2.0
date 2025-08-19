<?php
/**
 * Embed Google Drive image
 *
 * @since   2.2.1
 * Copyright (c) 2024, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\Media;

use WOOMC\Dependencies\TIVWP\HTML;

/**
 * Class GImg
 *
 * @since   2.2.1
 */
class GImg extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @since   2.2.1
	 *
	 * @var string
	 */
	public const TYPE = 'gimg';

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
	 * Generate embed HTML.
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
