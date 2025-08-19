<?php

if (!defined('ABSPATH'))
    exit;

class ACOPLW_Backend
{

    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;
    public $hook_suffix = array();
    public $plugin_slug;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function __construct($file = '', $version = '1.0.0')
    {
        $this->_version = $version;
        $this->_token = ACOPLW_TOKEN;
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        $this->plugin_slug = 'abc';

        $this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';


        register_activation_hook($this->file, array($this, 'install'));
        // register_deactivation_hook($this->file, array($this, 'deactivation'));
        add_action('save_post', array($this, 'delete_transient'), 1);
        add_action('edited_term', array($this, 'delete_transient'));
        add_action('delete_term', array($this, 'delete_transient'));
        add_action('created_term', array($this, 'delete_transient'));

        add_action('admin_menu', array($this, 'register_root_page'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);

        $plugin = plugin_basename($this->file);
        add_filter("plugin_action_links_$plugin", array($this, 'add_settings_link'));

        $updater = new ACOPLW_Updater ( ACOPLW_STORE_URL, ACOPLW_FILE, array (
                'version' => ACOPLW_VERSION, // current version number
                'license' => get_option('acoplw_plugin_license_key'), // license key (used get_option above to retrieve from DB)
                'item_id' => ACOPLW_ITEM_ID, // id of this product in EDD
                'author' => 'Acowebs', // author of this plugin
                'url' => home_url()
            )
        );
    }

    /**
     *
     *
     * Ensures only one instance of WCPA is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see WordPress_Plugin_Template()
     * @return Main WCPA instance
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }

    public function register_root_page()
    {
        $this->hook_suffix[] = add_menu_page(
            __('Badges', 'aco-product-labels-for-woocommerce'), __('Badges', 'aco-product-labels-for-woocommerce'), 'edit_products', 'acoplw_badges_ui', array($this, 'admin_ui'), esc_url($this->assets_url) . '/images/icon.png', 25);
        $this->hook_suffix[] = add_submenu_page(
            'acoplw_badges_ui', __('Product Lists', 'aco-product-labels-for-woocommerce'), __('Product Lists', 'aco-product-labels-for-woocommerce'), 'edit_products', 'acoplw_product_lists_ui', array($this, 'admin_ui_pro_lists'));
        $this->hook_suffix[] = add_submenu_page(
            'acoplw_badges_ui', __('Settings', 'aco-product-labels-for-woocommerce'), __('Settings', 'aco-product-labels-for-woocommerce'), 'edit_products', 'acoplw_settings_ui', array($this, 'admin_ui_settings'));
    }

    public function admin_ui()
    {
        ACOPLW_Backend::view('admin-root', []);
    }

    public function add_settings_link($links)
    {
        $settings = '<a href="' . admin_url('admin.php?page=acoplw_badges_ui#/') . '">' . __('Badges','aco-product-labels-for-woocommerce') . '</a>';
        $products = '<a href="' . admin_url('admin.php?page=acoplw_product_lists_ui#/') . '">' . __('Product Lists','aco-product-labels-for-woocommerce') . '</a>';
        array_push($links, $settings);
        array_push($links, $products);
        return $links;
    }

    /**
     *    Create post type forms
     */

    public static function view($view, $data = array())
    {
        extract($data);
        include_once(plugin_dir_path(__FILE__) . 'views/' . $view . '.php');
    }

    // End admin_enqueue_styles ()

    public function admin_ui_pro_lists()
    {
        ACOPLW_Backend::view('admin-lists', []);
    }

    public function admin_ui_settings()
    {
        ACOPLW_Backend::view('admin-settings', []);
    }

    /**
     * Load admin CSS.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_styles($hook = '')
    {
                
        $currentScreen = get_current_screen();
        $screenID = $currentScreen->id; //
        if (strpos($screenID, 'acoplw_') !== false) {

            wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/backend.css', array(), $this->_version);
            wp_enqueue_style($this->_token . '-admin');
            
        }
    }

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_scripts($hook = '')
    {

        $currentScreen  = get_current_screen();
        $screenID       = $currentScreen->id; 
        $acoplwDPRules  = [];
        $defaultLang    = ''; 
        $currentLang    = ''; 
        $pricingrules   = [];

        if (strpos($screenID, 'acoplw_') !== false) {

            if (!isset($this->hook_suffix) || empty($this->hook_suffix)) {
                return;
            }

            // All Categories
            $categories = get_terms ( 'product_cat', ['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'id=>name'] );

            // Product List
            if ( get_option ('acoplw_dp_list_status') && get_option ('acoplw_dp_list_status') == 1 && function_exists('AWDP') ) {
                $acoplwList = get_posts ( array ( 'fields' => 'ids', 'numberposts' => -1, 'post_type' => array ( ACOPLW_PRODUCT_LIST, ACOPLW_DP_PRODUCT_LIST ), 'orderby' => 'title', 'order' => 'ASC' ) );
            } else {
                $acoplwList = get_posts ( array ( 'fields' => 'ids', 'numberposts' => -1, 'post_type' => ACOPLW_PRODUCT_LIST, 'orderby' => 'title', 'order' => 'ASC') );
            }
            $acoplwList = array_map ( function ($v) {
                return ['id' => $v, 'name' => get_the_title($v)];
            }, $acoplwList ); 

            // Dynamic Pricing Rules
            if ( get_option ('acoplw_dp_list_status') && get_option ('acoplw_dp_list_status') == 1 && function_exists('AWDP') ) {
                $acoplwDPRules = get_posts ( array ( 'fields' => 'ids', 'numberposts' => -1, 'post_type' => ACOPLW_DP_RULES, 'orderby' => 'title', 'order' => 'ASC') );
            }
            $acoplwDPRules = array_map ( function ($v) {
                return ['id' => $v, 'name' => get_the_title($v)];
            }, $acoplwDPRules ); 

            // Preview Box
            $previewImage = plugin_dir_url(__FILE__). '../assets/images/preview-product.jpg';

            // Language Parameters
            $defaultLang    = call_user_func ( array ( new ACOPLW_ML(), 'default_language' ), '' );
            $currentLang    = call_user_func ( array ( new ACOPLW_ML(), 'current_language' ), '' );

            // Tags
            $taglist        = get_terms(array('hide_empty' => false, 'taxonomy' => 'product_tag'));
            $screen         = get_current_screen();

            $acoplwBaseurl = ACOPLW_URL;
            
            // Path to the acoplw-badges folder in the upload directory. (Version 4.0.0)
            $upload_dir     = wp_upload_dir();
            $acoplwUpload   = $upload_dir['baseurl'].'/acoplw-badges';

            // Path to acoplw external resources. (Version 4.0.0)
            $acoplw_cdn     = ACOPLW_CDN_URL;
        
            // Dynamic Pricing Active Rules
            if ( function_exists('AWDP') ) {
                $pricingrules = get_posts ( array ( 'fields' => 'ids', 'numberposts' => -1, 'post_type' => array ( AWDP_POST_TYPE ), 'orderby' => 'title', 'order' => 'ASC', 'meta_query' => array ( 'relation' => 'AND', array ( 'key' => 'discount_status', 'value' => 1, 'compare' => '=', 'type' => 'NUMERIC' ) ) ) );
            } 
            $pricingrules   = !empty ( $pricingrules ) ? array_map ( function ($v) {
                return ['id' => $v, 'name' => get_the_title($v)];
            }, $pricingrules ) : ''; 

            wp_enqueue_script('jquery');

            if ( in_array($screen->id, $this->hook_suffix) ) {

                wp_enqueue_media();
                
                if ( !wp_script_is('wp-i18n', 'registered') ) {
                    wp_register_script('wp-i18n', esc_url($this->assets_url) . 'js/i18n.min.js', array('jquery'), $this->_version, true);
                }

                wp_enqueue_script($this->_token . '-backend-script', esc_url($this->assets_url) . 'js/backend.js', array('jquery', 'wp-i18n'), $this->_version, true);
                wp_localize_script($this->_token . '-backend-script', 'acoplw_object', array(
                        'api_nonce'     => wp_create_nonce('wp_rest'),
                        'root'          => rest_url('acoplw/v1/'),
                        'cats'          => (array)$categories,
                        'tags'          => (array)$taglist,
                        'productlist'   => (array)$acoplwList,
                        'previewImage'  => $previewImage,
                        'acoplwBaseurl' => $acoplwBaseurl,
                        'acoplwUpload'  => $acoplwUpload,
                        'acoplw_cdn'    => $acoplw_cdn,
                        'acoplwDPRules' => $acoplwDPRules,
                        'defaultLang'   => $defaultLang,
                        'currentLang'   => $currentLang,
                        'pricingrules'  => $pricingrules
                    )
                );

                $plugin_rel_path = (dirname($this->file)) . '\languages'; /* Relative to WP_PLUGIN_DIR */
                if ( ACOPLW_Wordpress_Version >= 5 ) {
                    wp_set_script_translations ( ACOPLW_TOKEN . '-backend-script', 'aco-product-labels-for-woocommerce', $plugin_rel_path );
                }

            }

        }

    }


    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function install()
    {
        $this->_log_version_number();

    }

    /**
     * Log the plugin version number.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    private function _log_version_number()
    {
        update_option($this->_token . '_version', $this->_version);
    }

    public function delete_transient($arg = false)
    {
        if ($arg) {
            in_array(get_post_type($arg), ['product', ACOPLW_POST_TYPE, ACOPLW_PRODUCT_LIST]) && delete_transient(ACOPLW_PRODUCTS_TRANSIENT_KEY);
        } else {
            delete_transient(ACOPLW_PRODUCTS_TRANSIENT_KEY);
        }

    }

}