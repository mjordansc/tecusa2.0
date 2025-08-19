/* Custom JS to initialize carousels and sliders */

jQuery(document).ready(function ($) {


  /* Home - Banner Slider */
  $('.slider-banner-home').slick({
    infinite: true,
    speed: 300,
    autoplaySpeed: 3500,
    autoplay: true,
    slidesToScroll: 1,
    slidesToShow: 1,
    arrows: true,
    fade: true,
    dots: false,
  });

	/* Home - Products Slider */
  $('.slider-products').slick({
    infinite: true,
    speed: 300,
    autoplaySpeed: 3500,
    autoplay: true,
    slidesToScroll: 1,
    slidesToShow: 5,
    arrows: false,
    fade: false,
    dots: false,
    responsive: [{
      breakpoint: 992,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1
      }
    }]
  });

  /* Home - ExtremeLadies Slider */
  $('.slider-extremeladies').slick({
    infinite: true,
    speed: 300,
    autoplaySpeed: 3500,
    autoplay: true,
    slidesToScroll: 1,
    slidesToShow: 4,
    arrows: false,
    fade: false,
    dots: false,
    responsive: [{
      breakpoint: 992,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 1,
        centerMode: true,
        centerPadding: '60px',
      }
    },
  {
    breakpoint: 768,
    settings: {
      slidesToShow: 1,
      slidesToScroll: 1,
      centerMode: true,
      centerPadding: '60px',
    }
  }]
  });

  $('.slider-magazine').slick({
    infinite: true,
    speed: 300,
    autoplaySpeed: 3500,
    autoplay: false,
    slidesToScroll: 1,
    slidesToShow: 1,
    arrows: true,
    fade: true,
    dots: false,
  });

});
