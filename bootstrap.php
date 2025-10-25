<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notes.php';
require_once __DIR__ . '/includes/users.php';

init_session($config['app']);

$pdo = db_connect($config['db']);
