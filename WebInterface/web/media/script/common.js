var date = new Date();
var rand = Math.round(Math.random()*100)+date.getTime();
var window_;

var startsession = false;

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

	if (! startsession)
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

	var buffer = xml.getElementsByTagName('error');
	if (buffer.length == 1) {
		try {
			showError(buffer[0].getAttribute('message'));
		} catch(e) {
		}
		enableLogin();
		return false;
	}

	var buffer = xml.getElementsByTagName('session');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}
	session = buffer[0];

	var buffer = session.getElementsByTagName('user');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}
	user = buffer[0];

	var buffer = session.getElementsByTagName('server');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}
	server = buffer[0];

	$('session_id').value = session.getAttribute('id');
	$('session_mode').value = session.getAttribute('mode');
	$('session_login').value = user.getAttribute('login');
	$('session_displayname').value = user.getAttribute('displayName');
	$('session_server').value = server.getAttribute('fqdn');
	$('session_login').value = server.getAttribute('login');
	$('session_password').value = server.getAttribute('password');

	$('user_password').value = '';
	enableLogin();

	startsession = true;

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

function offContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/show.png" width="16" height="16" alt="+" title="" />';
	$(container+'_content').hide();

	return true;
}

function onContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/hide.png" width="16" height="16" alt="-" title="" />';
	$(container+'_content').show();

	return true;
}

function toggleContent(container) {
	if ($(container+'_content').visible())
		offContent(container);
	else
		onContent(container);

	return true;
}

Event.observe(window, 'load', function() {
	Effect.Center($('loginBox'));
	$('loginBox').style.top = (parseInt($('loginBox').style.top)-50)+'px';

	$('lockWrap').hide();
	$('lockWrap').style.width = document.body.clientWidth+'px';
	$('lockWrap').style.height = document.body.clientHeight+'px';

	$('errorWrap').hide();
	$('okWrap').hide();
	$('infoWrap').hide();

	Event.observe($('lockWrap'), 'click', function() {
		if ($('errorWrap').visible())
			hideError();

		if ($('okWrap').visible())
			hideOk();

		if ($('infoWrap').visible())
			hideInfo();
	});
});

function showLock() {
	if (! $('lockWrap').visible()) {
		$('lockWrap').style.width = document.body.clientWidth+'px';
		$('lockWrap').style.height = document.body.clientHeight+'px';

		$('lockWrap').show();
	}
}

function hideLock() {
	if ($('lockWrap').visible())
		$('lockWrap').hide();
}

function showError(errormsg) {
	hideOk();
	hideInfo();

	showLock();

	$('errorWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideError(); return false"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+errormsg;

	Effect.Center($('errorWrap'));

	Effect.Appear($('errorWrap'));
}

function hideError() {
	$('errorWrap').hide();

	hideLock();

	$('errorWrap').innerHTML = '';
	$('errorWrap').style.width = '';
	$('errorWrap').style.height = '';
}

function showOk(okmsg) {
	hideInfo();

	showLock();

	$('okWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideOk(); return false"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+okmsg;

	Effect.Center($('okWrap'));

	Effect.Appear($('okWrap'));
}

function hideOk() {
	$('okWrap').hide();

	hideLock();

	$('okWrap').innerHTML = '';
	$('okWrap').style.width = '';
	$('okWrap').style.height = '';
}

function showInfo(infomsg) {
	showLock();

	$('infoWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideInfo(); return false"><img src="media/image/cross.png" width="16" height="16" alt="" title="" /></a></div>'+infomsg;

	Effect.Center($('infoWrap'));

	Effect.Appear($('infoWrap'));
}

function hideInfo() {
	$('infoWrap').hide();

	hideLock();

	$('infoWrap').innerHTML = '';
	$('infoWrap').style.width = '';
	$('infoWrap').style.height = '';
}
