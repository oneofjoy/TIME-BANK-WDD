<?php
require_once __DIR__ . '/auth.php';
$user = require_member();
$data = post_data();
$id = $data['offering_id'] ?? null;
if ($id === '') $id = null;
$skill = filter_var($data['skill_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$duration = $data['duration_minutes'] ?? null;
$active = $data['is_active'] ?? null;
$title = trim(field($data, 'title'));
$description = trim(field($data, 'description'));
$errors = [];
if ($id !== null && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    $errors['offering_id'] = 'Choose a skill to edit.';
}
if (!$skill) $errors['skill_id'] = 'Choose a skill from the catalog.';
if (!is_int($duration) || !in_array($duration, [30, 60, 90, 120], true)) $errors['duration_minutes'] = 'Choose 30, 60, 90, or 120 minutes.';
if (!is_bool($active)) $errors['is_active'] = 'Choose whether to show this skill in Explore.';
if ($title === '' || mb_strlen($title) > 180 || preg_match('/[\x00-\x1F\x7F]/u', $title)) $errors['title'] = 'Enter a title (up to 180 characters).';
if (mb_strlen($description) > 1000 || str_contains($description, "\0")) $errors['description'] = 'Use up to 1,000 characters.';
if ($errors) respond(422, false, 'Please check the highlighted fields.', ['errors' => $errors]);
$db = database();
$query = $db->prepare('SELECT skill_id FROM skills WHERE skill_id=?');
$query->execute([$skill]);
if (!$query->fetch()) respond(422, false, 'Choose a valid skill.', ['errors' => ['skill_id' => 'This skill is not available.']]);
$cost = number_format($duration / 60, 2, '.', '');
try {
    $db->beginTransaction();
    if ($id !== null) {
        $query = $db->prepare('SELECT offering_id FROM skill_offerings WHERE offering_id=? AND tutor_id=? FOR UPDATE');
        $query->execute([$id, $user['user_id']]);
        if (!$query->fetch()) {
            $db->rollBack();
            respond(404, false, 'We couldn’t find this skill.');
        }
        $db->prepare('UPDATE skill_offerings SET skill_id=?,title=?,description=?,duration_minutes=?,credit_cost=?,is_active=?
            WHERE offering_id=? AND tutor_id=?')->execute([$skill, $title, $description, $duration, $cost, (int)$active, $id, $user['user_id']]);
    } else {
        $db->prepare("INSERT INTO skill_offerings(tutor_id,skill_id,title,description,duration_minutes,credit_cost,is_active,verification_status)
            VALUES(?,?,?,?,?,?,?,'unverified')")->execute([$user['user_id'], $skill, $title, $description, $duration, $cost, (int)$active]);
        $id = (int)$db->lastInsertId();
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
$_SESSION['member_flash'] = 'Your skill has been saved.';
respond(200, true, 'Your skill has been saved.', ['offering_id' => (int)$id]);
