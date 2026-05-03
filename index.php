<?php

declare(strict_types=1);

// Serve frontend for non-API routes
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (!str_starts_with($path, '/api')) {
    // Serve the HTML frontend for all non-API paths (SPA-style)
    require __DIR__ . '/public/index.html';
    exit;
}

require __DIR__ . '/src/router.php';
