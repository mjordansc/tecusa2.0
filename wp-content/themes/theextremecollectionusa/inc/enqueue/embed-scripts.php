<?php
/**
 * Embed Scripts
 *
 * @package fc_corporativa
 */


/* ------------- Embed JS Scripts */
function include_scripts() {
  wp_enqueue_script ('popper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js', false, '', true);
  wp_enqueue_script ('bs-js', get_template_directory_uri() . '/dist/js/include/bootstrap.min.js', array('jquery'), '', true);
  wp_enqueue_script ('slick-js', get_template_directory_uri() . '/dist/js/include/slick.min.js', array('jquery'), '', true);
  wp_enqueue_script ('select2-js', get_template_directory_uri() . '/dist/js/include/select2.min.js', array('jquery'), '', true);
  wp_enqueue_script ('parsley-js', get_template_directory_uri() . '/dist/js/include/parsley.min.js', array('jquery'), '', true);
  wp_enqueue_script ('skip-link-focus-fix', get_template_directory_uri() . '/dist/js/include/skip-link-focus-fix.js', array(), '20151215', true );
  wp_enqueue_script ('cookies-js', get_template_directory_uri() . '/dist/js/include/js.cookie.min.js', array('jquery'), '', true);
  wp_enqueue_script ('cf7-js', get_template_directory_uri() . '/dist/js/cf7/cf7.js', array('jquery'), '', true);
  wp_enqueue_script ('header-js', get_template_directory_uri() . '/dist/js/header/header.js', array('jquery'), '', true);
  wp_enqueue_script ('menu-js', get_template_directory_uri() . '/dist/js/menu/menu.js', array('jquery'), '', true);
  wp_enqueue_script ('calendar-js', get_template_directory_uri() . '/dist/js/calendar/calendar.js?v=1.1.0', array('jquery'), '', true);
  wp_enqueue_script ('select2-init-js', get_template_directory_uri() . '/dist/js/select2/init-select2.js', array('jquery'), '', true);
  wp_enqueue_script ('slider-js', get_template_directory_uri() . '/dist/js/slider/init-slider.js?v=1.1.0', array('jquery'), '', true);
  wp_enqueue_script ('forms-js', get_template_directory_uri() . '/dist/js/forms/processing.js?v=1.1.0', array('jquery'), '', true);
  wp_enqueue_script ('introform-js', get_template_directory_uri() . '/dist/js/intro/process-form.js', array('jquery'), '', true);
  wp_enqueue_script ('dropshipping-js', get_template_directory_uri() . '/dist/js/dropshipping/config-products.js?v=1.1.0', array('jquery'), '', true);
  wp_enqueue_script ('init-js', get_template_directory_uri() . '/dist/js/init.js?v=1.1.1', array('jquery'), '', true);
}
add_action( 'wp_enqueue_scripts', 'include_scripts' );


function include_pickr() {
 wp_enqueue_style('pickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css' );
 wp_enqueue_script('pickr-js','https://cdn.jsdelivr.net/npm/flatpickr');
 wp_enqueue_script('pickr-es-js','https://npmcdn.com/flatpickr/dist/l10n/es.js');
}
add_action('wp_enqueue_scripts','include_pickr');

function map_enqueue() {

  if (is_page_template('template-contact.php')) {

    wp_enqueue_script ('map-js', get_template_directory_uri() . '/dist/js/map/init-map.js?v=1.1', array(), '20151215', true);
    wp_enqueue_script( 'map-js-api', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyD9ODKwNJOFxBMINhl8ZsVAo7if7mx6Kaw&callback=initMap', array(), '20151215', true );
  }

}
add_action ('wp_enqueue_scripts', 'map_enqueue');
