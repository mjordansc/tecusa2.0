<?php
/**
 * Display Languages
 *
 * @package theextremecollectionusa
 */


/* ------------- Display list of languages using a shortcode */
add_shortcode('display_languages', 'display_languages_list');
function display_languages_list() {
  $post_id = get_queried_object_id();
  /*if (get_term($post_id) != '') :
    echo pll_get_term($post_id, 'es');
  endif;*/
  if ( ! function_exists( 'pll_the_languages' ) ) return;
  global $post;
  //$current_post_type = $post->post_type;
	$languages = pll_the_languages( array(
		'display_names_as'       => 'slug',
		'raw'                    => true,
		'hide_if_empty'					=> 1
	) );

  $output = '';
  $output = '<div class="align-items-center justify-content-end fs-09 language-list-wrapper">';
  foreach ( $languages as $language ) {
		$current = $language['current_lang'];
		$slug = $language['slug'];
    $locale = $language['locale'];

		if ($current) :
      $output .= '<span class="text-uppercase d-block color-black-50 ms-3">';
      $output .= $slug . '</span>';
    else :
      if (is_404()) {
        $url = get_site_url() . '/' . $slug .'/404.php';
      }
      elseif ( is_page() || is_home() || is_shop() ) {
        $url = $language['url'];
      }
      else {
        $url = '#';
      }
			$output .= '<a class="d-flex align-items-center text-uppercase ms-3" href="'. $url .'">';
      $output .= $slug . '</a>';
	   endif;
	}
  $output .= '</div>';
  return $output;
	die();
}

/* ------------- Display list of languages using a shortcode */
add_shortcode('display_languages_header', 'display_languages_list_header');
function display_languages_list_header() {
  $post_id = get_queried_object_id();
  /*if (get_term($post_id) != '') :
    echo pll_get_term($post_id, 'es');
  endif;*/
  if ( ! function_exists( 'pll_the_languages' ) ) return;
  global $post;
  //$current_post_type = $post->post_type;
	$languages = pll_the_languages( array(
		'display_names_as'       => 'slug',
		'raw'                    => true,
		'hide_if_empty'					=> 1
	) );

  $output = '';
  $output = '<div class="d-flex align-items-center justify-content-center language-list-wrapper">';
  foreach ( $languages as $language ) {
		$current = $language['current_lang'];
		$slug = $language['slug'];
    $locale = $language['locale'];

		if ($current) :
      $output .= '<span class="text-uppercase d-block color-black-50 me-3">';
      $output .= $slug . '</span>';
    else :
      if (is_404()) {
        $url = get_site_url() . '/' . $slug .'/404.php';
      }
      elseif ( is_page() || is_home() || is_shop() ) {
        $url = $language['url'];
      }
      else {
        $url = '#';
      }
			$output .= '<a class="d-flex align-items-center text-uppercase me-3" href="'. $url .'">';
      $output .= $slug . '</a>';
	   endif;
	}
  $output .= '</div>';
  return $output;
	die();
}

/* ------------- Display current language */
function display_current_language() {
  if ( ! function_exists( 'pll_the_languages' ) ) return;
 $languages = pll_the_languages( array(
   'display_names_as'       => 'slug',
   'raw'                    => true,
   'hide_if_empty'					=> 1
 ) );
 $output = '';
 foreach ( $languages as $language ) {
   $current = $language['current_lang'];
   $slug = $language['slug'];
   $url = $language['url'];
   $locale = $language['locale'];
   if ($current) {
     $output = '<span> ' . $slug . '</span>';
   }
 }
 return $output;
 die();
}

/* ------------- Display other languages based on current language */
function display_other_languages() {
 $post_id = get_queried_object_id();
 /*if (get_term($post_id) != '') :
   echo pll_get_term($post_id, 'es');
 endif;*/
 if ( ! function_exists( 'pll_the_languages' ) ) return;
 global $post;
 //$current_post_type = $post->post_type;
 $languages = pll_the_languages( array(
   'display_names_as'       => 'slug',
   'raw'                    => true,
   'hide_if_empty'					=> 1
 ) );
 $output = '';
 foreach ( $languages as $language ) {
   $current = $language['current_lang'];
   $slug = $language['slug'];
   $locale = $language['locale'];

   if (!$current) {
     if (is_404()) {
       $url = get_site_url() . '/' . $slug .'/404.php';
     }
     elseif ( is_page() || is_home() || is_shop() || is_single() )  {
       $url = $language['url'];
     }
     else {
       $url = '#';
     }
     $output .= '<a class="dropdown-item d-flex align-items-center text-uppercase" href="'. $url .'">';
     $output .= $slug . '</a>';
   }
 }
 return $output;
 die();
}
