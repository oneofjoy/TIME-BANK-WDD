<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/starter-pack.php';
$user = require_member();
require_method('GET');
respond(200, true, '', ['starter_pack' => starter_pack_status($user['user_id'])]);
