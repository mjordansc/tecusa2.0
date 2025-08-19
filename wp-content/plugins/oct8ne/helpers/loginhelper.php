<?php
/**
 * Oct8ne
 *
 * @author      Oct8ne
 * @version     1.0.0
 */

class Oct8neLoginHelper
{

    /**
     * Comprueba si estas logeado o no
     * @return bool
     */
    static function isLogged(){

        $apiToken = get_option('oct8ne_token',null);
        $licenseId = get_option('oct8ne_license',null);

        if(isset($apiToken) && isset($licenseId) && !empty($apiToken) && ! empty($licenseId)){

            return true;
        } else{

            return false;
        }
    }


    /**
     * Desconectar
     */
    static function logout(){

        delete_option('oct8ne_token');
        delete_option('oct8ne_license');
        delete_option('oct8ne_email');
        delete_option('oct8ne_server');
        delete_option('oct8ne_static');
    }

    /**
     * get email
     * @return mixed|void
     */
    static function getUserEmail() {

        return get_option('oct8ne_email',null);

    }

    static function getExtraScript() {

        return get_option('oct8ne_script_extra',null);

    }

    static function getScriptEvents() {
        $events = get_option('oct8ne_script_events', null);
        if(isset($events) && $events != null && $events != ''){
            return $events;
        }
        
        return 'DISABLED';
    }
    
    static function getScriptTimer() {
        $timer = get_option('oct8ne_script_timer', 'DISABLED');
        if(isset($timer) && $timer != null && $timer != ''){
            return $timer;            
        }
        
        return 'DISABLED';
    }

}