<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package The_Extreme_Collection_USA
 */

?>

<section class="wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<h1 class="text-center mt-5 mb-5 d-block title-underline">
					<?php the_title() ?>
				</h1>
			</div>
		</div>
	</div>
</section>

<section class="wrapper	mb-5 one-column" id="main-content">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<?php the_content() ?>
			</div>
		</div>
	</div>
</section>
