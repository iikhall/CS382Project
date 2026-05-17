/* Attendance - jQuery + AJAX: save monthly rates. */
$(function () {
  $('#attendanceForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#saveAttBtn').prop('disabled', true);
    $('#attErr').text('');
    $.post('api/attendance_save.php', $(this).serialize())
      .done(function (res) {
        if (res && res.ok) {
          $btn.addClass('is-saved').text('Saved ✓');
          setTimeout(function () {
            $btn.removeClass('is-saved').text('Save Attendance').prop('disabled', false);
          }, 1500);
        } else {
          $('#attErr').text((res && res.error) || 'Save failed.');
          $btn.prop('disabled', false);
        }
      })
      .fail(function (xhr) {
        $('#attErr').text((xhr.responseJSON && xhr.responseJSON.error) || 'Save failed.');
        $btn.prop('disabled', false);
      });
  });
});
