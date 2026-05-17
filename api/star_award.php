<?php
declare(strict_types=1);

/**
 * AJAX: award a motivational star. The star is always attributed to
 * the supervisor of the class's grade (not the acting admin).
 * POST { class_id, reason? }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$classId = (int) ($_POST['class_id'] ?? 0);
$reason  = trim((string) ($_POST['reason'] ?? ''));

$cl = $classId > 0 ? ClassModel::find($db, $classId) : null;
if ($cl === null) {
    json_response(['ok' => false, 'error' => 'Class not found.'], 404);
}
if (!ClassModel::canEvaluate($db, $cl)) {
    json_response(['ok' => false, 'error' => 'You cannot award stars for this class.'], 403);
}
if (mb_strlen($reason) > 255) {
    json_response(['ok' => false, 'error' => 'Reason is too long (max 255).'], 422);
}

// The star is awarded by the class's grade supervisor. If no supervisor
// is assigned yet, fall back to the acting user's name.
$supId = GradeSupervisor::supervisorFor($db, $cl['grade']);
$awardedByName = $supId !== null
    ? (string) ((new User($db))->findById($supId)['display_name'] ?? 'Supervisor')
    : (string) (User::current()['display_name'] ?? 'Supervisor');

Star::award($db, $classId, 'supervisor', $awardedByName, $reason);

json_response([
    'ok'    => true,
    'count' => Star::countForClass($db, $classId),
    'star'  => [
        'awarded_by'      => 'supervisor',
        'awarded_by_name' => $awardedByName,
        'reason'          => $reason,
        'awarded_at'      => date('Y-m-d H:i'),
    ],
]);
