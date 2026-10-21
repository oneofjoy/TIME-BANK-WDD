<?php
require_once __DIR__ . '/../../php/auth.php';
require_once __DIR__ . '/../../php/member-data.php';
require_once __DIR__ . '/../../php/starter-pack.php';
$memberUser = require_member(true);

function member_h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function member_url(string $path): string { return member_h(project_path() . $path); }
function member_asset(string $path): string
{
    // A new URL whenever content changes, even if a browser keeps older assets cached.
    $version = substr(hash_file('sha256', __DIR__ . '/../../' . $path), 0, 12);
    return member_url($path . '?v=' . $version);
}
function member_read(callable $read): ?array
{
    try { return $read(); }
    catch (Throwable $error) { error_log('TimeBank Member: ' . $error->getMessage()); return null; }
}
function member_number($value): string
{
    return $value === null ? 'Unavailable' : member_h(rtrim(rtrim(number_format((float)$value, 2, '.', ','), '0'), '.'));
}
function member_time(string $value, string $format = 'D, d M Y · H:i'): string
{
    return member_h((new DateTimeImmutable($value, new DateTimeZone('Asia/Jakarta')))->format($format));
}
function member_start(string $title, string $description): void
{
    $base = member_url('');
    $flash = $_SESSION['member_flash'] ?? null;
    unset($_SESSION['member_flash']);
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= member_h($title) ?> — TimeBank</title>
    <link rel="icon" href="<?= $base ?>Assets/Logo/timebank-icon.png">
    <link data-site-fonts rel="stylesheet" media="print" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= member_asset('css/global.css') ?>">
    <link rel="stylesheet" href="<?= member_asset('Shared/Navbar/navbar.css') ?>">
    <link rel="stylesheet" href="<?= member_asset('Shared/Footer/footer.css') ?>">
    <link rel="stylesheet" href="<?= member_asset('Pages/Member/member.css') ?>">
    <script src="<?= member_asset('js/include.js') ?>" defer></script>
    <script src="<?= member_asset('js/auth.js') ?>" defer></script>
    <script src="<?= member_asset('Shared/Navbar/navbar.js') ?>" defer></script>
    <script src="<?= member_asset('Pages/Member/member.js') ?>" defer></script>
</head>
<body class="member-page">
    <a class="site-skip-link" href="#main-content">Skip to content</a>
    <div id="navbar-mount"></div>
    <main id="main-content" class="site-container member-main" data-server-time="<?= member_h((new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format(DATE_ATOM)) ?>">
        <header class="member-heading"><span class="site-eyebrow">Your TimeBank</span>
            <h1><?= member_h($title) ?></h1><p><?= member_h($description) ?></p>
        </header>
        <?php if (is_string($flash)): ?><p class="member-notice member-notice--success" role="status"><?= member_h($flash) ?></p><?php endif; ?>
        <p class="member-notice member-status" role="status" hidden></p>
    <?php
}
function member_end(): void { ?></main><div id="footer-mount"></div></body></html><?php }
function member_starter_pack(array $status): void
{
    ?><section class="member-starter member-panel" data-starter-pack data-starter-state="<?= member_h(json_encode($status)) ?>" aria-labelledby="starter-pack-title">
        <div><h2 id="starter-pack-title">Starter Pack</h2>
            <p><?= $status['received'] ? 'You’ve received your one-time welcome credits.' : 'Start learning with 3 Time Credits. Available once per account.' ?></p>
            <p class="member-caption" data-starter-description><?= !$status['wallet_available'] ? 'Your Time Wallet couldn’t be loaded. Please try again later.' : ($status['received'] ? 'Earn more credits by sharing a skill with someone else.' : 'Claim your welcome credits to book your first lesson.') ?></p></div>
        <div class="member-starter-action"><button type="button" class="site-button" data-claim-starter <?= !$status['can_claim'] ? 'disabled' : '' ?>><?= !$status['wallet_available'] ? 'Unavailable' : ($status['received'] ? 'Received' : 'Claim Starter Pack') ?></button>
            <p class="member-field-error" data-starter-message role="status" hidden></p><button class="member-text-button" type="button" data-starter-retry hidden>Try again</button></div>
    </section><?php
}
function member_empty(string $title, string $description): void
{
    ?><div class="member-empty"><h3><?= member_h($title) ?></h3><p><?= member_h($description) ?></p></div><?php
}
function member_data_error(): void
{
    ?><section class="member-panel"><h2>We couldn’t load your information.</h2><p>Please try again in a moment.</p>
    <button class="site-button site-button--secondary" data-page-retry>Try again</button></section><?php
}
function member_stat(string $label, $value, string $key): void
{
    ?><div class="member-stat"><span><?= member_h($label) ?></span><strong data-stat="<?= member_h($key) ?>"><?= member_number($value) ?></strong></div><?php
}
function member_session_list(array $sessions, int $id, string $context, bool $details = false): void
{
    if (!$sessions) {
        if ($context === 'dashboard') {
            member_empty('No upcoming sessions.', 'Your booked learning and teaching sessions will appear here.');
            ?><a class="member-empty-link" href="<?= member_url('Pages/Explore/explore.html') ?>">Explore Skills →</a><?php
            return;
        }
        $teaching = str_contains($context, 'teaching');
        member_empty($teaching ? 'No teaching sessions yet.' : 'No learning sessions yet.', $teaching
            ? 'Add a teaching time in My Skills. Your session will appear here when someone joins.'
            : 'Join a class in Explore Skills to see it here.');
        ?><a class="member-empty-link" href="<?= member_url($teaching ? 'Pages/Member/Skills/' : 'Pages/Explore/explore.html') ?>"><?= $teaching ? 'Go to My Skills' : 'Explore Skills' ?> →</a><?php
        return;
    }
    $upcomingIds = array_column(member_upcoming($sessions), 'session_id');
    ?><div class="member-list"><?php foreach ($sessions as $session):
        $teaching = (int)$session['tutor_id'] === $id;
        $status = $session['session_status'];
        $waiting = $status === 'in_progress' && !empty($session['ended_at']);
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
        $startTime = new DateTimeImmutable($session['start_at'], new DateTimeZone('Asia/Jakarta'));
        $endTime = new DateTimeImmutable($session['end_at'], new DateTimeZone('Asia/Jakarta'));
        $expired = $status === 'scheduled' && $endTime <= $now;
        $statusLabel = $waiting ? 'Awaiting confirmation' : ($expired ? 'Time passed' : ucfirst(str_replace('_', ' ', $status)));
        $filterStatus = in_array($session['session_id'], $upcomingIds, true) ? 'upcoming' : $status;
        $dialogId = 'session-' . $context . '-' . $session['session_id'];
        ?><article class="member-session-row" <?= $details ? 'id="session-' . member_h($session['session_id']) . '"' : '' ?> data-session-row data-filter-state="<?= member_h($filterStatus) ?>">
            <div class="member-session-date"><strong><?= member_time($session['start_at'], 'd') ?></strong><span><?= member_time($session['start_at'], 'M') ?></span></div>
            <div class="member-session-body"><h3><?= member_h($session['title']) ?></h3>
                <p><?= $teaching ? 'Teaching' : 'Learning' ?> with <?= member_h($teaching ? $session['learner_name'] : $session['tutor_name']) ?></p>
                <p><time><?= member_time($session['start_at']) ?>–<?= member_time($session['end_at'], 'H:i') ?> WIB</time></p>
            </div>
            <div class="member-session-actions"><span class="member-badge member-badge--<?= member_h($expired ? 'cancelled' : $status) ?>"><?= member_h($statusLabel) ?></span>
                <?php if ($details): ?><button class="member-text-button" data-open-dialog="<?= member_h($dialogId) ?>">View Details</button><?php endif; ?>
                <?php if ($details && $status === 'scheduled'): ?>
                    <?php if ($teaching): ?><button class="member-text-button" data-session-action="start" data-session-id="<?= member_h($session['session_id']) ?>" data-session-start="<?= member_h($startTime->format(DATE_ATOM)) ?>" data-session-end="<?= member_h($endTime->format(DATE_ATOM)) ?>" <?= $startTime > $now || $expired ? 'disabled' : '' ?> title="<?= $expired ? 'This time has passed. Cancel this session to return the credits.' : 'Available during the scheduled time' ?>">Start Session</button><?php endif; ?>
                    <button class="member-text-button member-danger" data-session-action="cancel" data-session-id="<?= member_h($session['session_id']) ?>">Cancel Session</button>
                <?php elseif ($details && $status === 'in_progress'): ?>
                    <?php if ($teaching && !$waiting): ?><button class="member-text-button" data-session-action="end" data-session-id="<?= member_h($session['session_id']) ?>">End Session</button>
                    <?php elseif (!$teaching && $waiting): ?><button class="member-text-button" data-session-action="confirm" data-session-id="<?= member_h($session['session_id']) ?>">Confirm Complete</button><?php endif; ?>
                <?php endif; ?>
                <?php if (!$details): ?><a class="member-text-button" href="<?= member_url('Pages/Member/Sessions/?view=' . ($teaching ? 'teaching' : 'learning') . '#session-' . $session['session_id']) ?>">View in My Sessions →</a><?php endif; ?>
            </div>
            <?php if ($details): ?><dialog class="member-dialog" id="<?= member_h($dialogId) ?>" aria-labelledby="<?= member_h($dialogId) ?>-title">
                <div class="member-dialog-heading"><h2 id="<?= member_h($dialogId) ?>-title">Session details</h2><button class="member-close" data-close-dialog aria-label="Close session details">×</button></div>
                <h3><?= member_h($session['title']) ?></h3><dl class="member-facts">
                    <div><dt>Tutor</dt><dd><?= member_h($session['tutor_name']) ?></dd></div>
                    <div><dt>Learner</dt><dd><?= member_h($session['learner_name']) ?></dd></div>
                    <div><dt>Date &amp; time</dt><dd><?= member_time($session['start_at']) ?>–<?= member_time($session['end_at'], 'H:i') ?> WIB</dd></div>
                    <div><dt>Duration</dt><dd><?= member_h($session['duration_minutes']) ?> minutes</dd></div>
                    <div><dt>Time Credits</dt><dd><?= member_number($session['credits_used']) ?></dd></div>
                    <div><dt>Status</dt><dd><?= member_h($statusLabel) ?></dd></div>
                    <?php if ($waiting): ?><div><dt>Next step</dt><dd><?= $teaching ? 'Waiting for your learner to confirm the session.' : 'Close these details and choose Confirm Complete when you’re ready.' ?></dd></div><?php endif; ?>
                </dl>
            </dialog><?php endif; ?>
        </article>
    <?php endforeach; ?></div><?php
}
function member_teaching_time_list(array $times, bool $schedule = false): void
{
    if (!$times) {
        member_empty('No upcoming teaching times.', 'Add a time from My Skills so someone can book a lesson with you.');
        return;
    }
    ?><div class="member-list"><?php foreach ($times as $time):
        $past = !$time['is_future'];
        $cancelled = $time['slot_status'] === 'blocked';
        $target = 'teaching-time-' . $time['availability_id'];
        ?><article class="member-session-row" id="<?= member_h($schedule ? 'schedule-time-' . $time['availability_id'] : $target) ?>" data-teaching-time="<?= member_h($time['availability_id']) ?>">
            <div class="member-session-date"><strong><?= member_time($time['start_at'], 'd') ?></strong><span><?= member_time($time['start_at'], 'M') ?></span></div>
            <div class="member-session-body"><h3><?= member_h($time['title']) ?></h3>
                <p><time><?= member_time($time['start_at']) ?>–<?= member_time($time['end_at'], 'H:i') ?> WIB</time></p>
                <p><?= member_h($time['duration_minutes']) ?> minutes</p>
                <?php if (!$time['is_active'] || $time['verification_status'] === 'rejected'): ?><p>This skill is not available in Explore.</p><?php endif; ?>
            </div>
            <div class="member-session-actions"><span class="member-badge <?= $past || $cancelled ? 'member-badge--cancelled' : '' ?>"><?= $cancelled ? 'Cancelled' : ($past ? 'Time passed' : 'Not booked yet') ?></span>
                <?php if ($schedule): ?><a class="member-text-button" href="<?= member_url('Pages/Member/Sessions/?view=teaching#' . $target) ?>">View Teaching Time →</a>
                <?php else: ?><a class="member-text-button" href="<?= member_url('Pages/Member/Skills/#skill-' . $time['offering_id']) ?>">Add Teaching Time →</a><?php endif; ?>
                <?php if (!$past && !$cancelled): ?><button type="button" class="member-text-button member-danger" data-cancel-teaching-time="<?= member_h($time['availability_id']) ?>" data-class-title="<?= member_h($time['title']) ?>">Cancel Time</button><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?></div><?php
}

function member_cancel_teaching_time_dialog(): void
{
    ?><dialog class="member-dialog" id="cancel-teaching-time" aria-labelledby="cancel-time-title">
        <div class="member-dialog-heading"><h2 id="cancel-time-title">Cancel this teaching time?</h2><button type="button" class="member-close" data-close-dialog aria-label="Close teaching time cancellation">×</button></div>
        <p data-cancel-time-description></p><p class="member-caption">Learners will no longer be able to book this time.</p>
        <form class="member-form" data-member-form="cancel-teaching-time" novalidate>
            <p class="member-notice" data-form-message role="status" hidden></p><input type="hidden" name="availability_id">
            <div class="member-form-actions"><button class="site-button" type="submit">Cancel Time</button><button type="button" class="member-text-button" data-close-dialog>Keep Time</button></div>
        </form>
    </dialog><?php
}

function member_field(string $prefix, string $name, string $label, string $type, $value = '', string $attributes = ''): void
{
    $id = $prefix . '-' . $name;
    ?><div class="member-field"><label for="<?= member_h($id) ?>"><?= member_h($label) ?></label>
        <div class="<?= $type === 'password' ? 'member-password' : '' ?>">
        <?php if ($type === 'textarea'): ?><textarea id="<?= member_h($id) ?>" name="<?= member_h($name) ?>" aria-describedby="<?= member_h($id) ?>-error" <?= $attributes ?>><?= member_h($value) ?></textarea>
        <?php else: ?><input id="<?= member_h($id) ?>" name="<?= member_h($name) ?>" type="<?= member_h($type) ?>" value="<?= member_h($value) ?>" aria-describedby="<?= member_h($id) ?>-error" <?= $attributes ?>><?php endif; ?>
        <?php if ($type === 'password'): ?><button type="button" data-password-toggle="<?= member_h($id) ?>" aria-label="Show <?= member_h(strtolower($label)) ?>" aria-pressed="false">Show</button><?php endif; ?>
        </div><p class="member-field-error" id="<?= member_h($id) ?>-error" data-error-for="<?= member_h($name) ?>" hidden></p>
    </div><?php
}
function member_avatar(array $profile): void
{
    $relative = safe_avatar_path($profile['avatar_path'] ?? null);
    if ($relative) {
        ?><img class="member-avatar" data-member-avatar src="<?= member_url($relative) ?>" data-avatar-initial="<?= member_h(strtoupper(substr($profile['username'], 0, 1))) ?>" alt="<?= member_h($profile['full_name']) ?>" width="88" height="88"><?php
    } else {
        ?><span class="member-avatar" aria-hidden="true"><?= member_h(strtoupper(substr($profile['username'], 0, 1))) ?></span><?php
    }
}
