var my_width;
var my_height;

if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
	my_width  = document.documentElement.clientWidth;
	my_height = document.documentElement.clientHeight;
} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
	my_width  = document.body.clientWidth;
	my_height = document.body.clientHeight;
}

var date = new Date();
var rand = Math.round(Math.random()*100)+date.getTime();
var window_;

var startsession = false;

function startSession() {
	disableLogin();

	var use_popup = false;
	if ($('use_popup_true') && $('use_popup_true').checked)
		use_popup = true;

	var debug = false;
	if ($('debug_true') && $('debug_true').checked)
		debug = true;

	var ret = new Ajax.Request(
		'ajax/login.php',
		{
			method: 'post',
			parameters: {
				login: $('user_login').value,
				password: $('user_password').value,
				mode: $('session_mode').value,
				language: $('session_language').value,
				keymap: $('session_keymap').value,
				use_popup: use_popup,
				debug: debug
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
		} catch(e) {}
		enableLogin();
		return false;
	}

	var buffer = xml.getElementsByTagName('session');
	if (buffer.length != 1) {
		enableLogin();
		return false;
	}

	$('user_password').value = '';
	enableLogin();

	startsession = true;

	return true;
}

function onStartSessionFailure(transport) {
	alert('onStartSessionFailure');

	enableLogin();

	startsession = false;

	return false;
}

function popupOpen(rand_) {
	var w = window.open('about:blank', 'Ulteo'+rand_, 'toolbar=no,status=no,top=0,left=0,width='+screen.width+',height='+screen.height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

	return w;
}

function offContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/show.png" width="9" height="9" alt="+" title="" />';
	$(container+'_content').hide();

	return true;
}

function onContent(container) {
	$(container+'_ajax').innerHTML = '<img src="media/image/hide.png" width="9" height="9" alt="-" title="" />';
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
	$('lockWrap').hide();
	$('lockWrap').style.width = my_width+'px';
	$('lockWrap').style.height = my_height+'px';

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
		$('lockWrap').style.width = my_width+'px';
		$('lockWrap').style.height = my_height+'px';

		$('lockWrap').show();
	}
}

function hideLock() {
	if ($('lockWrap').visible())
		$('lockWrap').hide();
}

function showError(errormsg) {
	hideError();

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
	hideOK();

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
	hideInfo();

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

function updateFlag(id_) {
	$('session_language_flag').src = 'media/image/flags/'+id_+'.png';
}

switchsettings_lock = false;
function switchSettings() {
	if (switchsettings_lock)
		return;

	switchsettings_lock = true;
	setTimeout(function() {
		switchsettings_lock = false;
	}, 1200);

	if ($('advanced_settings').visible()) {
		$('advanced_settings_status').innerHTML = '<img src="media/image/show.png" width="9" height="9" alt="" title="" />';
		Effect.SlideUp($('advanced_settings'));
	} else {
		$('advanced_settings_status').innerHTML = '<img src="media/image/hide.png" width="9" height="9" alt="" title="" />';
		Effect.SlideDown($('advanced_settings'));
	}
}
