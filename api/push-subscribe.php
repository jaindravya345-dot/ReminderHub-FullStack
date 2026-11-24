
<?php

require_once __DIR__ . '/../config/bootstrap.php';

$userId = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

require_csrf();

$data = request_json();

$endpoint = trim((string)($data['endpoint'] ?? ''));
$publicKey = trim((string)($data['keys']['p256dh'] ?? ''));
$authToken = trim((string)($data['keys']['auth'] ?? ''));

if (
    !filter_var($endpoint, FILTER_VALIDATE_URL) ||
    !str_starts_with($endpoint, 'https://') ||
    $publicKey === '' ||
    $authToken === ''
) {
    json_response(['ok' => false, 'error' => 'Invalid push subscription.'], 422);
}

$stmt = db()->prepare(
    'SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?'
);
$stmt->execute([$userId, $endpoint]);

$existing = $stmt->fetch();

if ($existing) {
    $stmt = db()->prepare(
        'UPDATE push_subscriptions
         SET public_key = ?, auth_token = ?
         WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$publicKey, $authToken, $existing['id'], $userId]);
} else {
    $stmt = db()->prepare(
        'INSERT INTO push_subscriptions
         (user_id, endpoint, public_key, auth_token)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $endpoint, $publicKey, $authToken]);
}

json_response(['ok' => true, 'message' => 'Subscription saved.']);
