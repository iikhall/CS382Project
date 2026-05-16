<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

User::logout();

header('Location: ' . base_url('login.php'));
exit;
