<?php
require_once __DIR__ . '/database.php';

const STARTER_PACK_CREDITS = 3;
const STARTER_PACK_NOTE = 'TimeBank Starter Pack';
const LEGACY_WELCOME_NOTE = 'Welcome to TimeBank. Your first Time Credit is on us.';

function starter_pack_status(int $id): array
{
    $q = database()->prepare('SELECT wallet_id,available_credits FROM wallets WHERE user_id=?');
    $q->execute([$id]);
    $wallet = $q->fetch();
    $received = false;
    $credits = null;
    if ($wallet) {
        // The old registration bonus also counts as the one-time welcome gift.
        // Historical daily credits remain untouched and are not a welcome marker.
        $q = database()->prepare("SELECT COUNT(*) AS gifts,SUM(available_delta) AS credits
            FROM credit_transactions WHERE wallet_id=? AND transaction_type='bonus' AND note IN (?,?)");
        $q->execute([$wallet['wallet_id'], STARTER_PACK_NOTE, LEGACY_WELCOME_NOTE]);
        $gift = $q->fetch();
        $received = (int)$gift['gifts'] > 0;
        if ($received) $credits = $gift['credits'];
    }
    return ['reward' => STARTER_PACK_CREDITS, 'received' => $received,
        'received_credits' => $credits, 'can_claim' => (bool)$wallet && !$received,
        'wallet_available' => (bool)$wallet, 'available_credits' => $wallet ? $wallet['available_credits'] : null];
}
