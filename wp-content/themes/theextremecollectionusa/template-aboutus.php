<?php

/* Template Name: About Us */

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

<section class="extra-wrapper mb-5" id="about-us-adriana">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 ">
        <?php img_with_alt('aboutus_adriana_img'); ?>
      </div>
      <div class="col-md-6 mt-4 mt-md-0">
        <div>
          <?php the_field('aboutus_adriana_txt') ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="extra-wrapper mb-5" id="the-brand">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-12">
        <h2 class="text-center mt-lg-5 mb-5 d-block title-underline">
          <?php the_field('aboutus_thebrand_title') ?>
        </h2>
      </div>
      <div class="col-md-6 order-md-1 order-2 mt-4 mt-md-0">
        <div class="text-start text-md-end">
          <?php the_field('aboutus_thebrand_txt') ?>
        </div>
      </div>
      <div class="col-md-6 order-md-2 order-1">
        <?php img_with_alt('aboutus_thebrand_img'); ?>
      </div>
    </div>
  </div>
</section>

<section class="mb-5" id="made-in-spain">
  <div class="extra-wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <h2 class="text-center mt-lg-5 mb-5 d-block title-underline">
            <?php the_field('aboutus_madeinspain_title') ?>
          </h2>
        </div>
        <div class="col-12 text-center">
          <div>
            <?php the_field('aboutus_madeinspain_txt_1') ?>
          </div>
          <?php img_with_alt('aboutus_madeinspain_img_100', 'madeinspain-logo'); ?>
          <div class="mt-3 mb-5">
            <?php the_field('aboutus_madeinspain_txt_2') ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <?php img_with_alt('aboutus_madeinspain_img') ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="extra-wrapper mb-5" id="about-us-craftmanship">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="text-center mt-lg-5 mb-lg-5 mb-3 d-block title-underline">
          <?php the_field('aboutus_craftmanship_title') ?>
        </h2>
      </div>
      <div class="col-12 text-center order-2 order-lg-1">
        <div>
          <?php the_field('aboutus_craftmanship_txt') ?>
        </div>
      </div>
      <div class="col-12 text-center order-1 order-lg-2 mt-lg-4 mb-3 mb-lg-0">
        <?php $vimeoId = get_field('aboutus_craftmanship_vimeo_id') ?>
        <?php echo $vimeoId ?>
      </div>
    </div>
  </div>
</section>

<section class="wrapper mb-5" id="about-us-sustainability">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-12">
        <h2 class="text-center mt-lg-5 mb-5 d-block title-underline">
          <?php the_field('aboutus_sustainability_title') ?>
        </h2>
      </div>
      <div class="col-lg-7 order-2 order-lg-1 mt-5 mt-lg-0">
        <div>
          <?php the_field('aboutus_sustainability_txt') ?>
        </div>
      </div>
      <div class="col-lg-5 text-center order-1 order-lg-2">
        <?php img_with_alt('aboutus_sustainability_img', 'sustainability-icons-img') ?>
      </div>
    </div>
  </div>
</section>

<section class="extra-wrapper" id="about-us-collaborations">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="text-center mt-lg-5 mb-5 d-block title-underline">
          <?php the_field('aboutus_collaborations_title') ?>
        </h2>
      </div>
    </div>
    <?php $counter = 1 ?>
    <?php while ( have_rows('aboutus_collaboration_list') ) : the_row();?>
      <div class="row mb-3 align-items-center">
        <div class="col-md-5 order-1 text-center <?php if ($counter % 2 != 0): echo 'order-md-1'; else: echo 'order-md-2'; endif;?> ">
          <?php img_with_alt_sub('aboutus_collaboration_list_img', 'collaboration-logo') ?>
        </div>
        <div class="col-md-7 order-2 text-center <?php if ($counter % 2 != 0): echo 'order-md-2 text-md-start'; else: echo 'order-md-1 text-col-right'; endif;?> ">
          <span class="fw-600 d-block mb-2"><?php echo get_sub_field('aboutus_collaboration_list_name') ?></span>
          <div>
            <?php the_sub_field('aboutus_collaboration_list_txt') ?>
          </div>
        </div>
      </div>
      <div class="row mb-5">
        <div class="col-md-6">
          <?php img_with_alt_sub('aboutus_collaboration_list_img_1') ?>
        </div>
        <div class="col-md-6 mt-3 mt-md-0">
          <?php img_with_alt_sub('aboutus_collaboration_list_img_2') ?>
        </div>
      </div>
      <?php $counter++ ?>
    <?php endwhile ?>
    <?php while ( have_rows('aboutus_featured_collaboration_list') ) : the_row();?>
      <div class="row mb-5">
        <div class="col-12">
          <span class="fw-600 d-block mb-2 text-center"><?php the_sub_field('aboutus_featured_collaboration_list_name') ?></span>
          <div class="text-center mt-3 mb-4">
            <?php the_sub_field('aboutus_featured_collaboration_list_txt') ?>
          </div>
        </div>
        <div class="col-md-6">
          <?php img_with_alt_sub('aboutus_featured_collaboration_list_img_1') ?>
        </div>
        <div class="col-md-6 mt-3 mt-md-0">
          <?php img_with_alt_sub('aboutus_featured_collaboration_list_img_2') ?>
        </div>
      </div>
    <?php endwhile ?>
  </div>
</section>

<section class="extra-wrapper mb-5">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="text-center mt-md-5 mb-5 d-block title-underline">
          <?php the_field('aboutus_awards_title') ?>
        </h2>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <?php img_with_alt('aboutus_awards_logo') ?>
        <span class="fw-600 d-block mb-2 text-center text-md-end mt-4"><?php the_field('aboutus_awards_name') ?></span>
        <div class="text-md-end text-center">
          <?php echo get_field('aboutus_awards_txt') ?>
        </div>
      </div>
      <div class="col-md-6">
        <?php img_with_alt('aboutus_awards_img') ?>
      </div>
    </div>
  </div>
</section>
<?php
get_footer();
