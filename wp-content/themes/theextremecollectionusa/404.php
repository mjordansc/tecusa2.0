<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package The_Extreme_Collection_USA
 */

get_header();
?>

<section class="wrapper" id="error-404">
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-6 col-12 text-center pr-xl-5
        d-flex align-items-center flex-column justify-content-center mt-4 mb-3 my-md-5">
          <span class="mb-4 text-center fw-700 ft-Ubuntu text-uppercase section-title d-block">
            <?php pll_e('Error 404') ?>
          </span>
          <div>
            <p>
              <?php pll_e('Oops, that wasn\'t supposed to happen! Here are some helpful links instead:') ?>
            </p>
          </div>
          <div class="text-center mt-4 d-flex align-items-center
          justify-content-center flex-column">
            <a href="<?php echo site_url() ?>"
              class="button-black-border px-4 py-2 mb-3">
              <?php pll_e('Home') ?>
            </a>
            <a href="<?php echo site_url() ?>/contact/"
              class="button-black-border px-4 py-2 mb-3">
              <?php pll_e('Contact') ?>
            </a>
            <a href="<?php echo site_url() ?>/about-us/"
              class="button-black-border px-4 py-2">
              <?php pll_e('About Us') ?>
            </a>
          </div>
        </div>
        <div class="col-md-6 col-12">
					<div class="bg-image-regular" style="background-image:url(<?php echo get_template_directory_uri() ?>/img/404.jpg)">
					</div>
        </div>
		</div>
	</div>
</section>

<?php
get_footer();
