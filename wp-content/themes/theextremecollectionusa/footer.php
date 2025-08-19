<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package The_Extreme_Collection_USA
 */

?>

	<footer id="colophon" class="wrapper border-top-1 border-grey pt-4 pb-2">
		<div class="container-fluid">
			<div class="row justify-content-center">
				<div class="col-xl-12 col-lg-8 px-0 pb-4 my-3">
					<div class="container-fluid">
						<div class="row">
							<div class="col-xl-4 col-lg-6 col-12">
								<p class="fw-600 mt-3"><?php pll_e('Showroom') ?></p>
								<a class="mb-3 d-flex align-items-center justify-content-start"
								href="tel:<?php the_field('contact_data_showroom_phone_link', 'option') ?>">
									<span class="icon icon-contact icon-phone me-3"></span>
									<span class="fw-600 x-small"><?php the_field('contact_data_showroom_phone', 'option') ?></span>
								</a>
								<div
									class="mb-3 d-flex align-items-center justify-content-start">
									<span class="icon icon-contact icon-marker me-3"></span>
									<div class="d-flex flex-column">
										<span class="fw-600 x-small"><?php the_field('contact_data_showroom_address', 'option') ?></span>
										<span class="fw-600 x-small"><?php the_field('contact_data_showroom_address_2', 'option') ?></span>
									</div>
								</div>
							</div>
							<div class="col-xl-4 col-lg-6 col-12">
								<p class="fw-600 mt-3"><?php pll_e('Personal Shopper') ?></p>
								<a href="mailto:<?php the_field('contact_data_personalshopper_email', 'option') ?>"
				          class="mb-3 d-flex align-items-center justify-content-start">
				          <span class="icon icon-contact icon-email me-3"></span>
				          <span class="fw-600 x-small"><?php the_field('contact_data_personalshopper_email', 'option') ?></span>
				        </a>
								<a class="mb-3 d-flex align-items-center justify-content-start"
								href="tel:<?php the_field('contact_data_personalshopper_link', 'option') ?>">
									<span class="icon icon-contact icon-phone me-3"></span>
									<span class="fw-600 x-small"><?php the_field('contact_data_personalshopper', 'option') ?></span>
								</a>
							</div>
							<div class="col-xl-4 col-lg-6 col-12">
								<p class="fw-600 mt-3"><?php pll_e('Wholesale') ?></p>
								<a href="mailto:<?php the_field('contact_data_wholesale_email', 'option') ?>"
									class="mb-3 d-flex align-items-center justify-content-start">
									<span class="icon icon-contact icon-email me-3"></span>
									<span class="fw-600 x-small"><?php the_field('contact_data_wholesale_email', 'option') ?></span>
								</a>
								<a class="mb-3 d-flex align-items-center justify-content-start"
								href="tel:<?php the_field('contact_data_wholesale_phone_link', 'option') ?>">
									<span class="icon icon-contact icon-phone me-3"></span>
									<span class="fw-600 x-small"><?php the_field('contact_data_wholesale_phone', 'option') ?></span>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-3 col-12">
					<div class="container-fluid px-0">
						<div class="row">
							<div class="col-lg-6 col-12 category-col-1">
								<?php
		            if ( !function_exists('dynamic_sidebar')
		            || !dynamic_sidebar('Footer_Categories_1') ) : ?>
		            <?php
		            endif; ?>
							</div>
							<div class="col-lg-6 col-12">
								<div class="margin-footer">
								<?php
		            if ( !function_exists('dynamic_sidebar')
		            || !dynamic_sidebar('Footer_Categories_2') ) : ?>
		            <?php
		            endif; ?>
								</div>
							</div>
						</div>
					</div>
        </div>
				<div class="col-lg-3 col-12 mt-4 mt-lg-0 ps-xl-5">
					<div class="container-fluid px-0">
						<div class="row">
							<div class="col-lg-6 col-12">
								<?php
		            if ( !function_exists('dynamic_sidebar')
		            || !dynamic_sidebar('Footer_Info_1') ) : ?>
		            <?php
		            endif; ?>
							</div>
							<div class="col-lg-6 col-12">
								<div class="margin-footer">
								<?php
		            if ( !function_exists('dynamic_sidebar')
		            || !dynamic_sidebar('Footer_Info_2') ) : ?>
		            <?php
		            endif; ?>
								</div>
							</div>
						</div>
					</div>
        </div>
				<div class="col-lg-3 col-12 mt-4 mt-lg-0 legal-col">
					<?php
					if ( !function_exists('dynamic_sidebar')
					|| !dynamic_sidebar('Footer_Legal') ) : ?>
					<?php
					endif; ?>
        </div>
				<div class="col-lg-3 col-12 d-none d-lg-block footer-contact">
					<?php
					if ( !function_exists('dynamic_sidebar')
					|| !dynamic_sidebar('Footer_Social') ) : ?>
					<?php
					endif; ?>
				</div>
			</div>
			<div class="row">
				<div class="col-12 mt-4 text-center">
					<span class="footer-copyright">© <?php echo date('Y') . ' '?>THE EXTREME COLLECTION USA</span>
				</div>
			</div>
		</div>
	</footer><!-- #colophon -->

	<?php /* ?>
	<div id="newsletter-popup">
		<a href="#" class="newsletter-close">x</a>
		<!-- Begin Mailchimp Signup Form -->
		<link href="//cdn-images.mailchimp.com/embedcode/classic-10_7_dtp.css" rel="stylesheet" type="text/css">
		<style type="text/css">
			#mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif;  width:600px;}

		</style>
		<div id="mc_embed_signup">
		<form action="https://theextremecollectionusa.us13.list-manage.com/subscribe/post?u=8821b383bd943b71bb98c5ffb&amp;id=85d1295a70" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
		    <div id="mc_embed_signup_scroll">
			<span class="d-block section-subtitle"><?php pll_e('10% off by subscribing to the newsletter') ?></span>
		<div class="mc-field-group">
			<input type="email" placeholder="<?php pll_e('Insert your email') ?>"value="" name="EMAIL" class="required email" id="mce-EMAIL">
		</div>
			<div id="mce-responses" class="clear foot">
				<div class="response" id="mce-error-response" style="display:none"></div>
				<div class="response" id="mce-success-response" style="display:none"></div>
			</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
		    <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_8821b383bd943b71bb98c5ffb_85d1295a70" tabindex="-1" value=""></div>
		        <div class="optionalParent">
		            <div class="clear foot">
									<div class="submit-wrapper relative">
		                <input type="submit" value="
										<?php if ( function_exists('pll_current_language') ) : ?>
											<?php if ( pll_current_language() == 'en' ) : ?>
												<?php echo 'Subscribe'; ?>
											<?php else : ?>
												<?php echo 'Suscribirse'; ?>
											<?php endif; ?>
										<?php endif; ?>" name="subscribe" id="mc-embedded-subscribe" class="button">
										<div class="button-frame"></div>
									</div>
		                <p class="brandingLogo"><a href="http://eepurl.com/h23Fi1" title="Mailchimp - email marketing made easy and fun"></a></p>
		            </div>
		        </div>
						<div class="mt-4">
							<p class="fs-09"><?php pll_e('When you subscribe to our Newsletter you accept our Privacy Policy and Terms and Conditions') ?></p>
						</div>
		    </div>
		</form>
		</div>
		<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script><script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[3]='ADDRESS';ftypes[3]='address';fnames[4]='PHONE';ftypes[4]='phone';fnames[5]='BIRTHDAY';ftypes[5]='birthday'; }(jQuery));var $mcj = jQuery.noConflict(true);</script>
		<!--End mc_embed_signup-->
	</div> 

	<?php */?>

	<div id="newsletter-popup">
		<a href="#" class="newsletter-close">x</a>
      	<link href="//cdn-images.mailchimp.com/embedcode/classic-061523.css" rel="stylesheet" type="text/css">
  		<style type="text/css">
        	#mc_embed_signup{background:#fff; false;clear:left; font:14px Helvetica,Arial,sans-serif; width: 600px;}
        	/* Add your own Mailchimp form style overrides in your site stylesheet or in this style block.
           We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
		</style>
		<div id="mc_embed_signup">
			<form action="https://theextremecollectionusa.us13.list-manage.com/subscribe/post?u=8821b383bd943b71bb98c5ffb&amp;id=85d1295a70&amp;f_id=00960ae3f0" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
				<div id="mc_embed_signup_scroll">
					<span class="d-block section-subtitle"><?php pll_e('10% off by subscribing to the newsletter') ?></span>
					<div class="mc-field-group">
						<input type="email" placeholder="<?php pll_e('E-mail') ?>"value="" name="EMAIL" class="required email" id="mce-EMAIL">
					</div>
					<div class="d-flex align-items-center justify-content-start flex-column flex-lg-row">
						<div class="mc-field-group me-lg-1">
							<input type="text" placeholder="<?php pll_e('Name') ?>" name="FNAME" class=" text" id="mce-FNAME" value="">
						</div>
						<div class="mc-field-group ms-lg-1">
							<input type="text" placeholder="<?php pll_e('Phone Number') ?>" name="PHONE" class="REQ_CSS" id="mce-PHONE" value="">
						</div>
					</div>
					
				<div id="mce-responses" class="clear foot">
					<div class="response" id="mce-error-response" style="display: none;"></div>
					<div class="response" id="mce-success-response" style="display: none;"></div>
				</div>
				<div aria-hidden="true" style="position: absolute; left: -5000px;">
					/* real people should not fill this in and expect good things - do not remove this or risk form bot signups */
					<input type="text" name="b_8821b383bd943b71bb98c5ffb_85d1295a70" tabindex="-1" value="">
				</div>
				<div class="optionalParent">
					<div class="clear foot">
						<input type="submit" name="subscribe" id="mc-embedded-subscribe" class="button" value="Subscribe">
						<p style="margin: 0px auto; opacity: 0"><a href="http://eepurl.com/h23Fi1" title="Mailchimp - email marketing made easy and fun"><span style="display: inline-block; background-color: transparent; border-radius: 4px;"><img class="refferal_badge" src="https://digitalasset.intuit.com/render/content/dam/intuit/mc-fe/en_us/images/intuit-mc-rewards-text-dark.svg" alt="Intuit Mailchimp" style="width: 220px; height: 40px; display: flex; padding: 2px 0px; justify-content: center; align-items: center;"></span></a></p>
					</div>
				</div>
				<div class="mt-4">
									<p class="fs-09"><?php pll_e('When you subscribe to our Newsletter you accept our Privacy Policy and Terms and Conditions') ?></p>
								</div>
					</div>
				</div>
			</form>
		<a href="#" class="newsletter-close-text">[Close]</a>
		</div>
<script type="text/javascript" src="//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js"></script><script type="text/javascript">(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[4]='PHONE';ftypes[4]='phone';fnames[2]='LNAME';ftypes[2]='text';fnames[3]='ADDRESS';ftypes[3]='address';fnames[5]='BIRTHDAY';ftypes[5]='birthday';}(jQuery));var $mcj = jQuery.noConflict(true);</script></div>


</div><!-- #page -->

<?php wp_footer(); ?>

<!-- Start of oct8ne code -->
   <script type="text/javascript">
     var oct8ne = document.createElement("script");
     oct8ne.server = "backoffice-eu.oct8ne.com/";
     oct8ne.type = "text/javascript";
     oct8ne.async = true;
     oct8ne.license ="238BCEFF8EBAC5377D95E3BDB2C4D1D9";
     oct8ne.src = (document.location.protocol == "https:" ? "https://" : "http://") + "static-eu.oct8ne.com/api/v2/oct8ne-api-2.3.js?" + (Math.round(new Date().getTime() / 86400000));
     oct8ne.locale = "en-US";
     oct8ne.baseUrl ="//theextremecollectionusa.com";
     var s = document.getElementsByTagName("script")[0];
     insertOct8ne();
     function insertOct8ne() {
               s.parentNode.insertBefore(oct8ne, s);
     }
   </script>
<!--End of oct8ne code -->

</body>
</html>
