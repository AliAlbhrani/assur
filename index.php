<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve API requests to the router
if (str_starts_with($path, '/api')) {
    require __DIR__ . '/src/router.php';
    exit;
}

// Serve actual HTML files from /public directly
if (str_starts_with($path, '/public/') && str_ends_with($path, '.html')) {
    $file = __DIR__ . $path;
    if (file_exists($file)) {
        readfile($file);
        exit;
    }
}

// Serve the login page for everything else
require __DIR__ . '/public/index.html';
