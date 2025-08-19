<?php

/**
 * Video Background's class to add a video background to a WP Bakery (Visual Composer) row
 *
 * @author Push Labs https://pushlabs.co
 * @copyright Copyright (c) Push Labs (hello@pushlabs.co)
 * @since 2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'Vidbg_WPBakery' ) ) {
  /**
   * WP Bakery Integration
   *
   * @package Video Background/Video Background Pro
   * @author Push Labs
   * @version 1.0.0
   */
  class Vidbg_WPBakery {

    // Class' properties
    private $prefix;
    protected $vidbg_atts;


    public function __construct( $vc_row_atts = null ) {
      // Get the VC Row's attributes
      $this->vc_row_atts = $vc_row_atts;

      // vidbg_atts will hold our [vidbg] shortcode attributes
      $this->vidbg_atts = array();

      // The data prefix for the attributes we'll add to Visual Composer
      $this->prefix = 'vidbg_vc_';

      // Add our attributes to the VC row
      add_action( 'vc_before_init', array( $this, 'register_fields' ) );
    }

    /**
     * Define and add our attributes to the VC Row
     *
     * @since 2.7.0
     */
    public function register_fields() {
      // Define our attributes
      $attributes = array(
        array(
          'type'        => 'textfield',
          'heading'     => __( 'Link to .mp4', 'video-background' ),
          'param_name'  => $this->prefix . 'mp4',
          'description' => __( 'Please specify the link to the .mp4 file.', 'video-background' ),
          'dependency'  => array(
            'element' => $this->prefix . 'type',
            'value'   => 'self-host',
          ),
          'group'       => __( 'Video Background', 'video-background' ),
          'weight' => -10,
        ),
        array(
          'type'        => 'textfield',
          'heading'     => __( 'Link to .webm', 'video-background' ),
          'param_name'  => $this->prefix . 'webm',
          'description' => __( 'Please specify the link to the .webm file.', 'video-background' ),
          'dependency'  => array(
            'element' => $this->prefix . 'type',
            'value'   => 'self-host',
          ),
          'group'       => __( 'Video Background', 'video-background' ),
          'weight' => -20,
        ),
        array(
          'type'        => 'attach_image',
          'heading'     => __( 'Fallback Image', 'video-background' ),
          'param_name'  => $this->prefix . 'poster',
          'description' => __( 'Please upload a fallback image.', 'video-background' ),
          'group'       => __( 'Video Background', 'video-background' ),
          'weight' => -30,
        ),
        array(
          'type'        => 'checkbox',
          'heading'     => __( 'Enable Overlay?', 'video-background' ),
          'param_name'  => $this->prefix . 'overlay',
          'description' => __( 'Add an overlay over the video. This is useful if your text isn\'t readable with a video background.', 'video-background' ),
          'group'       => __( 'Video Background', 'video-background' ),
          'value'       => '0',
          'weight' => -40,
        ),
        array(
          'type'        => 'colorpicker',
          'heading'     => __( 'Overlay Color', 'video-background' ),
          'param_name'  => $this->prefix . 'overlay_color',
          'value'       => '#000',
          'description' => __( 'If overlay is enabled, a color will be used for the overlay. You can specify the color here.', 'video-background' ),
          'dependency'  => array(
            'element' => $this->prefix . 'overlay',
            'value'   => 'true',
          ),
          'group'       => __( 'Video Background', 'video-background' ),
          'weight' => -50,
        ),
        array(
          'type'        => 'textfield',
          'heading'     => __( 'Overlay Opacity', 'video-background' ),
          'param_name'  => $this->prefix . 'overlay_alpha',
          'value'       => '0.3',
          'description' => __( 'Specify the opacity of the overlay. Accepts any value between 0.00-1.00 with 0 being completely transparent and 1 being completely invisible. Ex. 0.30', 'video-background' ),
          'dependency'  => array(
            'element' => $this->prefix . 'overlay',
            'value'   => 'true',
          ),
          'group'       => __( 'Video Background', 'video-background' ),
          'weight' => -60,
        ),
        array(
          'type'        => 'checkbox',
          'heading'     => __( 'Disable Loop?', 'video-background' ),
          'param_name'  => $this->prefix . 'loop',
          'description' => __( 'Turn off the loop for Video Background. Once the video is complete, it will display the last frame of the video.', 'video-background' ),
          'group'       => __( 'Video Background', 'video-background' ),
          'value'       => '0',
          'weight' => -70,
        ),
      );

      // Only add tap to unmute if is not Video Background Pro
      if ( ! function_exists( 'vidbgpro_install_plugin' ) ) {
        $attributes[] = array(
          'type'        => 'checkbox',
          'heading'     => __( 'Display "Tap to unmute" button?', 'video-background' ),
          'param_name'  => $this->prefix . 'tap_to_unmute',
          'description' => __( 'Allow your users to interact with the sound of the video background.', 'video-background' ),
          'group'       => __( 'Video Background', 'video-background' ),
          'value'       => '0',
          'weight' => -80,
        );
      }

      $attributes = apply_filters( 'vidbg_wpbakery_fields', $attributes );

      // Add the params to the VC row
      vc_add_params( 'vc_row', $attributes );
    }

    /**
     * Find all attributes with the prefix and add them to the $vidbg_atts array
     *
     * @since 2.7.0
     */
    public function get_vidbg_attributes() {
      // If the class doesn't have a VC Row atts arg, don't run the function
      if ( $this->vc_row_atts === null ) {
        return;
      }

      // Run a foreach loop on the VC Row atts
      foreach ( $this->vc_row_atts as $attribute_key => $attribute ) {
        // Find the attributes with the $prefix
        if ( substr( $attribute_key, 0, strlen( $this->prefix ) ) === $this->prefix ) {
          // Remove the $prefix
          $attribute_key = substr( $attribute_key, strlen( $this->prefix ) );

          // If the attribute is the poster, get the image source of the attribute ID, otherwise get the attribute
          if ( $attribute_key === 'poster' ) {
            $img_src_arr = wp_get_attachment_image_src( $attribute, 'full' );
            $this->vidbg_atts[$attribute_key] = $img_src_arr[0];
          } else {
            $this->vidbg_atts[$attribute_key] = $attribute;
          }
        }
      }
    }

    /**
     * Generate the shortcode for the frontend
     *
     * @since 2.7.0
     */
    public function generate_shortcode() {
      // If the class doesn't have a VC Row atts arg, don't run the function
      if ( $this->vc_row_atts === null ) {
        return;
      }

      // Get the Video Background attributes in the VC Row
      $this->get_vidbg_attributes();

      // Conditionally determine if the row has a video background
      if ( function_exists( 'vidbgpro_install_plugin' ) ) {
        // If plugin is Video Background Pro

        // If type is YouTube and YouTube URL param is empty, quit
        if ( isset( $this->vidbg_atts['type'] ) && ! isset( $this->vidbg_atts['youtube_url'] ) ) {
          return;
        }

        // If type is self-host and mp4 amd webm param are both empty, quit
        if ( ! isset( $this->vidbg_atts['type'] ) && ! isset( $this->vidbg_atts['mp4'] ) && ! isset( $this->vidbg_atts['webm'] ) ) {
          return;
        }
      } else {
        // If plugin is Video Background

        // If mp4 and webm params are empty, quit
        if ( ! isset( $this->vidbg_atts['mp4'] ) && ! isset( $this->vidbg_atts['webm'] ) ) {
          return;
        }
      }

      // Debug Visual Composer row attributes
      // var_dump( $this->vc_row_atts );

      if ( array_key_exists( 'loop', $this->vidbg_atts ) ) {
        $this->vidbg_atts['loop'] = $this->vidbg_atts['loop'] === 'false' ? 'true' : 'false';
      }

      $this->vidbg_atts = apply_filters( 'vidbg_sanitize_wpbakery_fields', $this->vidbg_atts );

      // Create our container selector
      // Check if plugin is Video Background Pro or Video Background
      if ( function_exists( 'vidbgpro_create_unique_ref' ) ) {
        $unique_class = vidbgpro_create_unique_ref();
      } else {
        $unique_class = vidbg_create_unique_ref();
      }

      $container_class = $unique_class . '-container';

      // Add our class to the shortcode atts array
      $this->vidbg_atts['container'] = '.' . $container_class;

      // Add our source to the shortcode atts array
      $this->vidbg_atts['source'] = 'WPBakery Integration';

      // Use to test the attributes created for $vidbg_atts
      // var_dump( $this->vidbg_atts );

      // Our jQuery code to add the container class to the container so we can target the VC Row
      $add_container_to_row = "
        jQuery(function($){
          $('." . $unique_class . "').next('.vc_row').addClass('" . $container_class . "');
        });
      ";

      if ( function_exists( 'vidbgpro_install_plugin' ) ) {
        $script_handle = 'vidbgpro';
      } else {
        $script_handle = 'vidbg-video-background';
      }

      // Add our "container to row" script
      wp_add_inline_script( $script_handle, $add_container_to_row );

      // Construct the shortcode with our attributes
      // Check if plugin is Video Background Pro or Video Background
      if ( function_exists( 'vidbgpro_construct_shortcode' ) ) {
        $shortcode = vidbgpro_construct_shortcode( $this->vidbg_atts );
      } else {
        $shortcode = vidbg_construct_shortcode( $this->vidbg_atts );
      }

      // Output the shortcode
      $output = do_shortcode( $shortcode );
      $output .= '<div class="' . $unique_class . '" style="display: none;"></div>';

      return $output;
    }
  }

  // Call our class to init the VC Row fields
  $vidbg_init_wpbakery = new Vidbg_WPBakery();

  // vc_theme_before_vc_row allows us to add content before a Visual Composer Row
  if ( !function_exists( 'vc_theme_before_vc_row' ) ) {
    /**
     * Add the shortcode and unique class before the Visual Composer row
     *
     * @since 2.7.0
     */
    function vc_theme_before_vc_row($atts, $content = null) {
      // Create an instance of the class with the VC row's $atts
      $vidbg_init_wpbakery_row = new Vidbg_WPBakery( $atts );

      // Return the generated shortcode
      return $vidbg_init_wpbakery_row->generate_shortcode();
    }
  }
}
