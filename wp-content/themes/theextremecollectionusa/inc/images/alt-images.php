<?php
/**
 * Add ALT to images
 *
 * @package TEC
 */

/* ------------- Get ALT information from Wordpress */

function img_with_alt($custom_field, $class=null, $page_id = null) {

	global $post;
	$image_id = get_field($custom_field, $page_id);
	$image_src = wp_get_attachment_image_src($image_id, 'full');
	$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);

	echo '<img class="img-fluid '. $class . '" src="' . $image_src[0] . '"
	alt="'. $image_alt . '">';
}
function img_with_alt_sub($custom_field, $class = null, $page_id = null) {

	global $post;
	$image_id = get_sub_field($custom_field, $page_id);
	$image_src = wp_get_attachment_image_src($image_id, 'full');
	$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);

	echo '<img class="img-fluid '. $class . '" src="' . $image_src[0] . '"
	alt="'. $image_alt . '">';
}
function img_with_alt_featured() {

	global $post;
	$image_id = get_post_thumbnail_id( $post->ID );
	$image_src = wp_get_attachment_image_src($image_id, 'full');
	$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);

	echo '<img class="img-fluid" src="' . $image_src[0] . '"
	alt="'. $image_alt . '">';
}

function img_with_alt_term($custom_field, $term) {

	global $post;
	$image_id = get_field($custom_field, $term);
	$image_src = wp_get_attachment_image_src($image_id, 'full');
	$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);

	echo '<img class="img-fluid" src="' . $image_src[0] . '"
	alt="'. $image_alt . '">';
}
