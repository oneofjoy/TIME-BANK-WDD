<?php
require_once __DIR__ . '/auth.php';
$user = require_member();
$data = post_data();
$name = trim(field($data, 'full_name'));
$bio = trim(field($data, 'bio'));
$location = trim(field($data, 'location'));
$errors = [];
$changeAvatar = array_key_exists('avatar_id', $data);
$avatarId = field($data, 'avatar_id');
$avatars = avatar_options();
if ($changeAvatar && $avatarId !== 'initials' && !array_key_exists($avatarId, $avatars)) {
    $errors['avatar_id'] = 'Choose one of the profile pictures shown here.';
}
if ($name === '' || mb_strlen($name) > 120 || preg_match('/[\x00-\x1F\x7F]/u', $name)) {
    $errors['full_name'] = 'Enter your full name (up to 120 characters).';
}
if (mb_strlen($bio) > 1000 || str_contains($bio, "\0")) $errors['bio'] = 'Use up to 1,000 characters.';
if (mb_strlen($location) > 120 || preg_match('/[\x00-\x1F\x7F]/u', $location)) $errors['location'] = 'Use up to 120 characters.';
if ($errors) respond(422, false, 'Please check the highlighted fields.', ['errors' => $errors]);
$db = database();
try {
    $db->beginTransaction();
    $db->prepare('UPDATE users SET full_name=? WHERE user_id=?')->execute([$name, $user['user_id']]);
    $db->prepare('INSERT INTO user_profiles(user_id,bio,location) VALUES(?,?,?)
        ON DUPLICATE KEY UPDATE bio=VALUES(bio),location=VALUES(location)')->execute([$user['user_id'], $bio, $location]);
    if ($changeAvatar) {
        $path = $avatarId === 'initials' ? null : $avatars[$avatarId]['path'];
        $db->prepare('UPDATE user_profiles SET avatar_path=? WHERE user_id=?')->execute([$path, $user['user_id']]);
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
$_SESSION['member_flash'] = 'Your profile has been updated.';
respond(200, true, 'Your profile has been updated.', ['user' => current_user()]);
