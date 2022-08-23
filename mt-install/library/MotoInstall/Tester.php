<?php
namespace MotoInstall;

use MotoInstall;

class Tester
{
    protected static $_disabled = array(
        'extensions' => array(),
        'classes' => array(),
        'functions' => array(),
        'disk_resources' => array(),
        'resources' => array(),
        'include_path' => false,
        'autoload_class' => false,
    );

    protected static $_failing = array();

    protected static $_curlErrorMessages = array(
        3 => 'The URL was not properly formatted.',
        5 => 'Couldn\'t resolve proxy. The given proxy host could not be resolved.',
        6 => 'Couldn\'t resolve host. The given remote host was not resolved.',
        7 => 'Failed to connect() to host or proxy.',
        9 => 'We were denied access to the resource given in the URL',
        18 => 'A file transfer was shorter or larger than expected.',
        33 => 'The server does not support or accept range requests.',
        47 => 'Too many redirects. When following redirects, libcurl hit the maximum amount.',
    );

    protected static $_cache = array();

    protected static $_initializing = false;

    public static function bootstrap($params)
    {
        if (static::$_initializing) {
            return;
        }
        static::$_initializing = true;
        if (is_array($params)) {
            $disabled = Util::getValue($params, 'disabled', array());
            if (is_array($disabled)) {
                static::$_disabled = Util::mergeArray(static::$_disabled, $disabled);
            }
            $failing = Util::getValue($params, 'failing', array());
            if (is_array($failing)) {
                static::$_failing = Util::mergeArray(static::$_failing, $failing);
            }
        }
    }
    public static function isExtensionLoaded($name)
    {
        if (is_array(static::$_disabled['extensions']) && in_array($name, static::$_disabled['extensions'])) {
            return false;
        }

        return extension_loaded($name);
    }
    public static function isClassExists($name)
    {
        if (is_array(static::$_disabled['classes']) && in_array($name, static::$_disabled['classes'])) {
            return false;
        }

        return class_exists($name, false);
    }
    public static function isFunctionExists($name)
    {
        if (is_array(static::$_disabled['functions']) && in_array($name, static::$_disabled['functions'])) {
            return false;
        }

        return function_exists($name);
    }
    public static function isWritablePath($absolutePath, $path = null)
    {
        if (is_string($path) && is_array(static::$_disabled['disk_resources']) && in_array($path, static::$_disabled['disk_resources'])) {
            return false;
        }

        return is_writable($absolutePath);
    }
    public static function isIncludePathInvalid($original)
    {
        if (static::$_disabled['include_path']) {
            return true;
        }

        return (get_include_path() === $original);
    }
    public static function isAutoloadClassStarted()
    {
        return !static::$_disabled['autoload_class'];
    }
    public static function isResource($target, $name = null)
    {
        if (is_string($name) && is_array(static::$_disabled['resources']) && in_array($name, static::$_disabled['resources'])) {
            if (is_resource($target) && (get_resource_type($target) === 'stream')) {
                $info = stream_get_meta_data($target);
                fclose($target);
                $filePath = Util::getValue($info, 'uri');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            return false;
        }

        return is_resource($target);
    }
    public static function isFailed($name = null)
    {
        if (!is_string($name)) {
            return false;
        }
        $failing = Util::getValue(static::$_failing, $name);
        if (!$failing) {
            return false;
        }

        if ($failing === true) {
            return true;
        }

        if (is_array($failing)) {
            $value = Util::getValue(static::$_cache, $name, 0);

            $isFailed = false;
            $maximum = -1;
            if (@$failing[0] === 'random') {
                $isFailed = mt_rand(1, 100) <= @$failing[1];
                $maximum = Util::getValue($failing, 2, $maximum);
            } elseif (@$failing[0] === 'max') {
                $maximum = Util::getValue($failing, 1, $maximum);
                $isFailed = true;
            }
            if ($isFailed && $maximum > 0) {
                if ($value < $maximum) {
                    static::$_cache[$name] = ++$value;
                } else {
                    $isFailed = false;
                }
            }

            return $isFailed;
        }

        return false;
    }
    public static function sanitizeCurlInfo($info)
    {
        if (!is_array($info)) {
            return $info;
        }
        $failing = Util::getValue(static::$_failing, 'curl:info');
        if (is_array($failing)) {
            $info = Util::mergeArray($info, $failing);
        }

        return $info;
    }
    public static function sanitizeCurlErrorNumber($errorNumber)
    {
        if (is_int($errorNumber) && $errorNumber > 0) {
            return $errorNumber;
        }
        $failing = Util::getValue(static::$_failing, 'curl:error_number');
        if (is_int($failing)) {
            return $failing;
        }
        if ($failing === 'random') {
            $failing = array_keys(static::$_curlErrorMessages);
        }
        if (is_array($failing)) {
            $errorNumber = $failing[mt_rand(0, count($failing) - 1)];
        }

        return $errorNumber;
    }
    public static function sanitizeCurlErrorMessage($message, $errorNumber = 0)
    {
        if (!empty($message)) {
            return $message;
        }
        $failing = Util::getValue(static::$_failing, 'curl:error_message');
        if (!$failing) {
            return $message;
        }
        if (!is_int($errorNumber)) {
            return $message;
        }

        return Util::getValue(static::$_curlErrorMessages, $errorNumber, $message);
    }
    public static function isMbStringFunctionOverload()
    {
        $value = (int) ini_get('mbstring.func_overload');
        if (array_key_exists('test:mbstring_func_overload', static::$_failing)) {
            $value = static::$_failing['test:mbstring_func_overload'];
            if (is_bool($value)) {
                return $value;
            }
        }

        return (bool) ($value & 2);
    }
    public static function isNetworkPath($path)
    {
        if (!is_string($path) || strlen($path) < 2) {
            return null;
        }

        if (array_key_exists('test:network_disk', static::$_failing)) {
            return static::$_failing['test:network_disk'];
        }

        return ($path[0] === '\\' && $path[1] === '\\');
    }
}
