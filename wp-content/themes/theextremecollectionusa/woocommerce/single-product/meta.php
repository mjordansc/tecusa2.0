<?php
/**
 * Single Product Meta
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
?>

<div class="product_meta">

	<?php do_action( 'woocommerce_product_meta_start' ); ?>

	<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>

		<span class="sku_wrapper"><?php esc_html_e( 'SKU:', 'woocommerce' ); ?> <span class="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' ); ?></span></span>

	<?php endif; ?>

	<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>

</div>

<?php /*
<input type="hidden" id="displayProductName" value="<?php echo $product->get_title() ?>">

<?php  if ( !$product->is_in_stock() ) : ?>
	<?php $availablePreorder = get_field('products_available_preorder', $product->get_id()) ?>
	<?php if ( $availablePreorder == 'yes' ) : ?>
		<a href="#" data-bs-toggle="modal" data-bs-target="#availabilityModal" class="button-black-border py-2 mt-3 mb-4 d-inline-flex px-3">
			<?php pll_e('Notify me when this product is available') ?>
		</a>
	<?php endif ?>
<?php endif ?>

*/?>
<?php $termGift = get_field('product_complimentary_gift_link', 'option') ?>
<?php $termLink = get_term_link ($termGift, 'product_cat') ?>
<?php $terms = get_the_terms( $product->get_id(), 'product_cat' ); ?>
<?php $giftFlag = false ?>
<?php foreach ( $terms as $term ) : ?>
	<?php $slug = $term->slug ?>
	<?php if ( $slug != 'exclusive-gift-w-mothers-day-purchase') : ?>
		<?php $giftFlag = true ?>
	<?php endif ?>
<?php endforeach ?>

<?php if ( $giftFlag ) : ?>
		<div class="d-flex align-items-center justify-content-start mb-4">
			<a target="_blank" href="<?php echo $termLink?>" class="button bg-black color-white" style="width:250px; margin: 0">
			<?php echo get_field('product_complimentary_gift_txt', 'option')?>
			</a>
		</div>
<?php endif ?>



<div class="product-section mt-3 mb-3 sustainability-section">
	<span class="fw-600 d-block mt-2 mb-2"><?php pll_e('Sustainability Guarantees') ?></span>
	<div class="sustainability-icons d-flex align-items-center justify-content-start">
		<?php if ( function_exists('pll_current_language') ) : ?>
			<?php if ( pll_current_language() == 'en' ) : ?>
				<?php while ( have_rows('sustainability_list', 'option') ) : the_row(); ?>
					<div class="text-center me-2">
						<?php img_with_alt_sub('sustainability_list_icon', 'option') ?>
						<span class="d-block fw-600 text-center"><?php the_sub_field('sustainability_list_label', 'option') ?></span>
					</div>
				<?php endwhile; ?>
			<?php else : ?>
				<?php while ( have_rows('sustainability_list_es', 'option') ) : the_row(); ?>
					<div class="text-center me-2">
						<?php img_with_alt_sub('sustainability_list_icon', 'option') ?>
						<span class="d-block fw-600 text-center"><?php the_sub_field('sustainability_list_label', 'option') ?></span>
					</div>
				<?php endwhile; ?>
			<?php endif; ?>
		<?php endif;?>

	</div>
</div>

<div class="product-section mb-3 materials-section">
	<span class="fw-600 d-block mt-3 mb-2"><?php pll_e('Materials') ?></span>
	<div class="d-flex align-items-center justify-content-start">
		<?php while ( have_rows('products_materials') ) : the_row(); ?>
			<div class="text-center me-2">
				<span class="d-block fw-600 text-center"><?php the_sub_field('products_materials_label') ?></span>
			</div>
		<?php endwhile; ?>
	</div>
</div>

<div class="product-section shipping-section">
	<span class="fw-600 d-block mt-3 mb-2"><?php pll_e('Shipping Times and Costs') ?></span>
	<div>
		<?php if ( function_exists('pll_current_language') ) : ?>
			<?php if ( pll_current_language() == 'en' ) : ?>
				<?php echo get_field('delivery', 'option') ?>
			<?php else : ?>
				<?php echo get_field('delivery_es', 'option') ?>
			<?php endif ?>
		<?php endif ?>
	</div>
</div>

<div class="product-section refunds-section">
	<span class="fw-600 d-block mt-3 mb-2"><?php pll_e('Returns & Refunds') ?></span>
	<div>
		<?php if ( function_exists('pll_current_language') ) : ?>
			<?php if ( pll_current_language() == 'en' ) : ?>
				<?php the_field('returns_and_refunds', 'option') ?>
			<?php else : ?>
				<?php the_field('returns_and_refunds_es', 'option') ?>
			<?php endif ?>
		<?php endif ?>
	</div>
</div>
