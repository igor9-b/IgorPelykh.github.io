<?php
namespace MotoInstall\Api;

use MotoInstall;

class Response
{
    protected $_code = 200;
    protected $_result = '';
    protected $_message = null;
    protected $_errors = null;
    protected $_cookies = array();
    protected $_headers = array(
        'Content-Type' => 'application/json',
    );
    public function setCode($code)
    {
        $this->_code = (int) $code;

        return $this;
    }
    public function getCode()
    {
        return $this->_code;
    }
    public function isNotFound()
    {
        return (404 === $this->getCode());
    }
    public function isSuccess()
    {
        $code = $this->getCode();

        return (200 <= $code && $code < 300);
    }
    public function setHeader($name, $value)
    {
        $this->_headers[$name] = $value;

        return $this;
    }
    public function getHeader($name, $default = null)
    {
        return (array_key_exists($name, $this->_headers) ? $this->_headers[$name] : $default);
    }
    public function getHeaders()
    {
        return $this->_headers;
    }
    public function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }
        foreach ($this->_headers as $name => $value) {
            header($name . ':' . $value);
        }

        if (!empty($this->_cookies)) {
            foreach ($this->_cookies as $name => $cookie) {
                @setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
            }
        }
    }
    public function setResult($content)
    {
        $this->_result = $content;

        return $this;
    }
    public function getResult()
    {
        return $this->_result;
    }
    public function setCookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->_cookies[$name] = array(
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'http' => $httponly,
        );

        return $this;
    }
    public function getCookieValue($key, $default = null)
    {
        return MotoInstall\Util::getValue($this->_cookies, $key, $default);
    }
    public function getCookies()
    {
        return $this->_cookies;
    }
    public function notFound()
    {
        $this->setCode(404);

        return $this;
    }
    public function setMessage($message)
    {
        $this->_message = $message;

        return $this;
    }
    public function getMessage()
    {
        return $this->_message;
    }
    public function setErrors($errors)
    {
        $this->_errors = $errors;
    }
    public function getErrors()
    {
        return $this->_errors;
    }
    public function toString()
    {
        $this->sendHeaders();

        $response = $this->toArray();

        return json_encode($response, JSON_PRETTY_PRINT);
    }
    public function toArray()
    {
        $result = $this->getResult();

        $response = array(
            'code' => $this->getCode(),
            'status' => $this->isSuccess(),
        );
        if (!empty($this->_message)) {
            $response['message'] = $this->_message;
        }
        if (!empty($this->_errors)) {
            if (is_array($this->_errors) || is_object($this->_errors)) {
                $response['errors'] = MotoInstall\Util::toArray($this->_errors, MotoInstall\System::config('application.fullResponse', false));
            } else {
                $response['errors'] = $this->_errors;
            }
        }

        if ($this->isSuccess()) {
            if (is_object($result) || is_array($result)) {
                $result = MotoInstall\Util::toArray($result, MotoInstall\System::config('application.fullResponse', false));
            }

            $response['result'] = $result;
        }

        return $response;
    }

    public function __toString()
    {
        return $this->toString();
    }
}
