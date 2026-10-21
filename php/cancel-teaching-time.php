<?php
require_once __DIR__ . '/class-actions.php';
$user = require_member();
$data = post_data();
$id = class_id($data, 'availability_id');
$db = database();
$q = $db->prepare('SELECT a.offering_id,o.tutor_id FROM availability a JOIN skill_offerings o ON o.offering_id=a.offering_id WHERE a.availability_id=?');
$q->execute([$id]);
$identity = $q->fetch();
if (!$identity) respond(404, false, 'This teaching time could not be found.');
if ((int)$identity['tutor_id'] !== $user['user_id']) respond(403, false, 'You can only cancel your own teaching times.');
try {
    $db->beginTransaction();
    // Match booking's account -> skill -> time lock order. A booking and a
    // cancellation racing for this time cannot both succeed.
    $q = $db->prepare('SELECT role,account_status FROM users WHERE user_id=? FOR UPDATE');
    $q->execute([$user['user_id']]);
    $account = $q->fetch();
    if (!$account || $account['role'] !== 'member' || $account['account_status'] !== 'active') class_fail(403, 'Please log in with an active member account.');
    $db->prepare('SELECT offering_id FROM skill_offerings WHERE offering_id=? FOR UPDATE')->execute([$identity['offering_id']]);
    $q = $db->prepare('SELECT start_at,slot_status FROM availability WHERE availability_id=? FOR UPDATE');
    $q->execute([$id]);
    $time = $q->fetch();
    if (!$time) class_fail(404, 'This teaching time could not be found.');
    $q = $db->prepare("SELECT booking_id FROM bookings WHERE availability_id=? AND booking_status NOT IN ('cancelled','rejected') LIMIT 1");
    $q->execute([$id]);
    if ($q->fetch() || $time['slot_status'] === 'reserved') class_fail(409, 'This time has been booked. Open My Sessions to cancel the session instead.');
    if ($time['slot_status'] === 'blocked') $message = 'This teaching time is already cancelled.';
    else {
        if ($time['start_at'] <= class_now()->format('Y-m-d H:i:s')) class_fail(409, 'This time has already passed. You can add a new teaching time from My Skills.');
        $db->prepare("UPDATE availability SET slot_status='blocked' WHERE availability_id=?")->execute([$id]);
        $message = 'Teaching time cancelled. It is no longer available to book.';
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
$_SESSION['member_flash'] = $message;
respond(200, true, $message);
