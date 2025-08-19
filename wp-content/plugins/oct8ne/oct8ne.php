<?php
/*
Plugin Name: oct8ne
Plugin URI: http://www.oct8ne.com
Description: Help your customers by showing them images and videos of your products using the coviewer to help them along in the decision making process.
Version: 1.0
Author: Oct8ne
Author URI: http://www.oct8ne.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once('helpers/loginhelper.php');
require_once('helpers/oct8neHtaccessHelper.php');
require_once('helpers/jsinfo.php');
require_once('helpers/oct8nedb.php');
require_once('helpers/search/searchfactory.php');
require_once('helpers/wishlist/wishlistfactory.php');
require_once('helpers/translation/translationfactory.php');
require_once('view/menu.php');

if (!class_exists('WC_Oct8ne_Plugin')) :

    class WC_Oct8ne_Plugin
    {

        /**
         * Singleton
         * @var
         */
        public static $instance;


        /**
         * Get or create class
         * @return WC_Oct8ne_Plugin
         */
        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        //http://hookr.io/plugins/woocommerce/#index=a
        //https://businessbloomer.com/woocommerce-visual-hook-guide-cart-page/

        /**
         * WC_Oct8ne_Plugin constructor.
         */
        protected function __construct()
        {
            add_action('admin_init', array($this, 'check_environment'));
            add_action('admin_notices', array($this, 'admin_notices'), 15);
            add_action('admin_menu', array($this, 'init'));

            //Position to load js
            $position = get_option('oct8ne_js_position','Footer');
            if($position == 'Footer') {add_action('wp_footer', array($this, 'addOct8neJs'));}
            else if ($position == 'Header'){add_action('wp_head', array($this, 'addOct8neJs'));}
            else  {add_action('wp_footer', array($this, 'addOct8neJs'));}


            add_action('woocommerce_new_order', 'action_woocommerce_new_order', 10, 1);

            //register hooks
            register_deactivation_hook(__FILE__, array('WC_Oct8ne_Plugin', 'unistall'));
            register_activation_hook(__FILE__, array('WC_Oct8ne_Plugin', 'install'));
            register_uninstall_hook(__FILE__, array('WC_Oct8ne_Plugin', 'fullUninstall'));

        }

        /**
         * Notificaciones
         * @var array
         */
        public $notices = array();

        /**
         * Private clone method to prevent cloning of the instance of the
         * *Singleton* instance.
         *
         * @return void
         */
        private function __clone()
        {
        }

        /**
         * Private unserialize method to prevent unserializing of the *Singleton*
         * instance.
         *
         * @return void
         */
        private function __wakeup()
        {
        }

        /**
         * On plugin activate
         */
        public static function install()
        {
            Oct8neHtaccessHelper::setHtaccessRules();

            Oct8neDb::createTables();

            if (is_plugin_active("yith-woocommerce-wishlist/init.php")) {

                update_option('oct8ne_wishlist_engine', "yith-woocommerce-wishlist");

            }

            //var_dump(ob_get_contents());

        }

        /**
         * On plugin deactivate
         */
        public static function unistall()
        {
            Oct8neHtaccessHelper::removeHtaccessRules();
        }

        /**
         * On plugin delete
         */
        public static function fullUninstall()
        {
            Oct8neDb::dropTables();

            delete_option('oct8ne_token');
            delete_option('oct8ne_license');
            delete_option('oct8ne_email');
            delete_option('oct8ne_search_engine');
            delete_option('oct8ne_wishlist_engine');
        }

        /**
         * Init the plugin after plugins_loaded so environment variables are set.
         */
        public function init()
        {
            // Don't hook anything else in the plugin if we're in an incompatible environment
            if (self::get_environment_warning()) {
                return;
            }

            add_submenu_page('woocommerce',
                'Ajustes plugin Oct8ne',
                'Oct8ne',
                'administrator',
                'oct8ne-settings',
                array('Menu', 'oct8ne_page_settings'));
        }


        /**
         * Comprueba si se cumplen los requisitos para instalar oct8ne
         */
        public function check_environment()
        {

            $environment_warning = self::get_environment_warning();

            if ($environment_warning) {
                $this->add_admin_notice('bad_environment', 'error', $environment_warning);
            }

            //desactivar si no se cumplen
            if (count($this->notices) > 0) {

                deactivate_plugins(plugin_basename(__FILE__));
                $this->notices = array();

            }
        }


        /**
         * Comprobar requisitos
         * @return bool|string|void
         */
        static function get_environment_warning()
        {
            if (!defined('WC_VERSION')) {
                return __('Oct8ne requires WooCommerce to be activated to work.', 'Oct8ne');
            }

            return false;
        }

        /**
         * Errores a mostrar
         * @param $slug
         * @param $class
         * @param $message
         */
        public function add_admin_notice($slug, $class, $message)
        {
            $this->notices[$slug] = array(
                'class' => $class,
                'message' => $message,
            );
        }

        /**
         * Mostrando errores
         */
        public function admin_notices()
        {

            foreach ((array)$this->notices as $notice_key => $notice) {
                echo "<div class='" . esc_attr($notice['class']) . "'><p>";
                echo wp_kses($notice['message'], array('a' => array('href' => array())));
                echo '</p></div>';
            }


            if (count($this->notices) > 0) {

                add_filter('gettext', function ($translated_text, $untranslated_text, $domain) {

                    $old = array("Plugin <strong>activated</strong>.");
                    $new = "Oct8ne not installed";

                    if (in_array($untranslated_text, $old, true)) $translated_text = $new;
                    return $translated_text;
                }, 0, 3);
            }
        }

        /**
         * Añade el javascript necesario para que funcione Oct8ne
         */
        public function addOct8neJs()
        {

            if (wp_script_is('jquery', 'done') && Oct8neLoginHelper::isLogged()) {
                ?>
                <script type="text/javascript">


                    var oct8ne = document.createElement("script");
                    oct8ne.type = "text/javascript";
                    oct8ne.src = (document.location.protocol == "https:" ? "https://" : "http://") + "<?php echo Oct8neJsInfo::getUrlStatic(); ?>" +'api/v2/oct8ne.js';
                    oct8ne.server = "<?php echo Oct8neJsInfo::getServer(); ?>";
                    oct8ne.async = true;
                    oct8ne.license = "<?php echo Oct8neJsInfo::getLicense(); ?>";
                    oct8ne.baseUrl = "<?php echo Oct8neJsInfo::getBaseUrl(); ?>";
                    oct8ne.checkoutUrl = "<?php echo Oct8neJsInfo::getCheckoutUrl(); ?>";
                    oct8ne.loginUrl = "<?php echo Oct8neJsInfo::getLoginUrl(); ?>";
                    oct8ne.checkoutSuccessUrl = "<?php echo Oct8neJsInfo::getSuccessUrl(); ?>";
                    oct8ne.locale = "<?php echo Oct8neJsInfo::getLocale(); ?>";
                    oct8ne.currencyCode = "<?php echo Oct8neJsInfo::getCurrency(); ?>";
					oct8ne.platform = "wordpress";
                    oct8ne.apiVersion = "2.5";
                    oct8ne.onProductAddedToCart = function (productId) {
                        //location.reload();
                    };

                    <?php if(Oct8neJsInfo::getExtraScript()){ ?>
    
                    <?php echo Oct8neJsInfo::getExtraScript(); ?>
                    <?php }?>

                    <?php if(Oct8neJsInfo::isProductPage()){ ?>
                    oct8ne.currentProduct = {
                        id: "<?php echo Oct8neJsInfo::getProductId(); ?>",
                        thumbnail: "<?php echo Oct8neJsInfo::getProductThumbnail(); ?>"
                    };

                    <?php }?>
                    
                    <?php 
                        $oct8neEvents = Oct8neJsInfo::getScriptEvents();
                        $oct8neTimer = Oct8neJsInfo::getScriptTimer();    
                    ?>
                        
<?php if($oct8neEvents == "DISABLED" && $oct8neTimer == "DISABLED"){ ?>
                    insertOct8ne();
<?php }else{?>
    
                    if (document.cookie.indexOf("oct8ne-room") == -1) { 
    <?php if($oct8neTimer != "DISABLED"){ ?>
            
                        setTimeout(insertOct8ne, <?php echo $oct8neTimer ?> * 1000);     
    <?php }?>
    <?php if($oct8neEvents != "DISABLED" && $oct8neEvents != "SCRIPT"){ ?>
        <?php if($oct8neEvents == "ALL"){ ?>
            
                        window.addEventListener('mousemove', insertOct8ne);
                        window.addEventListener('scroll', insertOct8ne);
                        window.addEventListener('click', insertOct8ne);
                        window.addEventListener('keydown', insertOct8ne);                    
                        window.addEventListener('touchstart', insertOct8ne);     
        <?php }else{?>
            
                        window.addEventListener('<?php echo $oct8neEvents ?>', insertOct8ne);    
        <?php }?>  
    <?php }?>  
                    }else{
                        insertOct8ne();
                    }
<?php }?>                   
                    
                    function insertOct8ne() {
                        if (!window.oct8neScriptInserted) {
                            var s = document.getElementsByTagName("script")[0];
                            s.parentNode.insertBefore(oct8ne, s);
                            window.oct8neScriptInserted = true;    
                            
<?php if($oct8neEvents != "DISABLED"){ ?>
    <?php if($oct8neEvents == "ALL"){ ?>

                            window.removeEventListener('mousemove', insertOct8ne);
                            window.removeEventListener('scroll', insertOct8ne);
                            window.removeEventListener('click', insertOct8ne);
                            window.removeEventListener('keydown', insertOct8ne);
                            window.removeEventListener('touchstart', insertOct8ne);
    <?php }else{?>
        <?php if($oct8neEvents != "SCRIPT"){ ?>
                            window.removeEventListener('<?php echo $oct8neEvents ?>', insertOct8ne);
        <?php }?>
    <?php }?>
<?php }?>
    
                        }
                    }

                </script>
                <?php
            }

        }
    }


    /**
     * Hook para cuando se crea una nueva order, añadir el historico
     * @param $order_get_id
     */
    function action_woocommerce_new_order($order_get_id)
    {

        $cookie = $_COOKIE["oct8ne-session"];

        if (isset($cookie) && !empty($cookie)) {

            Oct8neDb::newHistory($order_get_id, $cookie);

        }
    }

endif;

//Inicio de todoo
$GLOBALS['wc_soct8ne'] = WC_Oct8ne_Plugin::get_instance();


?>