<?php
declare(strict_types=1);

/**
 * AJAX: delete all internal messages. Admin only (destructive).
 * POST (no body needed).
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

Message::clearAll($db);

json_response(['ok' => true, 'count' => Message::count($db)]);
