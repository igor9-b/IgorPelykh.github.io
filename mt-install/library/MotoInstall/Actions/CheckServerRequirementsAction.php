<?php
namespace MotoInstall\Actions;

use MotoInstall;
use MotoInstall\Util;

class CheckServerRequirementsAction extends MotoInstall\AbstractAction
{
    const CHECKER_PHP_VERSION = 'php_version';
    const CHECKER_PHP_EXTENSIONS = 'php_extensions';
    const CHECKER_PHP_CLASSES = 'php_classes';
    const CHECKER_PHP_FUNCTIONS = 'php_functions';
    const CHECKER_PHP_SETTINGS = 'php_settings';
    const CHECKER_DISK_RESOURCES = 'disk_resources';
    public function processRequest(MotoInstall\Api\Request $request)
    {
        $requirements = new MotoInstall\DataBag(MotoInstall\System::config('serverRequirements'));
        $response = array();
        $status = true;
        $report = array();
        $passedCheckers = array();
        $failedCheckers = array();

        $data = $this->checkingPhpVersion($requirements->get('php_version'));
        if ($data['status']) {
            $passedCheckers[] = $data['checker'];
        } else {
            $status = false;
            $failedCheckers[] = $data['checker'];
        }
        $report[] = $data;

        $data = $this->checkingPhpExtensions($requirements->get('php_extensions'));
        if ($data['status']) {
            $passedCheckers[] = $data['checker'];
        } else {
            $status = false;
            $failedCheckers[] = $data['checker'];
        }
        $report[] = $data;

        $data = $this->checkingPhpClasses($requirements->get('php_classes'));
        if ($data['status']) {
            $passedCheckers[] = $data['checker'];
        } else {
            $status = false;
            $failedCheckers[] = $data['checker'];
        }
        $report[] = $data;

        $data = $this->checkingPhpFunctions($requirements->get('php_functions'));
        if ($data['status']) {
            $passedCheckers[] = $data['checker'];
        } else {
            $status = false;
            $failedCheckers[] = $data['checker'];
        }
        $report[] = $data;

        $data = $this->checkingPhpSettings($requirements->get('php_settings'));
        if ($data['status']) {
            $passedCheckers[] = $data['checker'];
        } else {
            $status = false;
            $failedCheckers[] = $data['checker'];
        }
        $report[] = $data;

        $data = $this->checkingDiskResources($requirements->get('disk_resources'));
        if ($data['status']) {
            $passedCheckers[] = $data['checker'];
        } else {
            $status = false;
            $failedCheckers[] = $data['checker'];
        }
        $report[] = $data;

        $response['status'] = $status;
        $response['failed'] = $failedCheckers;
        $response['passed'] = $passedCheckers;
        $response['report'] = $report;

        if (MotoInstall\System::isDebug() ) {
            $response['$requirements'] = $requirements;
        }

        return $response;
    }
    protected function returnFailed($checker, $requirements = null, $current = null)
    {
        $result = array(
            'checker' => $checker,
            'status' => false,
        );

        if ($current !== null) {
            $result['current'] = $current;
        }

        if ($requirements !== null) {
            $result['requirements'] = $requirements;
        }

        return $result;
    }
    protected function returnPassed($checker, $current = null)
    {
        $result = array(
            'checker' => $checker,
            'status' => true,
        );

        if ($current !== null) {
            $result['current'] = $current;
        }

        return $result;
    }
    public function checkingPhpVersion($params)
    {
        if (!is_array($params)) {
            return $this->returnFailed(static::CHECKER_PHP_VERSION);
        }
        $currentVersion = substr(PHP_VERSION_ID, 0, 3);
        $requirements = Util::getValue($params, $currentVersion);
        if (empty($requirements) || empty($requirements['min'])) {
            $requirements = array(
                'min' => array(),
                'recommended' => array(),
            );
            foreach ($params as $version => $value) {
                if (array_key_exists('min', $value)) {
                    $requirements['min'][] = $value['min'];
                }
                if (array_key_exists('recommended', $value)) {
                    $requirements['recommended'][] = $value['recommended'];
                }
            }

            return $this->returnFailed(static::CHECKER_PHP_VERSION, $requirements, PHP_VERSION);
        }

        if (version_compare(PHP_VERSION, $requirements['min'], '>=')) {
            return $this->returnPassed(static::CHECKER_PHP_VERSION, PHP_VERSION);
        }

        return $this->returnFailed(static::CHECKER_PHP_VERSION, $requirements, PHP_VERSION);
    }
    public function checkingPhpExtensions($items)
    {
        if (!is_array($items)) {
            return $this->returnFailed(static::CHECKER_PHP_EXTENSIONS, $items);
        }

        $requirements = array();
        $current = array();
        foreach ($items as $item) {
            if (MotoInstall\Tester::isExtensionLoaded($item)) {
                $current[] = $item;
            } else {
                $requirements[] = $item;
            }
        }

        if (count($requirements) > 0) {
            return $this->returnFailed(static::CHECKER_PHP_EXTENSIONS, $requirements, $current);
        }

        return $this->returnPassed(static::CHECKER_PHP_EXTENSIONS);
    }
    public function checkingPhpClasses($items)
    {
        if (!is_array($items)) {
            return $this->returnFailed(static::CHECKER_PHP_CLASSES, $items);
        }

        $requirements = array();
        $current = array();
        foreach ($items as $item) {
            if (MotoInstall\Tester::isClassExists($item)) {
                $current[] = $item;
            } else {
                $requirements[] = $item;
            }
        }

        if (count($requirements) > 0) {
            return $this->returnFailed(static::CHECKER_PHP_CLASSES, $requirements, $current);
        }

        return $this->returnPassed(static::CHECKER_PHP_CLASSES);
    }
    public function checkingPhpFunctions($items)
    {
        if (!is_array($items)) {
            return $this->returnFailed(static::CHECKER_PHP_FUNCTIONS, $items);
        }

        $disabled = @ini_get('disable_functions');
        $disabled = trim((string) $disabled);
        if (empty($disabled)) {
            $disabled = array();
        } else {
            $disabled = explode(',', $disabled);
            $disabled = array_map('trim', $disabled);
        }

        $requirements = array();
        $current = array();
        foreach ($items as $item) {
            if (in_array($item, $disabled) || !MotoInstall\Tester::isFunctionExists($item)) {
                $requirements[] = $item;
            } else {
                $current[] = $item;
            }
        }

        if (count($requirements) > 0) {
            return $this->returnFailed(static::CHECKER_PHP_FUNCTIONS, $requirements, $current);
        }

        return $this->returnPassed(static::CHECKER_PHP_FUNCTIONS);
    }
    public function checkingPhpSettings($items)
    {
        if (!is_array($items)) {
            return $this->returnFailed(static::CHECKER_PHP_SETTINGS, $items);
        }

        $requirements = array();
        $current = array();
        foreach ($items as $item) {
            switch($item) {
                case 'mbstring_func_overload':
                    if (MotoInstall\Tester::isMbStringFunctionOverload()) {
                        $requirements[] = $item;
                    }
                    break;
            }
            switch($item) {
                case 'network_disk':
                    if (MotoInstall\Tester::isNetworkPath(__FILE__)) {
                        $requirements[] = $item;
                    }
                    break;
            }
        }

        if (count($requirements) > 0) {
            return $this->returnFailed(static::CHECKER_PHP_SETTINGS, $requirements, $current);
        }

        return $this->returnPassed(static::CHECKER_PHP_SETTINGS);
    }
    public function checkingDiskResources($items)
    {
        if (!is_array($items)) {
            return $this->returnFailed(static::CHECKER_DISK_RESOURCES, $items);
        }

        $requirements = array();
        $current = array();
        $autoFixPermission = true;
        foreach ($items as $item) {
            $type = Util::getValue($item, 'type');
            $path = Util::getValue($item, 'path');
            $absolutePath = MotoInstall\System::getAbsolutePath($path);
            $relativePath = MotoInstall\System::getRelativePath($path);
            if ($type === 'dir') {
                if ($autoFixPermission) {
                    if (!is_dir($absolutePath)) {
                        Util::createDir($absolutePath);
                    } elseif (!is_writable($absolutePath)) {
                        Util::fixFilePermission($absolutePath);
                        clearstatcache(true, $absolutePath);
                    }
                }
                if (!MotoInstall\Tester::isWritablePath($absolutePath, $path)) {
                    $requirements[] = array('path' => $relativePath, 'type' => $type, 'permission' => 'writable');
                }
            } elseif ($type === 'file') {
                if ($autoFixPermission && is_file($absolutePath) && !is_writable($absolutePath)) {
                    Util::fixFilePermission($absolutePath);
                    clearstatcache(true, $absolutePath);
                }
                if (!is_file($absolutePath) || !MotoInstall\Tester::isWritablePath($absolutePath, $path)) {
                    $requirements[] = array('path' => $relativePath, 'type' => $type, 'permission' => 'writable');
                }
            }
        }

        if (count($requirements) > 0) {
            return $this->returnFailed(static::CHECKER_DISK_RESOURCES, $requirements, $current);
        }

        return $this->returnPassed(static::CHECKER_DISK_RESOURCES);
    }
    public function checkRequest(MotoInstall\Api\Request $request)
    {
        return true;
    }
}
