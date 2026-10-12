<?php
require_once __DIR__ . '/../../php/auth.php';
require_member(true);
$pageTitle = $pageTitle ?? 'Member';
$base = htmlspecialchars(project_path(), ENT_QUOTES, 'UTF-8');
$title = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?> — TimeBank</title>
    <link rel="icon" href="<?= $base ?>Assets/Logo/timebank-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>css/global.css">
    <link rel="stylesheet" href="<?= $base ?>Shared/Navbar/navbar.css">
    <link rel="stylesheet" href="<?= $base ?>Shared/Footer/footer.css">
    <link rel="stylesheet" href="<?= $base ?>Pages/Member/member.css">
    <script src="<?= $base ?>js/include.js" defer></script>
    <script src="<?= $base ?>js/auth.js" defer></script>
    <script src="<?= $base ?>Shared/Navbar/navbar.js" defer></script>
    <script src="<?= $base ?>Pages/Member/member.js" defer></script>
</head>
<body class="member-page">
    <a class="site-skip-link" href="#main-content">Skip to content</a>
    <div id="navbar-mount"></div>
    <main id="main-content" class="site-container member-main">
        <span class="site-eyebrow">Your TimeBank</span>
        <h1><?= $title ?></h1>
        <section class="member-placeholder" aria-labelledby="coming-soon">
            <h2 id="coming-soon">Coming Soon</h2>
            <p>This part of TimeBank is still being built. You can continue exploring while we get it ready.</p>
            <a class="site-button site-button--secondary" href="<?= $base ?>index.html#hero">Back to Home <span aria-hidden="true">→</span></a>
        </section>
        <p class="member-status" role="status" hidden></p>
    </main>
    <div id="footer-mount"></div>
</body>
</html>
