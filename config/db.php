<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

function db(): mysqli
{
    static $conn = null;
    if ($conn !== null) return $conn;

    $conn = new mysqli(
        $_ENV['DB_HOST'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        $_ENV['DB_NAME'],
        (int) ($_ENV['DB_PORT'] ?? 3306)
    );

    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed']));
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
