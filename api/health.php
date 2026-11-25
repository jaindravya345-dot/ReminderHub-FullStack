<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

try {
    db()->query('SELECT 1');
    json_response(['ok' => true, 'service' => 'ReminderHub']);
} catch (Throwable $e) {
    error_log('ReminderHub health check failed: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Service unavailable.'], 503);
}
