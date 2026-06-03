<?php
$publicPath = $_SERVER['DOCUMENT_ROOT'];
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}
@file_put_contents('php://stdout', '[' . date('D M j H:i:s Y') . "] {$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} [{$_SERVER['REQUEST_METHOD']}] URI: $uri\n");
require_once $publicPath.'/index.php';
