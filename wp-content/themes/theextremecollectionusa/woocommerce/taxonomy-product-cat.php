<?php
/**
 * The Template for displaying products in a product category. Simply includes the archive template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/taxonomy-product-cat.php.
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

$page_id = get_queried_object_id();
$termID = get_queried_object_id();
$current_term = get_term($termID, 'product_cat');

$productsPreorder = array();
$productsInStock = array();

$argsTax = array(
	'tax_query' 		=>	array(
		array (
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $termID,
			'operator' => 'IN'
			)
		),
);
$argsInStock = array(
	'stock_status'		=> 'instock',
);
$argsPreorder = array(
	'products_available_preorder'	=>	'yes',
	'stock_status' 					=>	'outofstock',
);


$args = array_merge($argsPreorder, $argsTax);
if ( isset($_GET['min_price']) && isset($_GET['max_price']) ) :
	$argsPrice = array (
		'price_range' 	=> $_GET['min_price'] . '|' . $_GET['max_price'],
	);
	$argsWithPrice = array_merge($argsPrice, $args);
	$productsPreorder = wooNativeLoop ($argsWithPrice);
else :
	$productsPreorder = wooNativeLoop ($args);
endif;
wp_reset_postdata();

$args = array_merge($argsInStock, $argsTax);
if ( isset($_GET['min_price']) && isset($_GET['max_price']) ) :
	$argsPrice = array (
		'price_range' 	=> $_GET['min_price'] . '|' . $_GET['max_price'],
	);
	$argsWithPrice = array_merge($argsPrice, $args);
	$productsInStock = wooNativeLoop($argsWithPrice);
else :
	$productsInStock = wooNativeLoop ($args);
endif;
wp_reset_postdata();

$productsArray = array_merge($productsPreorder, $productsInStock);
$args = array(
	'include' => $productsArray
);
$productsAll	= wooCustomizeLoop($args);

$badgeTopLeftList = get_field('labels_category_top_left', 'option');
$badgeTopLeftFlag = false;
foreach ( $badgeTopLeftList as $badgeTopLeftItem ) :
	if ( $badgeTopLeftItem == $termID) :
		$badgeTopLeftFlag = true;
	endif;
endforeach;

$badgeBottomImgList = get_field('labels_category_bottom_img', 'option');
$badgeBottomImgFlag = false;
foreach ( $badgeBottomImgList as $badgeBottomImgItem ) :
	if ( $badgeBottomImgItem == $termID) :
		$badgeBottomImgFlag = true;
	endif;
endforeach;

$badgeBelowPriceList = get_field('labels_category_below_price', 'option');
$badgeBelowPriceFlag = false;
foreach ( $badgeBelowPriceList as $badgeBelowPriceItem ) :
	if ( $badgeBelowPriceItem == $termID) :
		$badgeBelowPriceFlag = true;
	endif;
endforeach;

?>

<section id="category-banner" class="banner banner-category d-none d-md-block"
style="background-image:url('<?php the_field('taxonomy_banner_img', $current_term) ?>')"></section>

<section id="category-banner" class="banner banner-category banner-category-mobile d-md-none"
style="background-image:url('<?php the_field('taxonomy_banner_mobile_img', $current_term) ?>')"></section>

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
		<div class="row justify-content-between">
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
						foreach($productsAll->products as $singleProduct) :

							$post_object = get_post($singleProduct);
							setup_postdata($GLOBALS['post'] =& $post_object);
							do_action( 'woocommerce_shop_loop' );
							wc_get_template_part( 'content', 'product' );

						endforeach;
						wp_reset_postdata();
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

<div class="py-5"></div>

<section id="more-categories" class="wrapper mb-5">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				<span class="my-5 text-center d-block title-underline ft-Lemon fw-400 section-title">
					<?php pll_e('More Categories') ?>
				</span>
			</div>
		</div>
		<div class="row">
			<?php $more_categories = get_field('product_cat_more_categories', $current_term); ?>
			<?php foreach ( $more_categories as $other_cat ) :?>
				<?php $other_term = get_term($other_cat, 'product_cat') ?>
				<div class="col-sm-4 col-6 mt-3 mt-lg-0 text-center">
					<a href="<?php echo get_term_link($other_term) ?>">
						<?php img_with_alt_term('product_cat_other_image', $other_term) ?>
						<h2 class="text-center mt-4"><?php echo $other_term->name?></h2>
					</a>
				</div>
			<?php endforeach ?>
		</div>
	</div>
</section>

<?php
get_footer();
