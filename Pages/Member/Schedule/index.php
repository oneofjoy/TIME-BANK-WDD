<?php
require __DIR__ . '/../page.php';
$id = $memberUser['user_id'];
$data = member_read(fn() => ['sessions' => member_upcoming(member_sessions($id)), 'times' => member_open_teaching_times(member_teaching_times($id))]);
member_start('My Schedule', 'See your upcoming sessions. All times are in Jakarta time (WIB).');
if ($data === null) { member_data_error(); member_end(); return; }
?>
<?php foreach (['learning' => 'learner_id', 'teaching' => 'tutor_id'] as $role => $column): ?>
<section class="member-panel"><h2>Upcoming <?= ucfirst($role) ?></h2>
    <?php member_session_list(array_values(array_filter($data['sessions'], fn($session) => (int)$session[$column] === $id)), $id, 'schedule-' . $role); ?>
</section>
<?php endforeach; ?>
<section class="member-panel"><h2>Your Available Teaching Times</h2>
    <p class="member-caption">Add a teaching time from <a href="<?= member_url('Pages/Member/Skills/') ?>">My Skills</a>.</p>
    <?php member_teaching_time_list($data['times'], true); ?>
</section>
<?php member_cancel_teaching_time_dialog(); member_end(); ?>
