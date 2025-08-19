;(function($) {
	'use strict';
	const { __, _x, _n, _nx } = wp.i18n;
	$(document).ready(function($) {

		// create namespace to avoid any possible conflicts
		$.woaf_сustom_additional_order_filters = {
			init: function() {
				$.woaf_сustom_additional_order_filters.initSelect2();
				$.woaf_сustom_additional_order_filters.selectDeselectOptions();
				$.woaf_сustom_additional_order_filters.addNewCustomFilterRow();
				$.woaf_сustom_additional_order_filters.validateCustomFilterRows();
				$.woaf_сustom_additional_order_filters.removeCustomFilterRow();
			},
			selectDeselectOptions: function() {
				$('#select_all_filters, #deselect_all_filters').on('click', function(){
					var action = (this.id == 'select_all_filters' ? true : false);
					$.each( $('ul.waof_enebled_filters input[type="checkbox"]'), function( k, v ) {
						$(v).prop( "checked", action );
					});
				});
			},
			addNewCustomFilterRow: function() {
				$('.table-custom-filters .woaf-add-custom-filter').on('click', function(e){
					e.preventDefault();
					$('.woaf-custom-filter-blank-state').parents('tr').remove();
					var row_count = $('.table-custom-filters tbody').find('tr').length;
					$('.table-custom-filters tbody').append('<tr><td><input type="text" data-name="filter-name" name="filter_rows['+row_count+'][filter-name]" value="" placeholder="'+__('Filter name', 'woaf-plugin')+'"></td><td class="text-center"><select data-name="filter-statement" name="filter_rows['+row_count+'][filter-statement]"><option value="equal">=</option><option value="like">like</option></select></td><td><select class="select2" data-name="filter-field" name="filter_rows['+row_count+'][filter-field]" id="filter_rows['+row_count+'][filter-field]"></select></td><td class="text-center"><a href="#" class="remove_row"><span class="dashicons dashicons-no"></span></a></td></tr>');

					$.woaf_сustom_additional_order_filters.fixRowCounts();
					$.woaf_сustom_additional_order_filters.initSelect2();
				});
			},
			validateCustomFilterRows: function() {
				var customInputes = '.table-custom-filters tbody input, .table-custom-filters tbody select';

				$('#woaf-сustom-additional-order-filters').on('submit', function(e){
					$(customInputes).removeClass('error');
					$(customInputes).each(function(k,v){
						var val = $(v).val();
						if ( val == '' ) {
							$(v).addClass('error');
							e.preventDefault();
						}
					});
				});
			},
			removeCustomFilterRow: function(){
				$('.table-custom-filters').on('click', 'a.remove_row', function(e){
					e.preventDefault();
					var remove_row = confirm(__('Remove this filter?', 'woaf-plugin'));
					if ( remove_row ) {
						$(this).parents('tr').remove();
						$.woaf_сustom_additional_order_filters.fixRowCounts();
					}
				});
			},
			initSelect2: function() {
				var $eventSelect = $('select.select2');
				if(typeof order_keys_json !== "undefined"){
					$eventSelect.select2({
						data: order_keys_json,
						tags:true
					});
				}
			},
			fixRowCounts: function(){
				$('.table-custom-filters tbody tr').each(function(count,v){
					$(this).find('input, select').each(function(k,v){
						var fieldName = $(v).data('name');
						$(v).attr('name', 'filter_rows['+count+']['+fieldName+']');
					});
				});
			}
		};

		$.woaf_сustom_additional_order_filters.init();
	});
})(jQuery);