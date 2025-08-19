<?php

/* Template Name: Admin DropShipping */

get_header();

$pageId = get_queried_object_id();
$currentUser = wp_get_current_user();
if ( !empty($currentUser->roles) ) :
  $currentUserRole = $currentUser->roles[0];
else :
  $currentUserRole = '';
endif;

?>

<?php if ( $currentUserRole == 'administrator' ) : ?>

  <section class="wrapper my-5 py-5">
    <div class="container-fluid py-5">
      <div class="row justify-content-center">
        <div class="col-md-3">
          <h2 class="text-center mb-5">Before uploading CSV file</h2>
          <a href="#" id="dropshippingDeleteVariations" class="button mb-4">1. Delete added Variations</a>
          <a href="#" id="dropshippingDeleteVariableProducts" class="button">2. Delete Variable Products</a>
        </div>
        <div class="col-md-2"></div>
        <div class="col-md-3">
          <h2 class="text-center mb-5">After uploading CSV file</h2>
          <a href="#" id="dropshippingLinkTranslations" class="button mb-4">Link Translated Products</a>
        </div>
      </div>
    </div>
  </section>

  <section class="wrapper my-5 py-5">
    <div class="container-fluid py-5">
      <div class="row justify-content-center">
        <div class="col-12 relative">
          <div id="form-loader">
            <span class="form-preloader"></span>
            <p class="text-center">Processing your request, please wait...</p>
          </div>
          <div id="dropshippingResponse" class="text-center"></div>
        </div>
      </div>
    </div>
  </section>

<?php else : ?>

  <section class="wrapper my-5 py-5">
    <div class="container-fluid py-5">
      <div class="row justify-content-center">
        <div class="col-12 text-center">
          <h3 class="my-5">Sorry, you don't have privileges to access this content</h3>
        </div>
      </div>
    </div>
  </section>

<?php endif; ?>

<?php
get_footer();
