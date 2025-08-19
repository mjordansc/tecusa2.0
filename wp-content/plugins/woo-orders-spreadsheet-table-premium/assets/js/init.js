jQuery(document).ready(function ($) {
    var $searchModal = $('#be-filters');
    $searchModal.find('.product-taxonomy-selector').on('change', function(e){
        $(this).parent('li').find('select.select2').data('taxonomies', $(this).val()); 
    });
});