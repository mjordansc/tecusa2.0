<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package The_Extreme_Collection_USA
 */

get_header();
?>

<section class="wrapper pt-5 mb-5">
	<div class="container-fluid">
		<div class="row">
			<?php if ( have_posts() ) : ?>

				<div class="col-12">
					<h1 class="fs-12 ft-RedHat fw-600 page-title">
						<?php
						/* translators: %s: search query. */
						echo pll__('Search Results for:') . '<span>' . get_search_query() . '</span>';
						//printf( esc_html__( 'Search Results for: %s', 'the-extreme-collection-usa' ), '<span>' . get_search_query() . '</span>' );
						?>
					</h1>
				</div>

				<?php
				/* Start the Loop */
				while ( have_posts() ) :
					the_post();
					?>
					<div class="col-lg-3 col-12 search-results-wrapper text-center mt-5">
						<?php
						/**
						 * Run the loop for the search to output the results.
						 * If you want to overload this in a child theme then include a file
						 * called content-search.php and that will be used instead.
						 */
						get_template_part( 'template-parts/content', 'search' );
						?>
					</div>
				<?php
				endwhile;

			else :

				get_template_part( 'template-parts/content', 'none' );

			endif;
			?>
		</div>
	</div>
</section>

<?php
get_footer();
