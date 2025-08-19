<?php
/**
 * Custom Woocommerce Checkout Billing Form
 *
 * @package The_Extreme_Collection_USA
 */

 /* ------------- Display Flexifare price on Checkout load */
 add_action( 'wp_ajax_dropshippingLinkTranslations', 'dropshippingLinkTranslations' );
 add_action( 'wp_ajax_nopriv_dropshippingLinkTranslations', 'dropshippingLinkTranslations' );
 function dropshippingLinkTranslations() {

   $args = array(
     'status'  =>  'published',
     'limit'   =>  -1,
     'tag'     =>  array('spanish-stock'),
     'lang'    =>  'en'
   );
   $spanishProducts = wc_get_products( $args );
   $counter = 0;
   foreach ( $spanishProducts as $spanishProduct ) :
     $spanishProductID = $spanishProduct->get_id();
     $spanishProductSKU = $spanishProduct->get_sku();
     $enArray[$counter] = array(
       'SKU' =>  $spanishProductSKU,
       'ID'  =>  $spanishProductID
     );
     $counter++;
   endforeach;

   $args = array(
     'status'  =>  'published',
     'limit'   =>  -1,
     'tag'     =>  array('spanish-stock'),
     'lang'    =>  'es'
   );
   $spanishProducts = wc_get_products( $args );
   $counter = 0;
   foreach ( $spanishProducts as $spanishProduct ) :
     $spanishProductID = $spanishProduct->get_id();
     $spanishProductSKU = $spanishProduct->get_sku();
     $esArray[$counter] = array(
       'SKU' =>  $spanishProductSKU,
       'ID'  =>  $spanishProductID
     );
     $counter++;
   endforeach;

   foreach ( $enArray as $enArraySingle ) :
     foreach ( $esArray as $esArraySingle ) :
       if ( $enArraySingle['SKU'] == $esArraySingle['SKU'] ) :
         $translationArray = array(
           'en'  =>  $enArraySingle['ID'],
           'es'  =>  $esArraySingle['ID']
         );
         pll_save_post_translations($translationArray);
       endif;
     endforeach;
   endforeach;

   $args = array(
    'status' => 'published',
    'limit'  => -1,
    'lang'   => array('en', 'es')
  );
  $allProducts = wc_get_products( $args );
  foreach ($allProducts as $singleProduct ) :
    $singleProductID = $singleProduct->get_ID();
    if ( get_field('products_available_preorder', $singleProductID) != 'yes' ) :
      update_field('products_available_preorder', 'no', $singleProductID);
    endif;
  endforeach;

   ?>

   <p>All the translations for new variable products and variations have been linked!</p>
   <?php

   die();
 }
