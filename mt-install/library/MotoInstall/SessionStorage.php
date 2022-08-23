<?php
namespace MotoInstall;

use MotoInstall;

class SessionStorage
{
    protected static $_instance;

    protected $_prefix = 'MotoInstall_';
    public static function getInstance()
    {
        if (!static::$_instance) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    protected function __construct()
    {
        if (\PHP_SESSION_ACTIVE !== session_status()) {
            if (!session_start()) {
                throw new \RuntimeException('Failed to start the session');
            }
        }
    }

    protected function _sanitizeKey($key)
    {
        if (is_int($key)) {
            $key = (string) $key;
        }
        if (!is_string($key)) {
            throw new \InvalidArgumentException('Key must be a string');
        }
        $key = trim($key);
        if ($key === '') {
            throw new \InvalidArgumentException('Key must be not empty');
        }

        return $this->_prefix . $key;
    }

    public function set($key, $value)
    {
        $key = $this->_sanitizeKey($key);
        $_SESSION[$key] = $value;

        return true;
    }

    public function increment($key, $amount = 1)
    {
        $value = $this->get($key, 0) + $amount;

        $this->set($key, $value);

        return $value;
    }

    public function decrement($key, $amount = 1)
    {
        return $this->increment($key, $amount * -1);
    }

    public function put($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Data must be array');
        }
        foreach($data as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    public function get($key, $default = null)
    {
        return Util::getValue($_SESSION, $this->_sanitizeKey($key), $default);
    }

    public function remove($key)
    {
        $key = $this->_sanitizeKey($key);
        unset($_SESSION[$key]);

        return true;
    }

    public function push($key, $value)
    {
        $data = $this->get($key, array());
        $data[] = $value;
        $this->set($key, $data);

        return $data;
    }
}
