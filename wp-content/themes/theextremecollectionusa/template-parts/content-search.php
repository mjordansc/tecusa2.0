<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package The_Extreme_Collection_USA
 */

?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>


	</header><!-- .entry-header -->
	<?php the_extreme_collection_usa_post_thumbnail(); ?>

	<?php if ( 'post' === get_post_type() ) : ?>
		<div class="entry-summary">
			<?php the_excerpt(); ?>
		</div><!-- .entry-summary -->
	<?php elseif ( 'product' === get_post_type() ) :?>
		<a class="button search-button d-flex mt-3" href="<?php echo esc_url(get_permalink()) ?>">
			<?php pll_e('View Product') ?>
		</a>
	<?php endif; ?>

</div><!-- #post-<?php the_ID(); ?> -->
