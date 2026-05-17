<?php
declare(strict_types=1);

/**
 * AJAX: save monthly attendance rates. Admin or supervisor.
 * POST { value: { <id>: <0-100>, ... } }   (0 = no data / gap)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$values = $_POST['value'] ?? null;
if (!is_array($values) || $values === []) {
    json_response(['ok' => false, 'error' => 'No attendance values submitted.'], 422);
}

Attendance::updateValues($db, $values);

json_response(['ok' => true]);
