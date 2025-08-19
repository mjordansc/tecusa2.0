<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package The_Extreme_Collection_USA
 */

?>

<div class="row justify-content-center">
	<div class="col-lg-10 col-12 text-center mb-5 pt-4">
		<h1 class="entry-title"><?php the_title() ?></h1>
	</div>
</div>
<div class="row justify-content-between">
	<div class="col-lg-8">
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<div class="post-thumbnail-wrapper mb-5">
				<?php the_post_thumbnail() ?>
				<div class="d-flex align-items-center justify-content-lg-end mt-3">
					<?php $firstName = get_the_author_meta('user_firstname' )?>
					<?php $lastName = get_the_author_meta('user_lastname' )?>
					<span class="fw-600"><?php echo $firstName . ' ' . $lastName ?></span>
					<span class="ms-2 color-black-50"><?php the_date('F d, Y') ?></span>
				</div>
			</div>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>

		</article>
	</div>
	<div class="col-lg-3 blog-sidebar mt-5 mt-lg-0">
		<?php get_sidebar(); ?>
	</div>
</div>
