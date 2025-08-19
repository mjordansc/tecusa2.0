<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header();
?>

<div class="modal fade" id="sizeGuideModal" tabindex="-1" role="dialog" aria-labelledby="sizeGuideModal" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header align-items-center">
				<span class="d-block fw-600">
					<?php pll_e('Size Guide') ?>
				</span>
				<a href="#" type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</a>
			</div>
			<div class="modal-body relative">
				<table class="sizeguide-table d-none d-md-block">
					<thead>
						<?php if ( function_exists('pll_current_language') ) : ?>
							<?php if ( pll_current_language() == 'en' ) : ?>
								<?php while ( have_rows('size_guide_labels', 'option') ) : the_row(); ?>
									<td><?php the_sub_field('size_guide_labels_txt', 'option')?></td>
								<?php endwhile ?>
							<?php else: ?>
								<?php while ( have_rows('size_guide_labels_es', 'option') ) : the_row(); ?>
									<td><?php the_sub_field('size_guide_labels_txt', 'option')?></td>
								<?php endwhile ?>
							<?php endif; ?>
						<?php endif; ?>

					</thead>
				<?php if ( function_exists('pll_current_language') ) : ?>
					<?php if ( pll_current_language() == 'en' ) : ?>
						<?php while ( have_rows('size_guide_list', 'option') ) : the_row(); ?>
							<tr>
								<td><?php the_sub_field('size_guide_list_size', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_us', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_mx', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_sleeve', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_shoulders', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_bust', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_waist', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_hips', 'option')?></td>
							</tr>
						<?php endwhile ?>
					<?php else : ?>
						<?php while ( have_rows('size_guide_list_es', 'option') ) : the_row(); ?>
							<tr>
								<td><?php the_sub_field('size_guide_list_size', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_us', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_mx', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_sleeve', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_shoulders', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_bust', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_waist', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_hips', 'option')?></td>
							</tr>
						<?php endwhile ?>
					<?php endif ?>
				<?php endif ?>

				</table>
				<table class="sizeguide-table d-md-none">
					<thead>
						<?php if ( function_exists('pll_current_language') ) : ?>
							<?php if ( pll_current_language() == 'en' ) : ?>
								<?php $counter = 1; ?>
								<?php while ( have_rows('size_guide_labels', 'option') ) : the_row(); ?>
									<?php if ( $counter == 1 || $counter == 2 || $counter == 3 ) :?>
										<td><?php the_sub_field('size_guide_labels_txt', 'option')?></td>
									<?php endif; ?>
									<?php $counter++ ?>
								<?php endwhile ?>
							<?php else : ?>
								<?php $counter = 1; ?>
								<?php while ( have_rows('size_guide_labels_es', 'option') ) : the_row(); ?>
									<?php if ( $counter == 1 || $counter == 2 || $counter == 3 ) :?>
										<td><?php the_sub_field('size_guide_labels_txt', 'option')?></td>
									<?php endif; ?>
									<?php $counter++ ?>
								<?php endwhile ?>
							<?php endif; ?>
						<?php endif; ?>

					</thead>
					<?php if ( function_exists('pll_current_language') ) : ?>
						<?php if ( pll_current_language() == 'en' ) : ?>
							<?php while ( have_rows('size_guide_list', 'option') ) : the_row(); ?>
								<tr>
									<td><?php the_sub_field('size_guide_list_size', 'option')?></td>
									<td><?php the_sub_field('size_guide_list_us', 'option')?></td>
									<td><?php the_sub_field('size_guide_list_mx', 'option')?></td>
								</tr>
							<?php endwhile ?>
						<?php else : ?>
							<?php while ( have_rows('size_guide_list_es', 'option') ) : the_row(); ?>
								<tr>
									<td><?php the_sub_field('size_guide_list_size', 'option')?></td>
									<td><?php the_sub_field('size_guide_list_us', 'option')?></td>
									<td><?php the_sub_field('size_guide_list_mx', 'option')?></td>
								</tr>
							<?php endwhile ?>
						<?php endif; ?>
					<?php endif; ?>

				</table>
				<table class="sizeguide-table d-md-none">
					<thead>
						<?php if ( function_exists('pll_current_language') ) : ?>
							<?php if ( pll_current_language() == 'en' ) : ?>
								<?php $counter = 1; ?>
								<?php while ( have_rows('size_guide_labels', 'option') ) : the_row(); ?>
									<?php if ( $counter == 1 || $counter == 4 || $counter == 5 ) :?>
										<td><?php the_sub_field('size_guide_labels_txt', 'option')?></td>
									<?php endif; ?>
									<?php $counter++ ?>
								<?php endwhile ?>
							<?php else : ?>
								<?php $counter = 1; ?>
								<?php while ( have_rows('size_guide_labels_es', 'option') ) : the_row(); ?>
									<?php if ( $counter == 1 || $counter == 4 || $counter == 5 ) :?>
										<td><?php the_sub_field('size_guide_labels_txt', 'option')?></td>
									<?php endif; ?>
									<?php $counter++ ?>
								<?php endwhile ?>
							<?php endif; ?>
						<?php endif; ?>
					</thead>
					<?php if ( function_exists('pll_current_language') ) : ?>
						<?php if ( pll_current_language() == 'en' ) : ?>
							<?php while ( have_rows('size_guide_list', 'option') ) : the_row(); ?>
							<tr>
								<td><?php the_sub_field('size_guide_list_size', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_sleeve', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_shoulders', 'option')?></td>
							</tr>
							<?php endwhile ?>
						<?php else : ?>
							<?php while ( have_rows('size_guide_list_es', 'option') ) : the_row(); ?>
							<tr>
								<td><?php the_sub_field('size_guide_list_size', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_sleeve', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_shoulders', 'option')?></td>
							</tr>
							<?php endwhile ?>
						<?php endif; ?>
					<?php endif; ?>
			</table>
			<table class="sizeguide-table d-md-none">
				<thead>
					<?php if ( function_exists('pll_current_language') ) : ?>
						<?php if ( pll_current_language() == 'en' ) : ?>
							<?php $counter = 1; ?>
							<?php while ( have_rows('size_guide_labels', 'option') ) : the_row(); ?>
								<?php if ( $counter == 1 || $counter == 6 || $counter == 7 || $counter == 8) :?>
									<td><?php the_sub_field('size_guide_labels_txt', 'option')?></td>
								<?php endif; ?>
								<?php $counter++ ?>
							<?php endwhile ?>
						<?php else: ?>
							<?php $counter = 1; ?>
							<?php while ( have_rows('size_guide_labels_es', 'option') ) : the_row(); ?>
								<?php if ( $counter == 1 || $counter == 6 || $counter == 7 || $counter == 8) :?>
									<td><?php the_sub_field('size_guide_labels_txt', 'option')?></td>
								<?php endif; ?>
								<?php $counter++ ?>
							<?php endwhile ?>
						<?php endif; ?>
					<?php endif; ?>

				</thead>
				<?php if ( function_exists('pll_current_language') ) : ?>
					<?php if ( pll_current_language() == 'en' ) : ?>
						<?php while ( have_rows('size_guide_list', 'option') ) : the_row(); ?>
							<tr>
								<td><?php the_sub_field('size_guide_list_size', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_bust', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_waist', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_hips', 'option')?></td>
							</tr>
						<?php endwhile ?>
					<?php else: ?>
						<?php while ( have_rows('size_guide_list_es', 'option') ) : the_row(); ?>
							<tr>
								<td><?php the_sub_field('size_guide_list_size', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_bust', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_waist', 'option')?></td>
								<td><?php the_sub_field('size_guide_list_hips', 'option')?></td>
							</tr>
						<?php endwhile ?>
					<?php endif; ?>
				<?php endif; ?>

				</table>
				<div class="my-4">
					<p class="fs-09 mb-0"><?php pll_e('Displayed in inches.')?></p>
					<p class="fs-09 mb-0"><?php pll_e('Personal Shopper advice: Take one size bigger than your usual size.')?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="wrapper mt-5 pt-4 pt-lg-5" id="single-product">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<?php
					/**
					 * woocommerce_before_main_content hook.
					 *
					 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
					 * @hooked woocommerce_breadcrumb - 20
					 */
					do_action( 'woocommerce_before_main_content' );
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>

					<?php wc_get_template_part( 'content', 'single-product' ); ?>

				<?php endwhile; // end of the loop. ?>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
				<?php
					/**
					 * woocommerce_after_main_content hook.
					 *
					 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
					 */
					do_action( 'woocommerce_after_main_content' );
				?>
			</div>
		</div>
	</div>
</div>

<?php
get_footer();
