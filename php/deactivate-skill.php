<?php
require_once __DIR__ . '/auth.php';
$user = require_member();
$data = post_data();
$id = filter_var($data['offering_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) respond(422, false, 'Choose a skill to hide.', ['errors' => ['offering_id' => 'Choose a skill to hide.']]);
$query = database()->prepare('SELECT offering_id FROM skill_offerings WHERE offering_id=? AND tutor_id=?');
$query->execute([$id, $user['user_id']]);
if (!$query->fetch()) respond(404, false, 'We couldn’t find this skill.');
database()->prepare('UPDATE skill_offerings SET is_active=0 WHERE offering_id=? AND tutor_id=?')->execute([$id, $user['user_id']]);
$_SESSION['member_flash'] = 'Your skill is now hidden from Explore.';
respond(200, true, 'Your skill is now hidden from Explore.');
