<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
global $badgeTopLeftFlag;
global $badgeBottomImgFlag;
global $badgeBelowPriceFlag;

$productID = $product->get_id();
// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$hideProducts = get_field('labels_top_left_hide_products', 'option');
$hideProductsFlag = false;
foreach ( $hideProducts as $hideProduct ) :
	if ( $hideProduct == $productID) :
		$hideProductsFlag = true;
	endif;
endforeach;

if ( !$hideProductsFlag ) :
	if ( $badgeTopLeftFlag ) :
		$displayTopLeft = get_field('labels_top_left_txt', 'option');
	endif;
endif;

$hideProducts = get_field('labels_bottom_img_hide_products', 'option');
$hideProductsFlag = false;
foreach ( $hideProducts as $hideProduct ) :
	if ( $hideProduct == $productID) :
		$hideProductsFlag = true;
	endif;
endforeach;

if ( !$hideProductsFlag ) :
	if ( $badgeBottomImgFlag ) :
		$displayBottomImg = get_field('labels_bottom_img_txt', 'option');
	endif;
endif;

$hideProducts = get_field('labels_below_price_hide_products', 'option');
$hideProductsFlag = false;
foreach ( $hideProducts as $hideProduct ) :
	if ( $hideProduct == $productID) :
		$hideProductsFlag = true;
	endif;
endforeach;

if ( !$hideProductsFlag ) :
	if ( $badgeBelowPriceFlag ) :
		$displayBelowPrice = get_field('labels_below_price_txt', 'option');
	endif;
endif;

?>

<li <?php wc_product_class( '', $product ); ?>>
	<?php
	/**
	 * Hook: woocommerce_before_shop_loop_item.
	 *
	 * @hooked woocommerce_template_loop_product_link_open - 10
	 */
	do_action( 'woocommerce_before_shop_loop_item' );

	if ($displayBottomImg) : ?>
		<div class="badge-bottom-img">
			<?php echo $displayBottomImg ?>
		</div>
	<?php endif;

	/**
	 * Hook: woocommerce_before_shop_loop_item_title.
	 *
	 * @hooked woocommerce_show_product_loop_sale_flash - 10
	 * @hooked woocommerce_template_loop_product_thumbnail - 10
	 */
	do_action( 'woocommerce_before_shop_loop_item_title' );

	/**
	 * Hook: woocommerce_shop_loop_item_title.
	 *
	 * @hooked woocommerce_template_loop_product_title - 10
	 */
	do_action( 'woocommerce_shop_loop_item_title' );

	/**
	 * Hook: woocommerce_after_shop_loop_item_title.
	 *
	 * @hooked woocommerce_template_loop_rating - 5
	 * @hooked woocommerce_template_loop_price - 10
	 */
	do_action( 'woocommerce_after_shop_loop_item_title' );

	if ($displayBelowPrice) : ?>
		<div class="badge-under-price mb-3">
			<?php echo $displayBelowPrice?>
		</div>
	<?php endif;


	/**
	 * Hook: woocommerce_after_shop_loop_item.
	 *
	 * @hooked woocommerce_template_loop_product_link_close - 5
	 * @hooked woocommerce_template_loop_add_to_cart - 10
	 */
	do_action( 'woocommerce_after_shop_loop_item' );
	?>
	<div id="productImageGalleryData">
		<?php $imageGalleryIDs = $product->get_gallery_image_ids() ?>
		<?php $flag = false ?>
		<?php foreach ($imageGalleryIDs as $imageGalleryID) : ?>
			<?php $imgSrc = wp_get_attachment_image_src($imageGalleryID, 'full'); ?>
			<?php if ( !empty($imgSrc) && !$flag ) :?>
				<img src="<?php echo $imgSrc[0]  ?>" class="alternate-image img-fluid">
				<?php $flag = true ?>
			<?php endif ?>
		<?php endforeach ?>
	</div>

	<?php if ($displayTopLeft) : ?>
		<div class="badge-top-left">
			<?php echo $displayTopLeft ?>
		</div>
	<?php endif; ?>

</li>
