/**
 * Video Background
 * Copyright 2017 Push Labs
 * @preserve
 */

(function($){
  $.vidbg = function(el, options){
    // To avoid scope issues, use 'base' instead of 'this'
    // to reference this class from internal events and functions.
    var base = this;

    // Access to jQuery and DOM versions of element
    base.$el = $(el);
    base.el = el;

    // Add a reverse reference to the DOM object
    base.$el.data("vidbg", base);

    /**
     * Hex to RGB converted for overlay
     * @private
     * @param {String} hex
     * @returns {}
     */
    function hexToRGB( hex ) {
      // expand the shorthand hex to full form
      var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
      hex = hex.replace(shorthandRegex, function(m, r, g, b) {
        return r + r + g + g + b + b;
      });

      var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      return result ? {
          r: parseInt(result[1], 16),
          g: parseInt(result[2], 16),
          b: parseInt(result[3], 16)
      } : null;
    }

    base.init = function(){
      // Extend our plugin options to our init function
      base.options = $.extend({},$.vidbg.defaultOptions, options);

      // Create the vidbg-container element
      base.container();

      // Display our poster fallback image if applicable
      base.displayPoster();

      // Display our self hosted video if applicable
      base.selfHostVideo();

      //
      base.tapToUnmute();

      // Add our overlay to be initialized
      base.overlay();
    };

    /**
     * Our mobile detector
     * @public
     */
    base.isMobile = function() {
      var mobileBrowsers = navigator.userAgent.match(/(Android|iPod|iPhone|iPad|BlackBerry|IEMobile|Opera Mini)/);

      // Check the media query
      if ( mobileBrowsers ) {
        return true;
      } else {
        return false;
      }
    };

    /**
     * The video background container
     * @public
     */
    base.container = function() {
      // Create the vidbg container
      var $container = base.$container = $( '<div class="vidbg-container">' );

      // If the containers position is not relative, make it relative
      if ( base.$el.css( 'position' ) === 'static' ) {
        base.$el.css( 'position', 'relative' );
      }

      // make the container's z-index 1
      base.$el.css( 'z-index', '1' );

      // Add the vidbg container to the selector element
      $( base.$el ).prepend( $container );

      // If the selector is body make the containers position fixed
      if ( base.$el.is( 'body' ) ) {
        $container.css({
          position: 'fixed'
        });
      }
    };

    /**
     * The video overlay
     * @public
     */
    base.overlay = function() {
      var $container = base.$container;

      /**
       * Create our overlay
       * We are always going to include the overlay to hide the video options
       * on right click.
       */
      var $overlay = base.$overlay = $( '<div class="vidbg-overlay">' );

      // If the overlay is enabled, add the color
      if ( base.options.overlay === true ) {
        $overlay.css({
          background: 'rgba(' + hexToRGB(base.options.overlayColor).r + ', ' + hexToRGB(base.options.overlayColor).g + ', ' + hexToRGB(base.options.overlayColor).b + ', ' + base.options.overlayAlpha + ')',
        });
      }

      // Add the vidbg overlay to the container
      $container.append( $overlay );
    };

    /**
     * The function to display the tap to unmute button
     * @public
     */
    base.tapToUnmute = function() {

      // If Video Background is mobile, don't display the tapToUnmute button
      if ( base.isMobile() === true  ) {
        return;
      }

      // If the tap to unmute option is disabled, quit.
      if ( base.options.tapToUnmute === false ) {
        return;
      }

      // Create the tap to unmute button
      var $tapToUnmuteButton = base.$tapToUnmuteButton = $( '<div class="vidbg-tap-to-unmute">' + base.options.tapToUnmuteText + '</div>' );

      // On click unmute the video and remove the button
      $tapToUnmuteButton.on( 'click', function( event ) {
        event.preventDefault();

        base.$selfHostVideo.prop( 'muted', false );

        this.remove();
      });

      // Add the tap to unmute button to the element
      // The button is added outside the .vidbg-container due to the negative z-index.
      base.$el.append( $tapToUnmuteButton );
    }

    /**
     * The function to display the poster fallback
     * @public
     */
    base.displayPoster = function( forcePoster ) {
      // Declare our variables
      var $container = base.$container;

      if ( base.options.poster === '#' ) {
        return;
      }

      // If the forcePoster param is set to true, force the container
      if ( forcePoster === true ) {
        $container.css( 'background-image', 'url(' + base.options.poster + ')' );
      }

      // If VB is mobile, display the poster image
      if ( base.isMobile() === true ) {
        $container.css( 'background-image', 'url(' + base.options.poster + ')' );
      } else {
        if ( base.options.mp4 === '#' && base.options.webm === '#' ) {
          $container.css( 'background-image', 'url(' + base.options.poster + ')' );
        }
      }

    };

    /**
     * The function to create the self hosted video background
     * @public
     */
    base.selfHostVideo = function() {
      // If VB is mobile, or has no video links, quit
      if ( base.isMobile() === true || ( base.options.mp4 === '#' && base.options.webm === '#' ) ) {
        return;
      }

      // Delcare our variables
      var $container = base.$container;

      // Create the <video> HTML element for the video background
      var $selfHostVideo = base.$selfHostVideo = $( '<video playsinline>' +
        '<source src="' + base.options.mp4 + '" type="video/mp4">' +
        '<source src="' + base.options.webm + '" type="video/webm">' +
        '</video>' );

      // Add the video properities
      try {
        $selfHostVideo

          .prop({
            autoplay: true,
            loop: base.options.repeat,
            volume: 1,
            muted: base.options.mute,
            defaultMuted: base.options.mute,
            playbackRate: 1,
            defaultPlaybackRate: 1,
          });
      } catch (e) {
        console.log( 'error' );
      }

      var playPromise = $selfHostVideo.get(0).play();

      if (playPromise !== undefined) {
        playPromise.then(function() {
          // Autoplay succeed
        }).catch(function(error) {
          // The browser doesn't allow video backgrounds to be played with audio, show fallback
          // Autoplay failed
          base.displayPoster( true );
        });
      }

      // Size the video accordingly to its container
      $selfHostVideo.one( 'canplaythrough.vidbg', function() {
        base.resize();
      });

      /**
       * Once the video is playing, display it
       * This mitigates the risk of the video resizing after the video is displayed
       */
      $selfHostVideo.one( 'playing.vidbg', function() {
        $selfHostVideo.css({
          opacity: 1
        });
      });

      // Append the video to the container
      $container.append( $selfHostVideo );
    };

    /**
     * The function that sizes and resizes the video backgrounds
     * @public
     */
    base.resize = function() {
      // If VB is mobile, or has no video links, quit
      if ( base.isMobile() === true || ( base.options.mp4 === '#' && base.options.webm === '#' ) ) {
        return;
      }

      // Define our variables
      var $container = base.$container;

      // Get the container size
      var containerHeight = $container.outerHeight();
      var containerWidth = $container.outerWidth();

      // Get our video element
      var $video;
      $video = base.$selfHostVideo;

      // Get our video size
      var videoHeight, videoWidth;
      videoHeight = $video[0].videoHeight;
      videoWidth = $video[0].videoWidth;

      // Our resize logic for self hosted video backgrounds, pretty simple.
      if ( containerWidth / videoWidth > containerHeight / videoHeight ) {
        $video.css({
          // Add 2 pixels to mitigate empty space when resizing
          width: containerWidth + 2,
          height: 'auto',
        });
      } else {
        $video.css({
          width: 'auto',
          height: containerHeight + 2
        });
      }
    };

    // Run initializer
    base.init();
  };

  /**
   * Our default options
   * @public
   * @type {Object}
   */
  $.vidbg.defaultOptions = {
    mp4: '#', // The mp4 link if type is set to self-host
    webm: '#', // The webm link if type is set to self-host
    poster: '#', // The fallback image if on mobile
    mute: true, // Video mute (Depreciated due to autoplay restrictions)
    tapToUnmute: false, // Tap to Unmute button
    tapToUnmuteText: 'Tap to unmute', // Tap to unmute text
    repeat: true, // Video loop
    overlay: false, // The video overlay
    overlayColor: '#000', // The default overlay color if enabled
    overlayAlpha: '0.3', // The default overlay transparancy if enabled
  };

  /**
   * Create the plugin and instances
   * @param {Ojbect|String} options
   * @returns {jQuery}
   * @constructor
   */
  $.fn.vidbg = function(options){

    return this.each( function() {
      // Create the plugin instance and reference
      var plugin = new $.vidbg( this, options );
      $( this ).data( 'vidbg', plugin );

      // If window is resized, resize the plugin instance
      $( window ).resize( function() {
        plugin.resize();
      })
    });

  };

})(jQuery);
