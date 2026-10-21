<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/starter-pack.php';
$data = post_data();
$name = trim(field($data, 'full_name'));
$username = trim(field($data, 'username'));
$email = strtolower(trim(field($data, 'email')));
$password = field($data, 'password');
$errors = [];
if ($name === '' || mb_strlen($name) > 120 || preg_match('/[\x00-\x1F\x7F]/u', $name)) {
    $errors['full_name'] = 'Enter your full name (up to 120 characters).';
}
if (!preg_match('/\A[A-Za-z0-9_]{3,30}\z/', $username)) {
    $errors['username'] = 'Use 3–30 letters, numbers, or underscores.';
}
if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address (up to 190 characters).';
}
if (mb_strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
    $errors['password'] = strlen($password) > 72
        ? 'This password is too long. Please use a shorter one.'
        : (str_contains($password, "\0") ? 'Please use a different password.' : 'Use at least 8 characters.');
}
if ($password !== field($data, 'confirm_password')) $errors['confirm_password'] = 'The passwords don’t match. Please try again.';
if ($errors) respond(422, false, 'Please check the highlighted fields.', ['errors' => $errors]);

$db = database();
function registration_conflicts(PDO $db, string $username, string $email): array
{
    $errors = [];
    foreach (['username' => $username, 'email' => $email] as $column => $value) {
        // Column names are fixed internal values; submitted values are bound.
        $query = $db->prepare("SELECT user_id FROM users WHERE $column = ?");
        $query->execute([$value]);
        if ($query->fetch()) {
            $errors[$column] = $column === 'username'
                ? 'This username is already taken. Choose another.'
                : 'This email is already registered. Use another email or log in.';
        }
    }
    return $errors;
}
$conflicts = registration_conflicts($db, $username, $email);
if ($conflicts) respond(409, false, 'Please check your username and email.', ['errors' => $conflicts]);

try {
    $db->beginTransaction();
    $insert = $db->prepare("INSERT INTO users (full_name, username, email, password_hash, role, account_status) VALUES (?, ?, ?, ?, 'member', 'active')");
    $insert->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $id = $db->lastInsertId();
    $db->prepare('INSERT INTO user_profiles (user_id) VALUES (?)')->execute([$id]);
    $db->prepare('INSERT INTO wallets (user_id, available_credits, held_credits) VALUES (?, ?, 0)')->execute([$id, STARTER_PACK_CREDITS]);
    $walletId = $db->lastInsertId();
    $db->prepare("INSERT INTO credit_transactions(wallet_id,transaction_type,available_delta,held_delta,note) VALUES(?,'bonus',?,0,?)")
        ->execute([$walletId, STARTER_PACK_CREDITS, STARTER_PACK_NOTE]);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    if ($error instanceof PDOException && ($error->errorInfo[1] ?? null) === 1062) {
        respond(409, false, 'This username or email is already registered.', [
            'errors' => registration_conflicts($db, $username, $email)
        ]);
    }
    throw $error;
}
respond(201, true, 'Your account has been created. Log in to get started.');
