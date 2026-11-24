<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    json_response([
        'ok' => true,
        'authenticated' => !empty($_SESSION['user_id']),
        'user' => !empty($_SESSION['user_id']) ? ['id' => (int)$_SESSION['user_id'], 'name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']] : null,
        'csrfToken' => csrf_token(),
    ]);
}
if ($method !== 'POST') json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
require_csrf();
$data = request_json();
$action = $data['action'] ?? '';

if ($action === 'register') {
    $name = trim((string)($data['name'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    if ($name === '' || mb_strlen($name) > 80) json_response(['ok'=>false,'error'=>'Enter a name (maximum 80 characters).'], 422);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) json_response(['ok'=>false,'error'=>'Enter a valid email address.'], 422);
    if (strlen($password) < 10 || strlen($password) > 200) json_response(['ok'=>false,'error'=>'Password must be at least 10 characters.'], 422);
    $pdo = db();
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) json_response(['ok'=>false,'error'=>'An account with this email already exists.'], 409);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $hash]);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$pdo->lastInsertId();
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    json_response(['ok'=>true,'user'=>['id'=>$_SESSION['user_id'],'name'=>$name,'email'=>$email],'csrfToken'=>csrf_token()], 201);
}

if ($action === 'login') {
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') json_response(['ok'=>false,'error'=>'Enter your email and password.'], 422);
    $stmt = db()->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) json_response(['ok'=>false,'error'=>'Email or password is incorrect.'], 401);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    json_response(['ok'=>true,'user'=>['id'=>(int)$user['id'],'name'=>$user['name'],'email'=>$user['email']],'csrfToken'=>csrf_token()]);
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', ['expires'=>time()-42000,'path'=>$params['path'],'secure'=>$params['secure'],'httponly'=>$params['httponly'],'samesite'=>'Lax']);
    }
    session_destroy();
    json_response(['ok'=>true]);
}
json_response(['ok'=>false,'error'=>'Unknown action.'], 400);
