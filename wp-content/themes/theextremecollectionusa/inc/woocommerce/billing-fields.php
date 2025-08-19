<?php
/**
 * Custom Woocommerce Checkout Billing Form
 *
 * @package The_Extreme_Collection_USA
 */

add_filter( 'woocommerce_checkout_fields' , 'update_billing_fields' );
function update_billing_fields( $fields ) {
  unset($fields['billing']['billing_company']);
  $fields['billing']['billing_phone']['priority'] = 22;
  $fields['billing']['billing_email']['priority'] = 20;

  if ( function_exists( 'pll__' ) ) :
    $fields['billing']['billing_hear_about_us'] = array(
      'label'       => pll__('How did you hear about us?', 'woocommerce'),
      'required'    => true,
      'type'        => 'select',
      'class'       => array('form-row'),
      'clear'       => true,
      'options'     => array(
                        'blank'       => pll__('Select an option'),
                        'instagram-ad'  =>  'Instagram Ad',
                        'personal-shopper'   =>  'Personal Shopper/Whatsapp',
                        'newsletters'        =>  'Newsletter',
                        'facebook-ad' =>  'Facebook Ad',
                      'other' =>  'Other',
      )
    );
    else :
      $fields['billing']['billing_hear_about_us'] = array(
      'label'       => 'How did you hear about us?', 'woocommerce',
      'required'    => true,
      'type'        => 'select',
      'class'       => array('form-row'),
      'clear'       => true,
      'options'     => array(
                        'blank'       => 'Select an option',
                        'instagram-ad'  =>  'Instagram Ad',
                        'personal-shopper'   =>  'Personal Shopper/Whatsapp',
                        'newsletters'        =>  'Newsletter',
                        'facebook-ad' =>  'Facebook Ad',
                      'other' =>  'Other',
      )
    );
endif;
$fields['billing']['billing_hear_about_us']['priority'] = 200;

  return $fields;
}


add_action( 'woocommerce_checkout_update_order_meta', 'billing_custom_fields_update_order_meta' );
function billing_custom_fields_update_order_meta( $order_id ) {
  if ( ! empty( $_POST['billing_hear_about_us'] ) ) :
    update_post_meta(
      $order_id,
      'billing_hear_about_us',
      sanitize_text_field( $_POST['billing_hear_about_us'] )
    );
  endif;
}

add_action( 'woocommerce_admin_order_data_after_order_details', 'custom_editable_order_meta_general' );
function custom_editable_order_meta_general( $order ) {  ?>

   <br class="clear" />
   <br class="clear" />
   <h3>Extra Order Information</h3>
   <?php
   $order_id = $order->get_id();
   $hearAboutUs = get_post_meta( $order_id, 'billing_hear_about_us', true );

   if ( $hearAboutUs == 'instagram-ad' ) :
     $hearAboutUs = 'Instagram Ad';
   elseif ( $hearAboutUs == 'newsletters' ) :
     $hearAboutUs = 'Newsletter';
   elseif ( $hearAboutUs == 'personal-shopper' ) :
     $hearAboutUs = 'Personal Shopper/Whatsapp';
   elseif ( $hearAboutUs == 'facebook-ad' ) :
     $hearAboutUs = 'Facebook Ad';
   elseif ( $hearAboutUs == 'other' ) :
     $hearAboutUs = 'Other';
   else :
     $hearAboutUs = 'Nothing selected';
   endif;
   ?>

  <div>
    <h4>How did you hear about us?</h4>
    <p><?php echo $hearAboutUs ?></p>
  </div>

  <?php
}
