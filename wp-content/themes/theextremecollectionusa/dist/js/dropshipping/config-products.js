/* Config upload for dropshipping products */

jQuery(document).ready(function ($) {

  $('#dropshippingDeleteVariableProducts').on('click', function(e) {

    e.preventDefault();
    var origin = window.location.origin;
  	var pathname = window.location.pathname.split('/')[1];
  	var ajax_url = '/wp-admin/admin-ajax.php';
  	if (pathname === 'theextremecollectionusa') {
  		origin = origin + '/theextremecollectionusa'
  	}
    $('#form-loader').css('display', 'block');
    $('#dropshippingResponse').html('');
		$.ajax({

			url: origin + ajax_url,
			type:'post',
			data: {
							action:'dropshippingDeleteVariableProducts',
						},
			success: function(data) {
        console.log(data);
        $('#form-loader').css('display', 'none');
        $('#dropshippingResponse').html(data);
			},
			error: function (jqXHR, status, error) {
				console.log(error);
			},
		});

  });

  $('#dropshippingDeleteVariations').on('click', function(e) {

    e.preventDefault();
    var origin = window.location.origin;
    var pathname = window.location.pathname.split('/')[1];
    var ajax_url = '/wp-admin/admin-ajax.php';
    if (pathname === 'theextremecollectionusa') {
      origin = origin + '/theextremecollectionusa'
    }

    $('#form-loader').css('display', 'block');
    $('#dropshippingResponse').html('');

    $.ajax({

      url: origin + ajax_url,
      type:'post',
      data: {
              action:'dropshippingDeleteVariations',
            },
      success: function(data) {
        console.log(data);
        $('#form-loader').css('display', 'none');
        $('#dropshippingResponse').html(data);
      },
      error: function (jqXHR, status, error) {
        console.log(error);
      },
    });

  });

  $('#dropshippingLinkTranslations').on('click', function(e) {

    e.preventDefault();
    var origin = window.location.origin;
    var pathname = window.location.pathname.split('/')[1];
    var ajax_url = '/wp-admin/admin-ajax.php';
    if (pathname === 'theextremecollectionusa') {
      origin = origin + '/theextremecollectionusa'
    }
    $('#form-loader').css('display', 'block');
    $('#dropshippingResponse').html('');

    $.ajax({

      url: origin + ajax_url,
      type:'post',
      data: {
              action:'dropshippingLinkTranslations',
            },
      success: function(data) {
        console.log(data);
        $('#form-loader').css('display', 'none');
        $('#dropshippingResponse').html(data);
      },
      error: function (jqXHR, status, error) {
        console.log(error);
      },
    });

  });

})
