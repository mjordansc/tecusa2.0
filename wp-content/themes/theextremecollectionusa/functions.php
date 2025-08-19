<?php
/**
 * The Extreme Collection USA functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package The_Extreme_Collection_USA
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function the_extreme_collection_usa_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on The Extreme Collection USA, use a find and replace
		* to change 'the-extreme-collection-usa' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'the-extreme-collection-usa', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'the-extreme-collection-usa' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'the_extreme_collection_usa_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'the_extreme_collection_usa_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function the_extreme_collection_usa_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'the_extreme_collection_usa_content_width', 640 );
}
add_action( 'after_setup_theme', 'the_extreme_collection_usa_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function the_extreme_collection_usa_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'the-extreme-collection-usa' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'the-extreme-collection-usa' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}

register_sidebar(array(
			'name' => 'Footer_Categories_1',
			'id' => 'Footer_Categories_1',
			'before_title'  => '<span class="widgettitle d-block">',
			'after_title'   => '</span>',
	));
register_sidebar(array(
			'name' => 'Footer_Categories_2',
			'id' => 'Footer_Categories_2',
			'before_title'  => '<span class="widgettitle d-block">',
			'after_title'   => '</span>',
));
register_sidebar(array(
			'name' => 'Footer_Info_1',
			'id' => 'Footer_Info_1',
			'before_title'  => '<span class="widgettitle d-block">',
			'after_title'   => '</span>',
));
register_sidebar(array(
			'name' => 'Footer_Info_2',
			'id' => 'Footer_Info_2',
			'before_title'  => '<span class="widgettitle">',
			'after_title'   => '</span>',
));
register_sidebar(array(
			'name' => 'Footer_Legal',
			'id' => 'Footer_Legal',
			'before_title'  => '<span class="widgettitle">',
			'after_title'   => '</span>',
));
register_sidebar(array(
			'name' => 'Footer_Contact',
			'id' => 'Footer_Contact',
			'before_title'  => '<span class="widgettitle">',
			'after_title'   => '</span>',
));
register_sidebar(array(
			'name' => 'Footer_Social',
			'id' => 'Footer_Social',
			'before_title'  => '<span class="widgettitle">',
			'after_title'   => '</span>',
));

register_sidebar(
	array(
		'name' => 'Taxonomy_Sidebar',
		'id' => 'Taxonomy_Sidebar',
		'before_title'  => '<span class="widgettitle">',
		'after_title'   => '</span>',
	)
);

add_action( 'widgets_init', 'the_extreme_collection_usa_widgets_init' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/native/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/native/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/native/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/native/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/native/jetpack.php';
}

/**
 * Customize Wordpress backoffice
 */
require get_template_directory() . '/inc/backoffice/custom-backoffice.php';

/**
 * Add fields for custom options page
 */
require get_template_directory() . '/inc/backoffice/options-page.php';

/**
 * Enqueue fonts
 */
require get_template_directory() . '/inc/enqueue/embed-fonts.php';

/**
 * Enqueue scripts
 */
require get_template_directory() . '/inc/enqueue/embed-scripts.php';

/**
 * Enqueue styles
 */
require get_template_directory() . '/inc/enqueue/embed-styles.php';

/**
 * Enqueue video
 */
require get_template_directory() . '/inc/enqueue/embed-video.php';

/**
 * Dynamic tags
 */
require get_template_directory() . '/inc/templates/dynamic-tags.php';

/**
 * Alt Images
 */
require get_template_directory() . '/inc/images/alt-images.php';

/**
 * Include Bootstrap Navwalker
 */
require get_template_directory() . '/inc/menu/bootstrap-wp-navwalker.php';

/**
 * Add Last Nav Item
 */
require get_template_directory() . '/inc/menu/add-last-nav-item.php';


/**
 * Blog Pagination
 */
require get_template_directory() . '/inc/pagination/pagination.php';

/**
 * Translation
 */
require get_template_directory() . '/inc/translation/display-languages.php';
require get_template_directory() . '/inc/translation/translate-slugs.php';
require get_template_directory() . '/inc/translation/translation-strings.php';

/**
 * Load WooCommerce compatibility file.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce/woocommerce.php';
	require get_template_directory() . '/inc/woocommerce/billing-fields.php';
	require get_template_directory() . '/inc/woocommerce/update-currency.php';
	require get_template_directory() . '/inc/woocommerce/dropshipping/delete-variable-products.php';
	require get_template_directory() . '/inc/woocommerce/dropshipping/delete-variations.php';
	require get_template_directory() . '/inc/woocommerce/dropshipping/link-translated-products.php';
	require get_template_directory() . '/inc/woocommerce/custom-loop/wc-loop.php';
}

function print_r2($val) {
  echo '<pre>';
  print_r($val);
  echo  '</pre>';
}

add_action( 'pre_get_posts', 'tg_include_custom_post_types_in_search_results' );
function tg_include_custom_post_types_in_search_results( $query ) {
    if ( $query->is_main_query() && $query->is_search() && ! is_admin() ) {
        $query->set( 'post_type', array( 'post', 'pages', 'product' ) );
    }
}

add_filter( 'facebook_for_woocommerce_integration_prepare_product', 'facebook_sync_issue', 10, 2 );
function facebook_sync_issue( $product_data, $id ) {		
        $product = wc_get_product($id);
	$quantity =  $product->get_stock_quantity();    
	$product_data['inventory'] = $quantity; 
	return $product_data;
}

/*
add_action('admin_init', function () {
  update_option('woocommerce_thumbnail_image_width', 540); // grid/cards
  update_option('woocommerce_single_image_width',   500); // single product
  // Optional: cropping. Values: '1:1', 'custom', 'uncropped'
  update_option('woocommerce_thumbnail_cropping', '1:1');
});
*/