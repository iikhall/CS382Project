<?php
declare(strict_types=1);

/**
 * AJAX: assign (or clear) the supervisor for a whole grade.
 * Admin only. POST { grade, supervisor_user_id }  (0 -> unassign)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$grade = trim((string) ($_POST['grade'] ?? ''));
$supId = (int) ($_POST['supervisor_user_id'] ?? 0);

if ($grade === '') {
    json_response(['ok' => false, 'error' => 'Grade is required.'], 422);
}

$users = new User($db);
if ($supId > 0) {
    $u = $users->findById($supId);
    if ($u === null || $u['role'] !== 'supervisor') {
        json_response(['ok' => false, 'error' => 'Selected user is not a supervisor.'], 422);
    }
}

GradeSupervisor::assign($db, $grade, $supId > 0 ? $supId : null);
json_response(['ok' => true]);
