<?php
/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */
require_once ("woocommercesearch.php");
require_once ("ajaxSearchProOct8ne.php");

class Oct8neSearchFactory
{


    /**
     * Factory
     * @param $class
     * @return WoocommerceSearchOct8ne
     */
    public static function create($class){

        if($class=="woocommerce"){
            return new WoocommerceSearchOct8ne();            
        }else if($class=="ajaxSearchPro"){
            return new AjaxSearchProOct8ne();
        }else{
            return new WoocommerceSearchOct8ne();
        }
    }

    /**
     * Devuelve todos los engines de busqueda disponibles, por defecto woocommerce
     * @return array
     */
    public static function getEngines(){

        $engines = array();
        $engines["woocommerce"] = false;
        $pluginList = get_option( 'active_plugins' );
        
        if ( in_array( "ajax-search-pro/ajax-search-pro.php", apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            $engines["ajaxSearchPro"] = false;
        }
        
        $default = get_option('oct8ne_search_engine','woocommerce');
        
        $engines[$default] = true;
        return $engines;

    }
}