<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */
class Oct8neDb
{

    /**
     * Crea las tablas de la base de datos
     */
    public static function createTables(){

        global $wpdb;

        $table_name = $wpdb->prefix . "oct8neHistory";

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                 `id_oct8nehistory` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `cart_id` int(10) unsigned NOT NULL,
                `session_id` text NOT NULL,
                PRIMARY KEY  (`id_oct8nehistory`),
                INDEX `cart_id` (`cart_id`)
                ) $charset_collate;";


        require_once( get_home_path() . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }


    /**
     * Borra las tablas de la base de datos
     */
    public static function dropTables(){

        global $wpdb;

        $table_name = $wpdb->prefix . "oct8neHistory";

        $sql = "DROP TABLE IF EXISTS $table_name ";


        require_once( get_home_path() . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);

    }

    /*
     * Inserta un nuevo Oct8ne history
     */
    public static function newHistory($id_order, $cookie){

        global $wpdb;
        $table_name = $wpdb->prefix . "oct8neHistory";
        $wpdb->insert($table_name, array('cart_id' => $id_order, 'session_id' => $cookie) );
    }


    /**
     * @param $from
     * @param $to
     * @return array|null|object
     */
    public static function getOct8neOrders($from, $to){

        global $wpdb;

        $result = $wpdb->get_results("SELECT p.ID, h.session_id FROM {$wpdb->prefix}posts p inner JOIN {$wpdb->prefix}oct8neHistory h on(p.ID = h.cart_id) WHERE p.post_type='shop_order' and p.post_date BETWEEN '{$from}' and '{$to}'");

        return $result;

    }

}