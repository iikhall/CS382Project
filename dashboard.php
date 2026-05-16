<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();

$isSupervisor = User::isSupervisor();

// Admin -> all grades. Supervisor -> only their assigned grade(s).
$grades = $isSupervisor
    ? GradeSupervisor::gradesForSupervisor($db, User::id())
    : null;

// The weekly Sunday-based auto-reset is a school-wide mutation —
// only an admin visit may trigger it.
if (User::isAdmin()) {
    ClassModel::autoResetIfNewWeek($db);
}

$cards   = Stat::cards($db);
$byGrade = ClassModel::allGroupedByGrade($db, $grades);

$pageTitle   = 'Dashboard';
$activeNav   = 'dashboard';
$pageScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
    base_url('assets/js/dashboard.js'),
];
require __DIR__ . '/includes/header.php';
?>

<div class="flex-between mb-4">
  <div>
    <h1 class="page-title">Weekly Monitoring Board</h1>
    <p class="subtle">
      <?= $isSupervisor
          ? 'Classes in your assigned grade.'
          : 'Live discipline scores and student indicators across all classes.' ?>
    </p>
  </div>
</div>

<?php if ($isSupervisor && !$byGrade): ?>
  <div class="card mb-4">
    <p class="subtle mt-0">
      You have no grade assigned yet. An administrator must assign a grade
      to you before you can evaluate its classes.
    </p>
  </div>
<?php endif; ?>

<!-- Stat cards -->
<section class="grid grid-3 mb-4" aria-label="Key indicators">
  <?php foreach ($cards as $i => $c): ?>
    <div class="stat-card card is-hoverable" data-accent="<?= $i % 3 ?>">
      <div class="stat-card-icon" aria-hidden="true">&#9733;</div>
      <div class="stat-card-value"><?= htmlspecialchars($c['value']) ?></div>
      <div class="stat-card-label"><?= htmlspecialchars($c['label']) ?></div>
      <div class="stat-card-sub"><?= htmlspecialchars($c['sublabel']) ?></div>
    </div>
  <?php endforeach; ?>
</section>

<!-- Discipline class grid, grouped by grade -->
<?php foreach ($byGrade as $grade => $classes): ?>
  <section class="section">
    <div class="section-head">
      <span class="icon" aria-hidden="true">&#9678;</span>
      <h2 class="section-title"><?= htmlspecialchars($grade) ?></h2>
    </div>
    <div class="grid grid-4">
      <?php foreach ($classes as $cl): ?>
        <a class="class-card card is-hoverable"
           href="<?= htmlspecialchars(base_url('class.php?id=' . (int) $cl['id'])) ?>"
           aria-label="Open <?= htmlspecialchars($cl['name']) ?>">
          <div class="class-card-head">
            <span class="class-card-name"><?= htmlspecialchars($cl['name']) ?></span>
            <?php if ($cl['badge'] !== ''): ?>
              <span class="class-badge"><?= htmlspecialchars($cl['badge']) ?></span>
            <?php endif; ?>
          </div>
          <div class="score-ring">
            <span class="score-ring-total"><?= (int) $cl['total_score'] ?></span>
            <span class="score-ring-max">/30</span>
          </div>
          <ul class="score-rows">
            <li><span>Order</span><strong><?= (int) $cl['order_score'] ?> / 10</strong></li>
            <li><span>Cleanliness</span><strong><?= (int) $cl['cleanliness_score'] ?> / 10</strong></li>
            <li><span>Behavior</span><strong><?= (int) $cl['behavior_score'] ?> / 10</strong></li>
          </ul>
          <div class="class-card-foot subtle">
            Supervisor: <?= htmlspecialchars($cl['supervisor'] !== '' ? $cl['supervisor'] : '—') ?>
            <span class="star-pill" aria-label="<?= (int) $cl['star_count'] ?> stars">
              &#9733; <?= (int) $cl['star_count'] ?>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>

<!-- Attendance chart (data loaded via AJAX) -->
<section class="section">
  <div class="section-head">
    <span class="icon" aria-hidden="true">&#9201;</span>
    <h2 class="section-title">Disciplined Attendance Rate (Academic Year)</h2>
  </div>
  <div class="card">
    <div class="chart-box">
      <canvas id="attendanceChart" aria-label="Monthly attendance line chart" role="img"></canvas>
    </div>
    <p class="subtle chart-empty" id="attendanceEmpty" hidden>Loading attendance…</p>
  </div>
</section>

<!-- Academic performance donuts (data loaded via AJAX) -->
<section class="section">
  <div class="section-head">
    <span class="icon" aria-hidden="true">&#127891;</span>
    <h2 class="section-title">Student Academic Performance</h2>
  </div>
  <div id="academicGroups">
    <p class="subtle" id="academicLoading">Loading academic performance…</p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
