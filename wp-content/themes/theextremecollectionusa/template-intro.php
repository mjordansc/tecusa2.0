<?php

/* Template Name: Intro */

get_header('intro');

$pageId = get_queried_object_id();
?>

<section id="main" class="d-none d-md-block banner-intro relative" style="background-image:url(<?php the_field('intro_bg_img')?>)">
  <?php $custom_logo_id = get_theme_mod( 'custom_logo' ); ?>
	<?php $custom_logo_url = wp_get_attachment_image_src( $custom_logo_id , 'full' );?>
  <div class="intro-content text-center">
    <img src="<?php echo $custom_logo_url[0]?>">
    <span class="d-block ft-Lemon intro-title"><?php the_field('intro_txt') ?></span>
  </div>
</section>

<section id="main" class="d-md-none" >
  <div class="banner-intro" style="background-image:url(<?php the_field('intro_bg_mobile_img')?>)">
  </div>
  <?php $custom_logo_id = get_theme_mod( 'custom_logo' ); ?>
	<?php $custom_logo_url = wp_get_attachment_image_src( $custom_logo_id , 'full' );?>
  <div class="intro-content text-center mt-4">
    <img src="<?php echo $custom_logo_url[0]?>">
    <span class="d-block ft-Lemon intro-title"><?php the_field('intro_txt') ?></span>
  </div>
</section>

<?php
get_footer('intro');
