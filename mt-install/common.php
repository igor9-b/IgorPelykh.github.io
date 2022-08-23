<?php
include_once __DIR__ . '/library/MotoInstall/System.php';
if (!defined('WEBSITE_DIR')) {
    define('WEBSITE_DIR', dirname(__DIR__));
}
if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 128);
}

MotoInstall\System::bootstrap(array(
    'paths' => array(
        'website' => WEBSITE_DIR,
    )
));
