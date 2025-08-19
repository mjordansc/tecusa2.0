<?php
/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */
class Oct8neResponseHelper
{
    /**
     * Crea la respuesta en el formato adecuado
     * @param $data
     */
    public static function buildResponse($data){


        $data = json_encode($data);

        //si hay callback
        //$callback = $_GET['callback'];
        header("HTTP/1.1 200 OK");
        if (isset($_GET['callback']) && $_GET['callback']) {

            header('Content-type: application/javascript; charset=utf-8');
            $data = $_GET['callback'] . "(" . $data . ");";

        } else {

            header('Content-type: application/json; charset=utf-8');
        }

        echo $data;
        exit;

    }

}