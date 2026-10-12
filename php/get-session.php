<?php
require_once __DIR__ . '/auth.php';
require_method('GET');
respond(200, true, '', ['user' => current_user(), 'csrf_token' => $_SESSION['csrf_token']]);
