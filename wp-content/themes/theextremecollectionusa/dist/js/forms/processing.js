
jQuery(document).ready(function ($) {

  /* Hide and Show Step 3 of Checkout Page */
  $('form.woocommerce-checkout input').attr('required', '');
  $('form.woocommerce-checkout').attr('data-parsley-trigger', 'focusout');
  $('#billing_address_2').attr('required', false);
  $('#shipping_first_name').attr('required', false);
  $('#shipping_last_name').attr('required', false);
  $('#shipping_company').attr('required', false);
  $('#shipping_city').attr('required', false);
  $('#shipping_postcode').attr('required', false);
  $('#shipping_address_1').attr('required', false);
  $('#shipping_address_2').attr('required', false);
  $('.mailchimp-newsletter input').attr('required', false);
  $('#ship-to-different-address-checkbox').attr('required', false);
  $('form.woocommerce-checkout').parsley();
  let $hearAboutUs = $("#billing_hear_about_us");
  $hearAboutUs.next('.select2-container').append('<ul class="parsley-errors-list filled" id="parsley-id-44" aria-hidden="false"><li class="parsley-required">This value is required.</li></ul>')


  $('#continue-to-pay').on('click', function(e) {
    e.preventDefault();
    let hearAboutUsVal = $hearAboutUs.find(':selected').val();
    $('form.woocommerce-checkout').parsley().on('field:error', function() {
      console.log('Validation failed for: ', this.$element);
    });
    if ($('form.woocommerce-checkout').parsley().validate() == false) {
      console.log('hay error');
      return;
    } else if ( hearAboutUsVal === 'blank' ) {
      console.log('option not selected');
      $hearAboutUs.css('position', 'relative');
      $('#parsley-id-44').css('display', 'block');
      return;
    } else {
      $('html, body').animate({
        scrollTop :  0
      }, 0, 'swing');
      window.history.pushState( {} , '', '?step=3' );
      $('#progress-bar-step-2').removeClass('active').addClass('complete');
      $('#progress-bar-step-3').addClass('active');
      $('#checkout-step2').css('display', 'none');
      $('.woocommerce-form-coupon-toggle').css('display', 'none');
      $('#checkout-step3').css('display', 'block');
    }
  });

  $(document.body).on("change", $hearAboutUs, function(){
   console.log('cambio el select');
   $('#parsley-id-44').css('display', 'none');
  });



  /* Personal Shopper */

  $('#personalshopper_form input[type="submit"]').on('click', function (e) {
    e.preventDefault();
    //$('.input-datetime-second input').val($('#datetime-second').val());
    var prefix = $('.iti__selected-dial-code').text();
    $('.phone-input').val(prefix + $('#input-ps-phone').val());
    $('#form-loader').css('display', 'flex');
    $('#personalshopper_form form').submit();
    $('#form-loader').css('display', 'none');
  });

});
