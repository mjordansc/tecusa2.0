<?php
/**
 * Custom Woocommerce Checkout Billing Form
 *
 * @package The_Extreme_Collection_USA
 */

 /* ------------- Display Flexifare price on Checkout load */
 add_action( 'wp_ajax_dropshippingDeleteVariations', 'dropshippingDeleteVariations' );
 add_action( 'wp_ajax_nopriv_dropshippingDeleteVariations', 'dropshippingDeleteVariations' );
 function dropshippingDeleteVariations() {

   $args = array(
     'post_type'         => 'product_variation',
     'post_status'       => 'any',
     'posts_per_page'    => -1,
     'lang'              => array('en', 'es'),
     'meta_query' => array(
           array(
               'key'     => '_sku',
               'value'   => '^(ESP)',
               'compare' => 'REGEXP'
           )
       )

   );
   $allVariations = new WP_Query($args);

   while ( $allVariations->have_posts() ) :
     $allVariations->the_post();
     $variationID = get_the_ID();
     error_log($variationID);
     $variationObject = wc_get_product($variationID);
     $argsAttachment = array(
       'post_type'         => 'attachment',
       'post_status'       => 'any',
       'posts_per_page'    => -1,
       'post_parent'       => $variationID
     );
     $attachments = new WP_Query($argsAttachment);

     if ( $attachments->found_posts != 0 ) :
       foreach ($attachments as $attachment) :
        wp_delete_attachment($attachment->ID, 'true');
       endforeach;
     endif;
     wp_reset_postdata();

     error_log('made it past attachments');

     wp_delete_object_term_relationships( $variationID, 'product_cat' );
     wp_delete_object_term_relationships( $variationID, 'product_tag' );

     error_log('made it past destroy relationships');
     
    if ( $variationObject ) :
      $variationObject->delete(true);
    endif;

   endwhile;

   ?>

   <p>All product variations with "ESP" SKU have been deleted!</p>
   <?php

   die();
 }
