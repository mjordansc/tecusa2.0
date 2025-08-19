<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */
class Oct8neJsInfo
{
    public static function getExtraScript()
    {

        return get_option('oct8ne_script_extra', '');

    }
    
    static function getScriptEvents() {
        $events = get_option('oct8ne_script_events', null);
        if(isset($events) && $events != null && $events != ''){
            return $events;
        }
        
        return 'DISABLED';
    }
    
    static function getScriptTimer() {
        $timer = get_option('oct8ne_script_timer', 'DISABLED');
        if(isset($timer) && $timer != null && $timer != ''){
            return $timer;            
        }
        
        return 'DISABLED';
    }

    /**
     * Obtiene la licencia del linkup
     * @return mixed|void
     */
    public static function getLicense()
    {

        return get_option('oct8ne_license', '');

    }

    /**
     * Obtiene el server de oct8ne
     * @return mixed|void
     */
    public static function getServer(){

        return get_option('oct8ne_server', 'backoffice.oct8ne.com/');

    }

    /**
     * Obtiene el servidor static
     * @return mixed|void
     */
    public static function getUrlStatic(){

        return get_option('oct8ne_static', 'static.oct8ne.com/');

    }

    /**
     * Obtiene la url base de la tienda
     * @return string
     */
    public static function getBaseUrl()
    {
        //return get_home_url();

        return get_site_url();
    }

    /**
     * Obtiene la url de checkout
     * @return mixed
     */
    public static function getCheckoutUrl()
    {

        global $woocommerce;

        if (version_compare($woocommerce->version, "2.5.0", "<")) {

            return wc_get_checkout_url();

        } else {

            return $woocommerce->cart->get_checkout_url();
        }

    }

    /**
     * Obtiene la url login
     * @return false|string
     */
    public static function getLoginUrl()
    {

        return get_permalink(get_option('woocommerce_myaccount_page_id'));
    }

    /**
     * Url success
     * @return string
     */
    public static function getSuccessUrl()
    {
        return '';

    }

    /**
     * Obtiene el base locale
     * @return mixed
     */
    public static function getLocale()
    {

        global $woocommerce;

        $translation_engine_name = get_option('oct8ne_translation_engine', 'any');

        if ($translation_engine_name == "any") {

            $locale = $woocommerce->countries->get_base_country();

        } else {

            $engine = Oct8neTranslationFactory::create($translation_engine_name);
            $locale = $engine->getLocale();
        }
		
        switch (strtolower($locale)) {
				
			case "es" :
                return "es-ES";	
            case "ar" :
                return "es-AR";	
            case "fr" :
                return "fr-FR";
            case "en" :
                return "en-US";
			case "it" :
                return "it-IT";
			case "de" :
                return "de-DE";
			case "pt" :
                return "pt-PT";
			case "ro" :
                return "ro-RO";	
			case "bg" :
                return "bg-BG";	
			case "cz" :
                return "cs-CS";	
			case "dk" :
                return "da-DA";	
			case "gr" :
                return "el-EL";	
			case "jp" :
                return "jp-JP";
			case "nl" :
                return "nl-NL";
			case "pl" :
                return "pl-PL";	
			case "sk" :
                return "sk-SK";	
			case "ru" :
                return "ru-RU";	
			case "cl" :
                return "es-CL";	
			case "mx" :
                return "es-MX";	
			case "co" :
                return "es-CO";	
			case "py" :
                return "es-PY";	
			case "uy" :
                return "es-UY";	
			case "pe" :
                return "es-PE";	
			case "gt" :
                return "es-GT";	
            default :
                return "en-US";
        }

    }

    /**
     * Obtiene la moneda base
     * @return string
     */
    public static function getCurrency()
    {

        return get_woocommerce_currency();

    }

    /**
     * Comprueba si es un producto la pagina actual
     * @return bool
     */
    public static function isProductPage()
    {

        return is_product();

    }

    /**
     * Devuelve si es la pagina de un producto
     * @return mixed
     */
    public static function getProductId()
    {

        $product = WC_GET_PRODUCT();
        return $product->get_id();

    }

    /**
     * Devuelve el thumnail de un producto
     * @return mixed
     */
    public static function getProductThumbnail()
    {

        $product = WC_GET_PRODUCT();

        $attachment = "";
        if (has_post_thumbnail($product->get_id())) {
            $attachment_ids[0] = get_post_thumbnail_id($product->get_id());
            $attachment = wp_get_attachment_image_src($attachment_ids[0], 'full');

        }

        return $attachment[0];
    }

    /**
     * Devuelve todos los engines de busqueda disponibles, por defecto woocommerce
     * @return array
     */
    public static function getPositions()
    {

        $positions = array();
        $positions["Header"] = false;
        $positions["Footer"] = false;


        $default = get_option('oct8ne_js_position', 'Footer');

        $positions[$default] = true;
        return $positions;

    }


}