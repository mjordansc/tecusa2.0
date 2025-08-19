<?php
/**
 * Translate Slugs
 *
 * @package theextremecollectionusa
 */


 /* ------------- Display static URLs for Form Action attribute */
 function translate_static_slug($string) {
   if ( ! function_exists( 'pll_the_languages' ) ) return;

   $site_url = get_site_url();
   $translate_string = $string;

   $languages = pll_the_languages( array(
     'display_names_as'       => 'slug',
     'raw'                    => true,
     'hide_if_empty'					=> 1
   ) );
   $output = '';
   foreach ( $languages as $language ) {
 		$current = $language['current_lang'];
 		$slug = $language['slug'];
 		if ($current && $slug != 'en') {
      $output = $site_url . '/' . $slug . '/' . pll__($translate_string);
 		}
    elseif ($current && $slug == 'en') {
      $output = $site_url . '/' . pll__($translate_string);
    }
 	}
  return $output;
 	die();
 }
