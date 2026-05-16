/* Shared UI helpers. Loaded on every authenticated page before page scripts. */
(function () {
  // Single polite live region so toasts are announced to screen readers.
  var $region;
  $(function () {
    $region = $('<div class="sr-only" role="status" aria-live="polite"></div>');
    $('body').append($region);
  });

  window.toast = function (msg, danger) {
    var $t = $('<div class="toast"></div>').text(msg);
    if (danger) { $t.addClass('is-danger'); }
    $('body').append($t);
    if ($region) { $region.text(msg); }
    requestAnimationFrame(function () { $t.addClass('is-visible'); });
    setTimeout(function () {
      $t.removeClass('is-visible');
      setTimeout(function () { $t.remove(); }, 250);
    }, 1800);
  };
})();
