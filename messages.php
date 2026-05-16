<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_admin();

$summary  = Feedback::summary($db);
$messages = Message::all($db);

// Category -> CSS modifier (kept in sync with Message::CATEGORIES).
$catClass = [
    'Complaint'            => 'cat-danger',
    'Inquiry'              => 'cat-primary',
    'Status Report'        => 'cat-success',
    'Consultation Request' => 'cat-warning',
];

$pageTitle   = 'Messages';
$activeNav   = 'messages';
$pageScripts = [base_url('assets/js/messages.js')];
require __DIR__ . '/includes/header.php';
?>

<div class="flex-between mb-4">
  <div>
    <h1 class="page-title">Messages &amp; Ratings</h1>
    <p class="subtle">Internal staff submissions.</p>
  </div>
  <?php if ($messages): ?>
    <button type="button" id="clearAllBtn" class="btn btn-danger">Clear All Messages</button>
  <?php endif; ?>
</div>

<!-- Summary -->
<section class="grid grid-3 mb-4" aria-label="Summary">
  <div class="stat-card card" data-accent="0">
    <div class="stat-card-value" id="sumTotalRatings"><?= (int) $summary['total'] ?></div>
    <div class="stat-card-label">Total Ratings</div>
  </div>
  <div class="stat-card card" data-accent="1">
    <div class="stat-card-value" id="sumAvgRating"><?= htmlspecialchars((string) $summary['average']) ?></div>
    <div class="stat-card-label">Average Score</div>
  </div>
  <div class="stat-card card" data-accent="2">
    <div class="stat-card-value" id="sumMsgCount"><?= count($messages) ?></div>
    <div class="stat-card-label">Messages Received</div>
  </div>
</section>

<!-- Messages list -->
<div class="card">
  <h2 class="card-title mb-4">Contact Messages</h2>
  <ul class="message-list" id="messageList">
    <?php if (!$messages): ?>
      <li class="empty-state" id="messagesEmpty">
        <span class="empty-star" aria-hidden="true">&#9993;</span>
        No messages yet
      </li>
    <?php else: foreach ($messages as $m): ?>
      <li class="message-item">
        <div class="message-head">
          <span class="cat-tag <?= $catClass[$m['category']] ?? 'cat-primary' ?>">
            <?= htmlspecialchars($m['category']) ?>
          </span>
          <span class="subtle">
            To: <?= htmlspecialchars($m['recipient']) ?>
            &middot; <?= htmlspecialchars($m['created_at']) ?>
          </span>
        </div>
        <p class="message-body"><?= htmlspecialchars($m['message']) ?></p>
        <span class="subtle">
          From: <?= htmlspecialchars($m['sender'] ?? 'Unknown') ?>
        </span>
      </li>
    <?php endforeach; endif; ?>
  </ul>
</div>

<!-- Confirm clear modal -->
<div class="modal-backdrop" id="clearModal" role="dialog" aria-modal="true"
     aria-labelledby="clearModalTitle">
  <div class="modal">
    <h3 id="clearModalTitle" class="card-title mb-4">Clear all messages?</h3>
    <p class="subtle mb-4">This permanently deletes every contact message. This cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" id="confirmClearBtn" class="btn btn-danger">Delete All</button>
      <button type="button" id="cancelClearBtn" class="btn btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
