<?php
require_once __DIR__ . '/auth.php';
$data = post_data();
$name = trim(field($data, 'full_name'));
$email = strtolower(trim(field($data, 'email')));
$password = field($data, 'password');
$errors = [];
if ($name === '' || mb_strlen($name) > 120 || preg_match('/[\x00-\x1F\x7F]/u', $name)) {
    $errors['full_name'] = 'Enter your full name (up to 120 characters).';
}
if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address (up to 190 characters).';
}
if (mb_strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
    $errors['password'] = 'Use at least 8 characters and no more than 72 bytes.';
}
if ($password !== field($data, 'confirm_password')) $errors['confirm_password'] = 'Passwords must match.';
if ($errors) respond(422, false, 'Please check the highlighted fields.', ['errors' => $errors]);

$db = database();
$existing = $db->prepare('SELECT user_id FROM users WHERE email = ?');
$existing->execute([$email]);
if ($existing->fetch()) respond(409, false, 'This email is already registered.', ['errors' => ['email' => 'Use another email or log in.']]);

try {
    $db->beginTransaction();
    $insert = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, account_status) VALUES (?, ?, ?, 'member', 'active')");
    $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $id = $db->lastInsertId();
    $db->prepare('INSERT INTO user_profiles (user_id) VALUES (?)')->execute([$id]);
    $db->prepare('INSERT INTO wallets (user_id, available_credits, held_credits) VALUES (?, 0, 0)')->execute([$id]);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    if ($error instanceof PDOException && ($error->errorInfo[1] ?? null) === 1062) {
        respond(409, false, 'This email is already registered.', ['errors' => ['email' => 'Use another email or log in.']]);
    }
    throw $error;
}
respond(201, true, 'Your account has been created. Log in to get started.');
