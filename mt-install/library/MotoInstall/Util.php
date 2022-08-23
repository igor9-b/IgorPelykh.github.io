<?php
namespace MotoInstall;

class Util
{
    const DIR_PERMISSION = 0755;
    const FILE_PERMISSION = 0755;
    protected static $_lastUniqueId = '';
    public static function createDir($path, $mode = null, $recursive = true)
    {
        if ($mode === null) {
            $mode = static::DIR_PERMISSION;
        }
        if (file_exists($path)) {
            $result = is_dir($path);
        } else {
            $result = @mkdir($path, $mode, $recursive);
            @chmod($path, $mode);
        }

        return $result;
    }
    static public function filePutContents($filename, $data, $flag = null, $context = null)
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            static::createDir($dir);
        }
        $result = file_put_contents($filename, $data, $flag, $context);
        static::fixFilePermission($filename);

        return $result;
    }
    public static function getFilePermission($file, $dec = false)
    {
        if (!file_exists($file)) {
            return null;
        }
        $permission = substr(sprintf('%o', fileperms($file)), -4);
        if (!$dec) {
            $permission = octdec($permission);
        }

        return $permission;
    }
    public static function fixFilePermission($path, $mode = null)
    {
        if ($mode == null) {
            $mode = (is_dir($path) ? static::DIR_PERMISSION : (static::FILE_PERMISSION & ~umask()));
        }
        $permission = static::getFilePermission($path);
        if ($permission != null && $permission < $mode) {
            @chmod($path, $mode);
        }

        return true;
    }
    public static function deleteDir($src, $killSrc = true)
    {
        if (!is_dir($src)) {
            return false;
        }
        $dir = opendir($src);
        if (!$dir) {
            return false;
        }
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                if (is_dir($src . '/' . $file) && !is_link($src . '/' . $file)) {
                    static::deleteDir($src . '/' . $file, true);
                } else {
                    @unlink($src . '/' . $file);
                }
            }
        }
        closedir($dir);
        if ($killSrc) {
            rmdir($src);
        }

        return true;
    }
    public static function emptyDir($src)
    {
        return static::deleteDir($src, false);
    }
    public static function scanDir($root, $dir = '', $options = array(), $result = array())
    {
        if (!is_array($options)) {
            $options = array();
        }
        $options = array_merge(array(
            'addDir' => false,
            'compareFunction' => null,
            'skipThisPathFunction' => null,
        ), $options);

        if (is_callable($options['skipThisPathFunction']) && $options['skipThisPathFunction']($root, $dir)) {
            return $result;
        }

        $compareFunction = null;
        if (is_callable($options['compareFunction'])) {
            $compareFunction = $options['compareFunction'];
        }

        $scanSubDir = true;
        if (array_key_exists('maxLevel', $options)) {
            $scanSubDir = ($options['maxLevel'] > 0);
            $options['maxLevel']--;
        }
        $path = $root . '/' . $dir;
        if (!is_dir($path)) {
            return $result;
        }
        $list = scandir($path);
        if (!$list) {
            return $result;
        }
        for ($i = 0, $count = count($list); $i < $count; $i++) {
            $file = $list[$i];
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (is_dir($path . '/' . $file)) {
                if ($options['addDir']) {
                    if ($compareFunction === null || $compareFunction($dir, $file, $root, 'dir')) {
                        $result[] = ltrim($dir . '/' . $file, '/');
                    }
                }
                if ($scanSubDir) {
                    $result = static::scanDir($root, $dir . '/' . $file, $options, $result);
                }
            } else {
                if ($compareFunction === null || $compareFunction($dir, $file, $root, 'file')) {
                    $result[] = ltrim($dir . '/' . $file, '/');
                }
            }
        }

        return $result;
    }
    public static function copyFile($from, $to, $rewrite = true)
    {
        if (!is_file($from)) {
            return false;
        }
        if (is_file($to)) {
            if ($rewrite) {
                unlink($to);
            } else {
                return false;
            }
        }
        if (!is_dir(dirname($to))) {
            static::createDir(dirname($to));
        }
        $result = copy($from, $to);
        static::fixFilePermission($to);

        return $result;
    }
    public static function moveFile($from, $to, $rewrite = true)
    {
        if (!is_file($from)) {
            return false;
        }
        if (is_file($to)) {
            if ($rewrite) {
                unlink($to);
            } else {
                return false;
            }
        }
        if (!is_dir(dirname($to))) {
            static::createDir(dirname($to));
        }
        $result = rename($from, $to);
        static::fixFilePermission($to);

        return $result;
    }
    public static function copyFiles($files, $fromDir, $toDir, $rewrite = true)
    {
        if (!is_dir($fromDir)) {
            return false;
        }
        if (!is_dir($toDir)) {
            static::createDir($toDir);
        }
        if (!is_dir($toDir)) {
            return false;
        }

        for ($i = 0, $count = count($files); $i < $count; $i++) {
            $from = $fromDir . '/' . $files[$i];
            $to = $toDir . '/' . $files[$i];
            if (is_dir($from)) {
                static::createDir($to);
            } else {
                static::copyFile($from, $to, $rewrite);
            }
        }

        return true;
    }
    public static function moveFiles($files, $fromDir, $toDir, $rewrite = true)
    {
        if (!is_dir($fromDir)) {
            return false;
        }
        if (!is_dir($toDir)) {
            static::createDir($toDir);
        }
        if (!is_dir($toDir)) {
            return false;
        }

        for ($i = 0, $count = count($files); $i < $count; $i++) {
            $from = $fromDir . '/' . $files[$i];
            $to = $toDir . '/' . $files[$i];
            if (is_dir($from)) {
                static::createDir($to);
            } else {
                static::moveFile($from, $to, $rewrite);
            }
        }

        return true;
    }
    public static function copyDir($source, $destination, $rewrite = true)
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir(dirname($destination))) {
            static::createDir($destination);
        }

        $params = array(
            'addDir' => true
        );
        $files = static::scanDir($source, '', $params);
        if (!count($files)) {
            return false;
        }

        static::copyFiles($files, $source, $destination, $rewrite);

        return true;
    }
    public static function getUniqueId($level = 10)
    {
        $id = uniqid();
        if ($level < 1) {
            return $id;
        }
        if (static::$_lastUniqueId === $id) {
            usleep(1);

            return static::getUniqueId(--$level);
        }
        static::$_lastUniqueId = $id;

        return $id;
    }
    public static function getValue($source, $itemPath, $default = null)
    {
        if (empty($itemPath)) {
            return $default;
        }
        if (empty($source)) {
            return $default;
        }

        if (!is_array($itemPath)) {
            $itemPath = explode('.', $itemPath);
        }

        foreach ($itemPath as $key) {
            if (is_array($source)) {
                if (!array_key_exists($key, $source)) {

                    return $default;
                }
                $source = $source[$key];
            } elseif (is_object($source)) {
                if (!isset($source->{$key})) {

                    return $default;
                }
                $source = $source->{$key};
            } else {

                return $default;
            }
        }

        return $source;
    }
    public static function convertSizeStringToInteger($size)
    {
        $size = trim($size);
        $value = null;
        if (preg_match('/^([0-9]+)\s*(P|T|G|M|K){0,1}/i', $size, $matches)) {
            $value = isset($matches[1]) ? (int) $matches[1] : 0;
            $unit = isset($matches[2]) ? strtoupper($matches[2]) : '';
            switch ($unit) {
                case 'P':
                    $value *= 1024;
                case 'T':
                    $value *= 1024;
                case 'G':
                    $value *= 1024;
                case 'M':
                    $value *= 1024;
                case 'K':
                    $value *= 1024;
                    break;
            }
        }

        return $value;
    }
    public static function decodeValue($value, $type = null)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                if (is_bool($value)) {
                    return $value;
                }

                if (is_string($value)) {
                    return (strtolower(trim($value)) === 'true');
                }

                return (bool) $value;
            case 'object':
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                return (is_string($value) ? json_decode($value) : $value);
            case 'array':
                if (is_object($value)) {
                    $value = json_encode($value);
                }
                return (is_string($value) ? json_decode($value, true) : $value);
            default:
                return $value;
        }
    }
    public static function simpleRender($template, $data)
    {
        $vars = explode(',', '{{' . implode('}},{{', array_keys($data)) . '}}');
        $values = array_values($data);

        return str_replace($vars, $values, $template);
    }
    public static function isFunctionDisabled($function)
    {
        static $list = false;

        if ($list === false) {
            $list = @ini_get('disable_functions');
            $list = trim((string) $list);
            if (empty($list)) {
                $list = array();
            } else {
                $list = explode(',', $list);
                $list = array_map('trim', $list);
            }
        }

        return in_array($function, $list);
    }
    public static function toSnakeCase($value, $delimiter = '_')
    {
        $value = trim($value);
        if (!ctype_lower($value)) {
            $value = ucwords($value);
            $value = preg_replace('/\s+/', '', $value);
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/', '$1' . $delimiter, $value));
        }

        return $value;
    }
    public static function toCamelCase($value, $delimiters = '_-.')
    {
        if (is_string($delimiters)) {
            $delimiters = str_split($delimiters);
        }

        $value = trim($value);
        $value = strtolower($value);
        $value = str_replace($delimiters, ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);
        $value = lcfirst($value);

        return $value;
    }
    public static function toStudlyCase($value, $delimiters = '_-.')
    {
        if (is_string($delimiters)) {
            $delimiters = str_split($delimiters);
        }

        $value = trim($value);
        $value = strtolower($value);
        $value = str_replace($delimiters, ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);

        return $value;
    }
    public static function toArray($target, $extra = null)
    {
        if (is_array($target)) {
            foreach($target as $index => $item) {
                if (is_object($item) || is_array($item)) {
                    $target[$index] = static::toArray($item, $extra);
                }
            }

            return $target;
        }

        if (is_object($target)) {
            if (method_exists($target, 'toArray')) {
                return $target->toArray($extra);
            }
        }

        return (array) $target;
    }
    public static function arrayHas($array, $key)
    {
        if (empty($array) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }
    public static function arrayOnly($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
    public static function arrayExcept($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }
    public static function sanitizePath($path)
    {
        return preg_replace('/[\/\\\]+/', '/', (string) $path);
    }
    public static function mergeArray(array $a, array $b, $keepNumeric = false)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key) && !$keepNumeric) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::mergeArray($a[$key], $value, $keepNumeric);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
    public static function generateRandomBytes($length)
    {
        $length = (int) $length;
        if ($length < 1) {
            return false;
        }
        if (function_exists('random_bytes')) {
            try {
                return random_bytes($length);
            } catch (\Exception $e) {
            }
        }

        try {
            $result = openssl_random_pseudo_bytes($length);
        } catch (\Exception $e) {
            $result = false;
        }

        if ($result !== false) {
            return $result;
        }
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= chr(mt_rand(16, 254));
        }

        return $result;
    }
}
