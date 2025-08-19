<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */
interface Oct8neBaseTranslation
{
    public function getProduct($id_product, $locale);

    public function getLocale();
}