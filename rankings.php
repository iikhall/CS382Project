<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// School-wide rankings are confidential — admin only.
require_admin();

$ranked = ClassModel::ranked($db);
$podium = array_slice($ranked, 0, 3);
$rest   = array_slice($ranked, 3);

$pageTitle = 'Rankings';
$activeNav = 'rankings';
require __DIR__ . '/includes/header.php';
?>

<div class="text-center mb-4">
  <h1 class="page-title">Class Rankings</h1>
  <p class="subtle">Ranked by total score, then star count as the tiebreaker.</p>
</div>

<?php if (!$ranked): ?>
  <p class="subtle text-center">No classes to rank yet.</p>
<?php else: ?>

<?php
// Display order on the podium: 3rd (left), 1st (center), 2nd (right).
$order = [];
if (isset($podium[2])) { $order[] = [3, $podium[2]]; }
if (isset($podium[0])) { $order[] = [1, $podium[0]]; }
if (isset($podium[1])) { $order[] = [2, $podium[1]]; }
?>
<section class="podium" aria-label="Top classes">
  <?php foreach ($order as [$rank, $c]): ?>
    <div class="podium-col podium-<?= $rank ?>">
      <div class="podium-card">
        <strong><?= htmlspecialchars($c['name']) ?></strong>
        <span class="subtle"><?= (int) $c['total_score'] ?> / 30
          &middot; &#9733; <?= (int) $c['star_count'] ?></span>
      </div>
      <div class="podium-bar"><span class="podium-rank"><?= $rank ?></span></div>
    </div>
  <?php endforeach; ?>
</section>

<section class="section">
  <div class="section-head">
    <span class="icon" aria-hidden="true">&#9778;</span>
    <h2 class="section-title">Remaining Classes</h2>
  </div>
  <ul class="rank-list">
    <?php if (!$rest): ?>
      <li class="subtle">No other classes.</li>
    <?php else: $pos = 4; foreach ($rest as $c): ?>
      <li class="rank-row">
        <span class="rank-num"><?= $pos++ ?></span>
        <span class="rank-name"><?= htmlspecialchars($c['name']) ?>
          <span class="subtle">(<?= htmlspecialchars($c['grade']) ?>)</span>
        </span>
        <span class="rank-score">
          <strong><?= (int) $c['total_score'] ?></strong> / 30
          &middot; &#9733; <?= (int) $c['star_count'] ?>
        </span>
      </li>
    <?php endforeach; endif; ?>
  </ul>
</section>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
