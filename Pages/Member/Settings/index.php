<?php
require __DIR__ . '/../page.php';
$data = member_read(fn() => member_profile($memberUser['user_id']));
member_start('Account Settings', 'Keep your account details up to date.');
if ($data === null) { member_data_error(); member_end(); return; }
?>
<div class="member-settings-layout">
    <section class="member-panel"><h2>Account Information</h2><p><?= member_h($data['full_name']) ?></p><p class="member-caption">Update your name and introduction in <a href="<?= member_url('Pages/Member/Profile/') ?>">My Profile</a>.</p>
        <form class="member-form" data-member-form="update-account" novalidate>
            <p class="member-notice" data-form-message role="status" hidden></p>
            <?php member_field('account', 'username', 'Username', 'text', $data['username'], 'required minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" autocomplete="username" autocapitalize="none" spellcheck="false"');
            member_field('account', 'email', 'Email address', 'email', $data['email'], 'required maxlength="190" autocomplete="email"'); ?>
            <h3 class="member-form-section">Change Password</h3><p class="member-caption">Leave the new password fields empty to keep your current password.</p>
            <?php member_field('account', 'new_password', 'New password', 'password', '', 'minlength="8" autocomplete="new-password"');
            member_field('account', 'confirm_password', 'Confirm new password', 'password', '', 'minlength="8" autocomplete="new-password"'); ?>
            <h3 class="member-form-section">Confirm Your Changes</h3><p class="member-caption">Enter your current password to save account changes.</p>
            <?php member_field('account', 'current_password', 'Current password', 'password', '', 'required autocomplete="current-password"'); ?>
            <button class="site-button" type="submit">Save Changes</button>
        </form>
    </section>
    <section class="member-panel member-signout"><h2>Signing Out</h2><p>Log out when you’re finished.</p>
        <button class="site-button site-button--secondary" data-member-logout>Log Out</button>
    </section>
</div>
<?php member_end(); ?>
