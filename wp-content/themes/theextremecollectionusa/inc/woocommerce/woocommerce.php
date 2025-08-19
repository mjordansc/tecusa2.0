<?php
/**
 * WooCommerce Compatibility File
 *
 * @link https://woocommerce.com/
 *
 * @package The_Extreme_Collection_USA
 */

/**
 * WooCommerce setup function.
 *
 * @link https://docs.woocommerce.com/document/third-party-custom-theme-compatibility/
 * @link https://github.com/woocommerce/woocommerce/wiki/Enabling-product-gallery-features-(zoom,-swipe,-lightbox)
 * @link https://github.com/woocommerce/woocommerce/wiki/Declaring-WooCommerce-support-in-themes
 *
 * @return void
 */
function the_extreme_collection_usa_woocommerce_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 540,
			'single_image_width'    => 500,
			'product_grid'          => array(
				'default_rows'    => 4,
				'min_rows'        => 1,
				'default_columns' => 3,
				'min_columns'     => 1,
				'max_columns'     => 6,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'the_extreme_collection_usa_woocommerce_setup', 10 );

/**
 * WooCommerce specific scripts & stylesheets.
 *
 * @return void
 */
function the_extreme_collection_usa_woocommerce_scripts() {
	wp_enqueue_style( 'the-extreme-collection-usa-woocommerce-style', get_template_directory_uri() . '/woocommerce.css?v=1.0.0', array(), _S_VERSION );

	$font_path   = WC()->plugin_url() . '/assets/fonts/';
	$inline_font = '@font-face {
			font-family: "star";
			src: url("' . $font_path . 'star.eot");
			src: url("' . $font_path . 'star.eot?#iefix") format("embedded-opentype"),
				url("' . $font_path . 'star.woff") format("woff"),
				url("' . $font_path . 'star.ttf") format("truetype"),
				url("' . $font_path . 'star.svg#star") format("svg");
			font-weight: normal;
			font-style: normal;
		}';

	wp_add_inline_style( 'the-extreme-collection-usa-woocommerce-style', $inline_font );
}
add_action( 'wp_enqueue_scripts', 'the_extreme_collection_usa_woocommerce_scripts' );

/**
 * Disable the default WooCommerce stylesheet.
 *
 * Removing the default WooCommerce stylesheet and enqueing your own will
 * protect you during WooCommerce core updates.
 *
 * @link https://docs.woocommerce.com/document/disable-the-default-stylesheet/
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Add 'woocommerce-active' class to the body tag.
 *
 * @param  array $classes CSS classes applied to the body tag.
 * @return array $classes modified to include 'woocommerce-active' class.
 */
function the_extreme_collection_usa_woocommerce_active_body_class( $classes ) {
	$classes[] = 'woocommerce-active';

	return $classes;
}
add_filter( 'body_class', 'the_extreme_collection_usa_woocommerce_active_body_class' );

/**
 * Related Products Args.
 *
 * @param array $args related products args.
 * @return array $args related products args.
 */
function the_extreme_collection_usa_woocommerce_related_products_args( $args ) {
	$defaults = array(
		'posts_per_page' => 3,
		'columns'        => 3,
	);

	$args = wp_parse_args( $defaults, $args );

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'the_extreme_collection_usa_woocommerce_related_products_args' );

/**
 * Remove default WooCommerce wrapper.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

if ( ! function_exists( 'the_extreme_collection_usa_woocommerce_wrapper_before' ) ) {
	/**
	 * Before Content.
	 *
	 * Wraps all WooCommerce content in wrappers which match the theme markup.
	 *
	 * @return void
	 */
	function the_extreme_collection_usa_woocommerce_wrapper_before() {
		?>
			<main id="primary" class="site-main">
		<?php
	}
}
add_action( 'woocommerce_before_main_content', 'the_extreme_collection_usa_woocommerce_wrapper_before' );

if ( ! function_exists( 'the_extreme_collection_usa_woocommerce_wrapper_after' ) ) {
	/**
	 * After Content.
	 *
	 * Closes the wrapping divs.
	 *
	 * @return void
	 */
	function the_extreme_collection_usa_woocommerce_wrapper_after() {
		?>
			</main><!-- #main -->
		<?php
	}
}
add_action( 'woocommerce_after_main_content', 'the_extreme_collection_usa_woocommerce_wrapper_after' );


if ( ! function_exists( 'the_extreme_collection_usa_woocommerce_cart_link_fragment' ) ) {
	/**
	 * Cart Fragments.
	 *
	 * Ensure cart contents update when products are added to the cart via AJAX.
	 *
	 * @param array $fragments Fragments to refresh via AJAX.
	 * @return array Fragments to refresh via AJAX.
	 */
	function the_extreme_collection_usa_woocommerce_cart_link_fragment( $fragments ) {
		$item_count = WC()->cart->get_cart_contents_count();
		$fragments['#item-count'] = '<div id="item-count"><span class="item-count">'. $item_count .'</span></div>';
		$fragments['#item-count-mobile'] = '<div id="item-count-mobile"><span class="item-count">'. $item_count .'</span></div>';
		ob_start();
		?>
		<ul class="dropdown-menu dropdown-menu-minicart minicart-contents">
			<li>
				<div class="widget_shopping_cart_content">
					<?php woocommerce_mini_cart() ?>
				</div>
			</li>
		</ul>
		<?php
		the_extreme_collection_usa_woocommerce_cart_link();
		$fragments['a.minicart-contents'] = ob_get_clean();
		return $fragments;
	}
}
add_filter( 'woocommerce_add_to_cart_fragments', 'the_extreme_collection_usa_woocommerce_cart_link_fragment' );

if ( ! function_exists( 'the_extreme_collection_usa_woocommerce_cart_link' ) ) {
	/**
	 * Cart Link.
	 *
	 * Displayed a link to the cart including the number of items present and the cart total.
	 *
	 * @return void
	 */
	function the_extreme_collection_usa_woocommerce_cart_link() {
		?>
			<?php $item_count = WC()->cart->get_cart_contents_count(); ?>
			<div class="dropdown">
				<a href="#" class="dropdown-toggle dropdown-minicart
				relative d-flex align-items-center justify-content-start" data-bs-toggle="dropdown">
					<div id="item-count">
						<span class="item-count"><?php echo $item_count ?></span>
					</div>
					<span class="icon icon-header icon-cart ms-lg-4"></span>
				</a>
				<ul class="dropdown-menu dropdown-menu-minicart minicart-contents">
					<li>
						<div class="widget_shopping_cart_content">
							<?php woocommerce_mini_cart() ?>
						</div>
					</li>
				</ul>
			</div>
		<?php
	}
}

if ( ! function_exists( 'the_extreme_collection_usa_woocommerce_header_cart' ) ) {
	/**
	 * Display Header Cart.
	 *
	 * @return void
	 */
	function the_extreme_collection_usa_woocommerce_header_cart() {
		the_extreme_collection_usa_woocommerce_cart_link();
	}
}


add_filter( 'woocommerce_product_add_to_cart_text' , 'custom_woocommerce_product_add_to_cart_text' );
function custom_woocommerce_product_add_to_cart_text() {
	global $product;

	if ( $product ) :
		if ( $product->get_type() == 'variable' ) :
			return pll__('View Product');
		else :
			return ('Add to Cart');
		endif;
	endif;
}


add_filter( 'woocommerce_taxonomy_args_product_tag', 'my_woocommerce_make_tags_hierarchical' );
function my_woocommerce_make_tags_hierarchical( $args ) {
    $args['hierarchical'] = true;
    return $args;
};


/* Manually update order status from Processing to Complete */

/*add_action( 'woocommerce_thankyou', 'update_order_source', 20, 1 );
function updated_order_source( $order_id ) {
   if ( ! $order_id ) :
		 return;
	 endif;

   $order = wc_get_order( $order_id );

	 update_field('order_source', 'web', $order_id);
   // No updated status for orders delivered with Bank wire, Cash on delivery and Cheque payment methods.

}*/

/*add_action( 'woocommerce_before_shop_loop_item_title', 'add_on_hover_shop_loop_image' ) ;
function add_on_hover_shop_loop_image() {

    $image_id = wc_get_product()->get_gallery_image_ids()[1] ;

    if ( $image_id ) {

        echo wp_get_attachment_image( $image_id, array('250', '375') ) ;

    } else {  //assuming not all products have galleries set

        echo wp_get_attachment_image( wc_get_product()->get_image_id() ) ;

    }

}*/


add_action( 'wp_ajax_displaySpanishStockMessage', 'displaySpanishStockMessage' );
add_action( 'wp_ajax_nopriv_displaySpanishStockMessage', 'displaySpanishStockMessage' );
function displaySpanishStockMessage() {
	
	$selectedSize = $_POST['selectedSize'];
	$productID = $_POST['productID'];
	$product = wc_get_product($productID);
	$sizes = $product->get_attribute('pa_size');
	$args = array(
		'post_type'         => 'product_variation',
		'post_status'       => 'any',
		'post_parent'		=> $productID,
		'posts_per_page'    => -1,	
		'lang'              => array('en', 'es'),
		'meta_query' => array(
			  array(
				  'key'     => '_sku',
				  'value'   => '^(ESP)',
				  'compare' => 'REGEXP'
			  )
		  )
   
	  );
	  $productVariations = new WP_Query($args);
	  $match = false;

	  while ( $productVariations->have_posts() ) :
		$productVariations->the_post();
		$variationID = get_the_ID();
		$variationObject = wc_get_product($variationID);
		$attributes = $variationObject->get_variation_attributes();
		foreach ( $attributes as $key=>$value ) :
			if ( $key == 'attribute_pa_size' && $value == $selectedSize ) :
				$match = true;
			endif;
		endforeach;
	  endwhile;

	$data = array(
		'match'   =>  $match,
	);
	echo json_encode($data);

	die();
}