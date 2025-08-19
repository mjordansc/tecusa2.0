/* Custom JS to initialize international phone library */

jQuery(document).ready(function ($) {

	var $intlPhone = document.querySelector("#input-ps-phone");
  var phoneInput = window.intlTelInput($intlPhone, {
    formatOnDisplay: true,
		nationalMode: false,
		separateDialCode: true,
		preferredCountries: ["us", "ca", "mx"],
  });

});
