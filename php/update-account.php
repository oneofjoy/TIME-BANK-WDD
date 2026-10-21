<?php
require_once __DIR__ . '/auth.php';
$user = require_member();
$data = post_data();
$username = trim(field($data, 'username'));
$email = strtolower(trim(field($data, 'email')));
$current = field($data, 'current_password');
$password = field($data, 'new_password');
$confirmation = field($data, 'confirm_password');
$errors = [];
if (!preg_match('/\A[A-Za-z0-9_]{3,30}\z/', $username)) $errors['username'] = 'Use 3–30 letters, numbers, or underscores.';
if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address (up to 190 characters).';
if ($current === '' || strlen($current) > 72 || str_contains($current, "\0")) $errors['current_password'] = 'Enter your current password.';
if ($password !== '' && (mb_strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0"))) {
    $errors['new_password'] = strlen($password) > 72
        ? 'This password is too long. Please use a shorter one.'
        : (str_contains($password, "\0") ? 'Please use a different password.' : 'Use at least 8 characters.');
}
if ($password !== $confirmation) $errors['confirm_password'] = 'The passwords don’t match. Please try again.';
if ($errors) respond(422, false, 'Please check the highlighted fields.', ['errors' => $errors]);
$db = database();
function member_account_conflicts(PDO $db, int $id, string $username, string $email): array
{
    $errors = [];
    foreach (['username' => $username, 'email' => $email] as $column => $value) {
        $q = $db->prepare("SELECT user_id FROM users WHERE $column=? AND user_id<>?");
        $q->execute([$value, $id]);
        if ($q->fetch()) $errors[$column] = "This $column is already in use.";
    }
    return $errors;
}
try {
    $db->beginTransaction();
    $query = $db->prepare('SELECT password_hash FROM users WHERE user_id=? FOR UPDATE');
    $query->execute([$user['user_id']]);
    if (!password_verify($current, $query->fetchColumn())) {
        $db->rollBack();
        respond(422, false, 'Please check your current password.', ['errors' => ['current_password' => 'Your current password is incorrect.']]);
    }
    $conflicts = member_account_conflicts($db, $user['user_id'], $username, $email);
    if ($conflicts) {
        $db->rollBack();
        respond(409, false, 'Please check your username and email.', ['errors' => $conflicts]);
    }
    if ($password !== '') {
        $db->prepare('UPDATE users SET username=?,email=?,password_hash=? WHERE user_id=?')
            ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
    } else {
        $db->prepare('UPDATE users SET username=?,email=? WHERE user_id=?')->execute([$username, $email, $user['user_id']]);
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    if ($error instanceof PDOException && ($error->errorInfo[1] ?? null) === 1062) {
        respond(409, false, 'This username or email is already in use.', [
            'errors' => member_account_conflicts($db, $user['user_id'], $username, $email)
        ]);
    }
    throw $error;
}
if ($password !== '') {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$_SESSION['member_flash'] = 'Your account has been updated.';
respond(200, true, 'Your account has been updated.', ['user' => current_user(), 'csrf_token' => $_SESSION['csrf_token']]);
