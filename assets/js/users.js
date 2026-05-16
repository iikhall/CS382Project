/* Users (admin) - jQuery + AJAX: create/edit/delete. */
$(function () {
  var $modal = $('#userModal');
  function closeModal() { $modal.removeClass('is-open'); $('#userError').text(''); }

  function openAdd() {
    $('#userModalTitle').text('Add User');
    $('#userId').val('0');
    $('#uUsername').val('').prop('disabled', false);
    $('#usernameGroup').prop('hidden', false);
    $('#uDisplay').val('');
    $('#uRole').val('staff');
    $('#uPassword').val('');
    $('#uPasswordLabel').text('Password');
    $('#userError').text('');
    $modal.addClass('is-open');
    $('#uUsername').focus();
  }

  function openEdit(btn) {
    var $b = $(btn);
    $('#userModalTitle').text('Edit User');
    $('#userId').val($b.data('id'));
    $('#uUsername').val($b.data('username')).prop('disabled', true);
    $('#usernameGroup').prop('hidden', false);
    $('#uDisplay').val($b.data('display'));
    $('#uRole').val($b.data('role'));
    $('#uPassword').val('');
    $('#uPasswordLabel').text('New Password (leave blank to keep)');
    $('#userError').text('');
    $modal.addClass('is-open');
    $('#uDisplay').focus();
  }

  $('#addUserBtn').on('click', openAdd);
  $('#userTbody').on('click', '.u-edit', function () { openEdit(this); });
  $('#cancelUserBtn').on('click', closeModal);
  $modal.on('click', function (e) { if (e.target === this) { closeModal(); } });

  $('#userForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#saveUserBtn').prop('disabled', true);
    $('#userError').text('');
    $.post('api/user_save.php', {
      id:           $('#userId').val(),
      username:     $('#uUsername').val(),
      display_name: $('#uDisplay').val(),
      role:         $('#uRole').val(),
      password:     $('#uPassword').val()
    }).done(function (res) {
      if (res && res.ok) {
        toast('User saved ✓');
        setTimeout(function () { window.location.reload(); }, 600);
      } else {
        $('#userError').text((res && res.error) || 'Save failed.');
        $btn.prop('disabled', false);
      }
    }).fail(function (xhr) {
      $('#userError').text((xhr.responseJSON && xhr.responseJSON.error) || 'Save failed.');
      $btn.prop('disabled', false);
    });
  });

  /* ---- Delete ---- */
  var $del = $('#delModal');
  var delId = 0;
  $('#userTbody').on('click', '.u-delete', function () {
    delId = $(this).data('id');
    $('#delText').text('Delete user "' + $(this).data('username') + '"? This cannot be undone.');
    $del.addClass('is-open');
  });
  function closeDel() { $del.removeClass('is-open'); delId = 0; }
  $('#cancelDelBtn').on('click', closeDel);
  $del.on('click', function (e) { if (e.target === this) { closeDel(); } });

  $('#confirmDelBtn').on('click', function () {
    var $btn = $(this).prop('disabled', true);
    $.post('api/user_delete.php', { id: delId })
      .done(function (res) {
        if (res && res.ok) {
          $('#userTbody tr[data-id="' + res.id + '"]').remove();
          closeDel();
          toast('User deleted ✓');
        } else {
          toast((res && res.error) || 'Delete failed.', true);
          closeDel();
        }
        $btn.prop('disabled', false);
      })
      .fail(function (xhr) {
        toast((xhr.responseJSON && xhr.responseJSON.error) || 'Delete failed.', true);
        closeDel();
        $btn.prop('disabled', false);
      });
  });

  $(document).on('keydown', function (e) {
    if (e.key !== 'Escape') { return; }
    if ($modal.hasClass('is-open')) { closeModal(); }
    if ($del.hasClass('is-open')) { closeDel(); }
  });
});
