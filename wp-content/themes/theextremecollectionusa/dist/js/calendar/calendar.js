
jQuery(document).ready(function ($) {

  var default_pickup_date = new Date();
  var default_return_date = new Date();
  default_pickup_date.setHours( 10, 0, 0 );
  default_return_date.setHours( 10, 0, 0 );
  var numberOfDaysToAdd_return = 7;
  default_pickup_date.setDate(default_pickup_date.getDate());
  default_return_date.setDate(default_return_date.getDate() + numberOfDaysToAdd_return);

  var current_date = new Date();
  var current_pickup_date = default_pickup_date;

/*var current_lang_code = $('html')[0].lang;
if (current_lang_code == 'es-ES' ) {
  var current_lang = 'es';
} else if (current_lang_code == 'pt-PT') {
  var current_lang = 'pt';
} else {
  var current_lang = 'en';
}*/


//if ($(window).width() > 768) {
  var pickup_calendar = flatpickr(".datetime-first", {
    //locale: current_lang,
    enableTime: true,
    dateFormat: "d/m/Y H:i",
    time_24hr: false,
    minDate: current_date,
    defaultDate: current_date,
    monthSelectorType:"static",
    wrap:true,
    prevArrow:'',
    nextArrow:'',
    appendTo: window.document.querySelector('#datetime-first-wrapper'),
    disableMobile:true,
    onOpen: function(selectedDates, dateStr, instance) {
        $('.flatpickr-time').append('<div class="flatpickr-confirm" tabindex="-1">&nbsp;<span class="screen-reader-text">Confirm</span></div>');
    },
    onClose: function(selectedDates, dateStr, instance) {
        $('.flatpickr-time').find('.flatpickr-confirm').remove();
        //console.log(current_pickup_date);
    },
    onChange: function(selectedDates, dateStr, instance) {
      $('.flatpickr-day').attr('readonly', 'readonly');
      //var selected_date = pickup_calendar.parseDate(dateStr);
      //var new_date = new Date(selected_date);
      //current_pickup_date = new_date;
      //current_pickup_date.setHours( 10, 0, 0 );
      //new_date = new_date.fp_incr(7);
      //new_date.setHours( 10, 0, 0 );
      //return_calendar.setDate(new_date);
      //return_calendar.set('minDate', selected_date);
      $('.input-datetime-first').val(dateStr);
    },
  });
  var return_calendar = flatpickr(".datetime-second", {
    enableTime: true,
    //locale: current_lang,
    dateFormat: "d/m/Y H:i",
    time_24hr: false,
    defaultDate: default_return_date,
    minDate: current_date,
    //minDate: $('.pickup-date-label').attr('value'),
    monthSelectorType:"static",
    wrap:true,
    prevArrow:'',
    nextArrow:'',
    disableMobile:true,
    appendTo: window.document.querySelector('#datetime-second-wrapper'),
    onOpen: function(selectedDates, dateStr, instance) {
        $('.flatpickr-time').append('<div class="flatpickr-confirm" tabindex="-1">&nbsp;<span class="screen-reader-text">Confirm</span></div>');
    },
    onClose: function(selectedDates, dateStr, instance) {
        $('.flatpickr-time').find('.flatpickr-confirm').remove();
    },
    onChange: function(selectedDates, dateStr, instance) {
      $('.flatpickr-day').attr('readonly', 'readonly');
      $('.input-datetime-second').val(dateStr);
      /*if (current_return_date < current_pickup_date) {
        var new_date;
        new_date = current_pickup_date;
        new_date = new_date.fp_incr(7);
        new_date.setHours( 10, 0, 0 );
        return_calendar.setDate(new_date);
        $('.input-datetime-second').val(current_return_date);
      }*/
    }
  });
});
