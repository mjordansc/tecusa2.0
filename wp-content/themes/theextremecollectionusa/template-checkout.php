<?php

/* Template Name: Custom Checkout */

get_header();

$pageId = get_queried_object_id();
$completeOrder = false;
$availableOrder = false;
if (isset($_GET['key'])) {
    $availableOrder = true;
}
?>

<?php if ( !$availableOrder ) : ?>

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

  <section id="progress-bar" class="wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="checkout-progress-bar">
            <div id="progress-bar-step-1" class="progress-step d-flex align-items-center justify-content-center flex-column
            <?php if ( !is_user_logged_in() ) : ?> active <?php else : ?> complete <?php endif ?>">
              <span class="fw-600 text-center step-caption mb-2">
                <?php the_field('progress_step_one') ?>
              </span>
              <span class="text-center fw-600 step-number">
                1
              </span>
            </div>
            <div class="progress-line relative"></div>
            <div id="progress-bar-step-2" class="progress-step d-flex align-items-center justify-content-center flex-column
            <?php if ( is_user_logged_in() ) : ?> active <?php endif ?>">
              <span class="text-center step-caption fw-600 mb-2">
                <?php the_field('progress_step_two') ?>
              </span>
              <span class="text-center fw-600 step-number">
                2
              </span>
            </div>
            <div class="progress-line relative"></div>
            <div id="progress-bar-step-3" class="progress-step d-flex align-items-center justify-content-center flex-column">
              <span class="text-center fw-600 step-caption mb-2">
                <?php the_field('progress_step_three') ?>
              </span>
              <span class="text-center fw-600 step-number">
                3
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php else : ?>

  <section class="wrapper">
  	<div class="container-fluid">
  		<div class="row">
  			<div class="col-12">
  				<h1 class="text-center pt-lg-5 mt-5 d-block title-underline">
  					<?php pll_e('Thank you!') ?>
  				</h1>
  			</div>
  		</div>
  	</div>
  </section>

<?php endif; ?>

<section id="checkout-main" class="wrapper">
  <div class="container-fluid mt-5 pt-lg-5 mb-lg-5">
    <div class="row">
      <div class="col-12" id="checkout-notices">
        <?php wc_print_notices();  ?>
      </div>
    </div>
    <div class="row">
      <div class="col-12">

        <?php /* ============================================================================== */ ?>
        <?php /* Step - 1 User Details - Non-Logged in Users */ ?>
        <?php /* ============================================================================== */ ?>

        <?php if ( !is_user_logged_in() && !$availableOrder ) : ?>

          <?php do_action( 'woocommerce_before_customer_login_form' ); ?>

          <div class="container-fluid mt-lg-5" id="customer_login">
          	<div class="row justify-content-between">
          		<div class="col-lg-5">
          			<span class="d-block mb-4 section-subtitle fw-600"><?php esc_html_e( 'Login', 'woocommerce' ); ?></span>

          			<form class="woocommerce-form woocommerce-form-login login" method="post">

          				<?php do_action( 'woocommerce_login_form_start' ); ?>

          				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          					<label for="username"><?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
          					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
          				</p>
          				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          					<label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
          					<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
          				</p>

          				<?php do_action( 'woocommerce_login_form' ); ?>

          				<p class="form-row">
          					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
          						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
          					</label>
                    <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                    <div class="d-flex flex-lg-row flex-column align-items-start justify-content-start mt-4">
                      <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
                      <a href="#" id="guest-checkout" class="ms-lg-3 mt-5 mb-4 mt-lg-0 button-black-border d-flex align-items-center justify-content-center px-3"><?php pll_e('Checkout as guest') ?></a>
                    </div>
          				</p>
          				<p class="woocommerce-LostPassword lost_password mt-4">
          					<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
          				</p>

          				<?php do_action( 'woocommerce_login_form_end' ); ?>

          			</form>
          		</div>
          		<div class="col-lg-5 mt-4 mt-lg-0">
          			<span class="d-block mb-4 section-subtitle fw-600"><?php esc_html_e( 'Register', 'woocommerce' ); ?></span>

          			<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

          				<?php do_action( 'woocommerce_register_form_start' ); ?>

          				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

          					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          						<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
          						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
          					</p>

          				<?php endif; ?>

          				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          					<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
          					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
          				</p>

          				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

          					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          						<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
          						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
          					</p>

          				<?php else : ?>

          					<p><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></p>

          				<?php endif; ?>

          				<?php do_action( 'woocommerce_register_form' ); ?>

          				<p class="woocommerce-form-row form-row">
          					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
          					<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
          				</p>

          				<?php do_action( 'woocommerce_register_form_end' ); ?>

          			</form>
          		</div>
          	</div>
          </div>

          <?php do_action( 'woocommerce_after_customer_login_form' ); ?>

        <?php endif; ?>

        <?php /* ============================================================================== */ ?>
        <?php /* Step 2 - Address - Checkout shortcode  */ ?>
        <?php /* ============================================================================== */ ?>
        <div id="checkout-wrapper" class="<?php if (!is_user_logged_in() && !$availableOrder ) :?> d-none <?php else: ?> d-block <?php endif ?>">
          <?php echo do_shortcode('[woocommerce_checkout]')?>
        </div>
      </div>
    </div>
</section>



<?php
get_footer();
