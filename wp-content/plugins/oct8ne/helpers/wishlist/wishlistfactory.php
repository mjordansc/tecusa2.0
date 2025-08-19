<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */

require_once ("YITHWishlistSearch.php");

class WishListFactory
{


    /**
     * Devuelve la instancia de wishlist configurada
     * @param $instance
     * @return null|YITHWishlistSearch
     */
    public static function create($instance){

        if($instance == "yith-woocommerce-wishlist") {

            return new YITHWishlistSearch();

        } else return null;
    }

    /**
     * Devuelve el listado de Wishlist disponibles y establece la de por defecto
     * @return array
     */
    public static function getEngines(){


        $engines = array();
        $engines["any"] = false;

        //Añadir en el array de engines todas la disponibles
        if(is_plugin_active("yith-woocommerce-wishlist/init.php")){
            $engines["yith-woocommerce-wishlist"] = false;
        }


        //establecer la de por defecto
        $default = get_option('oct8ne_wishlist_engine','any');
        $engines[$default] = true;

        return $engines;
    }

}