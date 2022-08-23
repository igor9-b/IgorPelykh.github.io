<?php
error_reporting(E_ALL);
@ini_set('display_errors', 'off');
@ini_set('log_errors', 'on');

$file = __DIR__ . '/mt-admin/views/server_error.php';
if (file_exists($file)) {
    require_once $file;
}
require_once __DIR__ . '/app.php';
