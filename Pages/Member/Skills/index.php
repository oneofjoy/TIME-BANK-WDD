<?php
require __DIR__ . '/../page.php';
$id = $memberUser['user_id'];
$data = member_read(fn() => ['skills' => member_skills($id), 'catalog' => member_skill_catalog(), 'times' => member_open_teaching_times(member_teaching_times($id))]);
member_start('My Skills', 'Add and manage the skills you can teach.');
if ($data === null) { member_data_error(); member_end(); return; }
$nextTimes = [];
foreach ($data['times'] as $time) $nextTimes[$time['offering_id']] ??= $time;
?>
<div class="member-toolbar"><p><?= count($data['skills']) ?> <?= count($data['skills']) === 1 ? 'skill' : 'skills' ?></p>
    <button class="site-button" data-new-skill <?= !$data['catalog'] ? 'disabled' : '' ?>>Add Skill <span aria-hidden="true">+</span></button>
</div>
<p class="member-caption">Add a teaching time so someone can join your private class.</p>
<?php if (!$data['catalog']): ?><p class="member-caption">There are no skills to choose from yet. Please check again later.</p><?php endif; ?>
<?php if (!$data['skills']): ?><section class="member-panel"><?php member_empty('No skills offered yet.', 'Add a skill and tell others what they can learn with you.'); ?></section>
<?php else: ?><div class="member-skills-grid"><?php foreach ($data['skills'] as $skill): ?>
<article class="member-panel member-skill-card" id="skill-<?= member_h($skill['offering_id']) ?>">
    <span class="site-eyebrow"><?= member_h($skill['category_name']) ?></span><h2><?= member_h($skill['title']) ?></h2>
    <p class="member-skill-master"><?= member_h($skill['skill_name']) ?></p>
    <p class="member-bio"><?= member_h($skill['description'] ?: 'No description added.') ?></p>
    <div class="member-skill-meta"><span><?= member_h($skill['duration_minutes']) ?> min</span><span><?= member_number($skill['credit_cost']) ?> Time <?= (float)$skill['credit_cost'] === 1.0 ? 'Credit' : 'Credits' ?></span></div>
    <div class="member-skill-meta"><span class="member-badge"><?= $skill['is_active'] ? 'Active' : 'Inactive' ?></span><span class="member-caption"><?= member_h(['unverified' => 'Not reviewed yet', 'verified' => 'Reviewed', 'rejected' => 'Not approved'][$skill['verification_status']] ?? 'Not reviewed yet') ?></span></div>
    <?php $nextTime = $nextTimes[$skill['offering_id']] ?? null; ?>
    <div class="member-skill-times"><p class="member-caption"><?php if ($nextTime): ?>Next teaching time: <time><?= member_time($nextTime['start_at']) ?>–<?= member_time($nextTime['end_at'], 'H:i') ?> WIB</time><?php else: ?>No upcoming teaching times.<?php endif; ?></p>
        <a class="member-text-button" href="<?= member_url('Pages/Member/Sessions/?view=teaching' . ($nextTime ? '#teaching-time-' . $nextTime['availability_id'] : '#teaching-times-heading')) ?>">View Teaching Times →</a>
    </div>
    <div class="member-form-actions">
        <button class="member-text-button" data-edit-skill data-offering="<?= member_h(json_encode($skill, JSON_UNESCAPED_UNICODE)) ?>">Edit</button>
        <?php if ($skill['is_active'] && $skill['verification_status'] !== 'rejected'): ?><button class="member-text-button" data-add-class-time="<?= member_h($skill['offering_id']) ?>" data-class-title="<?= member_h($skill['title']) ?>">Add Teaching Time</button><?php endif; ?>
        <?php if ($skill['is_active']): ?><button class="member-text-button member-danger" data-deactivate-skill="<?= member_h($skill['offering_id']) ?>" data-offering-title="<?= member_h($skill['title']) ?>">Hide Skill</button><?php endif; ?>
    </div>
</article>
<?php endforeach; ?></div><?php endif; ?>
<dialog class="member-dialog" id="skill-editor" aria-labelledby="skill-editor-title">
    <div class="member-dialog-heading"><h2 id="skill-editor-title">Add Skill</h2><button class="member-close" data-close-dialog aria-label="Close skill form">×</button></div>
    <form class="member-form" data-member-form="save-skill" novalidate>
        <p class="member-notice" data-form-message role="status" hidden></p>
        <input type="hidden" name="offering_id">
        <div class="member-field"><label for="skill-skill_id">Skill</label><select id="skill-skill_id" name="skill_id" required aria-describedby="skill-skill_id-error">
            <option value="">Choose a skill</option>
            <?php foreach ($data['catalog'] as $catalogSkill): ?><option value="<?= member_h($catalogSkill['skill_id']) ?>"><?= member_h($catalogSkill['category_name'] . ' — ' . $catalogSkill['name']) ?></option><?php endforeach; ?>
        </select><p class="member-field-error" id="skill-skill_id-error" data-error-for="skill_id" hidden></p></div>
        <?php member_field('skill', 'title', 'Class title', 'text', '', 'required maxlength="180"');
        member_field('skill', 'description', 'Short description', 'textarea', '', 'maxlength="1000" rows="4"'); ?>
        <div class="member-field"><label for="skill-duration_minutes">Duration</label><select id="skill-duration_minutes" name="duration_minutes" aria-describedby="skill-duration_minutes-error">
            <?php foreach ([30, 60, 90, 120] as $minutes): ?><option value="<?= $minutes ?>" <?= $minutes === 60 ? 'selected' : '' ?>><?= $minutes ?> minutes</option><?php endforeach; ?>
        </select><p class="member-field-error" id="skill-duration_minutes-error" data-error-for="duration_minutes" hidden></p></div>
        <p class="member-caption" data-skill-cost>1 Time Credit · 1 hour = 1 credit.</p>
        <p class="member-caption">A new duration applies to teaching times you add next. Existing times stay the same.</p>
        <div class="member-field"><label class="member-checkbox"><input type="checkbox" name="is_active" checked> Show this skill in Explore</label><p class="member-field-error" data-error-for="is_active" hidden></p></div>
        <div class="member-form-actions"><button class="site-button" type="submit">Save Skill</button><button class="member-text-button" type="button" data-close-dialog>Cancel</button></div>
    </form>
</dialog>
<dialog class="member-dialog" id="deactivate-skill" aria-labelledby="deactivate-title">
    <div class="member-dialog-heading"><h2 id="deactivate-title">Hide this skill?</h2><button class="member-close" data-close-dialog aria-label="Close confirmation">×</button></div>
    <p data-deactivate-description></p><p class="member-caption">People won’t see this skill in Explore. Your booked sessions stay the same. You can show it again by editing the skill.</p>
    <form class="member-form" data-member-form="deactivate-skill" novalidate>
        <p class="member-notice" data-form-message role="status" hidden></p><input type="hidden" name="offering_id">
        <div class="member-form-actions"><button class="site-button" type="submit">Hide Skill</button><button type="button" class="member-text-button" data-close-dialog>Cancel</button></div>
    </form>
</dialog>
<dialog class="member-dialog" id="class-time-editor" aria-labelledby="class-time-title">
    <div class="member-dialog-heading"><h2 id="class-time-title">Add Teaching Time</h2><button class="member-close" data-close-dialog aria-label="Close teaching time form">×</button></div>
    <p data-time-class-title></p><p class="member-caption">One learner can book each time. All times are in Jakarta time (WIB). The end time follows your class duration.</p>
    <form class="member-form" data-member-form="add-class-time" novalidate>
        <p class="member-notice" data-form-message role="status" hidden></p><input type="hidden" name="offering_id">
        <?php member_field('class-time', 'start_at', 'Date and start time (WIB)', 'datetime-local', '', 'required'); ?>
        <div class="member-form-actions"><button class="site-button" type="submit">Add Time</button><button type="button" class="member-text-button" data-close-dialog>Cancel</button></div>
    </form>
</dialog>
<?php member_end(); ?>
