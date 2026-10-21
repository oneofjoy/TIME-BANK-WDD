<?php
require_once __DIR__ . '/auth.php';
$data = post_data();
$identifierKey = array_key_exists('identifier', $data) ? 'identifier' : 'email';
$identifier = trim(field($data, $identifierKey));
$isEmail = str_contains($identifier, '@');
$password = field($data, 'password');
$errors = [];
if ($isEmail ? strlen($identifier) > 190 || !filter_var($identifier, FILTER_VALIDATE_EMAIL)
             : !preg_match('/\A[A-Za-z0-9_]{3,30}\z/', $identifier)) {
    $errors[$identifierKey] = 'Enter a valid email address or username.';
}
if ($password === '' || strlen($password) > 72 || str_contains($password, "\0")) $errors['password'] = strlen($password) > 72 ? 'This password is too long. Please use a shorter one.' : 'Enter your password.';
if ($errors) respond(422, false, 'Please check the highlighted fields.', ['errors' => $errors]);
$column = $isEmail ? 'email' : 'username';
$query = database()->prepare("SELECT user_id, password_hash, role, account_status FROM users WHERE $column = ?");
$query->execute([$identifier]);
$account = $query->fetch();
// Keep verification work similar for an unknown address and an incorrect password.
$hash = $account['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
$verified = password_verify($password, $hash);
if (!$account || !$verified || $account['account_status'] !== 'active' || $account['role'] !== 'member') {
    respond(401, false, 'We couldn’t log you in. Check your email or username and password.');
}
session_regenerate_id(true);
$_SESSION = ['user' => ['user_id' => (int)$account['user_id']], 'csrf_token' => bin2hex(random_bytes(32))];
respond(200, true, 'Logged in successfully.', ['user' => current_user(), 'csrf_token' => $_SESSION['csrf_token']]);
