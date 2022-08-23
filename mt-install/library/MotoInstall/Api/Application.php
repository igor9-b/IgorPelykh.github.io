<?php
namespace MotoInstall\Api;

use MotoInstall;

class Application
{
    protected $_request;
    protected $_response;

    protected $_actionHandlers = array();

    public function __construct()
    {
        $post = $_POST;
        $json = file_get_contents('php://input');
        if (is_string($json)) {
            $json = trim($json);
            if ($json !== '' && ($json[0] === '{' || $json[0] === '[')) {
                $json = json_decode($json, true);
                if (is_object($json)) {
                    $json = (array) $json;
                }
                if (is_array($json)) {
                    $post = $json;
                }
            }
        }

        $this->setRequest($this->createApiRequest($_GET, $post));
        $this->setResponse(new MotoInstall\Api\Response());
    }
    public function createApiRequest($query = array(), $post = array())
    {
        return new MotoInstall\Api\Request($query, $post, $_COOKIE, $_SERVER);
    }
    public function handle($request = null)
    {
        if ($request instanceof MotoInstall\Api\Request) {
            $this->setRequest($request);
        }

        $this->dispatch();

        return $this->getResponse();
    }

    protected function dispatch()
    {
        try {
            $action = $this->getRequest()->get('action');
            $this->processAction($action);
        } catch (\Exception $e) {
            $this->processException($e);
        }

        return $this->getResponse();
    }
    public function processAction($action, $request = null)
    {
        $handler = $this->getActionHandler($action);
        if (!$handler) {
            throw new MotoInstall\Exception('ACTION_NOT_FOUND', 404, null, array(
                'action' => $action,
            ));
        }

        if ($request === null) {
            $request = $this->getRequest();
        }
        if (is_array($request)) {
            $request = $this->createApiRequest($request);
        }

        if (!($request instanceof MotoInstall\Api\Request)) {
            throw new MotoInstall\Exception('BAD_API_REQUEST');
        }

        $handler->checkRequest($request);

        $this->getResponse()->setResult($handler->processRequest($request));

        return $this->getResponse();
    }
    protected function getActionHandler($action)
    {
        $action = (string) $action;
        $action = trim($action);
        $class = 'MotoInstall\\Actions\\' . MotoInstall\Util::toStudlyCase($action) . 'Action';

        if (array_key_exists($class, $this->_actionHandlers)) {
            return $this->_actionHandlers[$class];
        }
        if (!class_exists($class)) {
            if (MotoInstall\System::isDevelopmentStage()) {
                throw new MotoInstall\Exception('ACTION_NOT_FOUND', 404, null, array(
                    'action' => $action,
                    'class' => $class,
                ));
            }

            return null;
        }

        $this->_actionHandlers[$class] = new $class($this);

        return $this->_actionHandlers[$class];
    }

    protected function processException($e)
    {
        if ($e instanceof MotoInstall\Exception && $e->getMessage() === 'ACTION_NOT_FOUND') {
            return $this->processNotFound($e);
        }
        $response = $this->getResponse();
        $code = $e->getCode();
        if ($code < 400 || $code > 500) {
            $code = 400;
        }
        $response->setCode($code);
        $response->setMessage($e->getMessage());
        if ($e instanceof MotoInstall\Exception) {
            $response->setErrors($e->getErrors());
        }

        return $response;
    }

    protected function processNotFound($e = null)
    {
        $response = $this->getResponse();
        $response->notFound();
        if ($e instanceof \Exception) {
            $response->setMessage($e->getMessage());
        }
        if ($e instanceof MotoInstall\Exception) {
            $response->setErrors($e->getErrors());
        }

        return $response;
    }

    protected function setRequest($request)
    {
        if (!($request instanceof MotoInstall\Api\Request)) {
            throw new MotoInstall\Exception('Bad request object');
        }

        $this->_request = $request;

        return $this;
    }
    public function getRequest()
    {
        return $this->_request;
    }

    protected function setResponse($response)
    {
        if (!($response instanceof MotoInstall\Api\Response)) {
            throw new MotoInstall\Exception('Bad response object');
        }

        $this->_response = $response;

        return $this;
    }
    public function getResponse()
    {
        return $this->_response;
    }
}
