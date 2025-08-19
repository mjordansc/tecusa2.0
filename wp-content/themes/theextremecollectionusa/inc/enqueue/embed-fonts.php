<?php
/**
 * Embed Fonts
 *
 * @package fc_corporativa
 */

/* ------------- Embed Google Fonts */
 function include_admin_font() {
 	wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Red+Hat+Display:wght@300;400;500;600;700&display=swap' );
  wp_enqueue_style('google-fonts-2', 'https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap' );
 }
 add_action( 'wp_enqueue_scripts', 'include_admin_font' );
