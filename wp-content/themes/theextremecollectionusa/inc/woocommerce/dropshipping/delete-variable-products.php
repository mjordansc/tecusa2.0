<?php
/**
 * Custom Woocommerce Checkout Billing Form
 *
 * @package The_Extreme_Collection_USA
 */

 /* ------------- Display Flexifare price on Checkout load */
 add_action( 'wp_ajax_dropshippingDeleteVariableProducts', 'dropshippingDeleteVariableProducts' );
 add_action( 'wp_ajax_nopriv_dropshippingDeleteVariableProducts', 'dropshippingDeleteVariableProducts' );
 function dropshippingDeleteVariableProducts() {

   $args = array(
     'status' => 'published',
     'limit'  => -1,
     'tag'    => array('spanish-stock'),
     'lang'   => array('en', 'es')
   );
   $spanishProducts = wc_get_products( $args );

    foreach ($spanishProducts as $spanishProduct) :
      $spanishProductID = $spanishProduct->get_ID();
      $attachments = get_attached_media( '',  );
      
      foreach ($attachments as $attachment) :
        wp_delete_attachment($attachment->ID, 'true');
      endforeach;

      $args = array(
        'post_type'         => 'attachment',
        'post_status'       => 'any',
        'posts_per_page'    => -1,
        'post_parent'       => $spanishProductID
      );
      $attachments = new WP_Query($args);

      if ( $attachments->found_posts != 0 ) :
        foreach ($attachments as $attachment) :
          wp_delete_attachment($attachment->ID, 'true');
        endforeach;
      endif;
      wp_reset_postdata();
      wp_delete_object_term_relationships( $spanishProductID, 'product_cat' );
      wp_delete_object_term_relationships( $spanishProductID, 'product_tag' );
      wp_delete_post($spanishProductID, true);

   endforeach;

   ?>
   <p>All Spanish Stock products have been deleted!</p>
   <?php

   die();
 }
