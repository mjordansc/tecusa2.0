<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package The_Extreme_Collection_USA
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<link rel="stylesheet" type="text/css"
	        href="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.0/cookieconsent.min.css" />
	<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.0/cookieconsent.min.js"></script>
	<script>
    window.addEventListener("load", function() {
        window.cookieconsent.initialise({
            "palette": {
                "popup": {
                    "background": "#f6f6f6",
                    "text": "#171717"
                },
                "button": {
                    "background": "#000",
                    "text": "#fff",
                    "border": "#000"
                }
            },
            "content": {
                "message": "<?php pll_e('We use our own and third-party cookies to improve our services and the overall user experience. If you continue browsing, we assume that you accept its use. Please review our Cookie Policy for more information.')?>",
                "dismiss": "<?php pll_e('Accept') ?>",
                "link": "<?php pll_e('Cookies Policy')?>",
                "href": "<?php echo translate_static_slug('cookies-policy') //echo translate_static_slug('cookies-policy'); ?>"
            }
        })
    });
    </script>

	<?php if (get_field('gtm_head', 'option')) : ?>
		<?php echo get_field('gtm_head', 'option') ?>
	<?php endif ?>
	<?php wp_head(); ?>
</head>
<?php //b35fbe87-c1e5-5a9a-aa25-e016f0e25739 ?>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
		<?php if (get_field('gtm_body', 'option')) : ?>
      <?php echo get_field('gtm_body', 'option') ?>
    <?php endif ?>
		<script
		async
		data-environment="production"
		src="https://osm.klarnaservices.com/lib.js"
		data-client-id="1a77ebf4-7ab3-5f50-b4c8-b2c6bd857ee0"></script>
    <div id="page" class="site margin-top-content top-bar-margin top-bar-margin-classy <?php
    if ( is_user_logged_in() ) {
      $user = wp_get_current_user();
      if ($user->roles[0] == 'administrator') { ?>
        margin-top-logged-admin
      <?php
      }
    }
    ?>">
      <header id="masthead" class="site-header fixed-top">
        <nav class="navbar-main navbar-expand-lg
        <?php
        if ( is_user_logged_in() ) {
          $user = wp_get_current_user();
          if ($user->roles[0] == 'administrator' ||$user->roles[0] == 'shop_manager') { ?>
            logged-admin
          <?php
          }
        }
        ?>
        navbar-transition top-bar-include">
          <a href="#" class="close-menu d-lg-none collapse closed">
           <span aria-hidden="true" class="color-blue">&times;</span>
          </a>

          <?php
          if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            if ($user->roles[0] == 'administrator' || $user->roles[0] == 'shop_manager') { ?>
            <div class="kiruyi-adminbar px-2 px-lg-5 bg-black color-custom-white
             fs-08 py-1">
              <div class="container-fluid">
                <div class="row">
                  <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
						<a href="<?php echo site_url()?>/wp-admin/">
							Admin Panel
                      	</a>
						<span class="mx-3">|</span>
						<a href="<?php echo site_url()?>/pos/">
							POS
                      	</a>
					  </div>
                      <a href="<?php echo wp_logout_url() ?>">
                        Log out
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php
            }
          }
          ?>

          <div class="top-bar-classy wrapper border-bottom-1 border-grey">
            <div class="container-fluid">
              <div class="row">
                <div class="col-12">
                  <div class="d-flex align-items-center justify-content-between">
										<a class="d-flex align-items-center justify-content-start"
										href="<?php echo translate_static_slug('klarna-shop-now-pay-later') ?>">
											<span class="fw-600 fs-09 d-none d-lg-block me-2"><?php pll_e('Shop Now. Pay Later.') ?></span>
											<img class="logo-klarna" src="<?php echo get_template_directory_uri() ?>/img/logo-klarna.png">
										</a>
										<div class="d-flex align-items-center justify-content-end">
											<a href="<?php echo translate_static_slug('personal-shopper') ?>" class="me-lg-0 d-block nav-link personal-shopper-link">
												<?php pll_e('Personal Shopper') ?>
											</a>
											<span class="mx-2 mt--2 color-grey separator-v-s d-none d-lg-block">|</span>
											<a href="<?php echo translate_static_slug('about-us')?>" class="me-3 me-lg-0 d-block nav-link d-none d-lg-block">
												<?php pll_e('Made in Spain') ?>
											</a>
											<span class="mx-2 mt--2 color-grey separator-v-s d-none d-lg-block">|</span>
											<div class="dropdown nav-link relative d-none d-lg-block">
                        <button class="btn btn-lang bg-white color-custom-black lang-selector dropdown-toggle
                        d-flex align-items-center text-uppercase"
                        type="button" id="languageDropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                          <?php echo display_current_language(); ?>
                        </button>
                        <div class="dropdown-menu dropdown-lang-menu" aria-labelledby="languageDropdown">
                          <?php echo display_other_languages(); ?>
                        </div>
                      </div>
											<div class="relative nav-link d-none d-lg-block">
                          <div id="currency-switcher-pointer"></div>
                          <?php echo do_shortcode('[woocommerce-currency-switcher format="{{code}}"]') ?>
                      </div>
											<span class="mx-2 mt--2 color-grey separator-v-s d-none d-lg-block">|</span>
											<a href="#" class="ms-0 ms-lg-4 d-none d-lg-block" data-bs-toggle="modal" data-bs-target="#searchModal">
												<span class="icon icon-header icon-search"></span>
											</a>
											<div class="d-none d-lg-block">
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
											</div>
											<a href="<?php echo home_url('/my-account') ?>" class="ms-0 ms-lg-4 d-none d-lg-block">
												<span class="icon icon-header icon-user"></span>
											</a>
										</div>

                  </div>
                </div>
              </div>
            </div>
          </div>

    			<div class="wrapper border-bottom-1 border-grey bottom-bar">
            <div class="container-fluid">
							<div class="row d-none d-lg-flex">
								<div class="col-12">
									<div class="navbar-brand">
										<?php the_custom_logo(); ?>
									</div>
								</div>
							</div>
              <div class="row">
                <div class="col-12">
                  <div class="d-flex align-items-center justify-content-between">
                    <?php
    									wp_nav_menu([
    									'menu'	           => 'primary',
    									'theme_location'   => 'primary',
    									'container'        => 'div',
    									'container_id'     => 'navbar',
    									'depth'            => 2,
    									'container_class'  => 'menu collapse navbar-collapse justify-content-center py-lg-3',
    									'menu_id'          => 'main-menu-classy',
    									'menu_class'       => 'navbar-nav nav-primary text-center text-lg-center justify-content-center',
    									'fallback_cb'      => 'WP_Bootstrap_Navwalker::fallback',
    									'walker'           => new bootstrap_5_wp_nav_menu_walker()
										]);
    									?>
          				</div>
                </div>
              </div>
							<div class="row d-lg-none">
								<div class="col-12">
									<div class="d-flex align-items-center justify-content-between d-lg-none">
										<div class="d-lg-none">
											<?php $item_count = WC()->cart->get_cart_contents_count(); ?>
												<a href="<?php echo home_url('/cart/')?>" class="dropdown-minicart
												relative d-flex align-items-center justify-content-start">
													<div id="item-count-mobile">
														<span class="item-count"><?php echo $item_count ?></span>
													</div>
													<span class="icon icon-header icon-cart ms-lg-4"></span>
												</a>
										</div>
										<div class="navbar-brand">
											<?php the_custom_logo(); ?>
										</div>
										<a class="navbar-toggler-right icon icon-header icon-menu"
										data-toggle="collapse" data-target="#navbar"
										aria-expanded="false" aria-label="Toggle navigation" href="#">
										</a>
									</div>
								</div>
							</div>
            </div>
    			</div>
    		</nav>
      </header>

			<div class="modal fade" id="searchModal" tabindex="-1" role="dialog" aria-labelledby="searchModal" aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header align-items-center">
							<span class="d-block fw-600">
								<?php pll_e('Search') ?>
							</span>
							<a href="#" type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</a>
						</div>
						<div class="modal-body">
							<?php echo do_shortcode('[aws_search_form]') ?>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade" id="availabilityModal" tabindex="-1" role="dialog" aria-labelledby="availabilityModal" aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header align-items-center">
							<span class="d-block fw-600">
								<?php pll_e('Notify me') ?>
							</span>
							<a href="#" type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</a>
						</div>
						<div class="modal-body" id="notifyFormWrapper">
							<?php if ( function_exists('pll_current_language') ) : ?>
								<?php $currentLang = pll_current_language(); ?>
								<?php if ($currentLang == 'en') : ?>
									<?php echo do_shortcode('[contact-form-7 id="4966" title="Availability Form"]') ?>
								<?php else : ?>
								  <?php echo do_shortcode('[contact-form-7 id="4990" title="Disponibilidad de stock"]') ?>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>


			<div class="modal fade" id="spanishStockModal" tabindex="-1" role="dialog" aria-labelledby="spanishStockModal"
			 aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header align-items-center">
							<span class="d-block fw-600">
								<?php pll_e('Shipping Notice') ?>
							</span>
							<a href="#" type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</a>
						</div>
						<div class="modal-body">
							<p><?php pll_e('This item is shipped from Spain and can take up to 14 days to be delivered to the US and 21 days to Canada and Mexico.')?></p>
						</div>
					</div>
				</div>
			</div>

      <div id="content" class="site-content">
