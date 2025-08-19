<?php
/**
 * Embed PDF.
 *
 * @since   1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Dependencies\TIVWP\Media;

use WOOMC\Dependencies\TIVWP\HTML;

/**
 * Class PDF
 */
class PDF extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @var string
	 */
	const TYPE = 'pdf';

	/**
	 * My URL extensions.
	 *
	 * @var string
	 */
	const EXT = array( 'pdf' );

	/**
	 * Return type of the media.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return self::TYPE;
	}

	/**
	 * Default style for the embed HTML.
	 *
	 * @return string
	 */
	public function get_css(): string {
		return parent::get_css() . 'height:90vh';
	}

	/**
	 * Generate embed HTML.
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
