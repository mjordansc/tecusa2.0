/* Process intro form */

jQuery(document).ready(function ($) {



  $('#submitIntroForm').on('click', function(e){

    e.preventDefault();
    var introCountry = $('#introCountry').val();
    var introLanguage = $('#introLanguage').val();
    var introCurrency = $('#introCurrency').val();

    Cookies.set('tec_country', introCountry, {expires: 7});
    Cookies.set('tec_language', introLanguage, {expires: 7});
    Cookies.set('tec_currency', introCurrency, {expires: 7});

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
							action:'updateCurrency',
							currency: introCurrency
						},
			success: function(data) {
        console.log(data);
        $('.banner-intro-form').css('display', 'none !important');
        if ( introLanguage == 'en_US' ) {
          window.location.reload();
        } else {
          window.location.href = origin + '/es/shop/';
        }
			},
			error: function (jqXHR, status, error) {
				console.log(error);
			},
		});


  });

  $('#submitIntroFormMobile').on('click', function(e){

    e.preventDefault();
    var introCountry = $('#introCountryMobile').val();
    var introLanguage = $('#introLanguageMobile').val();
    var introCurrency = $('#introCurrencyMobile').val();

    Cookies.set('tec_country', introCountry, {expires: 7});
    Cookies.set('tec_language', introLanguage, {expires: 7});
    Cookies.set('tec_currency', introCurrency, {expires: 7});

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
              action:'updateCurrency',
              currency: introCurrency
            },
      success: function(data) {
        console.log(data);
        $('.banner-intro-form').css('display', 'none !important');
        if ( introLanguage == 'en_US' ) {
          window.location.reload();
        } else {
          window.location.href = origin + '/es/shop/';
        }
      },
      error: function (jqXHR, status, error) {
        console.log(error);
      },
    });


  });


});
