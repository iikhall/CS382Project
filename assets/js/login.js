/* Login - jQuery + AJAX ($.post to api/login.php). */
$(function () {
  var $form  = $('#loginForm');
  var $btn   = $('#loginBtn');
  var $alert = $('#loginAlert');

  function showAlert(msg) {
    $alert.text(msg).addClass('is-visible');
  }
  function clearErrors() {
    $alert.removeClass('is-visible').text('');
    $('.field-error').text('');
    $('.form-control').removeClass('is-error');
  }
  function fieldError(id, msg) {
    $('#' + id).addClass('is-error');
    $('#' + id + 'Error').text(msg);
  }

  $form.on('submit', function (e) {
    e.preventDefault();
    clearErrors();

    var username = $.trim($('#username').val());
    var password = $('#password').val();
    var valid = true;

    if (username === '') { fieldError('username', 'Username is required.'); valid = false; }
    if (password === '') { fieldError('password', 'Password is required.'); valid = false; }
    if (!valid) { return; }

    $btn.prop('disabled', true).text('Signing in...');

    $.post('api/login.php', { username: username, password: password })
      .done(function (res) {
        if (res && res.ok) {
          window.location.href = res.redirect;
        } else {
          showAlert((res && res.error) || 'Login failed.');
          $btn.prop('disabled', false).text('Sign In');
        }
      })
      .fail(function (xhr) {
        var msg = 'Login failed. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.error) {
          msg = xhr.responseJSON.error;
        }
        showAlert(msg);
        $btn.prop('disabled', false).text('Sign In');
      });
  });
});
