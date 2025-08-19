<?php
add_filter( 'wpsf_register_settings_iconic_was', 'iconic_was_settings' );

/**
 * WooCommerce Attribute Swatches Settings
 *
 * @param array $wpsf_settings
 *
 * @return array
 */
function iconic_was_settings( $wpsf_settings ) {
	$wpsf_settings['tabs'][] = array(
		'id'    => 'style',
		'title' => __( 'Style', 'iconic-was' ),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'        => 'style',
		'section_id'    => 'general',
		'section_title' => __( 'General', 'iconic-was' ),
		'section_order' => 0,
		'fields'        => array(
			array(
				'id'       => 'selected',
				'title'    => __( 'Selected Style', 'iconic-was' ),
				'subtitle' => __( 'Choose the style for selected image or colour swatches.', 'iconic-was' ),
				'type'     => 'select',
				'default'  => 'border',
				'choices'  => array(
					'tick'   => __( 'Tick', 'iconic-was' ),
					'border' => __( 'Border', 'iconic-was' ),
				),
			),
			array(
				'id'       => 'accordion',
				'title'    => __( 'Enable Accordion', 'iconic-was' ),
				'subtitle' => __( 'Show swatches in accordion?', 'iconic-was' ),
				'type'     => 'select',
				'default'  => 'no',
				'choices'  => array(
					'no'  => __( 'No', 'iconic-was' ),
					'yes' => __( 'Yes', 'iconic-was' ),
				),
			),
		),
	);

	return $wpsf_settings;
}
