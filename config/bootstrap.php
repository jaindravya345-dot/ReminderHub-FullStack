<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        json_response(['ok' => false, 'error' => 'Your session token is invalid. Refresh the page and try again.'], 419);
    }
}

function require_login(): int {
    if (empty($_SESSION['user_id'])) {
        json_response(['ok' => false, 'error' => 'Please log in to continue.'], 401);
    }
    return (int) $_SESSION['user_id'];
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

$localConfig = is_file(__DIR__ . '/local.php')
    ? require __DIR__ . '/local.php'
    : [];

$host = getenv('REMINDERHUB_DB_HOST') ?: ($localConfig['host'] ?? '127.0.0.1');
$name = getenv('REMINDERHUB_DB_NAME') ?: ($localConfig['name'] ?? 'reminderhub');
$user = getenv('REMINDERHUB_DB_USER') ?: ($localConfig['user'] ?? 'reminderhub_app');
$pass = getenv('REMINDERHUB_DB_PASS') ?: ($localConfig['pass'] ?? '');

    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('ReminderHub database connection failed: ' . $e->getMessage());
        json_response(['ok' => false, 'error' => 'Database connection failed. Check config/bootstrap.php and import database/schema.sql.'], 500);
    }
    return $pdo;
}
