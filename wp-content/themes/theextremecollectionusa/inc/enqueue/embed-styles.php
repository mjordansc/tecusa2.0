<?php
/**
 * Embed Styles
 *
 * @package theextremecollectionusa
 */

/* ------------- Embed Modular CSS */
function include_styles() {
  wp_enqueue_style('bs-css', get_template_directory_uri() . '/dist/css/bootstrap.min.css' );
  wp_enqueue_style( 'slick-css', get_template_directory_uri() . '/dist/css/slick.css' );
  wp_enqueue_style( 'style-cookies', get_template_directory_uri() . '/dist/css/style-cookies.css?v=1.2.2' );
  wp_enqueue_style('select2-css', get_template_directory_uri() . '/dist/css/select2.min.css' );
  wp_enqueue_style('intl-phone-css', get_template_directory_uri() . '/dist/css/intlTelInput.css' );
  wp_enqueue_style('style-admin', get_template_directory_uri() . '/dist/css/style-admin.css' );
  wp_enqueue_style('style-general', get_template_directory_uri() . '/dist/css/style-general.css?v=1.2.2' );
  wp_enqueue_style('style-banner', get_template_directory_uri() . '/dist/css/style-banner.css?v=1.2.2' );
  wp_enqueue_style('style-blog', get_template_directory_uri() . '/dist/css/style-blog.css?v=1.2.2' );
  wp_enqueue_style('style-borders', get_template_directory_uri() . '/dist/css/style-borders.css' );
  wp_enqueue_style('style-colors', get_template_directory_uri() . '/dist/css/style-colors.css' );
  wp_enqueue_style('style-404', get_template_directory_uri() . '/dist/css/style-404.css' );
  wp_enqueue_style('style-extras', get_template_directory_uri() . '/dist/css/style-extras.css' );
  wp_enqueue_style('style-fonts', get_template_directory_uri() . '/dist/css/style-fonts.css?v=1.2.2' );
  wp_enqueue_style('style-footer', get_template_directory_uri() . '/dist/css/style-footer.css?v=1.2.2' );
  wp_enqueue_style('style-forms', get_template_directory_uri() . '/dist/css/style-forms.css?v=1.2.2' );
  wp_enqueue_style('style-gallery', get_template_directory_uri() . '/dist/css/style-gallery.css' ); 
  wp_enqueue_style('style-icons', get_template_directory_uri() . '/dist/css/style-icons.css' );
  wp_enqueue_style('style-navbar', get_template_directory_uri() . '/dist/css/style-navbar.css?v=1.2.2' );
  wp_enqueue_style('style-select2', get_template_directory_uri() . '/dist/css/style-select2.css' );
  wp_enqueue_style('style-slick', get_template_directory_uri() . '/dist/css/style-slick.css?v=1.2.2' );
  wp_enqueue_style('style-timeline', get_template_directory_uri() . '/dist/css/style-timeline.css' );
  wp_enqueue_style('style-calendar', get_template_directory_uri() . '/dist/css/style-calendar.css?v=1.2.2' );
  wp_enqueue_style('style-shop', get_template_directory_uri() . '/dist/css/style-shop.css?v=1.2.2' );
  wp_enqueue_style('style-taxonomies', get_template_directory_uri() . '/dist/css/style-taxonomies.css?v=1.2.2' );
  wp_enqueue_style('style-cart', get_template_directory_uri() . '/dist/css/style-cart.css' );
  wp_enqueue_style('style-checkout', get_template_directory_uri() . '/dist/css/style-checkout.css' );
  wp_enqueue_style('custom-css', get_template_directory_uri() . '/dist/css/custom.css?v=1.2.2' );
}
add_action( 'wp_enqueue_scripts', 'include_styles' );
