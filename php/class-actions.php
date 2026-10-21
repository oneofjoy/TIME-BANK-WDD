<?php
// Shared transaction rules for joining and running one-to-one lessons.
require_once __DIR__ . '/auth.php';

function class_fail(int $status, string $message): never
{
    $db = database();
    if ($db->inTransaction()) $db->rollBack();
    respond($status, false, $message);
}

function class_id(array $data, string $key): int
{
    $id = filter_var($data[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) class_fail(422, 'Choose a valid class or session.');
    return $id;
}

function class_now(): DateTimeImmutable { return new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')); }
function class_cents(string $amount): int
{
    [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');
    return (int)$whole * 100 + (int)str_pad($fraction, 2, '0');
}
function class_amount(int $cents): string
{
    return ($cents < 0 ? '-' : '') . intdiv(abs($cents), 100) . '.' . str_pad((string)(abs($cents) % 100), 2, '0', STR_PAD_LEFT);
}

function class_lock_users(int $tutor, int $learner, bool $checkAvailability = true, ?int $actor = null): void
{
    $q = database()->prepare('SELECT user_id,role,account_status FROM users WHERE user_id IN (?,?) ORDER BY user_id FOR UPDATE');
    $q->execute([$tutor, $learner]);
    $users = $q->fetchAll();
    if (count($users) !== 2) class_fail(409, 'This class is no longer available.');
    foreach ($users as $user) if (($checkAvailability || (int)$user['user_id'] === $actor)
        && ($user['role'] !== 'member' || $user['account_status'] !== 'active')) {
        class_fail(409, 'One of the accounts is no longer available for this session.');
    }
}

function class_wallets(int $tutor, int $learner): array
{
    $q = database()->prepare('SELECT wallet_id,user_id,available_credits,held_credits FROM wallets WHERE user_id IN (?,?) ORDER BY user_id FOR UPDATE');
    $q->execute([$tutor, $learner]);
    $wallets = [];
    foreach ($q->fetchAll() as $wallet) $wallets[(int)$wallet['user_id']] = $wallet;
    if (count($wallets) !== 2) class_fail(409, 'A Time Wallet could not be found. Please try again later.');
    return $wallets;
}

function class_ledger(int $wallet, int $booking, string $type, int $available, int $held, string $note): void
{
    database()->prepare('INSERT INTO credit_transactions(wallet_id,booking_id,transaction_type,available_delta,held_delta,note) VALUES(?,?,?,?,?,?)')
        ->execute([$wallet, $booking, $type, class_amount($available), class_amount($held), $note]);
}
