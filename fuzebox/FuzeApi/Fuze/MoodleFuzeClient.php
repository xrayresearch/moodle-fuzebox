<?php

/* * ************************************************************ 
 * 
 * Date: Mar 6, 2013
 * version: 1.0
 * programmer: Shani Mahadeva <satyashani@gmail.com>
 * Description:   
 * PHP class MoodleFuzeClient
 * 
 * 
 * *************************************************************** */

/**
 * Description of MoodleFuzeClient
 *
 * @author Shani Mahadeva
 */
require_once("Client.php");
class MoodleFuzeClient extends Fuze_Client{
    /**
     * Changes user password
     *
     * @param array $params Required keys are
     *      email 
     *      password New password
     *
     * @return stdClass
     */
    public function __construct($token = null) {
        $config = get_config('fuzebox');
        parent::__construct($config->url, $config->pk, $config->ek, $token);
    }
    public function resetPassword($params){
        $params['password'] = $this->_encodeParam($params['password']);
        $params['email'] = $this->_encodeParam($params['email']);
        $result = $this->call('user/resetpassword', $params);
        return $result;
    }
}

?>
