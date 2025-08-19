;(function($) {
	'use strict';
	const { __, _x, _n, _nx } = wp.i18n;
	$(document).ready(function($) {

		// create namespace to avoid any possible conflicts
		$.woaf_common_additional_order_filters = {
			init: function() {
				$.woaf_common_additional_order_filters.datepicker();
				$.woaf_common_additional_order_filters.filter_clear();
				$.woaf_common_additional_order_filters.show_filters();
				$.woaf_common_additional_order_filters.select2();
			},
			is_rtl: function() {
				return ( document.dir == 'rtl' || document.dir !==  '' ) ? true : false;
			},
			datepicker: function() {
				$( '#woaf_filter_start_date' ).datepicker({
					dateFormat: 'yy/mm/dd',
					maxDate: '0',
					isRTL: $.woaf_common_additional_order_filters.is_rtl(),
					onSelect: function (date) {
						var date2 = $('#woaf_filter_start_date').datepicker('getDate');
						date2.setDate(date2.getDate());
						$('#woaf_filter_end_date').datepicker('option', 'minDate', date2);
					}
				});
				$( '#woaf_filter_end_date' ).datepicker({
					dateFormat:'yy/mm/dd',
					maxDate: '0',
					isRTL: $.woaf_common_additional_order_filters.is_rtl()
				});
			},
			filter_clear: function() {
				$('#filter_clear').on('click', function(){
					$.each( $('.woaf_special_order_filter input, .woaf_special_order_filter select'), function( k, v ) {
						var type = $(v).attr('type');
						if ( type == 'text' || type == 'email' || type == 'number' ) {
							$(v).val('');
						}
						if ( type == null || $(v).prop('tagName') == 'SELECT' ) {
							$(v).val('');
						}
					});
					$('.order_statuses_select').select2();
				});
			},
			show_filters: function() {
				$('#woaf_show_filters').on('click', function(){
					$('.woaf_special_order_filter').slideToggle( "400", function() {
						if ( $('.woaf_special_order_filter').is(':visible') ) {
							document.cookie = 'woaf_additional_order_filter=open';
						} else {
							document.cookie = 'woaf_additional_order_filter=close';
						}
					});
				});
			},
			select2: function() {
				$('.order_statuses_select').select2();
			},
		};

		$.woaf_common_additional_order_filters.init();
	});
})(jQuery);