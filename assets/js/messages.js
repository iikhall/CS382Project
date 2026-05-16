/* Messages (admin) - jQuery + AJAX: confirm-clear modal. */
$(function () {
  var $modal = $('#clearModal');
  function open()  { $modal.addClass('is-open'); }
  function close() { $modal.removeClass('is-open'); }

  $('#clearAllBtn').on('click', open);
  $('#cancelClearBtn').on('click', close);
  $modal.on('click', function (e) { if (e.target === this) { close(); } });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $modal.hasClass('is-open')) { close(); }
  });

  $('#confirmClearBtn').on('click', function () {
    var $btn = $(this).prop('disabled', true);
    $.post('api/messages_clear.php', {})
      .done(function (res) {
        if (res && res.ok) {
          $('#messageList').html(
            '<li class="empty-state" id="messagesEmpty">' +
            '<span class="empty-star" aria-hidden="true">✉</span>No messages yet</li>'
          );
          $('#sumMsgCount').text(res.count);
          $('#clearAllBtn').remove();
          close();
          toast('All messages cleared ✓');
        } else {
          toast((res && res.error) || 'Could not clear messages.', true);
          $btn.prop('disabled', false);
        }
      })
      .fail(function (xhr) {
        toast((xhr.responseJSON && xhr.responseJSON.error) || 'Could not clear messages.', true);
        $btn.prop('disabled', false);
      });
  });
});
