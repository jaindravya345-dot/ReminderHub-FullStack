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

// Keep API failures in JSON format instead of leaking PHP's HTML error output
// into fetch(...).json() calls. Detailed errors go to the server log only.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $e): void {
    error_log('ReminderHub uncaught exception: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['ok' => false, 'error' => 'The server could not complete this request. Please try again.'], JSON_UNESCAPED_UNICODE);
    exit;
});

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
$port = getenv('REMINDERHUB_DB_PORT') ?: ($localConfig['port'] ?? null);
if (!$port && str_contains($host, ':')) {
    [$host, $port] = explode(':', $host, 2);
}
$portPart = $port ? ";port={$port}" : '';

    $dsn = "mysql:host={$host}{$portPart};dbname={$name};charset=utf8mb4";
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
    // Create the application tables automatically in the hosted database.
    // This keeps first-time setup online-only; no local MySQL server or manual
    // schema import is required. CREATE IF NOT EXISTS is safe on every request.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reminders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(120) NOT NULL,
            reminder_date DATE NOT NULL,
            reminder_time TIME NOT NULL,
            category ENUM('Personal','Health','Work','Payments','Family') NOT NULL DEFAULT 'Personal',
            priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
            notes VARCHAR(1000) NOT NULL DEFAULT '',
            is_completed TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_reminders_user_schedule (user_id, reminder_date, reminder_time),
            CONSTRAINT fk_reminders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            endpoint VARCHAR(2048) NOT NULL,
            public_key VARCHAR(255) NOT NULL,
            auth_token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_push_subscription_endpoint (endpoint(191)),
            INDEX idx_push_subscriptions_user (user_id),
            CONSTRAINT fk_push_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    } catch (PDOException $e) {
        error_log('ReminderHub schema initialization failed: ' . $e->getMessage());
        json_response(['ok' => false, 'error' => 'The online database connected, but automatic table setup failed. Check the database user permissions.'], 500);
    }

    return $pdo;
}
