var form_ = '';
var window_ = '';
var rand_ = Math.round(Math.random()*100);

function doLogin(this_) {
	form_ = this_;
	window_ = popupOpen(rand_);

	$('launch_button').disabled = true;

	new Ajax.Request(
		'ajax/login.php',
		{
			method: 'post',
			parameters: {
				do_login: 1,
				login: $('login_login').value,
				password: $('login_password').value
			},
			asynchronous: false,
			onSuccess: onLoginSuccess,
			onFailure: onLoginFailure
		}
	);

	setTimeout(function() {
		$('launch_button').disabled = false;
	}, 1000);

	return false;
}

function onLoginSuccess(transport) {
	$('login_status').innerHTML = '<p class="msg_ok">'+transport.responseText+'</p>';

	$('startsession').target = 'Ulteo'+rand_;
	$('startsession').submit();

	return true;
}

function onLoginFailure(transport) {
	$('login_status').innerHTML = '<p class="msg_error">'+transport.responseText+'</p>';

	sessionStop();

	window_.close();

	return false;
}
