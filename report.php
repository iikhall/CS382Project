<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();

$school    = Stat::meta($db, '_school_name', 'Smart School Dashboard');
$principal = Stat::meta($db, '_principal_name', 'Admin');
$vice      = Stat::meta($db, '_vice_principal_name', 'Deputy');
$me        = User::current();
$generated = date('Y-m-d H:i');
$week      = ClassModel::currentWeek();

// Admin -> every grade. Supervisor -> only their assigned grade(s).
$grades = User::isAdmin()
    ? null
    : GradeSupervisor::gradesForSupervisor($db, User::id());

$id = (int) ($_GET['id'] ?? 0);
$single = $id > 0 ? ClassModel::find($db, $id) : null;

// A supervisor may only open a class inside a grade they supervise.
if ($single !== null && $grades !== null
    && !in_array($single['grade'], $grades, true)) {
    header('Location: ' . base_url('report.php'));
    exit;
}

// "Generate PDF" navigates here with ?print=1 — save the snapshot to
// the Weekly Archive server-side (guaranteed, no AJAX), then the page
// auto-opens the print dialog.
$autoPrint = (($_GET['print'] ?? '') === '1');
if ($autoPrint) {
    Snapshot::save($db, date('Y-m-d'), User::current() ?? []);
}

// Ranking section: admin -> every class; supervisor -> only the
// class(es) in the grade(s) they are assigned to.
$rankWhere  = '';
$rankParams = [];
if ($grades !== null) {
    if ($grades === []) {
        $rankWhere = ' WHERE 1 = 0';
    } else {
        $rankWhere = ' WHERE c.grade IN ('
            . implode(',', array_fill(0, count($grades), '?')) . ')';
        $rankParams = array_values($grades);
    }
}
$rankedAll = $db->query(
    'SELECT c.name, c.grade,
            c.order_score, c.cleanliness_score, c.behavior_score,
            (c.order_score + c.cleanliness_score + c.behavior_score) AS total_score,
            COUNT(s.id) AS star_count,
            u.display_name AS supervisor_name
     FROM classes c
     LEFT JOIN stars s ON s.class_id = c.id
     LEFT JOIN grade_supervisors g ON g.grade = c.grade
     LEFT JOIN users u ON u.id = g.supervisor_user_id'
    . $rankWhere .
    ' GROUP BY c.id
     ORDER BY total_score DESC, star_count DESC, c.sort_order',
    $rankParams
)->fetchAll();
$rankScopeLabel = $grades === null
    ? 'All Classes'
    : ($grades ? implode(', ', $grades) : 'No Grade Assigned');
$attendanceRows = Attendance::months($db);

$pageTitle   = 'Reports';
$activeNav   = 'reports';
$pageScripts = [base_url('assets/js/report.js')];
require __DIR__ . '/includes/header.php';
?>

<?php
// Reusable renderers (defined once, used for any report variant).
$renderRanking = function (array $rows, string $scope): void { ?>
  <h2 class="report-section-title">Class Scores &amp; Ranking
    (<?= htmlspecialchars($scope) ?>)</h2>
  <table class="report-table">
    <thead><tr>
      <th>#</th><th>Class</th><th>Grade</th><th>Discipline</th><th>Clean.</th>
      <th>Behav.</th><th>Total</th><th>Stars</th><th>Supervisor</th>
    </tr></thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="9">No classes.</td></tr>
      <?php else: $rank = 1; foreach ($rows as $r): ?>
        <tr>
          <td><?= $rank++ ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['grade']) ?></td>
          <td><?= (int) $r['order_score'] ?></td>
          <td><?= (int) $r['cleanliness_score'] ?></td>
          <td><?= (int) $r['behavior_score'] ?></td>
          <td><strong><?= (int) $r['total_score'] ?></strong> / 30</td>
          <td><?= (int) $r['star_count'] ?></td>
          <td><?= htmlspecialchars($r['supervisor_name'] ?? '' ?: '—') ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
<?php };

$renderAttendance = function (array $months): void { ?>
  <h2 class="report-section-title">Disciplined Attendance Rate (Academic Year)</h2>
  <table class="report-table">
    <thead><tr><th>Month</th><th>Attendance Rate</th></tr></thead>
    <tbody>
      <?php foreach ($months as $m): $v = (int) $m['value']; ?>
        <tr>
          <td><?= htmlspecialchars($m['month']) ?></td>
          <td><?= $v === 0 ? '—' : $v . '%' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php };
?>

<div class="report-actions flex-between">
  <div>
    <h1 class="page-title">Reports</h1>
    <p class="subtle">Generate a printable PDF (use your browser's “Save as PDF”).</p>
  </div>
  <div class="snap-actions">
    <?php if ($single && User::isAdmin()): ?>
      <a class="btn btn-secondary" href="<?= htmlspecialchars(base_url('report.php')) ?>">School Report</a>
    <?php endif; ?>
    <?php
      $genUrl = base_url('report.php') . '?print=1' . ($single ? '&id=' . (int) $id : '');
    ?>
    <a class="btn btn-primary" id="printBtn"
       href="<?= htmlspecialchars($genUrl) ?>">Generate PDF</a>
  </div>
</div>
<?php if ($autoPrint): ?>
  <span id="autoPrint" hidden></span>
<?php endif; ?>

<div class="report-doc card">

<?php if ($single): ?>
  <?php
    $stars   = Star::forClass($db, $id);
    $courses = Subject::forClass($db, $id);
    $total   = (int) $single['order_score'] + (int) $single['cleanliness_score']
             + (int) $single['behavior_score'];
    $supervisor = null;
    $supId = GradeSupervisor::supervisorFor($db, $single['grade']);
    if ($supId !== null) {
        $supervisor = (new User($db))->findById($supId);
    }
  ?>
  <h1><?= htmlspecialchars($school) ?> — Class Report</h1>
  <p class="report-meta">
    <strong><?= htmlspecialchars($single['name']) ?></strong>
    (<?= htmlspecialchars($single['grade']) ?>, Section <?= (int) $single['section'] ?>)
    &middot; Week <?= $week ?> &middot; Generated <?= htmlspecialchars($generated) ?>
    by <?= htmlspecialchars($me['display_name'] ?? '') ?>
    <br>Principal: <?= htmlspecialchars((string) $principal) ?>
    &middot; Vice-Principal: <?= htmlspecialchars((string) $vice) ?>
  </p>

  <h2 class="report-section-title">Discipline Evaluation</h2>
  <table class="report-table">
    <tr><th>Discipline</th><td><?= (int) $single['order_score'] ?> / 10</td></tr>
    <tr><th>Cleanliness</th><td><?= (int) $single['cleanliness_score'] ?> / 10</td></tr>
    <tr><th>Behavior</th><td><?= (int) $single['behavior_score'] ?> / 10</td></tr>
    <tr><th>Total</th><td><strong><?= $total ?> / 30</strong></td></tr>
    <tr><th>Discipline Leader</th><td><?= htmlspecialchars($single['discipline_leader'] ?: '—') ?></td></tr>
    <tr><th>Grade Supervisor</th><td><?= htmlspecialchars($supervisor['display_name'] ?? '—') ?></td></tr>
    <tr><th>Notes</th><td><?= htmlspecialchars((string) ($single['motivation_notes'] ?? '') ?: '—') ?></td></tr>
  </table>

  <h2 class="report-section-title">Courses (<?= count($courses) ?>)</h2>
  <table class="report-table">
    <thead><tr>
      <th>Course</th><th>Teacher</th><th>Excellent</th><th>Very Good</th>
      <th>Good</th><th>Acceptable</th><th>Fail</th>
    </tr></thead>
    <tbody>
      <?php if (!$courses): ?>
        <tr><td colspan="7">No courses.</td></tr>
      <?php else: foreach ($courses as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['name']) ?></td>
          <td><?= htmlspecialchars($s['teacher'] ?: '—') ?></td>
          <td><?= (int) $s['excellent'] ?></td>
          <td><?= (int) $s['very_good'] ?></td>
          <td><?= (int) $s['good'] ?></td>
          <td><?= (int) $s['acceptable'] ?></td>
          <td><?= (int) $s['fail'] ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <h2 class="report-section-title">Motivational Stars (<?= count($stars) ?>)</h2>
  <table class="report-table">
    <thead><tr><th>Awarded By</th><th>Reason</th><th>Date</th></tr></thead>
    <tbody>
      <?php if (!$stars): ?>
        <tr><td colspan="3">No stars awarded.</td></tr>
      <?php else: foreach ($stars as $st): ?>
        <tr>
          <td><?= htmlspecialchars($st['awarded_by_name']) ?></td>
          <td><?= htmlspecialchars($st['reason'] ?: '—') ?></td>
          <td><?= htmlspecialchars($st['awarded_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

<?php else: ?>
  <?php
    $reportScope = $grades === null
        ? 'School-Wide'
        : (($grades ? implode(', ', $grades) : 'No Grade Assigned'));
  ?>
  <h1><?= htmlspecialchars($school) ?> — <?= htmlspecialchars($reportScope) ?> Report</h1>
  <p class="report-meta">
    Week <?= $week ?> &middot; Generated <?= htmlspecialchars($generated) ?>
    by <?= htmlspecialchars($me['display_name'] ?? '') ?>
    (<?= htmlspecialchars(str_replace('_', ' ', User::role())) ?>)
    <br>Principal: <?= htmlspecialchars((string) $principal) ?>
    &middot; Vice-Principal: <?= htmlspecialchars((string) $vice) ?>
  </p>

  <h2 class="report-section-title">Student Academic Performance</h2>
  <?php $academic = ClassModel::academic($db, $grades); ?>
  <?php if (!$academic): ?>
    <p>No academic data.</p>
  <?php else: foreach ($academic as $groupLabel => $subjects): ?>
    <h3 class="report-subhead"><?= htmlspecialchars($groupLabel) ?></h3>
    <table class="report-table">
      <thead><tr>
        <th>Course</th><th>Teacher</th><th>Excellent</th><th>Very Good</th>
        <th>Good</th><th>Acceptable</th><th>Fail</th><th>Top Band</th>
      </tr></thead>
      <tbody>
        <?php foreach ($subjects as $s):
          $bands = [
            'Excellent'  => (int) $s['excellent'],
            'Very Good'  => (int) $s['very_good'],
            'Good'       => (int) $s['good'],
            'Acceptable' => (int) $s['acceptable'],
            'Fail'       => (int) $s['fail'],
          ];
          $sum = array_sum($bands) ?: 1;
          $top = array_key_first($bands);
          foreach ($bands as $k => $v) { if ($v > $bands[$top]) { $top = $k; } }
        ?>
          <tr>
            <td><?= htmlspecialchars($s['subject']) ?></td>
            <td><?= htmlspecialchars($s['teacher'] ?: '—') ?></td>
            <td><?= $bands['Excellent'] ?></td>
            <td><?= $bands['Very Good'] ?></td>
            <td><?= $bands['Good'] ?></td>
            <td><?= $bands['Acceptable'] ?></td>
            <td><?= $bands['Fail'] ?></td>
            <td><strong><?= htmlspecialchars($top) ?></strong>
                (<?= (int) round($bands[$top] * 100 / $sum) ?>%)</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; endif; ?>
<?php endif; ?>

  <?php $renderRanking($rankedAll, $rankScopeLabel); ?>
  <?php $renderAttendance($attendanceRows); ?>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
