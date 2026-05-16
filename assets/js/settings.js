/* Settings (admin) - jQuery + AJAX: info, snapshots, reset. */
$(function () {
  /* ---- Generic confirm modal ---- */
  var $confirm = $('#confirmModal');
  var pending = null;
  function askConfirm(text, action) {
    $('#confirmText').text(text);
    pending = action;
    $confirm.addClass('is-open');
  }
  function closeConfirm() { $confirm.removeClass('is-open'); pending = null; }
  $('#confirmNoBtn').on('click', closeConfirm);
  $confirm.on('click', function (e) { if (e.target === this) { closeConfirm(); } });
  $('#confirmYesBtn').on('click', function () {
    if (typeof pending === 'function') { pending(); }
    closeConfirm();
  });

  /* ---- School info ---- */
  $('#infoForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#saveInfoBtn').prop('disabled', true);
    $('#infoError').text('');
    $.post('api/settings_save.php', {
      school:         $('#school').val(),
      principal:      $('#principal').val(),
      vice_principal: $('#vice_principal').val()
    }).done(function (res) {
      if (res && res.ok) {
        $btn.addClass('is-saved').text('Saved ✓');
        setTimeout(function () {
          $btn.removeClass('is-saved').text('Save Info').prop('disabled', false);
        }, 1500);
      } else {
        $('#infoError').text((res && res.error) || 'Save failed.');
        $btn.prop('disabled', false);
      }
    }).fail(function (xhr) {
      $('#infoError').text((xhr.responseJSON && xhr.responseJSON.error) || 'Save failed.');
      $btn.prop('disabled', false);
    });
  });

  /* ---- Save snapshot ---- */
  $('#saveSnapBtn').on('click', function () {
    var $btn = $(this).prop('disabled', true);
    $.post('api/snapshot_save.php', { date: $('#snapDate').val() })
      .done(function (res) {
        if (res && res.ok) {
          var s = res.snapshot;
          $('#snapsEmpty').remove();
          var html =
            '<li class="snap-item" data-id="' + s.id + '">' +
            '<span class="week-badge">' + s.week + '</span>' +
            '<div class="snap-meta"><strong>Week ' + s.week + '</strong>' +
            '<span class="subtle">' + s.snapshot_date + ' · by ' +
            $('<i>').text(s.saved_by_name || 'Admin').html() + '</span></div>' +
            '<div class="snap-actions">' +
            '<button type="button" class="btn btn-secondary snap-view" data-id="' + s.id + '">View</button>' +
            '<a class="btn btn-secondary" href="api/snapshot_view.php?id=' + s.id + '&download=1">Download</a>' +
            '<button type="button" class="btn btn-danger snap-delete" data-id="' + s.id + '">Delete</button>' +
            '</div></li>';
          $('#snapList').prepend(html);
          $('#clearSnapsBtn').prop('hidden', false);
          toast('Week saved to archive ✓');
        } else {
          toast((res && res.error) || 'Could not save snapshot.', true);
        }
        $btn.prop('disabled', false);
      })
      .fail(function (xhr) {
        toast((xhr.responseJSON && xhr.responseJSON.error) || 'Could not save snapshot.', true);
        $btn.prop('disabled', false);
      });
  });

  /* ---- View snapshot ---- */
  var $viewModal = $('#viewModal');
  function closeView() { $viewModal.removeClass('is-open'); }
  $('#closeViewBtn').on('click', closeView);
  $viewModal.on('click', function (e) { if (e.target === this) { closeView(); } });

  $('#snapList').on('click', '.snap-view', function () {
    var id = $(this).data('id');
    $.getJSON('api/snapshot_view.php', { id: id })
      .done(function (res) {
        if (res && res.ok) {
          $('#viewModalTitle').text('Snapshot — Week ' + res.snapshot.week +
                                    ' (' + res.snapshot.snapshot_date + ')');
          $('#snapJson').text(JSON.stringify(res.snapshot.classes, null, 2));
          $viewModal.addClass('is-open');
        } else {
          toast((res && res.error) || 'Could not load snapshot.', true);
        }
      })
      .fail(function () { toast('Could not load snapshot.', true); });
  });

  /* ---- Delete one ---- */
  $('#snapList').on('click', '.snap-delete', function () {
    var id = $(this).data('id');
    askConfirm('Delete this saved week? This cannot be undone.', function () {
      $.post('api/snapshot_delete.php', { id: id })
        .done(function (res) {
          if (res && res.ok) {
            $('.snap-item[data-id="' + id + '"]').remove();
            if (!$('#snapList .snap-item').length) {
              $('#snapList').html(
                '<li class="empty-state" id="snapsEmpty">' +
                '<span class="empty-star" aria-hidden="true">🕒</span>No saved weeks yet</li>'
              );
              $('#clearSnapsBtn').prop('hidden', true);
            }
            toast('Snapshot deleted ✓');
          } else {
            toast((res && res.error) || 'Delete failed.', true);
          }
        })
        .fail(function () { toast('Delete failed.', true); });
    });
  });

  /* ---- Clear all snapshots ---- */
  $('#clearSnapsBtn').on('click', function () {
    askConfirm('Delete the entire weekly archive? This cannot be undone.', function () {
      $.post('api/snapshot_delete.php', { all: 1 })
        .done(function (res) {
          if (res && res.ok) {
            $('#snapList').html(
              '<li class="empty-state" id="snapsEmpty">' +
              '<span class="empty-star" aria-hidden="true">🕒</span>No saved weeks yet</li>'
            );
            $('#clearSnapsBtn').prop('hidden', true);
            toast('Archive cleared ✓');
          } else {
            toast((res && res.error) || 'Clear failed.', true);
          }
        })
        .fail(function () { toast('Clear failed.', true); });
    });
  });

  /* ---- Reset current week ---- */
  $('#resetWeekBtn').on('click', function () {
    askConfirm('Reset all current scores and stars? The archive is not affected.', function () {
      $.post('api/week_reset.php', {})
        .done(function (res) {
          if (res && res.ok) {
            toast('Current week reset ✓');
          } else {
            toast((res && res.error) || 'Reset failed.', true);
          }
        })
        .fail(function () { toast('Reset failed.', true); });
    });
  });

  $(document).on('keydown', function (e) {
    if (e.key !== 'Escape') { return; }
    if ($viewModal.hasClass('is-open')) { closeView(); }
    if ($confirm.hasClass('is-open')) { closeConfirm(); }
  });
});
