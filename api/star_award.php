<?php
declare(strict_types=1);

/**
 * AJAX: award a motivational star. Admin or staff.
 * POST { class_id, awarded_by (principal|vice_principal), reason? }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$classId   = (int) ($_POST['class_id'] ?? 0);
$awardedBy = (string) ($_POST['awarded_by'] ?? '');
$reason    = trim((string) ($_POST['reason'] ?? ''));

$cl = $classId > 0 ? ClassModel::find($db, $classId) : null;
if ($cl === null) {
    json_response(['ok' => false, 'error' => 'Class not found.'], 404);
}
if (!ClassModel::canEvaluate($db, $cl)) {
    json_response(['ok' => false, 'error' => 'You cannot award stars for this class.'], 403);
}
if (!in_array($awardedBy, Star::AWARDERS, true)) {
    json_response(['ok' => false, 'error' => 'Invalid awarder.'], 422);
}
if (mb_strlen($reason) > 255) {
    json_response(['ok' => false, 'error' => 'Reason is too long (max 255).'], 422);
}

$nameKey = $awardedBy === 'principal' ? '_principal_name' : '_vice_principal_name';
$awardedByName = Stat::meta($db, $nameKey, ucfirst(str_replace('_', ' ', $awardedBy)));

Star::award($db, $classId, $awardedBy, (string) $awardedByName, $reason);

json_response([
    'ok'    => true,
    'count' => Star::countForClass($db, $classId),
    'star'  => [
        'awarded_by'      => $awardedBy,
        'awarded_by_name' => $awardedByName,
        'reason'          => $reason,
        'awarded_at'      => date('Y-m-d H:i'),
    ],
]);
