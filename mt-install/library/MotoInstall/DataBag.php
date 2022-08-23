<?php
namespace MotoInstall;

use MotoInstall;

class DataBag
{
    protected $_data;
    public function __construct($data = array())
    {
        $this->_data = (array) $data;
    }
    public function get($key, $default = null)
    {
        return MotoInstall\Util::getValue($this->_data, $key, $default);
    }
    public function set($key, $value)
    {
        $this->_data[$key] = $value;

        return $this;
    }
    public function has($key)
    {
        return MotoInstall\Util::arrayHas($this->_data, $key);
    }
    public function remove($key)
    {
        unset($this->_data[$key]);

        return $this;
    }
    public function toArray($full = false)
    {
        return Util::toArray($this->_data, $full);
    }

    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
