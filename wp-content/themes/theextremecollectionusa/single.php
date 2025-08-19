<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package The_Extreme_Collection_USA
 */

get_header();
$currentID = get_queried_object_id();
?>

<section class="wrapper my-5 pt-5 pb-3">
	<div class="container-fluid">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content-single', get_post_type() );
		endwhile;
		?>
	</div>
</section>

<section class="wrapper my-5" id="magazine">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<h2 class="title-underline text-center section-title mb-5">
					<?php pll_e('More Articles') ?>
				</h2>
			</div>
		</div>
		<div class="row justify-content-center">
			<div class="col-12 px-0">
				<div class="slider-magazine">
					<?php
					$args = array(
						'posts_per_page'   =>  4,
						'post_status'      =>  'publish',
						'post_type'        =>  'post'
					 );
					?>
					<?php $magazinePosts = new WP_Query($args); ?>
					<?php while ( $magazinePosts->have_posts() ) : $magazinePosts->the_post(); ?>

						<?php $postID = get_the_ID(); ?>
						<?php if ( $postID != $currentID ) : ?>
							<div class="container-fluid blog-grid">
								<?php $firstName = get_the_author_meta('user_firstname' )?>
								<?php $lastName = get_the_author_meta('user_lastname' )?>

								<div class="row align-items-center relative mb-5 pb-3">
									<div class="col-lg-7 image-col px-0">


										<div class="post-thumbnail-wrapper">
											<a href="<?php the_permalink() ?>" class="bg-image-regular"
												style="background-image:url(<?php the_post_thumbnail_url() ?>)">
											</a>
										</div>
										<div class="vertical-position mt-3 mt-lg-0">
											<div class="text-rotation">
												<span class="fw-600"><?php echo $firstName . ' ' . $lastName ?></span>
												<span class="color-black-25 ms-2"><?php the_date('F d, Y') ?></span>
											</div>
										</div>

									</div>
									<div class="col-lg-5 title-col px-0 px-lg-4">
										<h2 class="entry-title">
											<a href="<?php the_permalink() ?>">
												<?php the_title() ?>
											</a>
										</h2>
									</div>
								</div>
							</div>
						<?php endif ?>
					<?php endwhile ?>
					<?php wp_reset_postdata(); ?>
				</div>
			</div>
		</div>
	</div>
</section>


<?php
get_footer();
