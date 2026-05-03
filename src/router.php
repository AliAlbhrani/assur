<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/controllers/auth.php';

// ── CORS (dev) ───────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if (method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── ROUTES ───────────────────────────────────────────────────
$method = method();
$path   = path();

$routes = [
    // Auth
    ['POST',   '/api/login',           'login'],
    ['POST',   '/api/logout',          'logout'],
    ['GET',    '/api/me',              'me'],
    ['POST',   '/api/register',        'register'],
    ['POST',   '/api/change-password', 'change_password'],
    ['POST',    '/api/createAdmin',     'registerAdmin'],
];

foreach ($routes as [$rMethod, $rPath, $handler]) {
    if ($method === $rMethod && $path === $rPath) {
        if (is_callable($handler)) {
            $handler();
        } else {
            $handler();
        }
        exit;
    }
}

// 404
json_error("Route not found: {$method} {$path}", 404);
