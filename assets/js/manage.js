/* Class & course management (admin) - jQuery + AJAX. */
$(function () {
  function reloadSoon() { setTimeout(function () { window.location.reload(); }, 550); }

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

  /* ---- Add class ---- */
  var $classModal = $('#classModal');
  $('#addClassBtn').on('click', function () {
    $('#classForm')[0].reset();
    $('#cSection').val(1);
    $('#cSemester').val('Semester 1');
    $('#classError').text('');
    $classModal.addClass('is-open');
    $('#cCode').focus();
  });
  $('#cancelClassBtn').on('click', function () { $classModal.removeClass('is-open'); });
  $classModal.on('click', function (e) { if (e.target === this) { $classModal.removeClass('is-open'); } });

  $('#classForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $(this).find('button[type=submit]').prop('disabled', true);
    $('#classError').text('');
    $.post('api/class_create.php', {
      code:       $('#cCode').val(),
      name:       $('#cName').val(),
      grade:      $('#cGrade').val(),
      section:    $('#cSection').val(),
      semester:   $('#cSemester').val(),
      supervisor: $('#cSupervisor').val()
    }).done(function (res) {
      if (res && res.ok) {
        window.toast('Class created ✓');
        reloadSoon();
      } else {
        $('#classError').text((res && res.error) || 'Could not create class.');
        $btn.prop('disabled', false);
      }
    }).fail(function (xhr) {
      $('#classError').text((xhr.responseJSON && xhr.responseJSON.error) || 'Could not create class.');
      $btn.prop('disabled', false);
    });
  });

  $('#classWrap').on('click', '.del-class', function () {
    var id = $(this).data('id');
    askConfirm('Delete class "' + $(this).data('name') +
               '"? Its courses and stars are removed too.', function () {
      $.post('api/class_delete.php', { id: id })
        .done(function (res) {
          if (res && res.ok) { window.toast('Class deleted ✓'); reloadSoon(); }
          else { window.toast((res && res.error) || 'Delete failed.', true); }
        })
        .fail(function () { window.toast('Delete failed.', true); });
    });
  });

  /* ---- Add / edit course ---- */
  var $courseModal = $('#courseModal');
  function openCourse(opts) {
    $('#courseModalTitle').text(opts.id ? 'Edit Course' : 'Add Course');
    $('#sId').val(opts.id || 0);
    $('#sClassId').val(opts.classId);
    $('#sName').val(opts.name || '');
    $('#sTeacher').val(opts.teacher || '');
    $('#sExcellent').val(opts.excellent || 0);
    $('#sVeryGood').val(opts.very_good || 0);
    $('#sGood').val(opts.good || 0);
    $('#sAcceptable').val(opts.acceptable || 0);
    $('#sFail').val(opts.fail || 0);
    $('#courseError').text('');
    $courseModal.addClass('is-open');
    $('#sName').focus();
  }
  $('#classWrap').on('click', '.add-course', function () {
    openCourse({ classId: $(this).data('class-id') });
  });
  $('#classWrap').on('click', '.edit-course', function () {
    var d = $(this).data();
    openCourse({
      id: d.id, classId: d.classId, name: d.name, teacher: d.teacher,
      excellent: d.excellent, very_good: d.very_good, good: d.good,
      acceptable: d.acceptable, fail: d.fail
    });
  });
  $('#cancelCourseBtn').on('click', function () { $courseModal.removeClass('is-open'); });
  $courseModal.on('click', function (e) { if (e.target === this) { $courseModal.removeClass('is-open'); } });

  $('#courseForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $(this).find('button[type=submit]').prop('disabled', true);
    $('#courseError').text('');
    $.post('api/subject_save.php', {
      id:         $('#sId').val(),
      class_id:   $('#sClassId').val(),
      name:       $('#sName').val(),
      teacher:    $('#sTeacher').val(),
      excellent:  $('#sExcellent').val(),
      very_good:  $('#sVeryGood').val(),
      good:       $('#sGood').val(),
      acceptable: $('#sAcceptable').val(),
      fail:       $('#sFail').val()
    }).done(function (res) {
      if (res && res.ok) {
        window.toast('Course saved ✓');
        reloadSoon();
      } else {
        $('#courseError').text((res && res.error) || 'Could not save course.');
        $btn.prop('disabled', false);
      }
    }).fail(function (xhr) {
      $('#courseError').text((xhr.responseJSON && xhr.responseJSON.error) || 'Could not save course.');
      $btn.prop('disabled', false);
    });
  });

  $('#classWrap').on('click', '.del-course', function () {
    var id = $(this).data('id');
    askConfirm('Delete course "' + $(this).data('name') + '"?', function () {
      $.post('api/subject_delete.php', { id: id })
        .done(function (res) {
          if (res && res.ok) { window.toast('Course deleted ✓'); reloadSoon(); }
          else { window.toast((res && res.error) || 'Delete failed.', true); }
        })
        .fail(function () { window.toast('Delete failed.', true); });
    });
  });

  /* ---- Assign supervisor to a whole grade ---- */
  $('.grade-sup-save').on('click', function () {
    var grade = $(this).data('grade');
    var $btn = $(this).prop('disabled', true);
    $.post('api/grade_assign_supervisor.php', {
      grade: grade,
      supervisor_user_id: $('.grade-sup-select[data-grade="' + grade + '"]').val()
    }).done(function (res) {
      if (res && res.ok) { window.toast('Supervisor updated ✓'); }
      else { window.toast((res && res.error) || 'Could not assign supervisor.', true); }
      $btn.prop('disabled', false);
    }).fail(function (xhr) {
      window.toast((xhr.responseJSON && xhr.responseJSON.error) || 'Could not assign supervisor.', true);
      $btn.prop('disabled', false);
    });
  });

  $(document).on('keydown', function (e) {
    if (e.key !== 'Escape') { return; }
    $('.modal-backdrop.is-open').removeClass('is-open');
  });
});
