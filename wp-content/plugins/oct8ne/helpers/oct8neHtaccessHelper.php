<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */

class Oct8neHtaccessHelper
{

    /**
     * Regla que permite a Oct8ne Conectar con nuestro controlador
     */
    public static function setHtaccessRules()
    {
        return true;
    }

    /**
     * Eliminar regla Oct8ne
     */
    public static function removeHtaccessRules()
    {
        
    }


    /**
     * @return string
     * Regla Htaccess para Oct8ne
     */
    private static function getHtaccessRule() {

        return '#Oct8ne 
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteRule ^oct8ne/frame/([a-zA-Z]+)$ ' . plugins_url() . '/oct8ne/actions/oct8neconnector?octmethod=$1&%{QUERY_STRING} [QSA,L]
</IfModule>
#End_Oct8ne
        
';
    }
}