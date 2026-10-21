<?php
require __DIR__ . '/../page.php';
$id = $memberUser['user_id'];
$data = member_read(fn() => ['stats' => member_stats($id), 'upcoming' => array_slice(member_upcoming(member_sessions($id)), 0, 3), 'starter' => starter_pack_status($id)]);
member_start('Dashboard', 'Your sessions and Time Credits at a glance.');
if ($data === null) { member_data_error(); member_end(); return; }
?>
<section class="member-welcome"><div><span class="site-eyebrow">Good to see you again</span><h2>Hi, <?= member_h($memberUser['username']) ?>.</h2>
    <p>Make time to learn something new, or share a skill with someone else.</p></div>
    <a class="site-button" href="<?= member_url('Pages/Explore/explore.html') ?>">Explore Skills <span aria-hidden="true">↗</span></a>
</section>
<section class="member-stats" aria-label="Your activity">
    <?php member_stat('Sessions taught', $data['stats']['taught'], 'taught'); member_stat('Sessions learned', $data['stats']['learned'], 'learned'); member_stat('Available Time Credits', $memberUser['available_credits'], 'balance'); ?>
</section>
<?php member_starter_pack($data['starter']); ?>
<section class="member-panel"><div class="member-panel-heading"><h2>Upcoming Sessions</h2><a href="<?= member_url('Pages/Member/Sessions/') ?>">View all →</a></div>
    <?php member_session_list($data['upcoming'], $id, 'dashboard'); ?>
</section>
<section class="member-panel member-quick-link"><div><h2>What could you teach?</h2><p>Add a skill you’d like to teach.</p></div>
    <a class="site-button site-button--secondary" href="<?= member_url('Pages/Member/Skills/') ?>">My Skills →</a>
</section>
<?php member_end(); ?>
