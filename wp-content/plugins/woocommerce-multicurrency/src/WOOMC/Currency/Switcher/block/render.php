<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$format = array();
if ( ! empty( $attributes['showCode'] ) ) {
	$format[] = '{{code}}';
}
if ( ! empty( $attributes['showName'] ) ) {
	$format[] = '{{name}}';
}
if ( ! empty( $attributes['showSymbol'] ) ) {
	$format[] = count( $format ) ? '({{symbol}})' : '{{symbol}}';
}

$is_switcher_type_2 = isset( $attributes['switcherType'] ) && '2' === $attributes['switcherType'];

$shortcode = '[woocommerce-currency-switcher flag=' . ( empty( $attributes['showFlag'] ) ? '0' : '1' ) . ' type=' . ( $is_switcher_type_2 ? '2' : '1' );
if ( count( $format ) ) {
	$format_string = implode( ' ', $format );
	$format_string = str_replace( '{{code}} {{', '{{code}}: {{', $format_string );

	$shortcode .= ' format="';
	$shortcode .= esc_html( $format_string );
	$shortcode .= '"';
}
$shortcode .= ']';

echo do_shortcode( $shortcode );
