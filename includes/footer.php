<?php
declare(strict_types=1);

/**
 * Shared page shell (bottom). Optional before include:
 *   $pageScripts string[]  - extra <script src> URLs (CDN or base_url'd)
 */
$pageScripts = $pageScripts ?? [];
?>
    </div><!-- /.container -->
  </main>

  <footer class="app-footer">
    <div class="container">
      Smart School Dashboard &mdash; CS382 Internal System &copy; <?= date('Y') ?>
    </div>
  </footer>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
          integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
          crossorigin="anonymous"></script>
  <script src="<?= htmlspecialchars(base_url('assets/js/app.js')) ?>"></script>
  <?php foreach ($pageScripts as $src): ?>
  <script src="<?= htmlspecialchars($src) ?>"></script>
  <?php endforeach; ?>
</body>
</html>
