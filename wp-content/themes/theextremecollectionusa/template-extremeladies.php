<?php

/* Template Name: ExtremeLadies */

get_header();

$pageId = get_queried_object_id();
?>

<section class="wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <a href="<?php the_field('extremeladies_hashtag_link') ?>">
          <h1 class="text-center mt-5 mb-5 d-block title-underline">
            <?php the_title() ?>
          </h1>
        </a>
      </div>
    </div>
  </div>
</section>

<section class="wrapper" id="intro">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="text-center"><?php the_field('extremeladies_subtitle') ?></h2>
        <div class="text-center mt-4 mb-5">
          <?php the_field('extremeladies_txt') ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="wrapper mb-5" id="extreme-gallery">
  <div class="container-fluid">
    <div class="row">
      <?php $counter = 1 ?>
      <?php $i = 4 ?>
      <?php $j = 5 ?>
      <?php while ( have_rows('extremeladies_gallery') ) : the_row();?>
        <?php if ( $counter > $j ) : ?>
          <?php $i = $i+5;?>
          <?php $j = $j+5;?>
        <?php endif ?>
        <?php if ( $counter == $i ) :?>
          <div class="col-lg-8 mb-2 px-1">
            <a href="<?php the_field('extremeladies_insta_link') ?>" target="_blank">
              <?php img_with_alt_sub('extremeladies_gallery_img') ?>
            </a>
          </div>
        <?php elseif ( $counter == $j ) :?>
          <div class="col-lg-4 mb-2 px-1 long-square">
            <a href="<?php the_field('extremeladies_insta_link') ?>" target="_blank">
              <?php img_with_alt_sub('extremeladies_gallery_img') ?>
            </a>
          </div>
        <?php else : ?>
          <div class="col-lg-4 mb-2 px-1">
            <a href="<?php the_field('extremeladies_insta_link') ?>" target="_blank">
              <?php img_with_alt_sub('extremeladies_gallery_img') ?>
            </a>
          </div>
        <?php endif ?>
        <?php $counter++ ?>
      <?php endwhile ?>
    </div>
  </div>
</section>

<?php
get_footer();
