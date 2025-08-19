<?php
/**
 * Set up Options Page
 *
 * @package The_Extreme_Collection_USA
 */

/* ------------- Setup options page */

if( function_exists('acf_add_options_page') ) {

	acf_add_options_page(array(
		'page_title' 	=> 'TEC - Settings',
		'menu_title'	=> 'TEC - Settings',
		'menu_slug' 	=> 'tec-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false,
		'icon_url' 		=> 'dashicons-yes-alt',
		'position' 		=> 4
	));

}
