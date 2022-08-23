<?php
namespace MotoInstall;

class Exception extends \Exception
{
    protected $_errors = array();

    public function __construct($message = '', $code = 0, Exception $previous = null, $errors = null)
    {
        parent::__construct($message, $code, $previous);
        $this->setErrors($errors);
    }
    public function setErrors($errors)
    {
        $this->_errors = (array) $errors;
    }
    public function getErrors()
    {
        return $this->_errors;
    }

}
