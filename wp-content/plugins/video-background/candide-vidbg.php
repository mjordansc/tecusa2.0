<?php
/**
 * Plugin Name: Video Background
 * Plugin URI: https://pushlabs.co/documentation/video-background
 * Description: WordPress plugin to easily assign a video background to any element. Awesome.
 * Author: Push Labs
 * Version: 2.7.7
 * Author URI: https://pushlabs.co
 * Text Domain: video-background
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
  exit;
}

// Define some constants
define( 'VIDBG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'VIDBG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VIDBG_PLUGIN_BASE', plugin_basename(__FILE__) );
define( 'VIDBG_PLUGIN_VERSION', '2.7.7' );

/**
 * Install the plugin
 * deactivate vidbgpro if installed
 *
 * @since 2.5.2
 *
 * @uses is_plugin_active()
 * @uses deactivate_plugins()
 * @uses delete_option()
 */
function vidbg_install_plugin() {
  if( is_plugin_active( 'video-background-pro/vidbgpro.php') ) {
    deactivate_plugins( 'video-background-pro/vidbgpro.php' );
  }
  delete_option( 'vidbgpro-notice-dismissed' );
  delete_option( 'vidbg_disable_pro_fields' );
}
register_activation_hook( __FILE__, 'vidbg_install_plugin' );

/**
 * Display a notice if the update is important
 *
 * @since 3.0.0
 */
function vidbg_update_message( $data, $response ) {
  if ( isset( $data['upgrade_notice'] ) ) {
    printf( '<div class="vidbg-update-message">%s</div>', wpautop( $data['upgrade_notice'] ) );
  }
}
add_action( 'in_plugin_update_message-video-background/candide-vidbg.php', 'vidbg_update_message', 10, 2 );

/**
 * Determines if VC integration should be added
 *
 * @since 2.2.0
 *
 * @return Boolean
 */
function vidbg_is_vc_enabled() {
  $is_enabled = true;
  $is_enabled = apply_filters( 'vidbg_is_vc_enabled', $is_enabled );

  return $is_enabled;
}

/**
 * Determines if SiteOrigin integration should be added
 *
 * @since 2.2.0
 *
 * @return Boolean
 */
function vidbg_is_siteorigin_enabled() {
  $is_enabled = true;
  $is_enabled = apply_filters( 'vidbg_is_siteorigin_enabled', $is_enabled );

  return $is_enabled;
}

/**
 * Include the metabox framework
 */
if ( file_exists( dirname( __FILE__ ) . '/inc/vendor/cmb2/init.php' ) ) {
  require_once dirname( __FILE__ ) . '/inc/vendor/cmb2/init.php';
}
if ( file_exists( dirname( __FILE__ ) . '/inc/classes/cmb2_field_slider.php' ) ) {
  require_once dirname( __FILE__ ) . '/inc/classes/cmb2_field_slider.php';
}
if ( file_exists( dirname( __FILE__ ) . '/admin_premium_notice.php' ) ) {
  require_once dirname( __FILE__ ) . '/admin_premium_notice.php';
}

/**
 * Load the WPBakery (Visual Composer) integration if conditions are met
 *
 * @since 3.0.0
 */
function vidbg_load_vc_integration() {
  if ( class_exists( 'Vc_Manager' ) && vidbg_is_vc_enabled() === true ) {
    require_once dirname( __FILE__ ) . '/inc/classes/vidbg_wpbakery.php';
  }
}
add_action( 'after_setup_theme', 'vidbg_load_vc_integration' );

/**
 * Load the SiteOrigin Page Builder integration if conditions are met
 *
 * @since 3.0.0
 */
function vidbg_load_siteorigin_integration() {
  if ( class_exists( 'SiteOrigin_Panels_Css_Builder' ) && vidbg_is_siteorigin_enabled() === true ) {
    require_once dirname( __FILE__ ) . '/inc/classes/vidbg_siteorigin.php';
  }
}
add_action( 'after_setup_theme', 'vidbg_load_siteorigin_integration' );

/**
 * Load plugin textdomain.
 *
 * @since 2.5.0
 *
 * @uses load_plugin_textdomain()
 * @uses plugin_basename()
 */
function vidbg_load_textdomain() {
  load_plugin_textdomain( 'video-background', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'vidbg_load_textdomain' );

/**
 * Enqueue backend style and script
 * Note: renamed. Previous, vidbg_metabox_scripts()
 *
 * @since 2.1.4
 *
 * @uses wp_enqueue_style()
 * @uses plugins_url()
 * @uses wp_enqueue_script()
 * @uses plugin_dir_url()
 */
function vidbg_enqueue_admin_scripts() {
  wp_enqueue_style('vidbg-metabox-style', plugins_url('/css/vidbg-style.css', __FILE__));
  wp_enqueue_script( 'vidbg-admin-backend', plugin_dir_url( __FILE__ ) . '/js/vidbg-backend.js' );

  wp_localize_script( 'vidbg-admin-backend', 'vidbg_localized_text', array(
    'show_advanced' => __( 'Show Advanced Options', 'video-background' ),
    'hide_advanced' => __( 'Hide Advanced Options', 'video-background' ),
  ) );
}
add_action('admin_enqueue_scripts', 'vidbg_enqueue_admin_scripts');

/**
 * Enqueue vidbg jquery script
 * Note: renamed. Previous, vidbg_jquery
 *
 * @since 2.0.0
 *
 * @uses wp_enqueue_script()
 * @uses plugins_url()
 */
function vidbg_enqueue_scripts() {
  wp_register_script( 'vidbg-video-background', plugins_url('/js/vidbg.min.js', __FILE__), array('jquery'), VIDBG_PLUGIN_VERSION, true);
  wp_register_style( 'vidbg-frontend-style', plugins_url( '/css/pushlabs-vidbg.css', __FILE__), array(), VIDBG_PLUGIN_VERSION );

  // Enqueue the style
  wp_enqueue_style( 'vidbg-frontend-style' );
}
add_action( 'wp_enqueue_scripts', 'vidbg_enqueue_scripts' );

/**
 * Add custom color palette
 *
 * @since 2.5.0
 */
function vidbg_default_color_palette( $l10n ) {
  $l10n['defaults']['color_picker'] = array(
    'palettes' => array( '#000000', '#3498db', '#e74c3c', '#374e64', '#2ecc71', '#f1c40f' ),
  );
  return $l10n;
}
add_filter( 'cmb2_localized_data', 'vidbg_default_color_palette' );

/**
 * Construct the vidbg shortcode from an array
 *
 * @since 2.6
 *
 * @uses do_shortcode()
 *
 * @param $atts_array array A 2d array of shortcode attributes
 * @return string The [vidbg] constructed from the array input
 */
function vidbg_construct_shortcode( $atts_array ) {
  // If no array is provided, quit
  if ( empty( $atts_array ) ) {
    return;
  }

  // Our shortcode name
  $shortcode_name = 'vidbg';

  // Construct the shortcode
  $the_shortcode = '[' . $shortcode_name;
  foreach ( $atts_array as $key => $value ) {
    $the_shortcode .= ' ' . $key . '="' . $value .'"';
  }
  $the_shortcode .= ']';

  // Create the output
  $output = $the_shortcode;

  return $output;
}

/**
 * Helper function to output disabled Video Background Pro fields
 *
 * @since 2.5.4
 *
 * @uses get_option()
 */
function vidbg_disabled_pro_field( $field_name = 'Blank Pro', $field_id = 'pro_blank', $field_type = 'input', $field_description = '' ) {

  $output = '';
  $options = get_option( 'vidbg_disable_pro_fields' );

  if ( ! $options ) {
    if ( $field_type === 'input' ) {
      $field_class = 'cmb-row cmb-type-text cmb2-id-pro-disabled-field-' . $field_id . ' table-layout';
    } elseif ( $field_type === 'radio' ) {
      $field_class = 'cmb-row cmb-type-radio-inline cmb2-id-pro-disabled-field-' . $field_id . ' cmb-inline';
    }

    $output .= '<div class="' . $field_class . '">';
    $output .= '<div class="cmb-th"><label for="pro_disabled_' . $field_id . '">' . $field_name . '</label></div>';
    $output .= '<div class="cmb-td">';

    if ( $field_type === 'input' ) {
      $output .= '<input type="text" class="regular-text" name="pro_disabled_' . $field_id . '" id="' . $field_id . '" disabled>';
    }

    if ( $field_type === 'radio' ) {
      $output .= '<ul class="cmb2-radio-list cmb2-list">';
      $output .= '<li><input type="radio" value="off" class="cmb2-option" name="pro_disabled_' . $field_id . '" id="pro_disabled_' . $field_id . '1" checked="checked" disabled> <label for="pro_disabled_' . $field_id . '1">Off</label></li>';
      $output .= '<li><input type="radio" class="cmb2-option" name="pro_disabled_' . $field_id . '" id="pro_disabled_' . $field_id . '2" value="on" disabled> <label for="pro_disabled_' . $field_id . '2">On</label></li>';
      $output .= '</ul>';
    }

    if ( $field_id === 'overlay_texture' ) {
      $output .= '<input class="cmb2-upload-button button" type="button" value="Upload Overlay Texture" disabled="">';
    }

    $output .= '<p class="cmb2-metabox-description">' . $field_description . '</p>';
    $output .= '</div>';
    $output .= '</div>';
  }

  return $output;
}

/**
 * Register metabox and scripts
 *
 * @since 2.5.0
 *
 * @uses new_cmb2_box()
 * @uses __()
 * @uses add_field()
 * @uses vidbg_disabled_pro_field()
 */
function vidbg_register_metabox() {
  $prefix = 'vidbg_metabox_field_';
  $post_types = array( 'post', 'page' );

  /**
   * Allow the post types to be filtered out
   */
  $post_types = apply_filters( 'vidbg_post_types', $post_types );

  $vidbg_metabox = new_cmb2_box( array(
    'id'           => 'vidbg-metabox',
    'title'        => __( 'Video Background', 'video-background' ),
    'object_types' => $post_types,
    'context'      => 'normal',
    'priority'     => 'high',
  ) );

  $vidbg_metabox->add_field( array(
    'name' => __( 'Container', 'video-background' ),
    'desc' => __( 'Please specify the container you would like your video background to be in. <a href="https://pushlabs.co/docs/video-background/#finding-your-container" target="_blank">Learn how to find your container.</a>', 'video-background' ),
    'id'   => $prefix . 'container',
    'type' => 'text',
  ) );

  $vidbg_metabox->add_field( array(
    'name'    => __( 'Link to .mp4', 'video-background' ),
    'desc'    => __( 'Please specify the link to the .mp4 file. You can either enter a URL or upload a file.<br>For browser compatability, please enter an .mp4 and .webm file for video backgrounds.', 'video-background' ),
    'id'      => $prefix . 'mp4',
    'type'    => 'file',
    'options' => array(
      'add_upload_file_text' => __( 'Upload .mp4 file', 'video-background' ),
    ),
  ) );

  $vidbg_metabox->add_field( array(
    'name'    => __( 'Link to .webm', 'video-background' ),
    'desc'    => __( 'Please specify the link to the .webm file. You can either enter a URL or upload a file.<br>For browser compatability, please enter an .mp4 and .webm file for video backgrounds.', 'video-background' ),
    'id'      => $prefix . 'webm',
    'type'    => 'file',
    'options' => array(
      'add_upload_file_text' => __( 'Upload .webm file', 'video-background' ),
    ),
  ) );

  $vidbg_metabox->add_field( array(
    'name'    => __( 'Link to fallback image', 'video-background' ),
    'desc'    => __( 'Please specify a link to the fallback image in case the browser does not support video backgrounds. You can either enter a URL or upload a file.', 'video-background' ),
    'id'      => $prefix . 'poster',
    'type'    => 'file',
    'options' => array(
      'add_upload_file_text' => __( 'Upload fallback image', 'video-background' ),
    ),
  ) );

  $vidbg_metabox->add_field( array(
    'name'       => __( 'Overlay', 'video-background' ),
    'desc'       => __( 'Add an overlay over the video. This is useful if your text isn\'t readable with a video background.', 'video-background' ),
    'id'         => $prefix . 'overlay',
    'type'       => 'radio_inline',
    'default'    => 'off',
    'options' => array(
      'off' => __( 'Off', 'video-background' ),
      'on'  => __( 'On', 'video-background' ),
    ),
    'before_row' => '<div id="vidbg_advanced_options">',
  ) );

  $vidbg_metabox->add_field( array(
    'name'    => __( 'Overlay Color', 'video-background' ),
    'desc'    => __( 'If overlay is enabled, a color will be used for the overlay. You can specify the color here.', 'video-background' ),
    'id'      => $prefix . 'overlay_color',
    'type'    => 'colorpicker',
    'default' => '#000',
  ) );

  $vidbg_metabox->add_field( array(
    'name'    => __( 'Overlay Opacity', 'video-background' ),
    'desc'    => __( 'Specify the opacity of the overlay with the left being mostly transparent and the right being hardly transparent.', 'video-background' ),
    'id'      => $prefix . 'overlay_alpha',
    'type'    => 'vidbg_slider',
    'min'     => '10',
    'max'     => '99',
    'default' => '30',
  ) );

  $vidbg_metabox->add_field( array(
    'name'    => __( 'Turn off loop?', 'video-background' ),
    'desc'    => __( 'Turn off the loop for Video Background. Once the video is complete, it will display the last frame of the video.', 'video-background' ),
    'id'      => $prefix . 'no_loop',
    'type'    => 'radio_inline',
    'default' => 'off',
    'options' => array(
      'off' => __( 'Off', 'video-background' ),
      'on'  => __( 'On', 'video-background' ),
    ),
  ) );

  $vidbg_metabox->add_field( array(
    'name'    => __( 'Display "Tap to unmute" button?', 'video-background' ),
    'desc'    => __( 'Allow your users to interact with the sound of the video background. <a href="https://pushlabs.co/docs/video-background/#tap-to-unmute-text" target="_blank">Learn how to change this text.</a>', 'video-background' ),
    'id'      => $prefix . 'tap_to_unmute',
    'type'    => 'radio_inline',
    'default' => 'off',
    'options' => array(
      'off' => __( 'Off', 'video-background' ),
      'on'  => __( 'On', 'video-background' ),
    ),
    'after_row' => '</div>',
  ) );

  $vidbg_metabox->add_field( array(
    'before_field' => __( '<a href="#vidbg_advanced_options" class="button vidbg-button advanced-options-button">Show Advanced options</a>', 'video-background' ),
    'type'         => 'title',
    'id'           => $prefix . 'advanced_button',
  ) );

}
add_action( 'cmb2_admin_init', 'vidbg_register_metabox' );

/**
 * Add inline javascript to footer for video background
 *
 * @since 2.0.0
 *
 * @uses is_page()
 * @uses is_single()
 * @uses is_home()
 * @uses get_option()
 * @uses get_the_ID()
 * @uses get_post_meta()
 */
function vidbg_initialize_footer() {
  if( is_page() || is_single() || is_home() && get_option( 'show_on_front') == 'page' ) {

    if( is_page() || is_single() ) {
      $the_id = get_the_ID();
    } elseif( is_home() && get_option( 'show_on_front' ) == 'page' ) {
      $the_id = get_option( 'page_for_posts' );
    }

    $meta_prefix = 'vidbg_metabox_field_';
    $container_meta = get_post_meta( $the_id, $meta_prefix . 'container', true );
    $mp4_meta = get_post_meta( $the_id, $meta_prefix . 'mp4', true );
    $webm_meta = get_post_meta( $the_id, $meta_prefix . 'webm', true );
    $poster_meta = get_post_meta( $the_id, $meta_prefix . 'poster', true );
    $overlay_meta = get_post_meta( $the_id, $meta_prefix . 'overlay', true );
    $overlay_color_meta = get_post_meta( $the_id, $meta_prefix . 'overlay_color', true );
    $overlay_alpha_meta = get_post_meta( $the_id, $meta_prefix . 'overlay_alpha', true );
    $loop_meta = get_post_meta( $the_id, $meta_prefix . 'no_loop', true );
    $tap_to_unmute_meta = get_post_meta( $the_id, $meta_prefix . 'tap_to_unmute', true );

    // If there is no container element, return.
    if( empty( $container_meta ) ) {
      return;
    }

    // Create our shortcode attributes array
    $shortcode_atts = array(
      'container' => $container_meta,
      'mp4' => ( $mp4_meta == '' ) ? '#' : $mp4_meta,
      'webm' => ( $webm_meta == '' ) ? '#' : $webm_meta,
      'poster' => ( $poster_meta == '' ) ? '#' : $poster_meta,
      'overlay' => ( $overlay_meta == 'on' ) ? 'true' : 'false',
      'overlay_color' => !empty( $overlay_color_meta ) ? $overlay_color_meta : '#000',
      'overlay_alpha' => !empty( $overlay_alpha_meta ) ? '0.' . $overlay_alpha_meta : '0.3',
      'loop' => ( $loop_meta == 'on' ) ? 'false' : 'true',
      'tap_to_unmute' => ( $tap_to_unmute_meta == 'on' ) ? 'true' : 'false',
      'source' => 'Metabox',
    );

    // Construct the shortcode, then echo it.
    $the_shortcode = vidbg_construct_shortcode( $shortcode_atts );
    $output = do_shortcode( $the_shortcode );

    echo $output;
  }
}
add_action( 'wp_footer', 'vidbg_initialize_footer' );

/**
 * Shortcode for v1.0.x versions
 *
 * @since 1.0.0
 *
 * @uses shortcode_atts()
 * @uses do_shortcode()
 * @uses esc_js()
 */
function candide_video_background( $atts , $content = null ) {

    // Attributes
    extract(
      shortcode_atts(
        array(
          'container' => 'body',
          'mp4' => '#',
          'webm' => '#',
          'poster' => '#',
          'muted' => 'true',
          'loop' => 'true',
          'overlay' => 'false',
          'overlay_color' => '#000',
          'overlay_alpha' => '0.3',
          'tap_to_unmute' => 'false',
          'source' => 'Shortcode',
        ), $atts , 'vidbg'
      )
    );

    $has_overlay = ($overlay == 'true') ? 'true' : 'false';
    $has_loop = ($loop == 'true') ? 'true' : 'false';
    $has_tap_to_unmute = ($tap_to_unmute == 'true') ? 'true' : 'false';

    $tap_to_unmute_text = __( 'Tap to Unmute', 'video-background' );
    $tap_to_unmute_text = apply_filters( 'vidbg_tap_to_unmute_text', $tap_to_unmute_text );
    $tap_to_unmute_button = '<img src="' . plugins_url( 'img/volume-icon.svg', __FILE__ ) . '" width="20" height="20" /><span>' . esc_js($tap_to_unmute_text) . '</span>';

    $output = "
      jQuery(function($){
        // Source: " . esc_js($source) . "
        $( '" . esc_js($container) . "' ).vidbg( {
          mp4: '" . esc_js($mp4) . "',
          webm: '" . esc_js($webm) . "',
          poster: '" . esc_js($poster) . "',
          repeat: " . $has_loop . ",
          overlay: " . $has_overlay . ",
          overlayColor: '" . esc_js($overlay_color) . "',
          overlayAlpha: '" . esc_js($overlay_alpha) . "',
          tapToUnmute: " . $has_tap_to_unmute . ",
          tapToUnmuteText: '" . $tap_to_unmute_button . "',
        });
      });
    ";

    // Enqueue the vidbg script conditionally
    wp_enqueue_script( 'vidbg-video-background' );
    wp_add_inline_script( 'vidbg-video-background', $output );

}
add_shortcode( 'vidbg', 'candide_video_background' );

/**
 * Add getting started page
 *
 * @since 2.1.1
 *
 * @uses add_options_page()
 */
function vidbg_add_gettingstarted() {
  add_options_page(
    'Video Background',
    'Video Background',
    'manage_options',
    'pushlabs-vidbg',
    'vidbg_gettingstarted_page'
  );
}
add_action( 'admin_menu', 'vidbg_add_gettingstarted' );

/**
 * Getting started page content
 *
 * @since 2.1.1
 *
 * @uses _e()
 * @uses settings_fields()
 * @uses do_settings_sections()
 * @uses submit_button()
 */
function vidbg_gettingstarted_page() {
  echo '<div class="wrap">';
    _e( '<h2>Video Background</h2>', 'video-background' );
    _e( '<p>Video background makes it easy to add responsive, great looking video backgrounds to any element on your website.</p>', 'video-background' );
    _e( '<h3>Getting Started</h3>', 'video-background' );
    _e( '<p>There are four ways to use Video Background', 'video-background' );
    echo '<ol>';
      _e( '<li>With the metabox</li>', 'video-background' );
      _e( '<li>With the WPBakery (Visual Composer) integration</li>', 'video-background' );
      _e( '<li>With the SiteOrigin Page Builder integration</li>', 'video-background' );
      _e( '<li>With the shortcode</li>', 'video-background' );
    echo '</ol>';
    _e( '<a href="https://pushlabs.co/docs/video-background/" class="button" target="_blank">Further Documentation</a>', 'video-background' );
    _e( '<h3>Questions?</h3>', 'video-background' );
    _e( '<p>If you have any feedback/questions regarding the plugin you can reach me <a href="https://wordpress.org/support/plugin/video-background" target="_blank">here.</a>', 'video-background' );
    _e( '<h3>Supporting the Plugin</h3>', 'video-background' );
    _e( '<p>If you like Video Background and want to show your support, consider purchasing <a href="http://pushlabs.co/video-background-pro" target="_blank">Video Background Pro</a>. It comes with plenty of helpful features that make your life easier like:</p>', 'video-background' );
    echo '<ul>';
      _e( '<li>Mobile video background playback on supported browsers</li>', 'video-background' );
      _e( '<li>YouTube Integration</li>', 'video-background' );
      _e( '<li>Frontend Play/Pause Button Option</li>', 'video-background' );
      _e( '<li>Frontend Volume Button Option</li>', 'video-background' );
      _e( '<li>Overlay Image Textures</li>', 'video-background' );
      _e( '<li>Extensive Documentation</li>', 'video-background' );
      _e( '<li>Video Tutorials</li>', 'video-background' );
      _e( '<li>And Much More!</li>', 'video-background' );
    echo '</ul>';
    _e( '<a href="http://pushlabs.co/video-background-pro" class="button button-primary" rel="nofollow" target="_blank">Learn More About Video Background Pro</a>', 'video-background' );
  echo '</div>';
}

/**
 * Add getting started link on plugin page
 *
 * @since 2.1.1
 *
 * @uses __()
 */
function vidbg_gettingstarted_link($links) {
  $gettingstarted_link = __( '<a href="options-general.php?page=pushlabs-vidbg">Getting Started</a>', 'video-background' );
  array_unshift($links, $gettingstarted_link);
  return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'vidbg_gettingstarted_link' );

/**
 * Create a unique random class name to be used as a reference for other plugin integrations.
 *
 * @since 2.7.0
 *
 * @return String The reference class name (without the period prefix)
 */
function vidbg_create_unique_ref() {
  // Our possible list of characters
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen( $characters );
  $length = 14;

  // Create our string
  $unique_ref = '';
  for ( $i = 0; $i < $length; $i++ ) {
    $unique_ref .= $characters[rand(0, $charactersLength - 1)];
  }

  // Create our output
  $output = 'vidbg-ref-' . $unique_ref;

  return $output;
}
