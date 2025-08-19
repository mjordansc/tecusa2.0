<?php

/* Template Name: Klarna FAQ */

get_header();

$pageId = get_queried_object_id();
?>

<section class="wrapper pt-5">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 text-center">
        <img class="logo-klarna-faq" src="<?php echo get_template_directory_uri() ?>/img/logo-klarna.png">
      </div>
      <div class="col-12 pt-5 pt-lg-0">
        <h1 class="text-center mt-lg-5 mb-5 d-block title-underline">
          <?php the_title() ?>
        </h1>
      </div>
    </div>
  </div>
</section>

<section class="wrapper mb-5">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 one-column-klarna">
        <?php the_content() ?>
      </div>
    </div>
  </div>
</section>

<?php
get_footer();
