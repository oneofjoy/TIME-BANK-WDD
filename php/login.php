<?php
require_once __DIR__ . '/auth.php';
$data = post_data();
$email = strtolower(trim(field($data, 'email')));
$password = field($data, 'password');
$errors = [];
if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
if ($password === '' || strlen($password) > 72 || str_contains($password, "\0")) $errors['password'] = 'Enter your password (up to 72 bytes).';
if ($errors) respond(422, false, 'Please check the highlighted fields.', ['errors' => $errors]);
$query = database()->prepare('SELECT user_id, password_hash, role, account_status FROM users WHERE email = ?');
$query->execute([$email]);
$account = $query->fetch();
// Keep verification work similar for an unknown address and an incorrect password.
$hash = $account['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
$verified = password_verify($password, $hash);
if (!$account || !$verified || $account['account_status'] !== 'active' || $account['role'] !== 'member') {
    respond(401, false, 'Unable to log in with these credentials.');
}
session_regenerate_id(true);
$_SESSION = ['user' => ['user_id' => (int)$account['user_id']], 'csrf_token' => bin2hex(random_bytes(32))];
respond(200, true, 'Logged in successfully.', ['user' => current_user(), 'csrf_token' => $_SESSION['csrf_token']]);
