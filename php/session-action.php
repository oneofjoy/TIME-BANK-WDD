<?php
require_once __DIR__ . '/class-actions.php';
$user = require_member();
$data = post_data();
$id = class_id($data, 'session_id');
$action = field($data, 'action');
if (!in_array($action, ['start','end','confirm','cancel'], true)) respond(422, false, 'Choose an available session action.');
$db = database();
$q = $db->prepare('SELECT b.booking_id,b.availability_id,b.offering_id,b.learner_id,o.tutor_id
    FROM sessions s JOIN bookings b ON b.booking_id=s.booking_id JOIN skill_offerings o ON o.offering_id=b.offering_id WHERE s.session_id=?');
$q->execute([$id]);
$identity = $q->fetch();
if (!$identity) respond(404, false, 'We couldn’t find this session.');
$tutor = (int)$identity['tutor_id'];
$learner = (int)$identity['learner_id'];
$teaching = $user['user_id'] === $tutor;
if (!$teaching && $user['user_id'] !== $learner) respond(403, false, 'This session isn’t yours.');
if (in_array($action, ['start','end'], true) && !$teaching) respond(403, false, 'Only the tutor can start or end this session.');
if ($action === 'confirm' && $teaching) respond(403, false, 'The learner needs to confirm that the session is complete.');
try {
    $db->beginTransaction();
    // Every booking action locks participants first, then class/time/booking/session,
    // then wallets. Repeated clicks cannot create a second credit transfer.
    class_lock_users($tutor, $learner, $action !== 'cancel', $user['user_id']);
    $db->prepare('SELECT offering_id FROM skill_offerings WHERE offering_id=? FOR UPDATE')->execute([$identity['offering_id']]);
    $q = $db->prepare('SELECT start_at,end_at FROM availability WHERE availability_id=? FOR UPDATE');
    $q->execute([$identity['availability_id']]);
    $time = $q->fetch();
    $start = $time['start_at'];
    $q = $db->prepare('SELECT booking_status,escrow_status,credits_used FROM bookings WHERE booking_id=? FOR UPDATE');
    $q->execute([$identity['booking_id']]);
    $booking = $q->fetch();
    $q = $db->prepare('SELECT session_status,started_at,ended_at,tutor_confirmed_at FROM sessions WHERE session_id=? FOR UPDATE');
    $q->execute([$id]);
    $session = $q->fetch();
    $now = class_now()->format('Y-m-d H:i:s');
    $cost = class_cents($booking['credits_used']);
    $message = '';
    if ($action === 'start') {
        if ($session['session_status'] === 'in_progress') $message = 'This session has already started.';
        else {
            if ($session['session_status'] !== 'scheduled' || $booking['booking_status'] !== 'accepted') class_fail(409, 'This session can’t be started.');
            if ($start > $now) class_fail(409, 'You can start this session at its scheduled time.');
            if ($time['end_at'] <= $now) class_fail(409, 'This session time has passed. Cancel it to return the credits, then choose another time.');
            $db->prepare("UPDATE sessions SET session_status='in_progress',started_at=? WHERE session_id=?")->execute([$now, $id]);
            $message = 'Your session has started.';
        }
    } elseif ($action === 'end') {
        if ($session['session_status'] === 'completed' || ($session['ended_at'] && $session['session_status'] === 'in_progress')) $message = 'This session has already ended.';
        else {
            if ($session['session_status'] !== 'in_progress' || !$session['started_at']) class_fail(409, 'Start the session before ending it.');
            $db->prepare('UPDATE sessions SET ended_at=?,tutor_confirmed_at=? WHERE session_id=?')->execute([$now, $now, $id]);
            $message = 'Session ended. The learner can now confirm it is complete.';
        }
    } elseif ($action === 'confirm') {
        if ($session['session_status'] === 'completed' && $booking['escrow_status'] === 'released') $message = 'This session is already complete.';
        else {
            if ($session['session_status'] !== 'in_progress' || !$session['ended_at'] || !$session['tutor_confirmed_at']) class_fail(409, 'The tutor needs to end the session first.');
            if ($booking['escrow_status'] !== 'held') class_fail(409, 'The credits for this session need to be checked. Please try again later.');
            $wallets = class_wallets($tutor, $learner);
            if ($cost <= 0 || class_cents($wallets[$learner]['held_credits']) < $cost) class_fail(409, 'The reserved credits could not be found. Please try again later.');
            $db->prepare('UPDATE wallets SET held_credits=held_credits-? WHERE wallet_id=?')->execute([class_amount($cost), $wallets[$learner]['wallet_id']]);
            $db->prepare('UPDATE wallets SET available_credits=available_credits+? WHERE wallet_id=?')->execute([class_amount($cost), $wallets[$tutor]['wallet_id']]);
            class_ledger($wallets[$learner]['wallet_id'], $identity['booking_id'], 'settle', 0, -$cost, 'Credits used for your completed class.');
            class_ledger($wallets[$tutor]['wallet_id'], $identity['booking_id'], 'earn', $cost, 0, 'Credits earned for teaching a completed class.');
            $db->prepare("UPDATE bookings SET booking_status='completed',escrow_status='released' WHERE booking_id=?")->execute([$identity['booking_id']]);
            $db->prepare("UPDATE sessions SET session_status='completed',learner_confirmed_at=? WHERE session_id=?")->execute([$now, $id]);
            $message = 'Session complete. The Time Credits have been sent to your tutor.';
        }
    } else {
        if ($session['session_status'] === 'cancelled') $message = 'This session is already cancelled.';
        else {
            if ($session['session_status'] !== 'scheduled' || $booking['escrow_status'] !== 'held') class_fail(409, 'You can only cancel a session before it starts.');
            $wallets = class_wallets($tutor, $learner);
            if ($cost <= 0 || class_cents($wallets[$learner]['held_credits']) < $cost) class_fail(409, 'The reserved credits could not be found. Please try again later.');
            $db->prepare('UPDATE wallets SET available_credits=available_credits+?,held_credits=held_credits-? WHERE wallet_id=?')
                ->execute([class_amount($cost), class_amount($cost), $wallets[$learner]['wallet_id']]);
            class_ledger($wallets[$learner]['wallet_id'], $identity['booking_id'], 'refund', $cost, -$cost, 'Credits returned for a cancelled class.');
            $db->prepare("UPDATE sessions SET session_status='cancelled' WHERE session_id=?")->execute([$id]);
            $db->prepare("UPDATE bookings SET booking_status='cancelled',escrow_status='refunded' WHERE booking_id=?")->execute([$identity['booking_id']]);
            $db->prepare('UPDATE availability SET slot_status=? WHERE availability_id=?')->execute([$start > $now ? 'available' : 'blocked', $identity['availability_id']]);
            $message = 'Session cancelled. The reserved credits have been returned.';
        }
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
$_SESSION['member_flash'] = $message;
respond(200, true, $message);
