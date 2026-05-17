<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_admin();

$school = Stat::meta($db, '_school_name', 'Smart School Dashboard');
$prin   = Stat::meta($db, '_principal_name', 'Admin');
$vice   = Stat::meta($db, '_vice_principal_name', 'Deputy');
$snaps  = Snapshot::all($db);

$pageTitle   = 'Settings';
$activeNav   = 'settings';
$pageScripts = [base_url('assets/js/settings.js')];
require __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
  <h1 class="page-title">System Settings</h1>
  <p class="subtle">Manage school info, weekly snapshots and resets.</p>
</div>

<!-- School info -->
<div class="card mb-4">
  <div class="section-head">
    <span class="icon" aria-hidden="true">&#9881;</span>
    <div>
      <h2 class="card-title">School Information</h2>
      <p class="subtle">Names shown in reports, stars and branding.</p>
    </div>
  </div>
  <form id="infoForm">
    <div class="form-group">
      <label for="school">School Name</label>
      <input type="text" id="school" class="form-control"
             value="<?= htmlspecialchars((string) $school) ?>" required>
    </div>
    <div class="grid grid-2">
      <div class="form-group">
        <label for="principal">Principal Name</label>
        <input type="text" id="principal" class="form-control"
               value="<?= htmlspecialchars((string) $prin) ?>" required>
      </div>
      <div class="form-group">
        <label for="vice_principal">Vice-Principal Name</label>
        <input type="text" id="vice_principal" class="form-control"
               value="<?= htmlspecialchars((string) $vice) ?>" required>
      </div>
    </div>
    <span class="field-error" id="infoError"></span>
    <button type="submit" id="saveInfoBtn" class="btn btn-primary">Save Info</button>
  </form>
</div>

<!-- Weekly archive -->
<div class="card mb-4">
  <div class="flex-between mb-4">
    <div class="section-head mt-0">
      <span class="icon" aria-hidden="true">&#128338;</span>
      <h2 class="card-title">Weekly Archive</h2>
    </div>
    <button type="button" id="clearSnapsBtn" class="btn btn-danger"
            <?= $snaps ? '' : 'hidden' ?>>Clear All</button>
  </div>
  <ul class="snap-list" id="snapList">
    <?php if (!$snaps): ?>
      <li class="empty-state" id="snapsEmpty">
        <span class="empty-star" aria-hidden="true">&#128338;</span>
        No saved weeks yet
      </li>
    <?php else: foreach ($snaps as $s): ?>
      <li class="snap-item" data-id="<?= (int) $s['id'] ?>">
        <span class="week-badge"><?= (int) $s['week'] ?></span>
        <div class="snap-meta">
          <strong>Week <?= (int) $s['week'] ?></strong>
          <span class="subtle">
            <?= htmlspecialchars($s['snapshot_date']) ?>
            &middot; <?= (int) $s['class_count'] ?> classes
            &middot; by <?= htmlspecialchars($s['saved_by_name'] ?: 'Admin') ?>
          </span>
        </div>
        <div class="snap-actions">
          <button type="button" class="btn btn-secondary snap-view"
                  data-id="<?= (int) $s['id'] ?>">View</button>
          <a class="btn btn-secondary"
             href="<?= htmlspecialchars(base_url('api/snapshot_view.php?id=' . (int) $s['id'] . '&download=1')) ?>">
            Download
          </a>
          <button type="button" class="btn btn-danger snap-delete"
                  data-id="<?= (int) $s['id'] ?>">Delete</button>
        </div>
      </li>
    <?php endforeach; endif; ?>
  </ul>
</div>

<!-- Start new week (danger) -->
<div class="card danger-card">
  <div class="section-head">
    <span class="icon" aria-hidden="true">&#9888;</span>
    <div>
      <h2 class="card-title">Start New Week</h2>
      <p class="subtle">Resets all current scores and stars. The archive is not affected.</p>
    </div>
  </div>
  <button type="button" id="resetWeekBtn" class="btn btn-danger">Reset Current Week Data</button>
</div>

<!-- View snapshot modal -->
<div class="modal-backdrop" id="viewModal" role="dialog" aria-modal="true"
     aria-labelledby="viewModalTitle">
  <div class="modal modal-wide">
    <h3 id="viewModalTitle" class="card-title mb-4">Snapshot</h3>
    <pre class="snap-json" id="snapJson"></pre>
    <div class="modal-actions">
      <button type="button" id="closeViewBtn" class="btn btn-secondary">Close</button>
    </div>
  </div>
</div>

<!-- Confirm destructive modal (delete one / clear all / reset) -->
<div class="modal-backdrop" id="confirmModal" role="dialog" aria-modal="true"
     aria-labelledby="confirmTitle">
  <div class="modal">
    <h3 id="confirmTitle" class="card-title mb-4">Are you sure?</h3>
    <p class="subtle mb-4" id="confirmText">This action cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" id="confirmYesBtn" class="btn btn-danger">Confirm</button>
      <button type="button" id="confirmNoBtn" class="btn btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
