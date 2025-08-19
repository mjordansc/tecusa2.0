/* Redirect to Thank you Page for CF7 */


var origin = window.location.origin;
var pathname = window.location.pathname;

document.addEventListener('wpcf7mailsent', function (event) {
  if ('11' == event.detail.contactFormId) {
    location = origin + pathname + '/gracias/';
		console.log(location);
  } else if ('640' == event.detail.contactFormId) {
    location = origin + patnname + '/gracies/';
		console.log(location);
  } else if ('642' == event.detail.contactFormId) {
    location = origin + patnname + '/thank-you/';
		console.log(location);
  }
}, false);
