<?php
require __DIR__ . '/../page.php';
$id = $memberUser['user_id'];
$initialRole = ($_GET['view'] ?? '') === 'teaching' ? 'teaching' : 'learning';
$data = member_read(fn() => ['sessions' => member_sessions($id), 'times' => member_teaching_times($id)]);
member_start('My Sessions', 'Your learning and teaching, together in one place.');
if ($data === null) { member_data_error(); member_end(); return; }
?>
<div class="member-session-filters">
    <div class="member-tabs" role="tablist" aria-label="Session role">
        <button role="tab" id="learning-tab" aria-controls="learning-panel" aria-selected="<?= $initialRole === 'learning' ? 'true' : 'false' ?>" tabindex="<?= $initialRole === 'learning' ? '0' : '-1' ?>" data-role-tab="learning">Learning</button>
        <button role="tab" id="teaching-tab" aria-controls="teaching-panel" aria-selected="<?= $initialRole === 'teaching' ? 'true' : 'false' ?>" tabindex="<?= $initialRole === 'teaching' ? '0' : '-1' ?>" data-role-tab="teaching">Teaching</button>
    </div>
    <div class="member-field"><label for="session-status">Booked session status</label><select id="session-status" data-session-status>
        <option value="all">All sessions</option><option value="upcoming">Upcoming</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option>
    </select></div>
</div>
<?php foreach (['learning' => 'learner_id', 'teaching' => 'tutor_id'] as $role => $column): ?>
<section role="tabpanel" id="<?= $role ?>-panel" aria-labelledby="<?= $role ?>-tab" data-role-panel="<?= $role ?>" <?= $role !== $initialRole ? 'hidden' : '' ?>>
    <?php if ($role === 'teaching'):
        $upcomingTimes = array_values(array_filter($data['times'], fn($time) => $time['is_future'] && $time['slot_status'] === 'available'));
        $pastTimes = array_reverse(array_values(array_filter($data['times'], fn($time) => !$time['is_future'] || $time['slot_status'] === 'blocked'))); ?>
    <section class="member-panel" aria-labelledby="teaching-times-heading">
        <div class="member-panel-heading"><h2 id="teaching-times-heading">Teaching Times</h2><a href="<?= member_url('Pages/Member/Skills/') ?>">Add Teaching Time →</a></div>
        <p class="member-caption">These times are waiting for a learner. A booked lesson will appear under Booked Sessions.</p>
        <?php member_teaching_time_list($upcomingTimes); ?>
        <?php if ($pastTimes): ?><details class="member-teaching-history" data-teaching-history><summary>Past &amp; Cancelled Times (<?= count($pastTimes) ?>)</summary>
            <?php member_teaching_time_list($pastTimes); ?>
        </details><?php endif; ?>
    </section>
    <?php endif; ?>
    <section class="member-panel" data-booked-sessions aria-labelledby="<?= $role ?>-booked-heading">
        <h2 id="<?= $role ?>-booked-heading">Booked Sessions</h2>
        <?php member_session_list(array_values(array_filter($data['sessions'], fn($session) => (int)$session[$column] === $id)), $id, $role, true); ?>
        <div class="member-empty" data-filter-empty hidden><h3>No sessions match this status.</h3><p>Choose another status to see your sessions.</p></div>
    </section>
</section>
<?php endforeach; ?>
<dialog class="member-dialog" id="session-action-dialog" aria-labelledby="session-action-title">
    <div class="member-dialog-heading"><h2 id="session-action-title">Session</h2><button class="member-close" data-close-dialog aria-label="Close session action">×</button></div>
    <p data-session-action-description></p>
    <form class="member-form" data-member-form="session-action" novalidate>
        <p class="member-notice" data-form-message role="status" hidden></p><input type="hidden" name="session_id"><input type="hidden" name="action">
        <div class="member-form-actions"><button class="site-button" type="submit">Continue</button><button type="button" class="member-text-button" data-close-dialog>Go Back</button></div>
    </form>
</dialog>
<?php member_cancel_teaching_time_dialog(); member_end(); ?>
