<?php

/* Template Name: Contact Us */

get_header();

$pageId = get_queried_object_id();
?>

<section class="wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h1 class="text-center mt-5 mb-5 d-block title-underline">
          <?php the_title() ?>
        </h1>
      </div>
    </div>
  </div>
</section>

<section class="wrapper mb-5">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-5 pe-lg-5 mb-5">
        <?php //img_with_alt('contact_img') ?>
        <a class="mt-2 mb-3 d-flex align-items-center justify-content-start"
        href="tel:<?php the_field('contact_data_phone_link') ?>">
          <span class="icon icon-contact icon-phone me-3"></span>
          <span class="fw-600"><?php the_field('contact_data_phone') ?></span>
        </a>
        <p class="fw-600 mt-5"><?php pll_e('Personal Shopper') ?></p>
        <a href="https://wa.me/<?php the_field('contact_data_personalshopper_link')?>"
          class="mb-3 d-flex align-items-center justify-content-start">
          <span class="icon icon-contact icon-whatsapp me-3"></span>
          <span class="fw-600"><?php the_field('contact_data_personalshopper') ?></span>
        </a>
        <a href="mailto:<?php the_field('contact_data_email') ?>"
          class="mb-3 d-flex align-items-center justify-content-start">
          <span class="icon icon-contact icon-email me-3"></span>
          <span class="fw-600 x-small"><?php the_field('contact_data_email') ?></span>
        </a>
        <p class="fw-600 mt-5"><?php pll_e('Wholesale') ?></p>
        <a class="mb-3 d-flex align-items-center justify-content-start"
        href="tel:<?php the_field('contact_data_wholesale_phone_link') ?>">
          <span class="icon icon-contact icon-phone me-3"></span>
          <span class="fw-600"><?php the_field('contact_data_wholesale_phone') ?></span>
        </a>
        <a href="mailto:<?php the_field('contact_data_wholesale_email_link') ?>"
          class="mb-3 d-flex align-items-center justify-content-start">
          <span class="icon icon-contact icon-email me-3"></span>
          <span class="fw-600 x-small"><?php the_field('contact_data_wholesale_email') ?></span>
        </a>
        <p class="fw-600 mt-5"><?php pll_e('Store') ?></p>
        <a class="mb-3 d-flex align-items-center justify-content-start"
        href="tel:<?php the_field('contact_data_store_phone_link') ?>">
          <span class="icon icon-contact icon-phone me-3"></span>
          <span class="fw-600"><?php the_field('contact_data_store_phone') ?></span>
        </a>
        <div
          class="mb-3 d-flex align-items-center justify-content-start">
          <span class="icon icon-contact icon-marker me-3"></span>
          <span class="fw-600 x-small"><?php the_field('contact_data_store_address') ?></span>
        </div>
      </div>
      <div class="col-lg-7">
        <div id="maincontact_form">
          <?php $contact_form = get_field('contact_form') ?>
          <?php echo do_shortcode($contact_form) ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="mapWrapper" class="wrapper my-5">
  <div id="contactMap">
  </div>
</section>

<?php
get_footer();
