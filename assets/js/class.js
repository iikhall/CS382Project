/* Class detail - jQuery + AJAX: live sliders, save, award-star modal. */
$(function () {
  var classId = $('.class-detail-grid').data('class-id');
  var KEYS = ['order', 'cleanliness', 'behavior'];

  // Supervisor/admin: assign a teacher to a course in this class.
  $('.course-teacher-save').on('click', function () {
    var sid = $(this).data('subject-id');
    var $btn = $(this).prop('disabled', true);
    $.post('api/subject_assign_teacher.php', {
      subject_id: sid,
      teacher: $('.course-teacher[data-subject-id="' + sid + '"]').val()
    }).done(function (res) {
      window.toast(res && res.ok ? 'Teacher assigned ✓'
        : ((res && res.error) || 'Could not assign teacher.'), !(res && res.ok));
      $btn.prop('disabled', false);
    }).fail(function (xhr) {
      window.toast((xhr.responseJSON && xhr.responseJSON.error) || 'Could not assign teacher.', true);
      $btn.prop('disabled', false);
    });
  });

  function recalcTotal() {
    var total = 0;
    KEYS.forEach(function (k) { total += parseInt($('#' + k).val(), 10) || 0; });
    $('#totalScore').text(total);
  }

  // Live slider readout + total badge.
  KEYS.forEach(function (k) {
    $('#' + k).on('input', function () {
      $('#' + k + 'Out').text($(this).val() + ' / 10');
      recalcTotal();
    });
  });

  // Save (admin only - button absent for staff).
  $('#evalForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#saveBtn');
    if (!$btn.length || $btn.prop('disabled')) { return; }
    $btn.prop('disabled', true);

    $.post('api/class_update.php', {
      id:          classId,
      order:       $('#order').val(),
      cleanliness: $('#cleanliness').val(),
      behavior:    $('#behavior').val(),
      leader:      $('#leader').val(),
      supervisor:  $('#supervisor').val(),
      notes:       $('#notes').val()
    }).done(function (res) {
      if (res && res.ok) {
        $('#totalScore').text(res.total);
        $btn.addClass('is-saved').text('Saved ✓');
        setTimeout(function () {
          $btn.removeClass('is-saved').text('Save Changes').prop('disabled', false);
        }, 1500);
      } else {
        toast((res && res.error) || 'Save failed.', true);
        $btn.prop('disabled', false);
      }
    }).fail(function (xhr) {
      toast((xhr.responseJSON && xhr.responseJSON.error) || 'Save failed.', true);
      $btn.prop('disabled', false);
    });
  });

  // Award-star modal.
  var $modal = $('#starModal');
  function openModal()  { $modal.addClass('is-open'); $('#reason').val('').focus(); }
  function closeModal() { $modal.removeClass('is-open'); $('#reasonError').text(''); }

  $('#awardStarBtn').on('click', openModal);
  $('#cancelStarBtn').on('click', closeModal);
  $modal.on('click', function (e) { if (e.target === this) { closeModal(); } });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $modal.hasClass('is-open')) { closeModal(); }
  });

  $('#confirmStarBtn').on('click', function () {
    var $btn = $(this).prop('disabled', true);
    $.post('api/star_award.php', {
      class_id: classId,
      reason:   $.trim($('#reason').val())
    }).done(function (res) {
      if (res && res.ok) {
        $('#starsEmpty').remove();
        var s = res.star;
        var $li = $('<li class="star-item"></li>');
        $li.append('<span class="star-glyph" aria-hidden="true">★</span>');
        var $body = $('<div></div>');
        $body.append($('<strong></strong>').text(s.awarded_by_name));
        $body.append($('<span class="subtle"></span>').text(s.awarded_at));
        if (s.reason) { $body.append($('<p class="star-reason"></p>').text(s.reason)); }
        $li.append($body);
        $('#starList').prepend($li);
        closeModal();
        toast('Star awarded ✓');
      } else {
        $('#reasonError').text((res && res.error) || 'Could not award star.');
      }
      $btn.prop('disabled', false);
    }).fail(function (xhr) {
      $('#reasonError').text((xhr.responseJSON && xhr.responseJSON.error) || 'Could not award star.');
      $btn.prop('disabled', false);
    });
  });
});
