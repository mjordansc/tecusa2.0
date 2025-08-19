<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */

require_once("basewishlist.php");
require_once(ABSPATH . 'wp-content/plugins/oct8ne/helpers/loghelper.php');


class YITHWishlistSearch implements BaseWishList
{

    /**
     * Obtiene los elementos de la lista de deseos
     */
    public function getWishList()
    {

        $ids = array();

        //si este plugin no esta activo devuelvo array vacio
        if (!$this->isActive()) {

            Oct8neLogHelper::Log("ERROR", "Plugin yith-woocommerce-wishlist necesario para este motor de búsqueda no activo");
            return $ids;

        }

        $wishlist = YITH_WCWL::get_instance();


        $wishlists = $wishlist->get_wishlists();

        $default_wishlist = array_filter($wishlists, function ($x) {

            return $x["is_default"] == 1;
        });

        //si no hay lista de deseos por defecto devuelvo array vacio
        if (empty($default_wishlist)) {
            return array();
        }

        $default_wishlist = @$default_wishlist[0]["ID"];

        //comprobaciones ID lista de deseos obtenido
        if (!isset($default_wishlist) || empty($default_wishlist) || !is_numeric($default_wishlist)) return array();

        $wishlists_items = $wishlist->get_products(["wishlist_id" => $default_wishlist]); //hay que pasarle la id de la wishlsit por defecto

        foreach ($wishlists_items as $wishlists_item) {

            $ids[] = $wishlists_item["ID"];
        }

        return $ids;

    }

    /**
     * Añade un elemento a la lista de deseos
     * @return bool
     */
    public function addToWishList()
    {
        if (!$this->isActive()) return false;

        $result = true;

        $ids = $_GET["productIds"];


        if (isset($ids) && !empty($ids)) {

            $ids = explode(",", $ids);

            foreach ($ids as $id) {


                try {

                    $product = new WC_Product($id);

                    if ($product->exists()) {

                        $wishlist = YITH_WCWL::get_instance();
                        $details = array();
                        $details['add_to_wishlist'] = $id;
                        $details['user_id'] = get_current_user_id();
                        $wishlist->details = $details;
                        $resultx = $wishlist->add();

                    } else {

                        $resultx = false;
                    }

                } catch (Exception $e) {

                    $resultx = false;

                }


                $resultx == 'true' ? $result = true : $result = false;

                $result = $result && $resultx;

            }

            return $result;
        }

        return false;
    }

    /**
     * Comprueba si esta activo
     */
    private function isActive()
    {

        if (is_plugin_active("yith-woocommerce-wishlist/init.php")) {

            return true;
        }

        return false;

    }
}