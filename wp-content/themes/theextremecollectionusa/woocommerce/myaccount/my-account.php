<?php
/**
 * My Account page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="container-fluid px-0">
	<div class="row justify-content-between">
			<div class="col-lg-3">
				<?php do_action( 'woocommerce_account_navigation' ); ?>
			</div>
			<div class="col-lg-8 mt-4 mt-lg-0 ">
				<?php do_action( 'woocommerce_account_content' ); ?>
			</div>
	</div>
</div>
<?php
