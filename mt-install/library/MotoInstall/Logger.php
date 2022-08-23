<?php
namespace MotoInstall;

use MotoInstall;

class Logger
{
    const DEFAULT_LEVEL = 300;

    const EMERGENCY = 600;
    const ALERT = 550;
    const CRITICAL = 500;
    const ERROR = 400;
    const WARNING = 300;
    const NOTICE = 250;
    const INFO = 200;
    const DEBUG = 100;

    protected static $_levels = array(
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::NOTICE => 'NOTICE',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL',
        self::ALERT => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    );
    protected static $_instance;

    protected static $_initializing = false;

    protected static $_minLevel = 300;

    protected $_logFile;
    public static function bootstrap($minLevel = 'WARNING')
    {
        if (static::$_initializing) {
            return;
        }
        static::$_initializing = true;
        if (is_string($minLevel)) {
            $minLevel = static::getLevelCode($minLevel);
        }
        if (is_int($minLevel)) {
            static::$_minLevel = $minLevel;
        }
        static::_registerErrorHandler(static::getInstance());
    }
    public static function getInstance()
    {
        if (!static::$_instance) {
            static::$_instance = new static('@installationTempDir/application.log');
        }

        return static::$_instance;
    }
    protected function __construct($path)
    {
        $absolutePath = System::getAbsolutePath($path);
        if (Util::filePutContents($absolutePath, '', FILE_APPEND) === false) {
            throw new Exception('Cant write a log file', 400, null, array(
                'file' => System::getRelativePath($path),
            ));
        }

        $this->_logFile = $absolutePath;
    }
    public function getLogFilePath()
    {
        return $this->_logFile;
    }
    protected function _errorCodeToName($code)
    {
        switch ($code) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return 'UNKNOWN';
    }
    protected static function _registerErrorHandler($logger)
    {
        error_reporting(E_ALL);
        @ini_set('display_errors', 'on');
        @ini_set('log_errors', 'on');
        @ini_set('error_log', $logger->getLogFilePath());
        register_shutdown_function(array($logger, 'handleShutdown'));
        set_error_handler(array($logger, 'handleError'));

        @ini_set('display_errors', 'off');
    }
    public function handleShutdown()
    {
        $error = error_get_last();
        if (is_array($error) && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
            $this->alert('Fatal Error [' . $this->_errorCodeToName($error['type']) . '] : ' . $error['message'], $error);
        }
    }
    public function handleError($level, $message = '', $file = '', $line = 0)
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        switch ($level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $level = static::ALERT;
                break;
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $level = static::WARNING;
                break;
            case E_PARSE:
            case E_COMPILE_ERROR:
                $level = static::EMERGENCY;
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
                $level = static::NOTICE;
                break;
        }

        $this->addLog($level, $message, array(
            'file' => $file,
            'line' => $line,
        ));

        return true;
    }
    public function addLog($level, $message, $context = null)
    {
        if ($level < static::$_minLevel) {
            return false;
        }

        $message = (string) $message;
        $log = '[' . date('Y-m-d H:i:s') . '] ' . static::getLevelName($level) . ' : ' . $message;
        if (is_array($context) || is_object($context)) {
            $context = MotoInstall\Util::toArray($context, MotoInstall\System::config('application.fullErrors', false));
            if (!empty($context)) {
                $log .= ' ' . function_exists('json_encode') ? json_encode($context) : serialize($context);
            }
        }
        $log .= "\n";

        return (boolean) Util::filePutContents($this->_logFile, $log, FILE_APPEND);
    }
    public static function getLevelName($level)
    {
        if (is_string($level)) {
            return strtoupper($level);
        }

        return Util::getValue(static::$_levels, $level, 'UNKNOWN');
    }
    public static function getLevelCode($level)
    {
        if (is_string($level) && defined(__CLASS__ . '::' . strtoupper($level))) {
            return constant(__CLASS__ . '::' . strtoupper($level));
        }

        return static::DEFAULT_LEVEL;
    }
    public function debug($message, $context = array())
    {
        return $this->addLog(static::DEBUG, $message, $context);
    }
    public function info($message, $context = array())
    {
        return $this->addLog(static::INFO, $message, $context);
    }
    public function notice($message, $context = array())
    {
        return $this->addLog(static::NOTICE, $message, $context);
    }
    public function warning($message, $context = array())
    {
        return $this->addLog(static::WARNING, $message, $context);
    }
    public function error($message, $context = array())
    {
        return $this->addLog(static::ERROR, $message, $context);
    }
    public function critical($message, $context = array())
    {
        return $this->addLog(static::CRITICAL, $message, $context);
    }
    public function alert($message, $context = array())
    {
        return $this->addLog(static::ALERT, $message, $context);
    }
    public function emergency($message, $context = array())
    {
        return $this->addLog(static::EMERGENCY, $message, $context);
    }
}
