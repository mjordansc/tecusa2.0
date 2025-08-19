<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

$page_id = get_option('woocommerce_shop_page_id');
$bannerType = get_field('home_banner_type', $page_id);
?>

<?php /*
<?php if ( !isset($_COOKIE['tec_country']) && !isset($_COOKIE['tec_language']) && !isset($_COOKIE['tec_currency']) ) :?>
	<?php $showIntroForm = true; ?>
<?php else : ?>
	<?php $showIntroForm = false; ?>
<?php endif; ?>



<?php if ( $showIntroForm ) : ?>
	<section id="main" class="d-none d-md-block banner-intro banner-intro-form relative"
	style="background-image:url(<?php the_field('intro_bg_img', 1244)?>)">
	  <?php $custom_logo_id = get_theme_mod( 'custom_logo' ); ?>
		<?php $custom_logo_url = wp_get_attachment_image_src( $custom_logo_id , 'full' );?>
	  <div class="intro-content text-center">
	    <img src="<?php echo $custom_logo_url[0]?>">
	    <form class="intro-form" id="introForm" method="post"
	        action="<?php echo home_url('/shop/') ?>">
	      <div class="d-flex align-items-center justify-content-center">
	        <select name="introCountry" id="introCountry">
	          <option value="USA">USA</option>
	          <option value="Canada">Canada</option>
	          <option value="Mexico">Mexico</option>
	        </select>
	        <select name="introLanguage" id="introLanguage">
	          <option value="en_US">English</option>
	          <option value="es_MX">Español</option>
	        </select>
	        <select name="introCurrency" id="introCurrency">
	          <option value="USD">USD</option>
	          <option value="CAD">CAD</option>
	          <option value="MXN">MXN</option>
	        </select>
	      </div>
	      <button class="button mt-4" type="submit" id="submitIntroForm">Continue</button>
	      </div>
	    </form>
	  </div>
	</section>

	<section id="main" class="d-md-none banner-intro-form bg-white">
	  <div class="banner-intro" style="background-image:url(<?php the_field('intro_bg_mobile_img', 1244)?>)">
	  </div>
	  <?php $custom_logo_id = get_theme_mod( 'custom_logo' ); ?>
		<?php $custom_logo_url = wp_get_attachment_image_src( $custom_logo_id , 'full' );?>
	  <div class="intro-content text-center mt-4">
	    <img src="<?php echo $custom_logo_url[0]?>">
			<form class="intro-form mt-4 px-5" id="introFormMobile">
				<div class="d-flex align-items-center justify-content-center flex-column">
					<select name="introCountryMobile" id="introCountryMobile">
						<option value="USA">USA</option>
						<option value="Canada">Canada</option>
						<option value="Mexico">Mexico</option>
					</select>
					<select name="introLanguageMobile" id="introLanguageMobile">
						<option value="en_US">English</option>
						<option value="es_MX">Español</option>
					</select>
					<select name="introCurrencyMobile" id="introCurrencyMobile">
						<option value="USD">USD</option>
						<option value="CAD">CAD</option>
						<option value="MXN">MXN</option>
					</select>
					<button class="button mt-3" type="submit" id="submitIntroFormMobile">Continue</button>
				</div>
			</form>
	  </div>
	</section>

<?php endif ?>
*/ ?>

<div class="<?php if ( $showIntroForm ) : echo 'd-none'; endif; ?>">
<?php if ($bannerType == 'video') : ?>
	<?php $bannerVideo = get_field('home_banner_video', $page_id); ?>
	<section class="video-wrapper relative">
		<div class="d-none d-lg-block">
			<!--<iframe src="https://player.vimeo.com/video/768352701?autoplay=1&loop=1&autopause&background=1&muted=1" width="100%" height="auto" frameborder="0" allow="autoplay" allowfullscreen></iframe>-->
			<?php echo do_shortcode('[vidbg container=".video-wrapper" mp4="' . $bannerVideo . '"]') ?>
		</div>
		<div class="bg-image-regular banner-home" style="background-image: url(<?php the_field('home_banner_mobile_img', $page_id) ?>)">
			<div class="wrapper z-index-21 relative">
				<div class="container-fluid">
					<div class="row d-flex align-items-center justify-content-start">
						<div class="col-lg-5 mt--40">
							<h1 class="color-white home-title mb-4">
								<?php the_field('home_banner_title', $page_id) ?>
							</h1>
							<a href="<?php the_field('home_banner_link', $page_id) ?>" class="white-button">
								<?php the_field('home_banner_link_txt', $page_id) ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="bg-overlay bg-black"></div>
		<div class="scroll-home">
			<a href="#featured" class="rel-wrapper d-block d-lg-none">
				<img src="<?php echo get_template_directory_uri(); ?>/img/scroll-empty.png">
				<img class="scroll-line-position move-down" src="<?php echo get_template_directory_uri(); ?>/img/scroll-line.png">
			</a>
		</div>
	</section>
<?php else : ?>
		<section class="slider-wrapper relative">
			<div class="slider-banner-home">
				<?php while ( have_rows('home_banner_slider', $page_id) ) : the_row();?>
					<div class="container-fluid px-0">
						<div class="row align-items-center">
							<div class="col-lg-8">
								<div class="d-none d-lg-block">
									<div class="bg-image-regular" style="background-image: url(<?php the_sub_field('home_banner_slider_img', $page_id) ?>)">
									</div>
								</div>
								<div class="d-lg-none">
									<div class="bg-image-regular" style="background-image: url(<?php the_sub_field('home_banner_slider_mobile_img', $page_id) ?>)">
									</div>
									<div class="bg-overlay bg-black"></div>
								</div>
							</div>
							<div class="col-lg-3 relative">
								<div class="slider-title-wrapper">
									<?php $h1Selector = get_sub_field('home_banner_slider_h1'); ?>
									<?php if ( $h1Selector == 'yes') : ?>
										<h1 class="home-title mb-4"><?php the_sub_field('home_banner_slider_title', $page_id) ?></h1>
									<?php else : ?>
										<span class="home-title mb-4 d-block ft-Lemon">
											<?php the_sub_field('home_banner_slider_title', $page_id) ?>
										</span>
									<?php endif ?>

									<a href="<?php the_sub_field('home_banner_slider_btn_link', $page_id) ?>" class="white-button d-lg-none">
										<?php the_sub_field('home_banner_slider_btn_txt', $page_id) ?>
									</a>
									<a href="<?php the_sub_field('home_banner_slider_btn_link', $page_id) ?>" class="button d-none d-lg-flex">
										<?php the_sub_field('home_banner_slider_btn_txt', $page_id) ?>
									</a>
								</div>
							</div>
						</div>
					</div>
				<?php endwhile ?>

			</div>

			<div class="scroll-home">
				<a href="#featured" class="rel-wrapper d-block d-lg-none">
					<img src="<?php echo get_template_directory_uri(); ?>/img/scroll-empty.png">
					<img class="scroll-line-position move-down" src="<?php echo get_template_directory_uri(); ?>/img/scroll-line.png">
				</a>
			</div>
		</section>
	<?php endif ?>


	<section class="wrapper" id="featured-2023">
		<div class="container-fluid mt-5">
			<div class="row pt-lg-3">
				<div class="col-md-4">
					<?php img_with_alt('home_featured_img_1', '', $page_id) ?>
					<div class="featured-category-caption">
						<h2 class="title-featured"><?php the_field('home_featured_title_1', $page_id) ?></h2>
						<a href="<?php the_field('home_featured_link_1', $page_id) ?>"
							class="d-flex align-items-center justify-content-end mt-3">
							<?php pll_e('Shop Now') ?><span class="ms-2 icon icon-link icon-arrow-right"></span>
						</a>
					</div>
				</div>
				<div class="col-md-4">
					<?php img_with_alt('home_featured_img_2', '', $page_id) ?>
					<div class="featured-category-caption">
						<h2 class="title-featured"><?php the_field('home_featured_title_2', $page_id) ?></h2>
						<a href="<?php the_field('home_featured_link_2', $page_id) ?>"
							class="d-flex align-items-center justify-content-end mt-3">
							<?php pll_e('Shop Now') ?><span class="ms-2 icon icon-link icon-arrow-right"></span>
						</a>
					</div>
				</div>
				<div class="col-md-4">
					<?php img_with_alt('home_featured_img_3', '', $page_id) ?>
					<div class="featured-category-caption">
						<h2 class="title-featured"><?php the_field('home_featured_title_3', $page_id) ?></h2>
						<a href="<?php the_field('home_featured_link_3', $page_id) ?>"
							class="d-flex align-items-center justify-content-end mt-3">
							<?php pll_e('Shop Now') ?><span class="ms-2 icon icon-link icon-arrow-right"></span>
						</a>
					</div>
				</div>
			</div>
		</div>
	</section>		

	<!--<section class="extra-wrapper" id="featured">
		<div class="container-fluid mt-5">
			<div class="row mt-5 pt-lg-3">
				<div class="col-sm-6 col-8 m-featured-left">
					<?php img_with_alt('home_featured_img_1', '', $page_id) ?>
				</div>
				<div class="col-sm-6 col-12 p-featured p-featured-right mt-4 mt-lg-0">
					<h3 class="ft-RedHat color-black-50 fw-600"><?php the_field('home_featured_cat_1') ?></h3>
					<h2 class="title-featured"><?php the_field('home_featured_title_1', $page_id) ?></h2>
					<a href="<?php the_field('home_featured_link_1', $page_id) ?>"
						class="d-flex align-items-center justify-content-end mt-3">
						<?php pll_e('Shop Now') ?><span class="ms-2 icon icon-link icon-arrow-right"></span>
					</a>
				</div>
			</div>
			<div class="row mt-5 pt-4 pt-lg-0">
				<div class="col-sm-6 col-12 p-featured p-featured-left mt-4 mt-lg-0 order-2 order-sm-1">
					<h3 class="ft-RedHat color-black-50 fw-600"><?php the_field('home_featured_cat_2') ?></h3>
					<h2 class="title-featured"><?php the_field('home_featured_title_2', $page_id) ?></h2>
					<a href="<?php the_field('home_featured_link_2', $page_id) ?>"
						class="d-flex align-items-center justify-content-end mt-3">
						<?php pll_e('Shop Now') ?><span class="ms-2 icon icon-link icon-arrow-right"></span>
					</a>
				</div>
				<div class="col-4 d-sm-none"></div>
				<div class="col-sm-6 col-8 text-right order-1 order-sm-2 m-featured-right">
					<?php img_with_alt('home_featured_img_2', '', $page_id) ?>
				</div>
			</div>
		</div>
	</section>-->

	<section class="wrapper" id="product-row">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12 slider-wrapper">
					<?php $productsRow = get_field('home_products_row', $page_id) ?>
					<div class="slider-products">
						<?php foreach ( $productsRow as $item ) :?>
							<a href="<?php echo get_the_permalink($item) ?>">
								<?php $src = get_the_post_thumbnail_url($item) ?>
								<img src="<?php echo $src ?>" class="img-fluid">
							</a>
						<?php endforeach ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="wrapper my-5" id="product-categories">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12">
					<h2 class="title-underline text-center section-title mb-5">
						<?php the_field('home_categories_title', $page_id) ?>
					</h2>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-8">
					<div class="container-fluid px-0">
						<div class="row category-height">
							<div class="col-lg-6 pe-lg-1 pb-lg-1 mb-2 mb-lg-0">
								<?php $category_square_link_1 = get_term_link(get_field('home_category_square_1', $page_id), 'product_cat'); ?>
								<a href="<?php echo $category_square_link_1 ?>"
								class="bg-image-regular d-block relative category-s-height"
								style="background-image: url('<?php the_field('home_category_square_img_1', $page_id) ?>') ">
									<h2 class="category-title">
										<?php the_field('home_category_square_title_1', $page_id) ?>
									</h2>
								</a>
							</div>
							<div class="col-lg-6 ps-lg-1 pe-lg-1 pb-lg-1 mb-2 mb-lg-0">
								<?php $category_square_link_2 = get_term_link(get_field('home_category_square_2', $page_id), 'product_cat'); ?>
								<a href="<?php echo $category_square_link_2 ?>"
								class="d-block bg-image-regular relative category-s-height"
								style="background-image: url('<?php the_field('home_category_square_img_2', $page_id) ?>') ">
									<h2 class="category-title">
										<?php the_field('home_category_square_title_2', $page_id) ?>
									</h2>
								</a>
							</div>
						</div>
						<div class="row">
							<div class="col-12 pt-lg-1 pe-lg-1 mb-2 mb-lg-0">
								<?php $category_horizontal_link = get_term_link(get_field('home_category_horizontal', $page_id), 'product_cat'); ?>
								<a href="<?php echo $category_horizontal_link ?>"
								class="d-block bg-image-regular relative category-s-height"
								style="background-image: url('<?php the_field('home_category_hor_img', $page_id) ?>') ">
									<h2 class="category-title">
										<?php the_field('home_category_hor_title', $page_id) ?>
									</h2>
								</a>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-4 relative ps-lg-1 mb-2 mb-lg-0">
					<?php $category_vertical_link = get_term_link(get_field('home_category_vertical', $page_id), 'product_cat'); ?>
					<a href="<?php echo $category_vertical_link ?>"
					class="d-none d-lg-block bg-image-regular relative category-l-height"
					style="background-image: url('<?php the_field('home_category_ver_img', $page_id) ?>') ">
						<h2 class="category-title">
							<?php the_field('home_category_ver_title', $page_id) ?>
						</h2>
					</a>
					<a href="<?php echo $category_vertical_link ?>"
					class="d-block d-lg-none bg-image-regular relative category-l-height"
					style="background-image: url('<?php the_field('home_category_ver_mobile_img', $page_id) ?>') ">
						<h2 class="category-title">
							<?php the_field('home_category_ver_title', $page_id) ?>
						</h2>
					</a>
				</div>
			</div>
		</div>
	</section>

	<section class="wrapper mt-5 pt-lg-5 pb-lg-5" id="johanna-ortiz-collab">
		<div class="container-fluid">
			<div class="row d-flex align-items-center">
				<div class="col-md-8 order-2 order-md-1">
					<div class="container-fluid px-0">
						<div class="row">
							<div class="col-md-4 pe-md-1 mb-1 mb-md-0">
								<?php img_with_alt('home_johannaortiz_img_1', '', $page_id) ?>
							</div>
							<div class="col-md-4 px-md-2 mb-1 mb-md-0">
								<?php img_with_alt('home_johannaortiz_img_2', '', $page_id) ?>
							</div>
							<div class="col-md-4 ps-md-1 mb-1 mb-md-0">
							<?php img_with_alt('home_johannaortiz_img_3', '', $page_id) ?>
							</div>
						</div>
					</div>
					<a href="<?php the_field('home_johannaortiz_link', $page_id) ?>" target="_blank"
						class="d-flex d-md-none align-items-center justify-content-center mt-4">
						<?php pll_e('Read VOGUE Article') ?><span class="ms-3 icon icon-link icon-arrow-right"></span>
					</a>
				</div>
				<div class="col-md-4 ps-md-5 order-1 order-md-2">
					<div class="d-flex align-items-center justify-content-center 
					flex-column-reverse flex-md-row collab-johanna-logos">
						<?php $logoID = get_theme_mod( 'custom_logo' ); ?>
						<?php $logo = wp_get_attachment_image_src( $logoID , 'full' ); ?>
						<?php img_with_alt('home_johannaortiz_logo', '', $page_id) ?>
						<img src="<?php echo $logo[0] ?>" class="img-fluid">
					</div>
					<h3 class="ft-RedHat fw-400 my-4 text-center text-md-start">
						<?php the_field('home_johannaortiz_txt', $page_id) ?>
					</h3>
					<a href="<?php the_field('home_johannaortiz_link', $page_id) ?>" target="_blank"
						class="d-none d-md-flex align-items-center justify-content-end">
						<?php pll_e('Read VOGUE Article') ?><span class="ms-3 icon icon-link icon-arrow-right"></span>
					</a>
				</div>
	
			</div>
		</div>
	</section>

	<section class="wrapper mt-5 pt-lg-5 pb-lg-5" id="personal-shopper">
		<div class="container-fluid">
			<div class="row d-flex align-items-center">
				<div class="col-md-4 pe-md-5">
					<h2 class="section-title title-underline text-center text-md-start">
						<?php the_field('home_personalshopper_title', $page_id) ?>
					</h2>
					<h3 class="ft-RedHat fw-400 my-4 text-center text-md-start">
						<?php the_field('home_personalshopper_txt', $page_id) ?>
					</h3>
					<a href="<?php the_field('home_personalshopper_link', $page_id) ?>"
						class="d-none d-md-flex align-items-center justify-content-end">
						<?php pll_e('Book Now') ?><span class="ms-3 icon icon-link icon-arrow-right"></span>
					</a>
				</div>
				<div class="col-md-8">
					<?php img_with_alt('home_personalshopper_img', '', $page_id) ?>
					<a href="<?php the_field('home_personalshopper_link', $page_id) ?>"
						class="d-flex d-md-none align-items-center justify-content-center mt-4">
						<?php pll_e('Book Now') ?><span class="ms-3 icon icon-link icon-arrow-right"></span>
					</a>
				</div>
			</div>
		</div>
	</section>

	<section class="wrapper my-5" id="extremeladies">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12">
					<a href="<?php the_field('home_extremeladies_link', $page_id) ?>">
						<h2 class="title-underline text-center section-title mb-5">
							<?php the_field('home_extremeladies_title', $page_id) ?>
						</h2>
					</a>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<?php $extremeladies = get_field('home_extremeladies') ?>
					<div class="slider-extremeladies">
						<?php while ( have_rows('home_extremeladies', $page_id) ) : the_row();?>
							<?php img_with_alt_sub('home_extremeladies_img', 'ladies-cover', $page_id) ?>
						<?php endwhile ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php if ( function_exists('pll_current_language') ) : ?>
		<?php if ( pll_current_language() == 'en' ) : ?>
			<section class="wrapper my-5 pt-5" id="magazine">
				<div class="container-fluid">
					<div class="row">
						<div class="col-12">
							<h2 class="title-underline text-center section-title mb-5">
								<?php the_field('home_magazine_title', $page_id) ?>
							</h2>
						</div>
					</div>
					<div class="row justify-content-center">
						<div class="col-12 px-0">
							<div class="slider-magazine">
								<?php
								$args = array(
									'posts_per_page'   =>	-1,
									'post_status'      =>	'publish',
									'post_type'				 =>	'post'
								 );
								?>
								<?php $magazinePosts = new WP_Query($args); ?>
								<?php while ( $magazinePosts->have_posts() ) : $magazinePosts->the_post(); ?>
									<div class="container-fluid blog-grid">
										<?php $firstName = get_the_author_meta('user_firstname' )?>
										<?php $lastName = get_the_author_meta('user_lastname' )?>

										<div class="row align-items-center relative mb-5 pb-3">
											<div class="col-lg-7 image-col px-0">


												<div class="post-thumbnail-wrapper">
													<a href="<?php the_permalink() ?>" class="bg-image-regular"
														style="background-image:url(<?php the_post_thumbnail_url() ?>)">
													</a>
												</div>
												<div class="vertical-position mt-3 mt-lg-0">
													<div class="text-rotation">
														<span class="fw-600"><?php echo $firstName . ' ' . $lastName ?></span>
														<span class="color-black-25 ms-2"><?php the_date('F d, Y') ?></span>
													</div>
												</div>

											</div>
											<div class="col-lg-5 title-col px-0 px-lg-4">
												<h2 class="entry-title">
													<a href="<?php the_permalink() ?>">
														<?php the_title() ?>
													</a>
												</h2>
											</div>
										</div>
									</div>
								<?php endwhile ?>
								<?php wp_reset_postdata(); ?>
							</div>
						</div>
					</div>
				</div>
			</section>
		<?php endif ?>
	<?php endif ?>

<?php
get_footer();
