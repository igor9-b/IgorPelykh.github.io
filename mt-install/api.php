<?php
require_once __DIR__ . '/common.php';
$app = new MotoInstall\Api\Application();

$response = $app->handle();

echo $response;
