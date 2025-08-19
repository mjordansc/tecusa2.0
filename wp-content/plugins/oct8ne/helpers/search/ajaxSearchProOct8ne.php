<?php
/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */

require_once ("basesearch.php");

class AjaxSearchProOct8ne extends Oct8neBaseSearch
{

    public function getEngineName()
    {
        return "AjaxSearchPro";
    }

    public function search(){

        $result = array();
        $args = array();

        $filterInfo = array();
        $filterInfo["applied"] = array();
        $filterInfo["available"] = array();

        $search = $_GET["search"];

        $locale = $_GET["locale"];

        if (!isset($search) || empty($search)) return $result;

        //CRITERIO DE ORDENACION
        $orderby = $_GET["orderby"];
        switch ($orderby) {

            case "relevance":
                $args["orderby"] = "relevance";
                break;

            case "name":
                $args["orderby"] = "title";
                break;

            case "price":
                $args["orderby"] = "meta_value_num";
                $args["meta_key"] = "_price";
                break;

            default:
                $args["orderby"] = "relevance";
                break;
        }


        //DIRECCION DE LA BUSQUEDA ASCENDENTE O DESCENTE
        $dir = $search = $_GET["dir"];
        if (!isset($dir) || empty($dir)) $dir = "ASC";

        //NUMERO DE PAGINA
        $page = $search = $_GET["page"];
        if (!isset($page) || empty($page)) $page = 1;

        //NUMERO DE ELEMENTOS POR PAGINA
        $pagesize = $search = $_GET["pageSize"];
        if (!isset($pagesize) || empty($pagesize)) $pagesize = 10;
        $args["posts_per_page"] = $pagesize;
        $args["s"] = $_GET["search"];
        $args["page"] = $page;
        $args["post_type"] = "product";
        $args["post_status"] = "publish";
        $args["order"] = $dir;
        $args['suppress_filters'] = false;               

        try {
            if (in_array("wpml-all-import/wpml-all-import.php", apply_filters( 'active_plugins', get_option('active_plugins')))) {
                $lang = explode("-", $locale)[0];
                $args['_wpml_lang'] = $lang;
            }
        }
        catch(Exception $e) {
            
        }         
        
        $args['meta_query'] = array(
            //'relation' => 'OR',
            array(
                'key' => '_stock_status',
                'value' => 'instock',
                'compare' => '=',
            )
        );

          //Búsqueda por sku (la palabra debe empezar por '_')
        if (strpos($_GET["search"], '#') === 0) { 
            
             $sku = preg_replace('/#/', '', $_GET["search"], 1);
             $id = $this->get_product_by_sku($sku);
             if ( $id ) {
                $connector = new Oct8neConnector();
                $result["results"][] =  $connector->getProductData($id, $locale);
                $result["total"] = 1;
                $result["filters"] = $filterInfo;
            }

        }else{ //Sino es sku buscamos en el campo título.
            add_filter('posts_where', array('Oct8neConnector', 'alter_posts_where_clause'));        
            
            //obtiene los posts
            $asp_query = new ASP_Query($args);

            $myposts = $asp_query->posts;
            $count = $asp_query->found_posts;
            $result["total"] = $count;

            $connector = new Oct8neConnector();

            foreach ($myposts as $post) {
                $result["results"][] = $connector->getProductData($post->ID, $locale);          
            }

            $result["filters"] = $filterInfo;
        }
        return $result;
    }
    

    public function get_product_by_sku ($sku) {
        global $wpdb;
        $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ));
    
        return $product_id;  
    }
}