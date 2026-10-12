<?php
require_once __DIR__ . '/auth.php';
post_data();
require_member();
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 3600, 'path' => $params['path'],
    'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax',
]);
session_destroy();
respond(200, true, 'Logged out successfully.');
