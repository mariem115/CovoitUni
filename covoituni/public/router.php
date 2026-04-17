<?php

/**
 * Router for PHP built-in server (`php -S ... -t public public/router.php`).
 * Serves real files from /public; forwards everything else to the Symfony front controller.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$file = __DIR__.$path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__.'/index.php';
