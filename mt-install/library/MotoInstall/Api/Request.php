<?php
namespace MotoInstall\Api;

use MotoInstall;

class Request
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    protected $_method = 'GET';
    protected $_query = null;
    protected $_post = null;
    protected $_cookies = null;
    protected $_server = null;
    protected $_headers = null;

    protected $_initialized = false;
    protected $_request = null;
    protected $_params = array();

    protected $_requestUrl = null;
    protected $_requestUrlMethod = 'get';
    protected $_requestUri = null;
    protected $_autoRedirect = true;

    public function __construct($query = array(), $post = array(), $cookies = array(), $server = array())
    {
        $this->_query = new MotoInstall\DataBag($query);
        $this->_post = new MotoInstall\DataBag($post);
        $this->_cookies = new MotoInstall\DataBag($cookies);
        $this->_server = new MotoInstall\DataBag($server);
        $method = $this->_server->get('REQUEST_METHOD', 'GET');
        $this->_method = strtoupper($method);

        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }

        $this->_headers = new MotoInstall\DataBag($headers);
    }
    public function getHeaders()
    {
        return $this->_headers;
    }
    public function getHeaderValue($key, $default = null)
    {
        return $this->_headers->get($key, $default);
    }
    public function getQuery()
    {
        return $this->_query;
    }
    public function getQueryValue($key, $default = null)
    {
        return $this->_query->get($key, $default);
    }
    public function getPost()
    {
        return $this->_post;
    }
    public function getPostValue($key, $default = null)
    {
        return $this->_post->get($key, $default);
    }
    public function getCookies()
    {
        return $this->_cookies;
    }
    public function getCookieValue($key, $default = null)
    {
        return $this->_cookies->get($key, $default);
    }
    public function getServer()
    {
        return $this->_server;
    }
    public function getServerValue($key, $default = null)
    {
        return $this->_server->get($key, $default);
    }
    public function isGet()
    {
        return ($this->_method === static::METHOD_GET);
    }
    public function isPost()
    {
        return ($this->_method === static::METHOD_POST);
    }
    public function isAjax()
    {
        return (trim(strtolower($this->_server->get('HTTP_X_REQUESTED_WITH', ''))) === strtolower('XMLHttpRequest'));
    }
    public function isJson()
    {
        if ($this->_server->has('CONTENT_TYPE') && preg_match('/application\/json/', strtolower($this->_server->get('CONTENT_TYPE')))) {
            return true;
        }

        return $this->isAjax();
    }
    public function get($key, $default = null)
    {
        $result = $this->_post->get($key);
        if ($result !== null) {
            return $result;
        }

        return $this->_query->get($key, $default);
    }

    public function toArray()
    {
        return array(
            'method' => $this->_method,
            'query' => $this->_query->toArray(),
            'post' => $this->_post->toArray(),
            'cookies' => $this->_cookies->toArray(),
            'headers' => $this->_headers->toArray(),
        );
    }
}