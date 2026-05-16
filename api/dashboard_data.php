<?php
declare(strict_types=1);

/**
 * AJAX: chart data for the dashboard (attendance line + academic donuts).
 * GET -> JSON. Authenticated users only.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

api_require_login();

$months = Attendance::months($db);
$attendance = [
    'labels' => array_map(static fn($m) => $m['month'], $months),
    // 0 = no data -> null so Chart.js renders a gap
    'values' => array_map(
        static fn($m) => ((int) $m['value']) === 0 ? null : (int) $m['value'],
        $months
    ),
];

$academic = [];
$grades = User::isSupervisor()
    ? GradeSupervisor::gradesForSupervisor($db, User::id())
    : null;
$academicSrc = ClassModel::academic($db, $grades);
foreach ($academicSrc as $groupLabel => $subjects) {
    $donuts = [];
    foreach ($subjects as $s) {
        $bands = [
            'Excellent'  => (int) $s['excellent'],
            'Very Good'  => (int) $s['very_good'],
            'Good'       => (int) $s['good'],
            'Acceptable' => (int) $s['acceptable'],
            'Fail'       => (int) $s['fail'],
        ];
        $total = array_sum($bands) ?: 1;
        $dominant = array_key_first($bands);
        foreach ($bands as $k => $v) {
            if ($v > $bands[$dominant]) { $dominant = $k; }
        }
        $donuts[] = [
            'subject'      => $s['subject'],
            'teacher'      => $s['teacher'] ?? '',
            'bands'        => $bands,
            'dominant'     => $dominant,
            'dominant_pct' => (int) round($bands[$dominant] * 100 / $total),
        ];
    }
    $academic[] = ['group' => $groupLabel, 'donuts' => $donuts];
}

json_response([
    'ok'         => true,
    'attendance' => $attendance,
    'academic'   => $academic,
]);
