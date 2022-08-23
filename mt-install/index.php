<?php
error_reporting(E_ALL);
@ini_set('display_errors', 'off');
@ini_set('log_errors', 'on');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require __DIR__ . '/server_error.php';
require_once __DIR__ . '/app.php';
