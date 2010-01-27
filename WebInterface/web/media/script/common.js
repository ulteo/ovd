var date = new Date();
var rand = Math.round(Math.random()*100)+date.getTime();
var window_;

function startSession(login_, password_, mode_) {
	disableLogin();

	var ret = new Ajax.Request(
		'ajax/login.php',
		{
			method: 'post',
			parameters: {
				login: login_,
				password: password_,
				mode: mode_
			},
			asynchronous: false,
			onSuccess: onStartSessionSuccess,
			onFailure: onStartSessionFailure
		}
	);

	if (parseInt(ret.getStatus()) != 200)
		return false;

	if ($('use_popup_true') && $('use_popup_true').checked) {
		window_ = popupOpen(rand);
		$('startsession').target = 'Ulteo'+rand;
	}

	return true;
}

function disableLogin() {
	$('submitButton').hide();
	$('submitLoader').show();
}

function enableLogin() {
	$('submitButton').show();
	$('submitLoader').hide();
}

function onStartSessionSuccess(transport) {
	var xml = transport.responseXML;

	var buffer = xml.getElementsByTagName('session');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}
	session = buffer[0];

	var buffer = session.getElementsByTagName('server');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}
	server = buffer[0];

	var session_id = session.getAttribute('id');
	var session_server = server.getAttribute('fqdn');
	var session_login = server.getAttribute('login');
	var session_password = server.getAttribute('password');

	$('session_id').value = session_id;
	$('session_server').value = session_server;
	$('session_login').value = session_login;
	$('session_password').value = session_password;

	return true;
}

function onStartSessionFailure(transport) {
	alert('onStartSessionFailure');

	enableLogin();

	return false;
}

function popupOpen(rand_) {
	var w = window.open('about:blank', 'Ulteo'+rand_, 'toolbar=no,status=no,top=0,left=0,width='+screen.width+',height='+screen.height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

	return w;
}
