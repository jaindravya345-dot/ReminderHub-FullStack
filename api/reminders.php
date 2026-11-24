<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = db();

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT id, title, reminder_date, reminder_time, category, priority, notes, is_completed, created_at FROM reminders WHERE user_id = ? ORDER BY reminder_date ASC, reminder_time ASC, id DESC');
    $stmt->execute([$userId]);
    $rows = array_map(static function(array $r): array {
        return [
            'id' => (int)$r['id'], 'title' => $r['title'], 'date' => $r['reminder_date'],
            'time' => substr((string)$r['reminder_time'], 0, 5), 'category' => $r['category'],
            'priority' => $r['priority'], 'notes' => $r['notes'], 'done' => (bool)$r['is_completed'],
        ];
    }, $stmt->fetchAll());
    json_response(['ok'=>true,'reminders'=>$rows]);
}
require_csrf();
$data = request_json();

if ($method === 'POST') {
    $title = trim((string)($data['title'] ?? ''));
    $date = (string)($data['date'] ?? '');
    $time = (string)($data['time'] ?? '');
    $category = (string)($data['category'] ?? 'Personal');
    $priority = (string)($data['priority'] ?? 'normal');
    $notes = trim((string)($data['notes'] ?? ''));
    $validDate = DateTime::createFromFormat('Y-m-d', $date);
    $validTime = DateTime::createFromFormat('H:i', $time);
    if ($title === '' || mb_strlen($title) > 120) json_response(['ok'=>false,'error'=>'Title is required (maximum 120 characters).'], 422);
    if (!$validDate || $validDate->format('Y-m-d') !== $date || !$validTime || $validTime->format('H:i') !== $time) json_response(['ok'=>false,'error'=>'Choose a valid date and time.'], 422);
    if (!in_array($category, ['Personal','Health','Work','Payments','Family'], true)) json_response(['ok'=>false,'error'=>'Choose a valid category.'], 422);
    if (!in_array($priority, ['low','normal','high'], true)) json_response(['ok'=>false,'error'=>'Choose a valid priority.'], 422);
    if (mb_strlen($notes) > 1000) json_response(['ok'=>false,'error'=>'Notes must be 1000 characters or fewer.'], 422);
    $stmt = $pdo->prepare('INSERT INTO reminders (user_id, title, reminder_date, reminder_time, category, priority, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId,$title,$date,$time,$category,$priority,$notes]);
    json_response(['ok'=>true,'id'=>(int)$pdo->lastInsertId()], 201);
}

if ($method === 'PATCH') {
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) json_response(['ok'=>false,'error'=>'A valid reminder ID is required.'], 422);
    $done = filter_var($data['done'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($done === null) json_response(['ok'=>false,'error'=>'Completion status must be true or false.'], 422);
    $stmt = $pdo->prepare('UPDATE reminders SET is_completed = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$done ? 1 : 0, $id, $userId]);
    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare('SELECT id FROM reminders WHERE id = ? AND user_id = ?');
        $check->execute([$id,$userId]);
        if (!$check->fetch()) json_response(['ok'=>false,'error'=>'Reminder not found.'], 404);
    }
    json_response(['ok'=>true]);
}


if ($method === 'DELETE') {
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) json_response(['ok'=>false,'error'=>'A valid reminder ID is required.'], 422);
    $stmt = $pdo->prepare('DELETE FROM reminders WHERE id = ? AND user_id = ?');
    $stmt->execute([$id,$userId]);
    if ($stmt->rowCount() === 0) json_response(['ok'=>false,'error'=>'Reminder not found.'], 404);
    json_response(['ok'=>true]);
}

if ($method === 'PUT') {
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim((string)($data['title'] ?? ''));
    $date = (string)($data['date'] ?? '');
    $time = (string)($data['time'] ?? '');
    $category = (string)($data['category'] ?? '');
    $priority = (string)($data['priority'] ?? '');
    $notes = trim((string)($data['notes'] ?? ''));

    $validDate = DateTime::createFromFormat('Y-m-d', $date);
    $validTime = DateTime::createFromFormat('H:i', $time);

    if (!$id) {
        json_response(['ok'=>false,'error'=>'Invalid reminder ID.'], 422);
    }

    if ($title === '' || mb_strlen($title) > 120) {
        json_response(['ok'=>false,'error'=>'Enter a valid title.'], 422);
    }

    if (!$validDate || $validDate->format('Y-m-d') !== $date ||
        !$validTime || $validTime->format('H:i') !== $time) {
        json_response(['ok'=>false,'error'=>'Choose a valid date and time.'], 422);
    }

    if (!in_array($category, ['Personal','Health','Work','Payments','Family'], true)) {
        json_response(['ok'=>false,'error'=>'Invalid category.'], 422);
    }

    if (!in_array($priority, ['low','normal','high'], true)) {
        json_response(['ok'=>false,'error'=>'Invalid priority.'], 422);
    }

    if (mb_strlen($notes) > 1000) {
        json_response(['ok'=>false,'error'=>'Notes are too long.'], 422);
    }

    $stmt = $pdo->prepare(
        'UPDATE reminders
         SET title = ?, reminder_date = ?, reminder_time = ?,
             category = ?, priority = ?, notes = ?
         WHERE id = ? AND user_id = ?'
    );

    $stmt->execute([
        $title, $date, $time, $category, $priority, $notes, $id, $userId
    ]);

    $check = $pdo->prepare(
        'SELECT id FROM reminders WHERE id = ? AND user_id = ?'
    );
    $check->execute([$id, $userId]);

    if (!$check->fetch()) {
        json_response(['ok'=>false,'error'=>'Reminder not found.'], 404);
    }

    json_response(['ok'=>true]);
}
json_response(['ok'=>false,'error'=>'Method not allowed.'], 405);