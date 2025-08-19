<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
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
?>
<?php
/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>
<input type="hidden" value="<?php the_ID() ?>" id="productID">
	<div class="container-fluid px-0 mt-lg-5 pt-lg-5">
		<div class="row justify-content-md-between">
			<div class="col-lg-4">
				<?php
				/**
				 * Hook: woocommerce_before_single_product_summary.
				 *
				 * @hooked woocommerce_show_product_sale_flash - 10
				 * @hooked woocommerce_show_product_images - 20
				 */
				do_action( 'woocommerce_before_single_product_summary' );
				?>
			</div>
			<div class="col-lg-6">
				<div class="summary entry-summary">
					<?php
					/**
					 * Hook: woocommerce_single_product_summary.
					 *
					 * @hooked woocommerce_template_single_title - 5
					 * @hooked woocommerce_template_single_rating - 10
					 * @hooked woocommerce_template_single_price - 10
					 * @hooked woocommerce_template_single_excerpt - 20
					 * @hooked woocommerce_template_single_add_to_cart - 30
					 * @hooked woocommerce_template_single_meta - 40
					 * @hooked woocommerce_template_single_sharing - 50
					 * @hooked WC_Structured_Data::generate_product_data() - 60
					 */
					do_action( 'woocommerce_single_product_summary' );
					?>
				</div>
			</div>
		</div>
		<?php $crossSellIDs = $product->get_cross_sell_ids(); ?>
		<?php if ( !empty($crossSellIDs) ) : ?>
			<div class="row mt-5">
			<div class="col-12 text-center">
				<h2 class="title-underline pt-3 pb-5"><?php pll_e('Complete the Look') ?></h2>
				<?php woocommerce_product_loop_start(); ?>
					<?php foreach ( $crossSellIDs as $crossSellID ) : ?>
						<?php $crossProduct = wc_get_product($crossSellID) ?>
						<?php
						$post_object = get_post( $crossSellID );

						setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

						wc_get_template_part( 'content', 'product' );
						?>
					<?php endforeach ?>
				<?php woocommerce_product_loop_end(); ?>
			</div>
		</div>
		<?php endif?>

		<div class="row">
			<div class="col-12">
				<?php
				/**
				 * Hook: woocommerce_after_single_product_summary.
				 *
				 * @hooked woocommerce_output_product_data_tabs - 10
				 * @hooked woocommerce_upsell_display - 15
				 * @hooked woocommerce_output_related_products - 20
				 */
				//do_action( 'woocommerce_after_single_product_summary' );
				woocommerce_output_related_products();
				?>
				<?php do_action( 'woocommerce_after_single_product' ); ?>
			</div>
		</div>
	</div>

</div>
