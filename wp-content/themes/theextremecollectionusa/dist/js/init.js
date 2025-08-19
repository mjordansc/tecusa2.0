/* Custom JS for TEC USA */

jQuery(document).ready(function ($) {

  /* Basic smooth scrolling */

  $('.smooth-link').on('click', function (e) {
    e.preventDefault();

    $('html, body').animate({
      scrollTop: $($(this).attr('href')).offset().top
    }, 1000, 'swing');
  });

  $('.inner-link').on('click', function (e) {
    e.preventDefault();

    $('html, body').animate({
      scrollTop: $($(this).attr('href')).offset().top-190
    }, 1000, 'swing');
  });

  /* Smooth scrolling for 3rd level menu */

  $('.dropdown-submenu .dropdown-menu .dropdown-item').on('click', function (e) {
    e.preventDefault();
    if (window.location.hash) {

      $("#navbar").fadeOut(500);
      $(".close-menu").fadeOut(500);
      $('body').css('overflow', 'scroll');

      var url = $(this).attr('href');
      var trimmed_url = url.substring(url.lastIndexOf('/') + 1);
      if (trimmed_url == '') {
        $('html, body').animate({
          scrollTop: 0
        }, 1000, 'swing');
      } else {
        $('html, body').animate({
          scrollTop: $(trimmed_url).offset().top-140
        }, 1000, 'swing');
      }

    } else {
        window.location.href = $(this).attr('href');
    }
    /**/
  });

  /* Smooth after window is loaded */

  if (window.location.hash) {
    var hash = window.location.hash;
    $('html, body').animate({
      scrollTop :  $(hash).offset().top-140
    }, 1000, 'swing');
  }

  /* Hide and Show Filter Price on Mobile */

  $('#filterPriceMobile').on('click', function(e) {
    e.preventDefault();
    $(this).toggleClass('dropdownopen');
    $('.taxonomy-sidebar .widget_price_filter').toggleClass('show');
  });

  var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
    sURLVariables = sPageURL.split('&'),
    sParameterName,
    i;

    for (i = 0; i < sURLVariables.length; i++) {
      sParameterName = sURLVariables[i].split('=');

      if (sParameterName[0] === sParam) {
          return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
      }
    }
    return false;
  };

  $(window).on('popstate', function() {
    var current_step = getUrlParameter('step');
    if (current_step == '2') {
      $('#checkout-step2').css('display', 'block');
      $('.woocommerce-form-coupon-toggle').css('display', 'block');
      $('#checkout-step3').css('display', 'none');
      $('html, body').animate({ scrollTop: 0 }, "slow");
      $('#progress-bar-step-2').removeClass('complete').addClass('active');
      $('#progress-bar-step-3').removeClass('active');
    }
  });

  /* Home Banner scrolling */
  $('.scroll-home a').on('click', function (e) {
    e.preventDefault();

    $('html, body').animate({
      scrollTop: $("#featured").offset().top-80
    }, 1000, 'swing');
    return false;

  });

  /* Add Step 2 to URL after Checkout Load and user is logged-in */

  $('.checkout-button.wc-forward').on('click', function(e){
    e.preventDefault();
    var origin = window.location.origin;
    var pathname = window.location.pathname.split('/')[1];
    if (pathname === 'theextremecollectionusa') {
      origin = origin + '/theextremecollectionusa'
    }
    document.location.href = origin + '/checkout?step=2';
  });

  $('.button.checkout.wc-forward').on('click', function(e){
    e.preventDefault();
    var origin = window.location.origin;
    var pathname = window.location.pathname.split('/')[1];
    if (pathname === 'theextremecollectionusa') {
      origin = origin + '/theextremecollectionusa'
    }
    document.location.href = origin + '/checkout?step=2';
  });


  $(window).scroll(function () {
    if(sessionStorage["PopupShown"] != 'yes') {
      var $target = $("#product-row");
      $('#newsletter-popup').toggleClass('show-newsletter', $(this).scrollTop() > 1200);
    }
  });


  $('.newsletter-close').on('click', function (e) {
  	e.preventDefault();
    $('#newsletter-popup').toggleClass('show-newsletter');
  	sessionStorage["PopupShown"] = 'yes';
  });
	
$('.newsletter-close-text').on('click', function (e) {
  	e.preventDefault();
    $('#newsletter-popup').toggleClass('show-newsletter');
  	sessionStorage["PopupShown"] = 'yes';
  });

  $('#guest-checkout').on('click', function (e) {
    e.preventDefault();
    $('#checkout-wrapper').removeClass('d-none');
    $('#checkout-step2').css('display', 'block');
    $('#customer_login').css('display', 'none');
    $('.woocommerce-form-coupon-toggle').css('display', 'block');
    $('html, body').animate({ scrollTop: 0 }, "slow");
    $('#progress-bar-step-1').addClass('complete');
    $('#progress-bar-step-2').addClass('active');
  });

  /* Click event handler for invisible container to open modal */
  $(document).on('click', function (e) {
    e.stopPropagation();
    var container = $('.woocommerce-currency-switcher .dropdown-content');
    var pointer = $('#currency-switcher-pointer');
    var pointerMobile = $('#currency-switcher-pointer-mobile');
    if ( pointer.is(e.target) || pointerMobile.is(e.target) )  {
      container.css('display', 'block');
    } else if ( !container.is(e.target)
    && container.has(e.target).length === 0
    && ( !pointer.is(e.target) || !pointerMobile.is(e.target) ) ) {
      container.css('display', 'none');
    }
  });


  /* Disabled behavior for variations - Iconic Swatches */

  $('.iconic-was-swatches__item.iconic-was-swatches__item--out-of-stock .iconic-was-swatch--disabled').on('click', function(e){
    e.preventDefault();
    console.log('clicked here');
    $('#availableBtnVariations').css('display', 'block');
  });

  $('.iconic-was-swatches__item a').not('.iconic-was-swatch--disabled').on('click', function(e) {
    console.log('clicked over here');
    $('#availableBtnVariations').css('display', 'none');
    //let variationsData = $('.variations_form').data('product_variations');
    let selectedSize = $(this).data('attribute-value-name');
    let productID = $('#productID').val();

    var origin = window.location.origin;
    var pathname = window.location.pathname.split('/')[1];
    var ajax_url = '/wp-admin/admin-ajax.php';
		if (pathname === 'theextremecollectionusa') {
			origin = origin + '/theextremecollectionusa'
		}

		$.ajax({

			url: origin + ajax_url,
			type:'post',
			data: {
							action:'displaySpanishStockMessage',
							selectedSize: selectedSize,
              productID: productID
						},
			success: function(data) {
        var data = $.parseJSON(data);
        if ( data.match ) {
          console.log('this is an ESP variation');
          $('#spanishStockModal').modal('show');
        }
			},
			error: function (jqXHR, status, error) {
				console.log(error);
			},
		});

  });

  let displayProductName = $('#displayProductName').val();
  $('#notifyFormWrapper input[type="submit"]').on('click', function() {
    $('#input-productname-wrapper input').val(displayProductName);
  });

  /* Hover effect for product images in category pages */

  if ($(window).width() > 768) {
    $('.product.has-post-thumbnail').on('mouseenter', function(event) {
      $(this).find('.alternate-image:first').css('display', 'block');
    });

    $('.product.has-post-thumbnail').on('mouseleave', function(event) {
      $(this).find('.alternate-image:first').css('display', 'none');
    });
  }

});
