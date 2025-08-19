<?php
/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */



file_find_require('wp-load.php');
require_once(plugin_dir_path( __FILE__ ) . '../../helpers/responsehelper.php');
include_once(plugin_dir_path( __FILE__ ) . '../../helpers/loginhelper.php');
require_once(plugin_dir_path( __FILE__ ) . '../../helpers/loghelper.php');
require_once(my_plugin_get_admin_path() . 'includes/plugin.php');

wp();

postProcess();


function postProcess()
{

    $meth = filter_input(INPUT_GET, 'octmethod');

    $connector = new Oct8neConnector();

    try {
        $meth .= 'OcthMethod';
        if (isset($meth) && method_exists($connector, $meth)) {

            $data = $connector->$meth();

            Oct8neResponseHelper::buildResponse($data);


        } else {

            throw new Exception("Method not Exists");
        }

    } catch (Exception $ex) {

        Oct8neLogHelper::LogException($ex);
    }

    die();

}

/**
 * Logica y metodos de oct8ne
 * Class Oct8neConnector
 */
class Oct8neConnector
{


    private $version = "1.0.12";

    /**
     * Obtiene datos de un producto
     * @param $id_product
     * @param bool $extended
     * @return array
     */
    public function getProductData($id_product, $locale, $extended = false)
    {

        $result = array();

        $translation_engine_name = get_option('oct8ne_translation_engine', 'any');

        if ($translation_engine_name == "any") {

            $product = wc_get_product($id_product);

        } else {

            $engine = Oct8neTranslationFactory::create("$translation_engine_name");

            if (isset($locale) && !empty($locale)) {
                $locale_ex = explode("-", $locale);
                $locale = $locale_ex[0];
            } else {

                $locale = $engine->getLocale();
            }

            $product = $engine->getProduct($id_product, $locale);

        }


        if (!isset($product) || $product == false) return null;

        $type = $product->get_type();
        global $woocommerce;

        $symbol = get_woocommerce_currency_symbol();

        $price = "";
        $regular_price = "";

        if ($type == "variable") {

            $variations = $product->get_variation_prices(true)["price"];
            reset($variations);
            $key = key($variations);
            $price = $variations[$key];

            $variations = $product->get_variation_prices(true)["regular_price"];
            reset($variations);
            $key = key($variations);
            $regular_price = $variations[$key];
        }


        $result["internalId"] = $id_product;
        $result["title"] = $product->get_name();
        $result["formattedPrice"] = $type == "variable" ? $price . " " . $symbol : number_format_i18n(wc_get_price_including_tax($product, array('price' => $product->get_price())), 2) . " {$symbol}";
        $result["formattedPrevPrice"] = $type == "variable" ? $regular_price . " " . $symbol : number_format_i18n(wc_get_price_including_tax($product, array('price' => $product->get_regular_price())), 2) . " {$symbol}";
        $result["productUrl"] = $this->cleanProtocol(get_permalink($id_product));

        //si el producto tiene thumnail
        if (has_post_thumbnail($id_product)) {
            $attachment_ids[0] = get_post_thumbnail_id($id_product);
            $attachment = wp_get_attachment_image_src($attachment_ids[0], 'full');
            $result["thumbnail"] = $this->cleanProtocol($attachment[0]);

        } else {

            $result["thumbnail"] = "";
        }

        //ProductInfo
        if ($extended) {

            $result["description"] = $product->get_description();

            $result["addToCartUrl"] = $type == "variable" ? $this->cleanProtocol($product->add_to_cart_url()) : $this->cleanProtocol(get_home_url()) . "?add-to-cart={$id_product}";
            $result["useProductUrl"] = $type == "variable" ? "true" : "false";


            $medias_aux = $product->get_gallery_image_ids();
            $medias = array();


            foreach ($medias_aux as $media) {

                $url = wp_get_attachment_image_src($media, 'full')[0];
                $medias[] = array("url" => $this->cleanProtocol($url));

            }

            if (!empty($result["thumbnail"])) {

                $medias[] = array("url" => $result["thumbnail"]);
            }

            $result["medias"] = $medias;

        }


        return $result;
    }


    /**
     * Contiene informacion sobre el producto
     * @return array
     */
    public function productInfoOcthMethod()
    {

        return $this->chooseProductSummaryInfo(true);
    }

    /**
     * Obtiene informacion simple sobre el producto. LLama a getSummary
     * @return array
     */
    public function productSummaryOcthMethod()
    {
        return $this->chooseProductSummaryInfo(false);

    }


    /**
     * Obtiene la informacion básica (ProductSummary) o extendida (ProductInfo)
     * @param bool $extended
     * @return array
     */
    private function chooseProductSummaryInfo($extended = false)
    {

        $result = array();

        $ids = filter_input(INPUT_GET, 'productIds');

        $locale = filter_input(INPUT_GET, 'locale');


        if (isset($ids) && !empty($ids)) {

            $ids = explode(",", $ids);

            foreach ($ids as $id) {

                if (is_numeric($id)) {

                    $product = $this->getProductData($id, $locale, $extended);
                    if (isset($product)) {
                        $result[] = $product;
                    }
                }
            }
        }

        return $result;

    }


    /**
     * Devuelve la busqueda de productos por los paremetros de entrada especificados
     * @return array
     */

    //https://codex.wordpress.org/Class_Reference/WP_Query#Status_Parameters
    //https://wordpress.stackexchange.com/questions/139196/display-all-products-by-category-with-woocommerce
    public function searchOcthMethod()
    {

        $searchengine = Oct8neSearchFactory::create(get_option('oct8ne_search_engine', 'woocommerce'));

        $result = $searchengine->search();

        return $result;
    }
    
    public function getOrderDetailsOcthMethod()
    {
        $reference = $_GET["reference"];
        $apiToken = $_GET["apiToken"];
        $localApiToken = get_option('oct8ne_token');
        
        if($apiToken != $localApiToken)
            return;
        
        $order_id = 0;
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_ywson_custom_number_order_complete',
                    'value' => $reference, //here you pass the Order Number
                    'compare' => '=',
                ),
                array(
                    'key' => 'ywson_custom_quote_number_order',
                    'value' => $reference, //here you pass the Order Number
                    'compare' => '=',
                ),
                array(
                    'key' => 'ywson_custom_subscription_number_order',
                    'value' => $reference, //here you pass the Order Number
                    'compare' => '=',
                ),
                array(
                    'key' => '_order_number',
                    'value' => $reference, //here you pass the Order Number
                    'compare' => '=',
                )                 
            )
        );
        $query = new WP_Query( $args );

        if ( !empty( $query->posts ) ) {
            $order_id = $query->posts[ 0 ]->ID;
        }
        if($order_id == 0)
            $order_id = $reference;
                
        $order = array();
        $orderWc = wc_get_order($order_id);
        
        if($orderWc == null)
            return $order;
        
        $orderDetails = $orderWc->get_data();
        
        $order["date"] = $orderWc->get_date_created()->date("Y-m-d H:i:s");
        $order["reference"] = $orderWc->get_order_number();
        $order["total"] = $orderDetails["total"];
        $order["currency"] = $orderDetails["currency"];
        $order["labelState"] = $orderDetails["status"];
        $order["deliveryDate"] = "";
        $order["carrier"] = "";
        $order["trackingNumber"] = "";
        $order["trackingUrl"] = "";
        $order["products"] = [];        
        $order["comments"] = [];
        
        $items = $orderWc->get_items();
        foreach ( $items as $item ) {
            $product=array();
            $product["name"] = $item->get_name();
            $product["quantity"] = $item->get_quantity();
            array_push($order["products"],$product);
        }
        
        $notes = $orderWc->customer_message;
        $note = array();
        $note["message"] = $notes;
        $note["customer"] = true;
        array_push($order["comments"],$note);
        
        return $order;
    }

    public function getOrdersOcthMethod()
    {
        $apiToken = $_GET["apiToken"];
        $email = $_GET["customerEmail"];

        $localApiToken = get_option('oct8ne_token');
        
        if($apiToken != $localApiToken)
            return;
        
        $orderList = [];
        $orderArg = array('customer' => $email,'limit' => -1);
        $orders = wc_get_orders($orderArg);
        if($orders){
            foreach ($orders as $orderWc) 
            {
                $orderDetails = $orderWc->get_data();
        
                $order = array();
                $order["date"] = $orderWc->get_date_created()->date("Y-m-d H:i:s");
                $order["reference"] = $orderWc->get_order_number();
                $order["total"] = $orderDetails["total"];
                $order["currency"] = $orderDetails["currency"];
                $order["labelState"] = $orderDetails["status"];
                $order["deliveryDate"] = "";
                if($order["reference"])
                    array_push($orderList, $order);
            }
        }        
        
        return $orderList;
    }
    /**
     * Obtiene informacion de productos relacionados con el indicado
     * @return array
     */
    public function productRelatedOcthMethod()
    {

        $result = array();

        $id = filter_input(INPUT_GET, 'productId');
        $locale = filter_input(INPUT_GET, 'locale');


        if (isset($id) && !empty($id)) {

            if (is_numeric($id)) {

                $product = new WC_Product($id);
                $categories = $product->get_category_ids();

                $args = array();
                $args["posts_per_page"] = 11;
                $args["post_type"] = "product";
                $args["post_status"] = "publish";

                $args["tax_query"] = array(array(
                    'taxonomy' => 'product_cat', //the slug of the taxonomy you want to get
                    'field' => 'term_id',
                    'terms' => $categories,
                    'operator' => 'IN'
                ));


                //para obtener el total
                $my_query = new WP_Query($args);
                $count = $my_query->found_posts;

                $result["total"] = $count;

                $myposts = get_posts($args);

                foreach ($myposts as $post) {

                    if ($post->ID != $id)

                        $product = $this->getProductData($post->ID, $locale);

                    if (isset($product)) {
                        $result["results"][] = $product;
                    }
                }

            }
        }

        return $result;
    }

    /**
     * @return array
     * Obtiene informacion sobre el usuario
     */
    public function customerDataOcthMethod()
    {
        $result = array();
        $wishlist = array();
        $cart = array();

        $locale = filter_input(INPUT_GET, 'locale');


        $id = get_current_user_id();

        if (isset($id) && !empty($id)) {

            $user = new WC_Customer($id);

            $result["id"] = $id;
            $result["firstName"] = $user->get_first_name();
            $result["lastName"] = $user->get_last_name();
            $result["email"] = $user->get_email();

        }


        global $woocommerce;
        $cart_aux = $woocommerce->cart;


        if (!$cart_aux->is_empty()) {

            $cart_aux = $woocommerce->cart->get_cart();
            foreach ($cart_aux as $line) {

                $summary = $this->getProductData($line["product_id"], $locale);

                if (isset($summary)) {
                    $summary["qty"] = $line["quantity"];
                    $cart[] = $summary;
                }
            }
        }

        $wishlist_items = $this->getWishListOcthMethod();

        foreach ($wishlist_items as $item) {

            $product = $this->getProductData($item, $locale);

            if (isset($product)) {
                $wishlist[] = $product;
            }
        }
        $result["wishlist"] = $wishlist;
        $result["cart"] = $cart;

        return $result;
    }


    /**
     * Añadir productos a la lista blanca
     * @return bool
     * @throws PrestaShopException
     */
    public function addToWishLisOcthMethodt()
    {

        $engine = WishListFactory::create(get_option("oct8ne_wishlist_engine"));

        if (isset($engine)) {

            return $engine->addToWishList();
        }

        return false;
    }

    /**
     * Obtiene la lista de desos
     * @return bool
     */
    private function getWishListOcthMethod()
    {

        $engine = WishListFactory::create(get_option("oct8ne_wishlist_engine"));


        if (isset($engine)) {

            return $engine->getWishList();
        }

        return array();
    }

    /**
     * Obtiene el carro de compra
     * @return array
     */
    public function getCartOcthMethod()
    {


        $result = array();
        $cart = array();


        global $woocommerce;

        $cart_aux = $woocommerce->cart;

        $cart_ = $woocommerce->cart;

        if (!$cart_aux->is_empty()) {

            $cart_aux = $woocommerce->cart->get_cart();

            $cart["price"] = number_format_i18n($cart_->subtotal_ex_tax, 2, ".");
            $cart["finalPrice"] = number_format_i18n($cart_->subtotal, 2, ".");
            $cart["currency"] = get_woocommerce_currency();
            $cart["totalItems"] = $cart_->get_cart_contents_count();

            foreach ($cart_aux as $line) {

                $summary["internalId"] = $line["product_id"];
                $product = new WC_Product($line["product_id"]);
                $summary["title"] = $product->get_title();
                $summary["qty"] = $line["quantity"];
                $summary["price"] = number_format_i18n($line["line_subtotal"], 2, ".");
                $cart[] = $summary;
            }

        }

        $result["cart"] = $cart;
        return $result;
    }


    /**
     * Obtiene un informe de los productos vendidos a traves de oct8ne entre dos fechas
     * @return array
     */
    public function getSalesReportOcthMethod()
    {

        $result = array();

        $from = filter_input(INPUT_GET, 'from');

        $to = filter_input(INPUT_GET, 'to');

        $apiToken = filter_input(INPUT_GET, 'apiToken');

        $in_token = get_option('oct8ne_token', '');


        if (isset($apiToken) && $in_token == $apiToken && isset($from) && isset($to)) {


            $customer_orders = Oct8neDb::getOct8neOrders($from, $to);

            foreach ($customer_orders as $aux) {

                $order = new WC_Order($aux->ID);

                if (isset($order)) {

                    $item = array();
                    $item["quoteid"] = $aux->ID;
                    $item["orderId"] = $aux->ID;
                    $item["sessionId"] = $aux->session_id;
                    $item["customerId"] = $order->get_customer_id();
                    $item["price"] = number_format_i18n($order->get_subtotal(), 2, ".");
                    $item["finalPrice"] = $order->get_total();
                    $item["currency"] = get_woocommerce_currency();
                    $item["productsCount"] = count($order->items);
                    $item["itemsCount"] = $order->get_item_count();
                    $item["lastAction"] = "C";
                    $item["utcCreated"] = $order->get_date_created()->date('Y-m-d H:i:s');
                    $item["utcLastUpdated"] = $order->get_date_modified()->date('Y-m-d H:i:s');

                }

                $result[] = $item;


            }

        }

        return $result;


    }


    /**
     * Devuelve informacion sobre el modulo
     * @return array
     */
    public function getAdapterInfoOcthMethod()
    {

        $result = array();

        $result["platform"] = "Woocommerce";
        $result["adapterName"] = "Oct8ne official adapter for Woocommerce";
        $result["adapterVersion"] = $this->version;
        $result["developedBy"] = "Oct8ne Inc";
        $result["supportUrl"] = "";
        $result["apiVersion"] = "2.3";
        $result["enabled"] = (is_plugin_active("oct8ne/oct8ne.php")) ? 1 : 0;

        return $result;


    }


    /**
     * Quita el protocolo
     * @param $string
     * @return mixed
     */
    private function cleanProtocol($string)
    {

        $aux = str_replace("https:", "", $string);
        $aux = str_replace("http:", "", $aux);
        return $aux;


    }

    static function alter_posts_where_clause($where)
    {
        global $wpdb;

        $search = filter_var(trim(filter_input(INPUT_GET, '$search1')), FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
        if (is_null($search))
            return $where;

        $search = $wpdb->esc_like($search);

        // Append to the WHERE clause:
        $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE '%s'", "%{$search}%");
        
        

        return $where;
    }

    public function linkUpOcthMethod()
    {

        $url = 'https://backoffice.oct8ne.com/platformConnection/linkup';
        
        $data = array('email' => sanitize_text_field($_POST['oct8ne_value']),
            'pass' => sanitize_text_field($_POST['oct8ne_password']),
            'platform' => 'wordpress',
            'urlDomain' => site_url(),
            'statusPlatform' => true);

        $options = array(
            'http' => array(
                'header' => "Content-Type: application/json;charset=UTF-8\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            )
        );
        $context = stream_context_create($options);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=UTF-8\r\n'));
        if ($context != null) {
            $opts = stream_context_get_options($context);
            if (isset($opts['http']['method']) && strtolower($opts['http']['method']) == 'post') {
                curl_setopt($curl, CURLOPT_POST, true);
                if (isset($opts['http']['content'])) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $opts['http']['content']);
                }
            }
        }
        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, true);

        $apiToken = $response['ApiToken'];

        $licenseId = $response['LicenseId'];

        $server = $response['Server'];

        $UrlStatic = $response['UrlStatic'];


        if (isset($apiToken) && isset($licenseId) && !empty($apiToken) && !empty($licenseId)) {

            update_option('oct8ne_token', $apiToken);
            update_option('oct8ne_license', $licenseId);
            update_option('oct8ne_email', sanitize_text_field($_POST['oct8ne_value']));

            if (isset($server) && !empty($server)) {
                
                update_option('oct8ne_server', $server);

            } else {
                update_option('oct8ne_server', 'backoffice.oct8ne.com/');

            }

            if (isset($UrlStatic) && !empty($UrlStatic)) {

                update_option('oct8ne_static', $UrlStatic);

            } else {
                update_option('oct8ne_static', 'static.oct8ne.com/');

            }

            wp_redirect(admin_url() . "admin.php?page=oct8ne-settings&lresult=3");
            exit();

        } else {

            wp_redirect(admin_url() . "admin.php?page=oct8ne-settings&lresult=2");
            exit();

        }
    }


    public function unlinkOcthMethod()
    {

        Oct8neLoginHelper::logout();

        wp_redirect(admin_url() . "admin.php?page=oct8ne-settings&lresult=1");

        exit();
    }

    public function changeConfigurationOcthMethod()
    {

        $search = sanitize_text_field(filter_input(INPUT_POST, 'oct8ne_search_engine'));
        $wishlist = sanitize_text_field(filter_input(INPUT_POST, 'oct8ne_wishlist_engine'));
        $position = sanitize_text_field(filter_input(INPUT_POST, 'oct8ne_js_position'));
        $translation = sanitize_text_field(filter_input(INPUT_POST, 'oct8ne_translation_engine'));
        $scriptExtra = sanitize_text_field(filter_input(INPUT_POST, 'oct8ne_script_extra'));

        $scriptLoadEvents = sanitize_text_field(filter_input(INPUT_POST, 'oct8ne_script_events'));
        $scriptLoadTimer = sanitize_text_field(filter_input(INPUT_POST, 'oct8ne_script_timer'));

        if (isset($search) && !empty($search)) update_option("oct8ne_search_engine", $search);
        if (isset($wishlist) && !empty($wishlist)) update_option("oct8ne_wishlist_engine", $wishlist);
        if (isset($position) && !empty($position)) update_option("oct8ne_js_position", $position);
        if (isset($translation) && !empty($translation)) update_option("oct8ne_translation_engine", $translation);
        if (isset($scriptExtra)) update_option("oct8ne_script_extra", $scriptExtra);
        if (isset($scriptLoadEvents)) update_option("oct8ne_script_events", $scriptLoadEvents);
        if (isset($scriptLoadTimer)) update_option("oct8ne_script_timer", $scriptLoadTimer);

        wp_redirect(admin_url() . "admin.php?page=oct8ne-settings&lresult=4");

        exit();
    }

}





function file_find_require($file,$folder=null) {
    if ($folder === null) {$folder = dirname(__FILE__);}
    $path = $folder.DIRECTORY_SEPARATOR.$file;
    if (file_exists($path)) {require($path); return $folder;}
    else {
        $upfolder = file_find_require($file,dirname($folder));
        if ($upfolder != '') {return $upfolder;}
    }
}

function my_plugin_get_admin_path() {
	$admin_path = str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, get_admin_url() );
	
	$admin_path = apply_filters( 'my_plugin_get_admin_path', $admin_path );
	return $admin_path;
}