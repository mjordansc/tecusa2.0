<?php
/**
 * HTML
 *
 * @since        1.12.1
 *
 * Copyright (c) 2023, TIV.NET INC. All Rights Reserved.
 * @noinspection PhpUnused
 */

namespace WOOMC\Dependencies\TIVWP;

/**
 * Class HTML
 *
 * @since 1.12.1
 */
class HTML {

	/**
	 * Generate attribute for HTML tag.
	 *
	 * @since 1.12.1
	 * @since 2.7.0 Use esc_url.
	 *
	 * @param string $name  Attribute name.
	 * @param string $value Attribute value.
	 *
	 * @return string
	 */
	public static function make_tag_attribute( string $name, string $value ): string {
		if ( in_array( $name, array( 'src', 'href' ), true ) ) {
			$escaped_value = \esc_url( $value );
		} else {
			$escaped_value = \esc_attr( $value );
		}

		return ' ' . \sanitize_key( $name ) . '="' . $escaped_value . '"';
	}

	/**
	 * Generate link ("A" tag).
	 *
	 * @since 2.7.1
	 *
	 * @param string $url    URL (will be escaped).
	 * @param string $text   Text (NOT escaped!).
	 * @param string $target Target.
	 * @param array  $atts   Additional attributes.
	 *
	 * @return string
	 */
	public static function make_link( string $url, string $text, string $target = '_self', array $atts = array() ): string {

		$attributes = array_merge(
			array(
				'href'   => $url,
				'target' => $target,
			),
			$atts
		);

		return self::make_tag( 'a', $attributes, $text );
	}

	/**
	 * Generate "external" link ("A" tag).
	 *
	 * @since 2.10.0
	 *
	 * @param string $url  URL (will be escaped).
	 * @param string $text Text (NOT escaped!).
	 * @param array  $atts Additional attributes.
	 *
	 * @return string
	 */
	public static function make_external_link( string $url, string $text, array $atts = array() ): string {

		$attributes = array_merge(
			array(
				'href'  => $url,
				'class' => 'tivwp-external-link',
			),
			$atts
		);

		return self::make_tag( 'a', $attributes, $text );
	}

	/**
	 * Generate HTML tag.
	 *
	 * @since        1.12.1
	 *
	 * @param string $tag_name   Tag name.
	 * @param array  $attributes List of Attributes.
	 * @param string $content    Content (eg between <h1> and </h1>).
	 *
	 * @return string
	 * @noinspection PhpUnused
	 */
	public static function make_tag( string $tag_name, array $attributes = array(), string $content = '' ): string {
		$tag = '<' . $tag_name;
		ksort( $attributes );
		foreach ( $attributes as $name => $value ) {
			$tag .= self::make_tag_attribute( $name, $value );
		}
		$tag .= $content ? '>' . $content . '</' . $tag_name . '>' : '/>';

		return $tag;
	}

	/**
	 * Replace HTML tag in a string.
	 *
	 * @since 1.12.1
	 *
	 * @param string $sz          String containing the tag.
	 * @param string $tag         Tag to replace.
	 * @param string $replacement Replacement (default='')
	 *
	 * @return string
	 */
	public static function replace_tag_in_string( string $sz, string $tag, string $replacement = '' ): string {
		return preg_replace( "/<$tag(.*)<\\/$tag>/", $replacement, $sz );
	}

	/**
	 * Icons
	 */

	/**
	 * Method icon.
	 *
	 * @since 2.8.0
	 * @since 2.10.0 Made public.
	 *
	 * @param string $icon_id Dashicon ID.
	 *
	 * @return string
	 */
	public static function icon( $icon_id ): string {
		return '<i class="dashicons dashicons-' . $icon_id . '"></i> ';
	}

	/**
	 * Method icon_info.
	 *
	 * @since 2.8.0
	 * @return string
	 */
	public static function icon_info(): string {
		return self::icon( 'info-outline' );
	}

	/**
	 * Method icon_warning.
	 *
	 * @since 2.8.0
	 * @return string
	 */
	public static function icon_warning(): string {
		return self::icon( 'warning' );
	}

	/**
	 * Method icon_document.
	 *
	 * @since 2.10.0
	 * @return string
	 */
	public static function icon_document(): string {
		return self::icon( 'media-document' );
	}

	/**
	 * Method icon_email.
	 *
	 * @since 2.10.0
	 * @return string
	 */
	public static function icon_email(): string {
		return self::icon( 'email-alt' );
	}

	/**
	 * Tags
	 */

	/**
	 * Method tag_wrap.
	 *
	 * @since 2.8.0
	 *
	 * @param string $tag HTML tag.
	 * @param string $sz  String to surround with '<tag>'.
	 *
	 * @return string
	 */
	protected static function tag_wrap( $tag, $sz ): string {
		return \is_scalar( $sz ) ? "<$tag>$sz</$tag>" : '';
	}

	/**
	 * Method bold.
	 *
	 * @since 2.8.0
	 *
	 * @param string $sz String to make bold.
	 *
	 * @return string
	 */
	public static function bold( $sz ): string {
		return self::tag_wrap( 'strong', $sz );
	}

	/**
	 * Method code.
	 *
	 * @since 2.8.0
	 *
	 * @param string $sz String to surround with '<code>'.
	 *
	 * @return string
	 */
	public static function code( $sz ): string {
		return self::tag_wrap( 'code', $sz );
	}

	/**
	 * Method span_notification.
	 *
	 * @since 2.8.0
	 *
	 * @param string $sz String to surround with notification markup.
	 *
	 * @return string
	 */
	public static function span_notification( $sz ): string {
		return \is_scalar( $sz ) ? '<span class="wp-ui-text-notification">' . $sz . '</span>' : '';
	}
}
