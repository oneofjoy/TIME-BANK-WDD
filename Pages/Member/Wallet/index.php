<?php
require __DIR__ . '/../page.php';
$data = member_read(fn() => ['wallet' => member_wallet($memberUser['user_id']), 'starter' => starter_pack_status($memberUser['user_id'])]);
member_start('Time Wallet', 'See your balance and how you’ve used your credits.');
if ($data === null) { member_data_error(); member_end(); return; }
$wallet = $data['wallet'];
?>
<section class="member-stats" aria-label="Time Credit summary">
    <?php member_stat('Available Time Credits', $wallet['available_credits'] ?? null, 'balance'); member_stat('Total Credits Earned', $wallet['earned'] ?? null, 'earned'); member_stat('Total Credits Spent', $wallet['spent'] ?? null, 'spent'); ?>
</section>
<?php member_starter_pack($data['starter']); ?>
<?php if ($wallet): ?><p class="member-caption"><?= member_number($wallet['held_credits']) ?> credits reserved for booked sessions. They count as spent only when the session is completed.</p><?php endif; ?>
<section class="member-panel"><h2>Transaction History</h2>
    <?php if (!$wallet): member_empty('Your wallet is unavailable.', 'Your balance and transactions could not be found. Please check again later.');
    elseif (!$wallet['transactions']): member_empty('No transactions yet.', 'Credits earned and spent will appear here.');
    else: ?>
    <div class="member-table-wrap"><table class="member-table"><caption class="member-sr-only">Time Credit transaction history</caption><thead><tr><th scope="col">Date</th><th scope="col">Description</th><th scope="col">Credit change</th></tr></thead><tbody>
        <?php foreach ($wallet['transactions'] as $transaction): ?><tr>
            <td><?= member_time($transaction['created_at']) ?> WIB</td>
            <td><strong><?= member_h(['earn' => 'Credits earned', 'hold' => 'Credits reserved', 'settle' => 'Credits used', 'refund' => 'Credits returned', 'bonus' => 'Extra credits', 'adjustment' => 'Balance update'][$transaction['transaction_type']] ?? 'Balance update') ?></strong><?php if ($transaction['note']): ?><span><?= member_h($transaction['note']) ?></span><?php endif; ?></td>
            <td><strong><?= (float)$transaction['available_delta'] > 0 ? '+' : '' ?><?= member_number($transaction['available_delta']) ?> available</strong>
                <?php if ((float)$transaction['held_delta'] !== 0.0): ?><span><?= (float)$transaction['held_delta'] > 0 ? '+' : '' ?><?= member_number($transaction['held_delta']) ?> reserved</span><?php endif; ?></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</section>
<?php member_end(); ?>
