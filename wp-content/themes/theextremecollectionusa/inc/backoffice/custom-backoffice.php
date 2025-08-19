<?php
/**
 * Custom Back-Office
 *
 * @package fc_corporativa
 */

/* ------------- Remove Admin Bar */

function remove_admin_bar( $show_admin_bar ) {
	return false;
}
add_filter( 'show_admin_bar' , 'remove_admin_bar' );

/* ------------- Customize Admin Style */
function custom_admin_ui() { ?>
	<style type="text/css">
		.nojq, #adminmenuback, #adminmenu li.current a.menu-top {
			background:#232F3D !important;
		}
		#adminmenuback, #adminmenuwrap, #adminmenu {
			background:#232F3D !important;
			border-right:1px solid #ccc;
		}
		#collapse-button:hover {
			color:#666 !important;
		}
		#wpadminbar .ab-top-menu > li.hover > .ab-item, #wpadminbar.nojq .quicklinks .ab-top-menu > li > .ab-item:focus, #wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item, #wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {
			color:#bbb !important;
			background:transparent !important;
		}
		#wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover > a, #wpadminbar .quicklinks .menupop ul li a:focus, #wpadminbar .quicklinks .menupop ul li a:focus strong, #wpadminbar .quicklinks .menupop ul li a:hover, #wpadminbar .quicklinks .menupop ul li a:hover strong, #wpadminbar .quicklinks .menupop.hover ul li a:focus, #wpadminbar .quicklinks .menupop.hover ul li a:hover, #wpadminbar .quicklinks .menupop.hover ul li div[tabindex]:focus, #wpadminbar .quicklinks .menupop.hover ul li div[tabindex]:hover, #wpadminbar li #adminbarsearch.adminbar-focused::before, #wpadminbar li .ab-item:focus .ab-icon::before, #wpadminbar li .ab-item:focus::before, #wpadminbar li a:focus .ab-icon::before, #wpadminbar li.hover .ab-icon::before, #wpadminbar li.hover .ab-item::before, #wpadminbar li:hover #adminbarsearch::before, #wpadminbar li:hover .ab-icon::before, #wpadminbar li:hover .ab-item::before, #wpadminbar.nojs .quicklinks .menupop:hover ul li a:focus, #wpadminbar.nojs .quicklinks .menupop:hover ul li a:hover {
			color:#bbb !important;
		}
		.woocommerce-admin-page #wpcontent {
			padding:1px !important;
		}
		#wp-admin-bar-wp-custom-logout {
			float:right !important;
		}
	</style>
 <?php }
add_action( 'admin_enqueue_scripts', 'custom_admin_ui' );

/* ------------- Customize WP Admin Bar */
add_action( 'admin_bar_menu', 'custom_wp_admin_bar', 999 );
function custom_wp_admin_bar( $wp_admin_bar ) {
$wp_admin_bar->remove_node( 'new-content' );
$wp_admin_bar->remove_node( 'comments' );
$wp_admin_bar->remove_node( 'wp-logo' );
$wp_admin_bar->remove_node( 'my-account' );
$wp_admin_bar->remove_node( 'view' );
$wp_admin_bar->remove_node( 'view-site' );
$args = array(
	'id' => 'wp-custom-logout',
	'title' => 'Log out',
	'href' => wp_logout_url('/login/'),
	'meta' => array( 'class' => 'custom-logout' )
);
$wp_admin_bar->add_node( $args );

}
/* ------------- Customize Login Logo */
function my_login_logo() {
	$custom_logo_id = get_theme_mod( 'custom_logo' );
	$custom_logo_url = wp_get_attachment_image_src( $custom_logo_id , 'full' );
?>
<style type="text/css">
body {
		background: #fff !important;
}
.login form {
	border: 1px solid #171717 !important;
	font-family: 'Red Hat Display', sans-serif;
}
#login h1 a,
.login h1 a {
		background-image: url(<?php echo $custom_logo_url[0]; ?>);
		height: 84px;
		width: 100%;
		background-size: contain;
		background-repeat: no-repeat;
}
#login #nav a, #login .privacy-policy-page-link, #login .forgetmenot,
#login #backtoblog {
	 display:none;
}
#login input {
	border: 0;
	border-radius: 0;
	border-bottom: 1px solid #171717;
}
#login .button-primary,
#language-switcher input[type="submit"]{
	background:#171717;
	border-color:#171717;
	font-family: 'Red Hat Display', sans-serif;
	border-radius: 0;
	position: relative;
	color: #fff;
}
#login .dashicons-visibility::before, #factoria-footer a  {
	color:#222;
}
#login input[type="text"]:focus,
#login input[type="password"]:focus,
#login input[type="checkbox"]:focus {
	border-color:#222;
	border-width:2px;
	box-shadow:0 0 0;
}
.login #language-switcher  {
	border: 0 !important;
}
</style>
<?php
}
add_action( 'login_enqueue_scripts', 'my_login_logo' );

function my_login_footer() {
	echo '<div id="factoria-footer" style="width:320px; margin:0 auto">'
	. '<p style="text-align:center; margin-top:20px; font-family: Lexend, sans-serif">'
	. 'Framework based on Wordpress developed by '
	. '<a href="https://kiruyi.com" target="_blank">'
	. 'Kiruyi</a></p>'
	. '</div>';
}
add_action('login_footer','my_login_footer');

function my_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'my_login_logo_url' );


/* Customize Logout Redirection */
add_action('wp_logout','logout_redirect');
function logout_redirect() {
	wp_safe_redirect( home_url('my-account') );
	exit();
}
/* ------------- Add GTM code from Options page */

/*function add_analytics_head() {
  echo get_field('gtm_head', 'option');
}
add_action('wp_head', 'add_analytics_head');

function add_analytics_body() {
  echo get_field('gtm_body', 'option');
}
add_action('wp_body_open', 'add_analytics_body');*/
