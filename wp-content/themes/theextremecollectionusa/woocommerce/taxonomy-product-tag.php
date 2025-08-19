<?php
/**
 * The Template for displaying products in a product tag. Simply includes the archive template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/taxonomy-product-tag.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     4.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header();

$term_id = get_queried_object_id();
$current_term = get_term($term_id, 'product_tag');

$badgeTopLeftList = get_field('labels_tag_top_left', 'option');
$badgeTopLeftFlag = false;
foreach ( $badgeTopLeftList as $badgeTopLeftItem ) :
	if ( $badgeTopLeftItem == $term_id) :
		$badgeTopLeftFlag = true;
	endif;
endforeach;

$badgeBottomImgList = get_field('labels_tag_bottom_img', 'option');
$badgeBottomImgFlag = false;
foreach ( $badgeBottomImgList as $badgeBottomImgItem ) :
	if ( $badgeBottomImgItem == $term_id) :
		$badgeBottomImgFlag = true;
	endif;
endforeach;

$badgeBelowPriceList = get_field('labels_tag_below_price', 'option');
$badgeBelowPriceFlag = false;
foreach ( $badgeBelowPriceList as $badgeBelowPriceItem ) :
	if ( $badgeBelowPriceItem == $term_id) :
		$badgeBelowPriceFlag = true;
	endif;
endforeach;

?>

<section id="category-banner" class="banner banner-category"
style="background-image:url('<?php the_field('taxonomy_banner_img', $current_term) ?>')"></section>
<section id="category-title" class="wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<h1 class="text-center pt-5 mb-5 d-block title-underline">
					<?php echo $current_term->name ?>
				</h1>
			</div>
		</div>
	</div>
</section>
<section id="main" class="wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-3 taxonomy-sidebar">
				<a href="#" id="filterPriceMobile" class="filter-dropdown d-lg-none">
					<?php pll_e('Filters') ?>
				</a>
				<?php
				if ( !function_exists('dynamic_sidebar')
				|| !dynamic_sidebar('Taxonomy_Sidebar') ) : ?>
				<?php
				endif; ?>
			</div>
			<div class="col-lg-8">
				<?php
				if ( woocommerce_product_loop() ) {

					/**
					 * Hook: woocommerce_before_shop_loop.
					 *
					 * @hooked woocommerce_output_all_notices - 10
					 * @hooked woocommerce_result_count - 20
					 * @hooked woocommerce_catalog_ordering - 30
					 */
					do_action( 'woocommerce_before_shop_loop' );

					woocommerce_product_loop_start();

					if ( wc_get_loop_prop( 'total' ) ) {
						while ( have_posts() ) {
							the_post();

							/**
							 * Hook: woocommerce_shop_loop.
							 */
							do_action( 'woocommerce_shop_loop' );

							wc_get_template_part( 'content', 'product' );
						}
					}

					woocommerce_product_loop_end();

					/**
					 * Hook: woocommerce_after_shop_loop.
					 *
					 * @hooked woocommerce_pagination - 10
					 */
					do_action( 'woocommerce_after_shop_loop' );
				} else {
					/**
					 * Hook: woocommerce_no_products_found.
					 *
					 * @hooked wc_no_products_found - 10
					 */
					do_action( 'woocommerce_no_products_found' );
				}
				?>
			</div>
		</div>
	</div>
</section>


<?php
get_footer();
