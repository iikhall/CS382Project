<?php
declare(strict_types=1);

/**
 * AJAX: save school info (placeholder names). Admin only.
 * POST { school, principal, vice_principal }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$school = trim((string) ($_POST['school'] ?? ''));
$prin   = trim((string) ($_POST['principal'] ?? ''));
$vice   = trim((string) ($_POST['vice_principal'] ?? ''));

if ($school === '' || $prin === '' || $vice === '') {
    json_response(['ok' => false, 'error' => 'All name fields are required.'], 422);
}

Stat::setMeta($db, '_school_name', $school);
Stat::setMeta($db, '_principal_name', $prin);
Stat::setMeta($db, '_vice_principal_name', $vice);

json_response(['ok' => true]);
