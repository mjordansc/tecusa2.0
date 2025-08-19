<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package The_Extreme_Collection_USA
 */

get_header();
?>

<section class="wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<h1 class="text-center pt-5 mb-5 d-block title-underline">
					Magazine
				</h1>
			</div>
		</div>
		<div class="row pt-3">
			<div class="col-12 px-0">
				<div class="container-fluid blog-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', get_post_type() );
					endwhile;
					?>
					<div class="row">
						<div class="col-12 d-flex align-items-center justify-content-center mb-5">
							<?php numeric_pagination(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>


<?php
get_footer();
