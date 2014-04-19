<?php

require_once 'Exception.php';

class Fuze_Crypt
{
    protected $_key;
    protected $_td;

    const AES_BLOCK_SIZE = 16;

    /**
     * @param string    $key      base64-encoded encryption key
     * @param integer   $key_len  length of raw key in bits
     */
    public function __construct($key, $key_len = 192)
    {

        $this->_td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');

        $key = self::urlsafe_b64decode($key);
        if (strlen($key) != $key_len/8) {
            $len = strlen($key);
            $expected = $key_len / 8;
            throw new Fuze_Crypt_Exception(
                "Incorrect key length: got {$len} bytes, expected {$expected}");
        }

        if (strlen($key) > mcrypt_enc_get_key_size($this->_td)) {
            $max = mcrypt_enc_get_key_size($this->_td);
            throw new Fuze_Crypt_Exception(
                "Given key is longer than {$max} bytes");
        }

        $iv_size = mcrypt_enc_get_iv_size($this->_td);
        $block_size = mcrypt_enc_get_block_size($this->_td);

        if ($iv_size != self::AES_BLOCK_SIZE || $block_size != self::AES_BLOCK_SIZE) {
            throw new Fuze_Crypt_Exception('Incorrect IV or block size!');
        }

        $this->_key = $key;
    }

    /**
     * Encode data using urlsafe Base64 alphabet
     *
     * @see http://www.php.net/manual/en/function.base64-encode.php#63543
     *
     * @param string $string
     * @return string The base64-encoded data
     */
    public static function urlsafe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+','/'),array('-','_'),$data);
        return $data;
    }

    /**
     * Decode Base64-encoded data using the urlsafe alphabet
     *
     * @see http://www.php.net/manual/en/function.base64-encode.php#63543
     * @param string $string
     *
     * @return string The original data
     */
    public static function urlsafe_b64decode($string)
    {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    /**
     * Encrypt data for the Fuze Partner API
     *
     * @param string $data
     * @return string $data encrypted, ase64-encoded $data
     */
    public function encrypt($data)
    {
        $pad = self::AES_BLOCK_SIZE - strlen($data) % self::AES_BLOCK_SIZE;
        $data .= str_repeat(chr($pad), $pad);
        if (stristr(PHP_OS, 'win') !== false) {
            $random_source = MCRYPT_RAND;
        } else {
            $random_source = MCRYPT_DEV_URANDOM;
        }
        $iv = mcrypt_create_iv(self::AES_BLOCK_SIZE, $random_source);
        mcrypt_generic_init($this->_td, $this->_key, $iv);
        $data = $iv . mcrypt_generic($this->_td, $data);
        mcrypt_generic_deinit($this->_td);

        return self::urlsafe_b64encode($data);
    }

    /**
     * Decode data previously encrypt()-ed
     *
     * @param string $data
     * @return string the original data
     */
    public function decrypt($data)
    {
        $data = self::urlsafe_b64decode($data);
        $iv = substr($data, 0, self::AES_BLOCK_SIZE);
        $data = substr($data, self::AES_BLOCK_SIZE);
        mcrypt_generic_init($this->_td, $this->_key, $iv);
        $data = mdecrypt_generic($this->_td, $data);
        mcrypt_generic_deinit($this->_td);
        $pad = ord(substr($data, -1, 1));
        $data = substr($data, 0, strlen($data) - $pad);

        return $data;
    }

    public function __destruct()
    {
        mcrypt_module_close($this->_td);
    }
}
