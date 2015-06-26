<?php
use Zend\Crypt\BlockCipher as BlockCipher;
use Zend\Crypt\Key\Derivation\Scrypt as KeyGen;
use Zend\Math\Rand;

class CheckfrontCryptoZend extends CheckfrontCrypto implements CheckfrontCryptoInterface {

    private static $server_key = '';

    public static function server_key() {
        return hex2bin(static::config()->get('server_key'));
    }

    /**
     * Return a value which is safe to use in url's and copy-paste operations and is
     * ANSI. May not actually have anything to do with PHP url_encode etc such as using binToHex
     *
     * @param $rawValue
     *
     * @return mixed
     */
    public static function friendly($rawValue) {
        return bin2hex($rawValue);
    }

    /**
     * Return the raw value for a cooked value which may be unsafe to use in urls, human-unfriendly etc
     * May not have anything to do with url_decode but us something like hexToBin
     *
     * @param $cookedValue
     *
     * @return mixed
     */
    public static function unfriendly($cookedValue) {
        return hex2bin($cookedValue);
    }

    /**
     * Encrypt value optionally using key
     *
     * @param $plainTextValue
     * @param $key
     *
     * @throws Exception
     * @return mixed
     */
    public static function encrypt($plainTextValue, $key = null) {
        try {
            $blockCipher = BlockCipher::factory('mcrypt', array('algo' => 'aes'));

            $blockCipher->setKey($key);

            return $blockCipher->encrypt($plainTextValue);

        } catch (Exception $e) {
            throw new Exception("Failed to encrypt");
        }
    }

    /**
     * Decrypt value optionally using key
     *
     * @param $encryptedValue
     * @param $key
     *
     * @throws Exception
     * @return mixed
     */
    public static function decrypt($encryptedValue, $key = null) {
        try {
            $blockCipher = BlockCipher::factory('mcrypt', array('algo' => 'aes'));

            $blockCipher->setKey($key);

            return $blockCipher->decrypt($encryptedValue);
            
        } catch (Exception $e) {
            throw new Exception("Failed to decrypt");
        }

    }

    /**
     * Generate a new unfriendly access key.
     *
     * @param null $salt
     *
     * @return string
     */
    public static function generate_key($salt = null) {
        $salt = mb_strlen($salt) === 32 ? $salt : Rand::getBytes(32, true);
        $pass = Rand::getBytes(32, true);
        return KeyGen::calc($pass, $salt, 2048, 2, 1, 32);
    }
}