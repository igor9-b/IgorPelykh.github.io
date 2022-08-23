<?php
namespace MotoInstall;

use MotoInstall;

class ClassLoader
{
    public function autoload($class)
    {
        if (class_exists($class, false)) {
            return true;
        }

        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        $file = stream_resolve_include_path($file);
        if (file_exists($file)) {
            require_once $file;
        }

        return class_exists($class);
    }
}
