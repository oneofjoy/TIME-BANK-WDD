<?php
require __DIR__ . '/../page.php';
$id = $memberUser['user_id'];
$data = member_read(fn() => ['profile' => member_profile($id), 'stats' => member_stats($id), 'skills' => member_skills($id)]);
member_start('My Profile', 'Your details and the skills you teach.');
if ($data === null) { member_data_error(); member_end(); return; }
$profile = $data['profile'];
?>
<section class="member-panel member-profile-summary">
    <?php member_avatar($profile); ?>
    <div><h2><?= member_h($profile['full_name']) ?></h2><p>@<?= member_h($profile['username']) ?></p><p><?= member_h($profile['email']) ?></p></div>
    <button class="site-button site-button--secondary" data-open-dialog="edit-profile">Edit Profile</button>
</section>
<section class="member-stats member-stats--two" aria-label="Completed sessions">
    <?php member_stat('Sessions taught', $data['stats']['taught'], 'taught'); member_stat('Sessions learned', $data['stats']['learned'], 'learned'); ?>
</section>
<div class="member-columns">
    <section class="member-panel"><h2>About Me</h2><p class="member-bio"><?= member_h($profile['bio'] ?: 'Tell others a little about yourself.') ?></p>
        <dl class="member-facts"><div><dt>Location</dt><dd><?= member_h($profile['location'] ?: 'Not added') ?></dd></div></dl>
    </section>
    <section class="member-panel"><div class="member-panel-heading"><h2>Skills Offered</h2><a href="<?= member_url('Pages/Member/Skills/') ?>">Manage →</a></div>
        <?php if (!$data['skills']): member_empty('No skills offered yet.', 'Add your first skill in My Skills.'); else: ?>
        <ul class="member-slot-list"><?php foreach ($data['skills'] as $skill): ?><li><strong><?= member_h($skill['title']) ?></strong><span><?= member_h($skill['category_name']) ?> · <?= $skill['is_active'] ? 'Active' : 'Inactive' ?></span></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </section>
</div>
<dialog class="member-dialog" id="edit-profile" aria-labelledby="edit-profile-title">
    <div class="member-dialog-heading"><h2 id="edit-profile-title">Edit Profile</h2><button class="member-close" data-close-dialog aria-label="Close edit profile">×</button></div>
    <form class="member-form" data-member-form="update-profile" novalidate>
        <p class="member-notice" data-form-message role="status" hidden></p>
        <fieldset class="member-avatar-picker"><legend>Profile picture</legend>
            <div class="member-avatar-preview" data-avatar-preview data-avatar-initial="<?= member_h(strtoupper(substr($profile['username'], 0, 1))) ?>"><?php member_avatar($profile); ?></div>
            <div class="member-avatar-options">
                <label class="member-avatar-option"><input type="radio" name="avatar_id" value="initials" <?= avatar_id($profile['avatar_path']) === 'initials' ? 'checked' : '' ?>><span class="member-avatar-choice" aria-hidden="true"><?= member_h(strtoupper(substr($profile['username'], 0, 1))) ?></span><span>Initials</span></label>
                <?php foreach (avatar_options() as $id => $option): ?><label class="member-avatar-option"><input type="radio" name="avatar_id" value="<?= member_h($id) ?>" data-avatar-src="<?= member_h($option['path']) ?>" <?= avatar_id($profile['avatar_path']) === $id ? 'checked' : '' ?>><img class="member-avatar-choice" src="<?= member_url($option['path']) ?>" width="56" height="56" alt=""><span><?= member_h($option['label']) ?></span></label><?php endforeach; ?>
            </div><p class="member-field-error" data-error-for="avatar_id" hidden></p>
        </fieldset>
        <?php member_field('profile', 'full_name', 'Full name', 'text', $profile['full_name'], 'required maxlength="120" autocomplete="name"');
        member_field('profile', 'bio', 'About you', 'textarea', $profile['bio'], 'maxlength="1000" rows="4"');
        member_field('profile', 'location', 'Location', 'text', $profile['location'], 'maxlength="120" autocomplete="address-level2"'); ?>
        <div class="member-form-actions"><button class="site-button" type="submit">Save Changes</button><button class="member-text-button" type="button" data-close-dialog>Cancel</button></div>
    </form>
</dialog>
<?php member_end(); ?>
