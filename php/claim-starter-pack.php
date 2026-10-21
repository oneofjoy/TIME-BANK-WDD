<?php
require_once __DIR__ . '/class-actions.php';
require_once __DIR__ . '/starter-pack.php';
$user = require_member();
post_data();
$db = database();
try {
    $db->beginTransaction();
    $q = $db->prepare('SELECT role,account_status FROM users WHERE user_id=? FOR UPDATE');
    $q->execute([$user['user_id']]);
    $account = $q->fetch();
    if (!$account || $account['role'] !== 'member' || $account['account_status'] !== 'active') class_fail(403, 'Please log in with an active member account.');
    $q = $db->prepare('SELECT wallet_id FROM wallets WHERE user_id=? FOR UPDATE');
    $q->execute([$user['user_id']]);
    $wallet = $q->fetchColumn();
    if (!$wallet) class_fail(409, 'Your Time Wallet couldn’t be found. Please try again later.');
    // Account and wallet locks serialize claims from separate browser sessions.
    if (starter_pack_status($user['user_id'])['received']) class_fail(409, 'You have already received your welcome credits. Earn more by teaching a skill.');
    $db->prepare('UPDATE wallets SET available_credits=available_credits+? WHERE wallet_id=?')->execute([STARTER_PACK_CREDITS, $wallet]);
    $db->prepare("INSERT INTO credit_transactions(wallet_id,transaction_type,available_delta,held_delta,note) VALUES(?,'bonus',?,0,?)")
        ->execute([$wallet, STARTER_PACK_CREDITS, STARTER_PACK_NOTE]);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
$_SESSION['member_flash'] = 'Your starter pack has been added. Enjoy your first lesson!';
respond(201, true, 'Your starter pack has been added.', ['starter_pack' => starter_pack_status($user['user_id'])]);
