<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

function project_path(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    foreach (['/php/', '/Pages/'] as $segment) {
        $position = strpos($script, $segment);
        if ($position !== false) return substr($script, 0, $position) . '/';
    }
    return '/';
}

session_name('timebank_session');
session_set_cookie_params([
    'lifetime' => 0, 'path' => project_path(), 'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function respond(int $code, bool $success, string $message = '', array $extra = []): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

// Unexpected backend failures are logged locally, never returned to the browser.
set_exception_handler(function (Throwable $error): void {
    error_log('TimeBank: ' . $error->getMessage());
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/php/')) {
        respond(500, false, 'The service is temporarily unavailable. Please try again.');
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>TimeBank unavailable</title><h1>TimeBank is temporarily unavailable.</h1><p>Please try again shortly.</p></html>';
});

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        respond(405, false, 'This request method is not supported.');
    }
}

function post_data(): array
{
    require_method('POST');
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        respond(403, false, 'Your request has expired. Refresh the page and try again.');
    }
    if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
        respond(415, false, 'Send JSON form data.');
    }
    $raw = file_get_contents('php://input', false, null, 0, 65537);
    if (strlen($raw) > 65536) respond(413, false, 'The request is too large.');
    try { $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR); }
    catch (JsonException $error) { respond(400, false, 'The request could not be read.'); }
    if (!is_array($data) || array_is_list($data) && $data !== []) respond(400, false, 'Invalid form data.');
    return $data;
}

function current_user(): ?array
{
    $id = $_SESSION['user']['user_id'] ?? null;
    if (!$id) return null;
    $statement = database()->prepare(
        'SELECT u.user_id, u.full_name, u.role, u.account_status, w.available_credits
         FROM users u LEFT JOIN wallets w ON w.user_id = u.user_id WHERE u.user_id = ?'
    );
    $statement->execute([$id]);
    $user = $statement->fetch();
    if (!$user || $user['account_status'] !== 'active' || $user['role'] !== 'member') {
        unset($_SESSION['user']);
        return null;
    }
    unset($user['account_status']);
    $user['user_id'] = (int)$user['user_id'];
    $_SESSION['user'] = $user;
    return $user;
}

function require_member(bool $page = false): array
{
    $user = current_user();
    if ($user) return $user;
    if ($page) {
        header('Location: ' . project_path() . 'Pages/Auth/Login/', true, 302);
        exit;
    }
    respond(401, false, 'Please log in to continue.');
}

function field(array $data, string $key): string
{
    return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : '';
}
