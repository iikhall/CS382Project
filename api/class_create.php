<?php
declare(strict_types=1);

/**
 * AJAX: create a class. Admin only.
 * POST { code, grade, section, name, semester, supervisor }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$code       = trim((string) ($_POST['code'] ?? ''));
$grade      = trim((string) ($_POST['grade'] ?? ''));
$section    = (int) ($_POST['section'] ?? 0);
$name       = trim((string) ($_POST['name'] ?? ''));
$semester   = trim((string) ($_POST['semester'] ?? ''));
$supervisor = trim((string) ($_POST['supervisor'] ?? ''));

if ($code === '' || $grade === '' || $name === '' || $semester === '') {
    json_response(['ok' => false, 'error' => 'Code, grade, name and semester are required.'], 422);
}
if ($section < 1 || $section > 99) {
    json_response(['ok' => false, 'error' => 'Section must be between 1 and 99.'], 422);
}
if (ClassModel::codeExists($db, $code)) {
    json_response(['ok' => false, 'error' => 'That class code is already used.'], 422);
}

$id = ClassModel::create($db, $code, $grade, $section, $name, $semester, $supervisor);
json_response(['ok' => true, 'id' => $id]);
