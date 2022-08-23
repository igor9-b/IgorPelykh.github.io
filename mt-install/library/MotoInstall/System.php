<?php
namespace MotoInstall;

use MotoInstall;

class System
{
    const ENV_DEVELOPMENT = 'development';

    protected static $_initializing = false;
    protected static $_initialized = false;
    protected static $_stage = 'production';
    protected static $_config = array();

    protected static $_paths = array(
        'website' => './../',
        'installerDir' => '@website/mt-install',
        'baseConfigFile' => '@installerDir/config/base.php',
        'productConfigFile' => '@installerDir/config/product.php',
        'localConfigFile' => '@installerDir/config/local.php',
        'libraryDir' => '@installerDir/library',
        'installationTempDir' => '@installerDir/temp',
        'installationFilesDir' => '@installerDir/temp/installation_files',
        'installationConfigFile' => '@installationTempDir/config.php',
    );
    public static function bootstrap($params)
    {
        if (static::$_initializing) {
            return;
        }
        static::$_initializing = true;

        error_reporting(E_ALL);
        @ini_set('display_errors', 'on');

        require_once __DIR__ . '/Exception.php';
        require_once __DIR__ . '/Util.php';
        require_once __DIR__ . '/Tester.php';
        require_once __DIR__ . '/Logger.php';

        try {
            static::_initPaths(MotoInstall\Util::getValue($params, 'paths', array()));
            static::_initEnvironment();
            static::_loadConfig();
            Tester::bootstrap(static::config('__TESTER__'));
            Logger::bootstrap(static::isDebug() ? Logger::DEBUG : static::config('debugLevel'));
            static::_initIncludePath();
            static::_initAutoload();
            static::_preCheck();
            static::_initEncryption();
        } catch (\Exception $e) {
            static::reportException($e);
        }

        static::$_initialized = true;
    }
    protected static function _initPaths($paths)
    {
        if (!is_array($paths)) {
            return false;
        }
        foreach ($paths as $name => $path) {
            static::setPath($name, $path);
        }

        return true;
    }
    protected static function _initEnvironment()
    {
        $stage = static::$_stage;

        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
        }

        if (defined('APPLICATION_ENV')) {
            $stage = APPLICATION_ENV;
        } else {
            if (getenv('APPLICATION_ENV')) {
                $stage = getenv('APPLICATION_ENV');
            }
        }

        $timezone = null;
        if (function_exists('date_default_timezone_get')) {
            $timezone = @date_default_timezone_get();
        }
        if (empty($timezone)) {
            $timezone = 'UTC';
        }
        if (function_exists('date_default_timezone_set')) {
            @date_default_timezone_set($timezone);
        }

        if (function_exists('mb_internal_encoding')) {
            @mb_internal_encoding('UTF-8');
        }

        static::$_stage = $stage;
    }
    public static function isDevelopmentStage()
    {
        return (static::$_stage === static::ENV_DEVELOPMENT);
    }
    public static function isDebug()
    {
        return (bool) static::config('debug');
    }
    protected static function _loadConfig()
    {
        static::_loadConfigFile(static::getAbsolutePath('@baseConfigFile'));
        static::_loadConfigFile(static::getAbsolutePath('@productConfigFile'));
        static::_loadConfigFile(static::getAbsolutePath('@localConfigFile'));
        static::$_stage = static::config('env', static::$_stage);
        static::_initPaths(static::config('paths'));
    }
    protected static function _loadConfigFile($file)
    {
        if (file_exists($file)) {
            $config = require_once $file;
            if (is_array($config)) {
                static::$_config = MotoInstall\Util::mergeArray(static::$_config, $config);
            }
        }
    }
    protected static function _initIncludePath()
    {
        $includePath = get_include_path();
        $checkPath = $includePath;

        $includePath = static::getAbsolutePath('@libraryDir') . PATH_SEPARATOR . $includePath;

        set_include_path($includePath);

        if (Tester::isIncludePathInvalid($checkPath)) {
            throw new MotoInstall\Exception('SET_INCLUDE_PATH_FAILED');
        }
    }
    protected static function _initAutoload()
    {
        try {
            require_once __DIR__ . '/ClassLoader.php';
            $loader = new MotoInstall\ClassLoader();
            spl_autoload_register(array($loader, 'autoload'), true);
            $file = static::getAbsolutePath('@libraryDir/vendor/autoload.php');
            if (file_exists($file)) {
                require_once $file;
            }
        } catch (\Exception $e) {
            throw new MotoInstall\Exception('CLASS_LOADER_FAILED');
        }

        if (!Tester::isAutoloadClassStarted()) {
            throw new MotoInstall\Exception('CLASS_LOADER_FAILED');
        }
    }

    protected static function _preCheck()
    {
        $path = '@installationTempDir';
        $absolutePath = static::getAbsolutePath($path);
        $relativePath = static::getRelativePath($path);
        if (is_dir($absolutePath)) {
            Util::fixFilePermission($absolutePath);
        } else {
            Util::createDir($absolutePath);
        }
        if (!MotoInstall\Tester::isWritablePath($absolutePath, $path)) {
            throw new MotoInstall\Exception('TEMP_FOLDER_NOT_WRITABLE', 400, null, array(
                'path' => $relativePath,
            ));
        }

        if (!extension_loaded('json')) {
            throw new MotoInstall\Exception('EXTENSION_NOT_LOADED', 400, null, array(
                'extensions' => 'json',
            ));
        }
    }
    public static function logger($message = null, $context = array())
    {
        if ($message === null) {
            return Logger::getInstance();
        }

        return Logger::getInstance()->debug($message, $context);
    }
    public static function session($key = null, $default = null)
    {
        if ($key === null) {
            return SessionStorage::getInstance();
        }

        if (is_array($key)) {
            return SessionStorage::getInstance()->put($key);
        }

        return SessionStorage::getInstance()->get($key, $default);
    }

    protected static function _initEncryption()
    {
        $path = static::getAbsolutePath('@installationConfigFile');
        $data = null;

        $checkPath = md5(__FILE__);

        if (is_file($path)) {
            $content = file_get_contents($path);
            $content = explode("\n", $content, 2);
            if (count($content) > 1) {
                $data = json_decode($content[1], true);
                if (Util::getValue($data, '__checkPath__') !== $checkPath) {
                    $data = null;
                }
                if (empty($data['key'])) {
                    $data = null;
                }
            }

            if (!is_array($data)) {
                unlink($path);
            }
        }
        if (!is_array($data)) {
            $data = array(
                '__checkPath__' => $checkPath,
                'key' => md5(__DIR__ . microtime(1) . md5_file(__FILE__) . mt_rand(100000, 999999)),
            );
            $content = '<' . '?' . "\n" . json_encode($data, JSON_PRETTY_PRINT);
            Util::filePutContents($path, $content);
        }

        Encryption::setDefaultKey($data['key']);
    }
    public static function setPath($name, $value)
    {
        $value = trim($value);
        $value = preg_replace('/[\/\\\]+/', '/', $value);
        $value = rtrim($value, '/');

        static::$_paths[$name] = $value;
    }
    public static function getPath($name, $default = null)
    {
        return (array_key_exists($name, static::$_paths) ? static::$_paths[$name] : $default);
    }
    public static function getRelativePath($path, $root = 'website')
    {
        $namespace = null;

        if ($path[0] === '@') {
            $pos = strpos($path, '/');
            if ($pos) {
                $namespace = substr($path, 1, $pos - 1);
                $path = substr($path, $pos + 1);
            } else {
                $namespace = substr($path, 1);
                $path = '';
            }
        }

        if ($namespace === $root) {
            return $path;
        }

        if (null !== $namespace) {
            $path = static::getPath($namespace) . (empty($path) ? '' : '/' . $path);
        }

        if ($path[0] === '@') {
            $path = static::getRelativePath($path);
        }

        return $path;
    }
    public static function reportException($exception)
    {
        $path = static::getAbsolutePath('@installerDir/server_error.php');
        if (file_exists($path)) {
            include $path;
            exit;
        }
        echo '<b>Exception</b> : [ ' . $exception->getCode() . ' ] ' . $exception->getMessage() . "<br/>\n";
        if (static::config('debug')) {
            echo "<b>Trace:</b><br>\n<pre>" . $exception->getTraceAsString() . "</pre><br>\n";
        }
        if ($exception instanceof MotoInstall\Exception) {
            $errors = $exception->getErrors();
            if (count($errors)) {
                echo "<b>Errors:</b><pre>\n" . print_r($errors, true) . "</pre>";
            }
        }
        exit;
    }
    public static function getAbsolutePath($path)
    {
        $namespace = null;

        if (isset($path[0]) && $path[0] === '@') {
            $pos = strpos($path, '/');
            if ($pos) {
                $namespace = substr($path, 1, $pos - 1);
                $path = substr($path, $pos + 1);
            } else {
                $namespace = substr($path, 1);
                $path = '';
            }
        }

        if (null !== $namespace) {
            $path = static::getPath($namespace) . (empty($path) ? '' : '/' . $path);
        }

        if (isset($path[0]) && $path[0] === '@') {
            $path = static::getAbsolutePath($path);
        }

        return $path;
    }
    public static function config($name, $default = null)
    {
        return MotoInstall\Util::getValue(static::$_config, $name, $default);
    }
    public static function getFrontendConfig()
    {
        $result = array(
            'time' => time(),
            'product' => static::config('product'),
            'checking' => array(
                array(
                    'action' => 'check-server-requirements',
                ),
            ),
        );
        if (static::config('env')) {
            $result['env'] = static::config('env');
        }
        if (static::config('debug')) {
            $result['debug'] = static::config('debug');
            $result['debugLevel'] = static::config('debugLevel');
        }
        $result['externalModules'] = static::config('externalModules');

        $result['product']['alreadyDownloaded'] = static::isProductAlreadyDownloaded();
        $result['product']['controlPanelPath'] = static::getRelativePath($result['product']['controlPanelPath']);
        $result['externalModules']['licenseCenter']['environment'] = static::getServerInformation();

        $networkResources = static::config('networkRequirements.resources');
        if (is_array($networkResources)) {
            $result['checking'][] = array(
                'action' => 'check-network-connection',
                'resources' => array_keys($networkResources),
            );
        }

        return $result;
    }
    public static function getServerInformation()
    {
        $response = array(
            'version' => 1,
            'data' => array(
                'product' => static::config('product.name'),
                'ipServer' => Util::getValue($_SERVER, 'SERVER_ADDR'),
                'hostName' => Util::getValue($_SERVER, 'HTTP_HOST'),
                'ipUser' => Util::getValue($_SERVER, 'REMOTE_ADDR'),
                'rootDir' => Util::getValue($_SERVER, 'DOCUMENT_ROOT'),
                'websiteDir' => static::getAbsolutePath('@website'),
                'phpVersion' => PHP_VERSION,
                'phpOS' => PHP_OS,
                'timestamp' => time(),
            ),
        );

        return $response;
    }
    public static function isProductAlreadyDownloaded()
    {
        $absolutePath = static::getAbsolutePath('@productSettingsFile');
        if (file_exists($absolutePath)) {
            return true;
        }

        return false;
    }

}
