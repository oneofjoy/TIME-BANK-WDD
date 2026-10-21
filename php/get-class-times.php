<?php
require_once __DIR__ . '/auth.php';
require_method('GET');
$id = filter_var($_GET['offering_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) respond(422, false, 'Choose a class.');
$q = database()->prepare("SELECT a.availability_id,a.start_at,a.end_at,TIMESTAMPDIFF(MINUTE,a.start_at,a.end_at) AS duration_minutes
    FROM availability a JOIN skill_offerings o ON o.offering_id=a.offering_id JOIN users u ON u.user_id=o.tutor_id
    WHERE o.offering_id=? AND o.is_active=1 AND o.verification_status<>'rejected' AND u.role='member' AND u.account_status='active'
        AND a.slot_status='available' AND a.start_at>? ORDER BY a.start_at LIMIT 100");
$q->execute([$id, (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s')]);
$times = $q->fetchAll();
foreach ($times as &$time) {
    $time['availability_id'] = (int)$time['availability_id'];
    $time['duration_minutes'] = (int)$time['duration_minutes'];
    $time['credit_cost'] = number_format($time['duration_minutes'] / 60, 2, '.', '');
    // Explicit offset avoids interpreting a Jakarta time as the browser's timezone.
    $time['start_at'] = str_replace(' ', 'T', $time['start_at']) . '+07:00';
    $time['end_at'] = str_replace(' ', 'T', $time['end_at']) . '+07:00';
}
unset($time);
respond(200, true, '', ['times' => $times]);
