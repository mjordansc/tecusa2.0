<?php

/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */

class Oct8neLogHelper
{

    /**
     * Escribe en el log
     * @param $type
     * @param $message
     * @return bool|int
     */

    public static function Log($type, $message){
        

        $date = date('Y-m');

        $filename = plugin_dir_path(__FILE__)."../log/".$date.".txt";

        $date = date('Y-m-d h:i:s');
        $result = file_put_contents($filename,"{$date} {$type} {$message}" .PHP_EOL, FILE_APPEND);

        return $result;
    }


    public static function LogException($ex)
    {
        self::Log('Exception', $ex->getFile().':'.$ex->getLine().' -> '.$ex->getMessage());
    }
}