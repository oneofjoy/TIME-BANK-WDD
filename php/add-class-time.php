<?php
require_once __DIR__ . '/class-actions.php';
$user = require_member();
$data = post_data();
$offering = class_id($data, 'offering_id');
$value = field($data, 'start_at');
$start = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Asia/Jakarta'));
if (!$start || $start->format('Y-m-d\TH:i') !== $value || $start <= class_now()) {
    respond(422, false, 'Choose a future date and time in Jakarta time (WIB).', ['errors' => ['start_at' => 'Choose a future date and time.']]);
}
$db = database();
try {
    $db->beginTransaction();
    $q = $db->prepare('SELECT role,account_status FROM users WHERE user_id=? FOR UPDATE');
    $q->execute([$user['user_id']]);
    $account = $q->fetch();
    if (!$account || $account['role'] !== 'member' || $account['account_status'] !== 'active') class_fail(403, 'Please log in with an active member account.');
    $q = $db->prepare('SELECT duration_minutes,is_active,verification_status FROM skill_offerings WHERE offering_id=? AND tutor_id=? FOR UPDATE');
    $q->execute([$offering, $user['user_id']]);
    $skill = $q->fetch();
    if (!$skill) class_fail(404, 'We couldn’t find your class.');
    if (!$skill['is_active'] || $skill['verification_status'] === 'rejected') class_fail(409, 'Make this skill available before adding a time.');
    if (!in_array((int)$skill['duration_minutes'], [30,60,90,120], true)) class_fail(409, 'Edit your class and choose a duration of 30, 60, 90, or 120 minutes.');
    $end = $start->modify('+' . (int)$skill['duration_minutes'] . ' minutes');
    $q = $db->prepare("SELECT a.availability_id FROM availability a JOIN skill_offerings o ON o.offering_id=a.offering_id
        WHERE o.tutor_id=? AND a.slot_status IN ('available','reserved') AND a.start_at<? AND a.end_at>? LIMIT 1");
    $q->execute([$user['user_id'], $end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]);
    if ($q->fetch()) class_fail(409, 'You already have a class or teaching time then. Choose another time.');
    $q = $db->prepare("SELECT s.session_id FROM sessions s JOIN bookings b ON b.booking_id=s.booking_id JOIN availability a ON a.availability_id=b.availability_id
        WHERE b.learner_id=? AND s.session_status IN ('scheduled','in_progress') AND a.start_at<? AND a.end_at>? LIMIT 1");
    $q->execute([$user['user_id'], $end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]);
    if ($q->fetch()) class_fail(409, 'You have a learning session then. Choose another time.');
    $q = $db->prepare("SELECT availability_id FROM availability WHERE offering_id=? AND start_at=? AND end_at=? AND slot_status='blocked' FOR UPDATE");
    $q->execute([$offering, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
    $previous = $q->fetchColumn();
    if ($previous) {
        $q = $db->prepare("SELECT booking_id FROM bookings WHERE availability_id=? AND booking_status NOT IN ('cancelled','rejected') LIMIT 1");
        $q->execute([$previous]);
        if ($q->fetch()) class_fail(409, 'This time already has a booked session. Choose another time.');
        // Explicitly adding the exact same time again restores the cancelled slot,
        // preserving its ID/history and the existing unique constraint.
        $db->prepare("UPDATE availability SET slot_status='available' WHERE availability_id=?")->execute([$previous]);
    } else {
        $db->prepare('INSERT INTO availability(offering_id,start_at,end_at) VALUES(?,?,?)')
            ->execute([$offering, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    if ($error instanceof PDOException && ($error->errorInfo[1] ?? null) === 1062) {
        respond(409, false, 'This teaching time already exists. Choose another time.');
    }
    throw $error;
}
$_SESSION['member_flash'] = 'Your class time is ready for someone to book.';
respond(201, true, 'Your class time has been added.');
