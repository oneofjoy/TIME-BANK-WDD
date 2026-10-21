<?php
require_once __DIR__ . '/class-actions.php';
$user = require_member();
$data = post_data();
$slot = class_id($data, 'availability_id');
$db = database();
$q = $db->prepare('SELECT a.offering_id,o.tutor_id FROM availability a JOIN skill_offerings o ON o.offering_id=a.offering_id WHERE a.availability_id=?');
$q->execute([$slot]);
$identity = $q->fetch();
if (!$identity) respond(404, false, 'This class time is no longer available.');
$tutor = (int)$identity['tutor_id'];
$learner = $user['user_id'];
if ($tutor === $learner) respond(403, false, 'You can’t join your own class.');
try {
    $db->beginTransaction();
    class_lock_users($tutor, $learner);
    $q = $db->prepare('SELECT is_active,verification_status FROM skill_offerings WHERE offering_id=? FOR UPDATE');
    $q->execute([$identity['offering_id']]);
    $offering = $q->fetch();
    if (!$offering || !$offering['is_active'] || $offering['verification_status'] === 'rejected') class_fail(409, 'This class is no longer available.');
    $q = $db->prepare('SELECT start_at,end_at,slot_status,TIMESTAMPDIFF(MINUTE,start_at,end_at) AS minutes FROM availability WHERE availability_id=? FOR UPDATE');
    $q->execute([$slot]);
    $time = $q->fetch();
    if ($time['slot_status'] !== 'available' || $time['start_at'] <= class_now()->format('Y-m-d H:i:s')) class_fail(409, 'Someone has booked this time, or it has already passed. Please choose another.');
    if (!in_array((int)$time['minutes'], [30,60,90,120], true)) class_fail(409, 'This class time needs to be updated by the tutor.');
    $q = $db->prepare("SELECT s.session_id FROM sessions s JOIN bookings b ON b.booking_id=s.booking_id
        JOIN skill_offerings o ON o.offering_id=b.offering_id JOIN availability a ON a.availability_id=b.availability_id
        WHERE (b.learner_id=? OR o.tutor_id=?) AND s.session_status IN ('scheduled','in_progress') AND a.start_at<? AND a.end_at>? LIMIT 1");
    $q->execute([$learner, $learner, $time['end_at'], $time['start_at']]);
    if ($q->fetch()) class_fail(409, 'You already have a session at this time. Choose another time.');
    $q = $db->prepare("SELECT a.availability_id FROM availability a JOIN skill_offerings o ON o.offering_id=a.offering_id
        WHERE o.tutor_id=? AND a.slot_status='available' AND a.start_at<? AND a.end_at>? LIMIT 1");
    $q->execute([$learner, $time['end_at'], $time['start_at']]);
    if ($q->fetch()) class_fail(409, 'You’ve offered to teach at this time. Choose another time.');
    $wallets = class_wallets($tutor, $learner);
    $cost = intdiv((int)$time['minutes'] * 100, 60);
    $wallet = $wallets[$learner];
    if (class_cents($wallet['available_credits']) < $cost) class_fail(409, 'You don’t have enough Time Credits for this class. You can earn credits by teaching.');
    $db->prepare("INSERT INTO bookings(offering_id,availability_id,learner_id,credits_used,booking_status,escrow_status) VALUES(?,?,?,?,'accepted','held')")
        ->execute([$identity['offering_id'], $slot, $learner, class_amount($cost)]);
    $booking = (int)$db->lastInsertId();
    $db->prepare('INSERT INTO sessions(booking_id) VALUES(?)')->execute([$booking]);
    $session = (int)$db->lastInsertId();
    $db->prepare("UPDATE availability SET slot_status='reserved' WHERE availability_id=?")->execute([$slot]);
    $db->prepare('UPDATE wallets SET available_credits=available_credits-?,held_credits=held_credits+? WHERE wallet_id=?')
        ->execute([class_amount($cost), class_amount($cost), $wallet['wallet_id']]);
    class_ledger($wallet['wallet_id'], $booking, 'hold', -$cost, $cost, 'Credits reserved for your class.');
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
respond(201, true, 'You’ve joined the class. Find it in My Sessions.', ['session_id' => $session]);
