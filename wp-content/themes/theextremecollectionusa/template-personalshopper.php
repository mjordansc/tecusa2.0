<?php

/* Template Name: Personal Shopper */

wp_enqueue_script ('ps-interphone', get_template_directory_uri() . '/dist/js/include/intlTelInput.js', array('jquery'), '', true);
wp_enqueue_script ('ps-init-interphone', get_template_directory_uri() . '/dist/js/calendar/init-phone.js', array('jquery'), '', true);


get_header();

$pageId = get_queried_object_id();
?>

<section class="wrapper pt-5">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 pt-5 pt-lg-0">
        <h1 class="text-center mb-5 d-block title-underline">
          <?php the_title() ?>
        </h1>
      </div>
    </div>
  </div>
</section>

<section class="wrapper mb-5">
  <div class="container-fluid">
    <div class="row justify-content-between">
      <div class="col-12 mb-4">
        <div class="text-center mb-4">
          <?php the_field('personalshopper_intro') ?>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="d-flex align-items-center justify-content-start mb-4">
          <span class="icon icon-personalshopper icon-marker me-3"></span>
          <?php the_field('personalshopper_showroom') ?>
        </div>
        <div class="d-flex align-items-center justify-content-start mb-4">
          <span class="icon icon-personalshopper icon-phone me-3"></span><?php the_field('personalshopper_phone') ?>
        </div>
        <div class="d-flex align-items-center justify-content-start">
          <div class="d-block">
            <span class="icon icon-personalshopper icon-whatsapp me-2"></span>
            <span class="icon icon-personalshopper icon-facetime me-3"></span>
          </div>
          <?php the_field('personalshopper_mobile') ?>
        </div>

      </div>
      <div class="col-lg-7 mt-5 mt-lg-0">
        <div id="personalshopper_form" class="">
          <?php $contact_form = get_field('personalshopper_form') ?>
          <?php echo do_shortcode($contact_form) ?>
          <div id="datetime-first-wrapper"></div>
          <div id="datetime-second-wrapper"></div>
          <div id="form-loader"><span class="form-preloader"></span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
get_footer();
