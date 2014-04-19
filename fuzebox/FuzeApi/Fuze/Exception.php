<?php

class Fuze_Exception extends Exception {}

class Fuze_Crypt_Exception extends Fuze_Exception {}

class Fuze_Client_Exception extends Fuze_Exception {}

class Fuze_Client_TransportException extends Fuze_Client_Exception {}

/**
 * Thrown when the server violates the API contract
 */
class Fuze_Client_ServerException extends Fuze_Client_Exception
{
    public $response;
    public $http_code;

    public function __construct($message, $response = null, $http_code = -1)
    {
        parent::__construct($message);
        $this->response = $response;
        $this->http_code = $http_code;
    }

    public function __toString()
    {
        return "{$this->message}; HTTP Code: {$this->http_code}";
    }
}

/**
 * Thrown when the server returns a fault response
 */
class Fuze_Client_FaultException extends Fuze_Client_Exception
{
    public $json;
    public function __construct(stdClass $json)
    {
        parent::__construct("Server fault: {$json->code} - {$json->message}");
        $this->json = $json;
    }
}