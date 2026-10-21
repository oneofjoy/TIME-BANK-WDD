<?php
// Small read helpers shared by the protected Member pages. No writes here.
require_once __DIR__ . '/database.php';

function member_rows(string $sql, array $parameters = []): array
{
    $query = database()->prepare($sql);
    $query->execute($parameters);
    return $query->fetchAll();
}

function member_profile(int $id): array
{
    return member_rows('SELECT u.full_name, u.username, u.email, p.bio, p.location, p.avatar_path
        FROM users u LEFT JOIN user_profiles p ON p.user_id=u.user_id WHERE u.user_id=?', [$id])[0];
}

function member_stats(int $id): array
{
    return member_rows("SELECT
        COALESCE(SUM(CASE WHEN o.tutor_id=? THEN 1 ELSE 0 END),0) AS taught,
        COALESCE(SUM(CASE WHEN b.learner_id=? THEN 1 ELSE 0 END),0) AS learned
        FROM sessions s JOIN bookings b ON b.booking_id=s.booking_id
        JOIN skill_offerings o ON o.offering_id=b.offering_id
        WHERE s.session_status='completed' AND (o.tutor_id=? OR b.learner_id=?)", [$id, $id, $id, $id])[0];
}

function member_sessions(int $id): array
{
    return member_rows('SELECT s.session_id, s.session_status, s.started_at, s.ended_at, s.tutor_confirmed_at, s.learner_confirmed_at,
        o.title, TIMESTAMPDIFF(MINUTE,a.start_at,a.end_at) AS duration_minutes,
        b.credits_used, b.learner_id, o.tutor_id, t.full_name AS tutor_name,
        l.full_name AS learner_name, a.start_at, a.end_at
        FROM sessions s JOIN bookings b ON b.booking_id=s.booking_id
        JOIN skill_offerings o ON o.offering_id=b.offering_id
        JOIN availability a ON a.availability_id=b.availability_id
        JOIN users t ON t.user_id=o.tutor_id JOIN users l ON l.user_id=b.learner_id
        WHERE b.learner_id=? OR o.tutor_id=? ORDER BY a.start_at, s.session_id', [$id, $id]);
}

function member_upcoming(array $sessions): array
{
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
    return array_values(array_filter($sessions, static fn($session) =>
        $session['session_status'] === 'in_progress' || ($session['session_status'] === 'scheduled'
        && new DateTimeImmutable($session['end_at'], new DateTimeZone('Asia/Jakarta')) >= $now)));
}

function member_skills(int $id): array
{
    return member_rows('SELECT o.offering_id, o.skill_id, o.title, o.description, o.duration_minutes,
        o.credit_cost, o.is_active, o.verification_status, sk.name AS skill_name, c.name AS category_name
        FROM skill_offerings o JOIN skills sk ON sk.skill_id=o.skill_id
        JOIN skill_categories c ON c.category_id=sk.category_id
        WHERE o.tutor_id=? ORDER BY o.created_at DESC, o.offering_id DESC', [$id]);
}

function member_skill_catalog(): array
{
    return member_rows('SELECT sk.skill_id, sk.name, c.name AS category_name
        FROM skills sk JOIN skill_categories c ON c.category_id=sk.category_id
        ORDER BY c.category_id, sk.name');
}

function member_teaching_times(int $id): array
{
    // An offered time is not a session until a learner books it. Keep past
    // unbooked times for the teacher without manufacturing bookings or sessions.
    return member_rows("SELECT a.availability_id, a.offering_id, a.start_at, a.end_at, a.slot_status,
        o.title, o.is_active, o.verification_status,
        TIMESTAMPDIFF(MINUTE,a.start_at,a.end_at) AS duration_minutes,
        a.start_at>? AS is_future
        FROM availability a
        JOIN skill_offerings o ON o.offering_id=a.offering_id
        WHERE o.tutor_id=? AND a.slot_status IN ('available','blocked')
            AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.availability_id=a.availability_id
                AND b.booking_status NOT IN ('cancelled','rejected'))
        ORDER BY a.start_at, a.availability_id",
        [(new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s'), $id]);
}

function member_open_teaching_times(array $times): array
{
    return array_values(array_filter($times, static fn($time) =>
        $time['is_future'] && $time['slot_status'] === 'available' && $time['is_active'] && $time['verification_status'] !== 'rejected'));
}

function member_wallet(int $id): ?array
{
    $wallet = member_rows('SELECT wallet_id, available_credits, held_credits FROM wallets WHERE user_id=?', [$id])[0] ?? null;
    if (!$wallet) return null;
    $totals = member_rows("SELECT
        COALESCE(SUM(CASE WHEN transaction_type='earn' THEN GREATEST(available_delta,0) ELSE 0 END),0) AS earned,
        COALESCE(SUM(CASE WHEN transaction_type='settle' THEN GREATEST(-(available_delta+held_delta),0) ELSE 0 END),0) AS spent
        FROM credit_transactions WHERE wallet_id=?", [$wallet['wallet_id']])[0];
    $wallet += $totals;
    $wallet['transactions'] = member_rows('SELECT transaction_type, available_delta, held_delta, note, created_at
        FROM credit_transactions WHERE wallet_id=? ORDER BY created_at DESC, transaction_id DESC', [$wallet['wallet_id']]);
    return $wallet;
}
