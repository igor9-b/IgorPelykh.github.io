<?php
namespace MotoInstall;

use MotoInstall;

class Encryption
{
    const CURRENT_VERSION = 2;

    protected static $_cipher = 'AES-128-CBC';

    protected static $_key = null;

    public static function setDefaultKey($key)
    {
        if (!is_string($key) || trim($key) === '') {
            throw new \InvalidArgumentException('Invalid key');
        }

        static::$_key = trim($key);
    }

    public static function encrypt($value, $key = null)
    {
        if ($key === null) {
            $key = static::$_key;
        }
        if (!is_string($key)) {
            throw new \InvalidArgumentException('Invalid key');
        }

        $iv = Util::generateRandomBytes(openssl_cipher_iv_length(static::$_cipher));

        $value = json_encode($value);
        if (!is_string($value)) {
            throw new \RuntimeException('Error on encoding value');
        }

        $encryptedValue = \openssl_encrypt($value, static::$_cipher, $key, 1, $iv);

        if ($encryptedValue === false) {
            throw new \RuntimeException('Can not encrypt value');
        }

        $hash = static::_generateHash($iv, $encryptedValue, $key);

        $result = static::_packResponse(array(
            'iv' => $iv,
            'hash' => $hash,
            'value' => $encryptedValue,
        ));

        return $result;
    }
    public static function decrypt($value, $key = null)
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Value must be a string');
        }
        if ($key === null) {
            $key = static::$_key;
        }
        if (!is_string($key)) {
            throw new \InvalidArgumentException('Invalid key');
        }

        $request = static::_unpackRequest($value);

        return static::_decryptRequest($request, $key);
    }
    protected static function _packResponse($response)
    {
        $header = static::CURRENT_VERSION . '@';

        $pack['h'] = $response['hash'];
        $pack['i'] = $response['iv'];
        $pack['v'] = $response['value'];

        $pack = array_map('base64_encode', $pack);

        return $header . base64_encode(json_encode($pack));
    }
    protected static function _unpackRequest($request)
    {
        $result = array(
            'version' => false
        );

        if ($request[1] !== '@') {
            $result['value'] = $request;

            return $result;
        }
        $version = (int) $request[0];
        $body = \mb_substr($request, 2, \mb_strlen($request, '8bit') - 2, '8bit');
        $result['version'] = $version;
        $body = base64_decode($body);

        $pack = json_decode($body, true);
        $result['hash'] = base64_decode($pack['h']);
        $result['iv'] = base64_decode($pack['i']);
        $result['value'] = base64_decode($pack['v']);

        return $result;
    }
    protected static function _decryptRequest($request, $key)
    {
        $iv = $request['iv'];
        $encryptedValue = $request['value'];

        $decryptedValue = \openssl_decrypt($encryptedValue, static::$_cipher, $key, 1, $iv);

        if ($decryptedValue === false) {
            throw new \RuntimeException('Could not decrypt data');
        }

        $decryptedValue = json_decode($decryptedValue, true);

        if ($decryptedValue === false) {
            throw new \RuntimeException('Could not decode data');
        }

        return $decryptedValue;
    }

    protected static function _generateHash($iv, $encryptedValue, $password)
    {
        return hash_hmac('sha256', $iv . $encryptedValue, $password, true);
    }
}
