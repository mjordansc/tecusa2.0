<?php
/**
 * Update Currency
 *
 * @package theextremecollectionusa
 */


 /* ------------- Display static URLs for Form Action attribute */
 add_action( 'wp_ajax_updateCurrency', 'updateCurrency' );
 add_action( 'wp_ajax_nopriv_updateCurrency', 'updateCurrency' );
 function updateCurrency() {
   $currency = $_POST['currency'];
   if( class_exists('WOOMC\Currency\Detector') ) :
     \WOOMC\Currency\Detector::set_currency_cookie( $currency, true );
   endif;
   die();
 }
