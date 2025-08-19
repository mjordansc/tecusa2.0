jQuery(window).on('load',function () {

    var acoDivClass = acoplw_frontend_object.classname ? '.'+acoplw_frontend_object.classname : '.images';
    var enableJQ    = acoplw_frontend_object.enablejquery ? parseInt(acoplw_frontend_object.enablejquery) : 0;

    // Detail Page Badge
    var badge = jQuery('.acoplw-hidden-wrap').not('header .acoplw-hidden-wrap');
	var flag = false;
	if ( badge.length >= 1 ) { // Check for badges
		var badgeCont = badge.find('.acoplw-badge').clone(); 
        jQuery(badgeCont).addClass('acoplw-singleBadge');
        jQuery(badgeCont).find('.acoplw-badge-icon').removeClass('acoplw-badge-listing-hide');
        if ( acoplw_frontend_object.classname ) {
			jQuery(acoDivClass).each( function (index, cont) { 
				if ( !flag && !jQuery(this).is(":hidden")) { 
					var position = jQuery(this);
					jQuery(this).css({'positon':'relative'});
					jQuery(badgeCont).prependTo(jQuery(position).parent());
					// jQuery(position).appendTo(badgeCont);
					flag = true;
				}
			});
			badge.remove();
		} else {
            jQuery('.woocommerce-product-gallery:first, .woocommerce-product-gallery--with-images:first').each( function (index, cont) { 
                var position = jQuery(this);
                jQuery(this).css({'positon':'relative'}); 
                if ( jQuery(position).parent().hasClass('product') ) {
                    jQuery(badgeCont).prependTo(jQuery(position));
                } else {
                    jQuery(badgeCont).prependTo(jQuery(position).parent());
                }
                flag = true;
            });
            if (!flag) { 
                jQuery(acoDivClass).each( function (index, cont) {
                    if ( !flag ) { 
                        var position = jQuery(this);
                        jQuery(this).css({'positon':'relative'});
                        jQuery(badgeCont).prependTo(jQuery(position).parent());
                        // jQuery(position).appendTo(badgeCont);
                        flag = true;
                    }
                });
            } else {
                badge.remove();
            }
        }
	}

    if ( jQuery('.jet-woo-products').length ) {
        jQuery('.jet-woo-products__item').each( function (index) {
            if( jQuery(this).next().is('span.acoplw-badge')) {
                var badgeCont = jQuery(this).next('.acoplw-badge'); 
                var position = jQuery(this);
                jQuery(this).css({'positon':'relative'});
                jQuery(badgeCont).detach().prependTo(jQuery(position));
            }
        });
    }

    // if ( jQuery('.jet-woo-builder-products-loop').length ) { 
	// 	jQuery('.jet-woo-builder-product').each( function (index) { 
    //         if( jQuery(this).prev().is('span.acoplw-badge')) { 
    //             var badgeCont = jQuery(this).prev('.acoplw-badge').removeClass('acoplw-badge-listing-hide'); 
    //             var position = jQuery(this).find('.jet-woo-builder-archive-product-thumbnail a');
    //             jQuery(this).css({'positon':'relative'});
    //             jQuery(badgeCont).detach().prependTo(jQuery(position));
    //         }
    //     });
	// }

    if ( enableJQ == 1 ) {
        jQuery('.acoplw-badge:not(.acoplw-singleBadge)').each( function (index) {
            let ImageContainerDiv = jQuery(this).parent().find('a img').closest('a');
            let badgeCont = jQuery(this); 
            jQuery(this).parent().find('a img').closest('a').addClass('acoplw-badgeOutter');
            jQuery(badgeCont).detach().prependTo(jQuery(ImageContainerDiv));
        });
    }
    
});

jQuery(document).ready(function ($) {

    let phptimestamp = acoplw_frontend_object.phptimestamp ? acoplw_frontend_object.phptimestamp : '';
    let jstime       = new Date(); 
    let jstimestamp  = (Date.parse(jstime) / 1000); 
    let tmstampdiff  = phptimestamp - jstimestamp; 
    // Timer
    function makeTimer() {

        $(".acoplwTimer").each(function() {
			
			var selectedDate = $(this).data('time');
			var endTime = new Date(selectedDate);		
			endTime = (Date.parse(endTime) / 1000);

			var now = new Date(); 
			now = (Date.parse(now) / 1000);

            if ( tmstampdiff <= 0 ) { return false; }

            now = now + tmstampdiff; 
			var timeLeft = endTime - now;

            if ( timeLeft <= 0 ) { return false; }

			var days    = Math.floor(timeLeft / 86400); 
			var hours   = Math.floor((timeLeft - (days * 86400)) / 3600);
			var minutes = Math.floor((timeLeft - (days * 86400) - (hours * 3600 )) / 60);
			var seconds = Math.floor((timeLeft - (days * 86400) - (hours * 3600) - (minutes * 60)));

			if (hours < "10") { hours = "0" + hours; }
			if (minutes < "10") { minutes = "0" + minutes; }
			if (seconds < "10") { seconds = "0" + seconds; }

			$(this).find(".acoplwDays").html(days);
			$(this).find(".acoplwHours").html(hours);
			$(this).find(".acoplwMinutes").html(minutes);
			$(this).find(".acoplwSeconds").html(seconds);

        });
        
    }
    setInterval(function() { makeTimer(); }, 1000);

}); 