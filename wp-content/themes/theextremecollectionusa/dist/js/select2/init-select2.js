/* Custom JS to initialize select2 */

jQuery(document).ready(function ($) {

  $('form.woocommerce-checkout select').addClass('enableSelect2');
  /* Initialize select2 and hide arrows*/
  $('.FormControlSelect2').select2({
    minimumResultsForSearch: Infinity
  });
  $('.intro-form select').select2({
    minimumResultsForSearch: Infinity,
    width: '100%'
  });
  $('.enableSelect2').select2({});
  $('b[role="presentation"]').hide();
  $('.select2-selection__arrow').addClass('select2-selection__arrow2');
  $('ul li:first-child').attr('disabled');

});
