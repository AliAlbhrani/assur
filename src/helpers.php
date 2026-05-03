<?php
declare(strict_types=1);

// ── JSON helpers ─────────────────────────────────────────────
function json_out(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400): never
{
    json_out(['error' => $message], $code);
}

// ── Request helpers ──────────────────────────────────────────
function body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD']);
}

function path(): string
{
    $uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);
    return rtrim($path, '/') ?: '/';
}

// ── Auth helpers ─────────────────────────────────────────────
function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
            'path'     => '/',
            'secure'   => ($_ENV['APP_ENV'] ?? 'development') === 'production',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function current_user(): ?array
{
    start_session();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        json_error('Unauthenticated', 401);
    }
}

function require_role(string ...$roles): void
{
    require_auth();
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        json_error('Unauthorized — insufficient role', 403);
    }
}

// ── Sanitize ─────────────────────────────────────────────────
function clean(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}
