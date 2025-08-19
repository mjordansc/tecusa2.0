<?php

/**
 * Created by PhpStorm.
 * User: migue
 * Date: 21/09/2017
 * Time: 13:20
 */

require_once("basetranslation.php");
require_once(ABSPATH . '/wp-content/plugins/oct8ne/helpers/loghelper.php');

class WPMLTranslationOct8ne implements Oct8neBaseTranslation
{

    /**
     * Obtiene el producto en el idioma especificado
     * @param $id_product
     * @param $locale
     * @return false|int|NULL|WC_Product
     */
    public function getProduct($id_product, $locale)
    {

        if (function_exists('wpml_object_id_filter')) {

            $product_id_aux = wpml_object_id_filter($id_product, 'product', true, $locale); // Returns the ID of the current custom post

        } else {

            $product_id_aux = $id_product;
        }


        $product = wc_get_product($product_id_aux);

        return $product;
    }


    /**
     * Obtiene la lengua seleccionada
     * @return mixed
     */
    public function getLocale()
    {

        if (defined('ICL_LANGUAGE_CODE')) {
            return ICL_LANGUAGE_CODE;
        } else return "";
    }
}