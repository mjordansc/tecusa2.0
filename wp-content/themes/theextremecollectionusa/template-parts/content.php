<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package The_Extreme_Collection_USA
 */

?>


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
