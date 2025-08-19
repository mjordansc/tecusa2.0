
<?php
/**
 * Add last nav item
 *
 * @package fc_corporativa
 */


 /* ------------- Get last nav item */

 function add_last_nav_item($items,$args) {

	$current_language = display_current_language();
	$other_languages = display_other_languages();
  $currencyShortcode = do_shortcode('[woocommerce-currency-switcher format="{{code}}"]');
  $languageShortcode = do_shortcode('[display_languages_header]');

 	if ($args->menu_id == 'main-menu-classy') {
    return $items .= '<li class="menu-item menu-item-type-post_type nav-item"><a href="'. translate_static_slug('about-us') .'" class="d-block d-lg-none nav-link">'. pll__('Made in Spain') .'</a></li>
    <li class="menu-item menu-item-type-post_type nav-item"><a href="'. translate_static_slug('extremecollectionlady') .'" class="d-block d-lg-none nav-link">#ExtremeCollectionLady</a></li>
    <li class="menu-item menu-item-type-post_type nav-item"><a href="'. translate_static_slug('my-account') .'" class="d-block d-lg-none nav-link">'. pll__('My Account') .'</a></li>
    <li class="menu-item menu-item-type-post_type nav-item"><a href="#" data-bs-toggle="modal" data-bs-target="#searchModal" class="d-block d-lg-none nav-link">'. pll__('Search') .'</a></li>
    <li class="menu-item menu-item-type-post_type nav-item mobile-header-lang">
      <div class="d-lg-none nav-link">'. $languageShortcode .'</div>
    </li>
    <li class="menu-item menu-item-type-post_type nav-item"><div class="relative nav-link d-lg-none">
      <div id="currency-switcher-pointer-mobile"></div>'. $currencyShortcode .'</div>
    </li>';

    return $items;


 		/*return $items .= (
 			'<li class="d-lg-none border-top-1 border-green mt-2 py-4">
			<div>
				<a href="tel:' . $phone . '" class="color-dark-green fs-09">
					Tel. '. $phone .'</a>
			</div>
			<div>
				<a href="mailto:' . $email . '" class="color-dark-green fs-09">
					'. $email .'</a>
			</div>
			</li>
			<li class="d-lg-none border-top-1 border-green py-4">
 			<a href="'. get_site_url() .'/contact/" class="fs-09 d-block d-lg-none text-uppercase mb-3">' . $presupuesto_label . '</a>
 			<a href="'. get_site_url() .'/noticias/" class="fs-09 d-block d-lg-none text-uppercase mb-3">' . $noticias_label . '</a>
			<div class="dropdown">
				<button class="btn btn-lang dropdown-toggle dropdown-bd-header
				d-flex align-items-center text-uppercase"
				type="button" id="dropdownMenuButton"
				data-toggle="dropdown" aria-haspopup="true"
				aria-expanded="false">
					' . $current_language . '
				</button>
				<div class="dropdown-menu dropdown-lang-menu" aria-labelledby="dropdownMenuButton">
					'. $other_languages .'
				</div>
			</div>
 			</li>');*/
 	} else {
 			return $items;
 	}
 }
 add_filter('wp_nav_menu_items','add_last_nav_item', 10, 2);
