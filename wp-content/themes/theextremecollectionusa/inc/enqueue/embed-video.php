<?php
/**
 * Embed Youtube Videos
 *
 * @package linguacop
 */

/* ------------- Embed Youtube Video */
function get_iframe_url($embed_video_link) {
		$url = get_field($embed_video_link);
    parse_str( parse_url( $url, PHP_URL_QUERY ), $my_array_of_vars );
		$video_url = $my_array_of_vars['v'];
		echo '<iframe width="100%" class="" src="https://www.youtube.com/embed/' . $video_url . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope;" allowfullscreen>';
		echo '</iframe>';
}
