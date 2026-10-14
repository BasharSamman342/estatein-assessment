<?php

/*
 * Router for PHP's built-in web server, used by both `php artisan serve` and `composer serve`.
 * Real files under public/ (including /storage uploads) are served as-is; everything else goes to Laravel.
 * Unlike the framework's copy, it resolves public/ from this file, so it works from any working directory.
 */

$publicPath = __DIR__.'/public';

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
