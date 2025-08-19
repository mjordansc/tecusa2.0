/* Custom JS for Mobile Menu behavior */

jQuery(document).ready(function ($) {

  /* Display Menu */
	$('.navbar-toggler-right').on('click', function (e) {
		e.preventDefault();
		$("#navbar").fadeIn(500, 'swing');
		$(".close-menu").fadeIn(1500);
		$(".navbar-toggler-right").css('opacity', '0');
	});

	/* Hide Menu */
	$('.close-menu').on('click', function (e) {
		e.preventDefault();
		$("#navbar").fadeOut(500);
		$(".close-menu").fadeOut(500);
		$('body').css('overflow', 'scroll');
		$(".navbar-toggler-right").css('opacity', '100%');
	});

	$('.dropdown a.dropdown-toggle').click(function(){
		$(this).toggleClass('rotate-toggle');
	});

	$('.dropdown-submenu').children('a').addClass('dropdown-toggle');

	$('.btn-lang').click(function(){
		$(this).toggleClass('rotate-lang-toggle');
	});

	$('.dropdown-menu a.dropdown-toggle').on('click', function(e) {
  if (!$(this).next().hasClass('show')) {
    $(this).parents('.dropdown-menu').first().find('.show').removeClass('show');
  }
  var $subMenu = $(this).next('.dropdown-menu');
  $subMenu.toggleClass('show');
	$(this).toggleClass('rotate-toggle');
  $(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function(e) {
    $('.dropdown-submenu .show').removeClass('show');
  });


  return false;
});


});
