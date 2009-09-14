function list_apps() {
	new Ajax.Updater(
		$('appsContainer'),
		'apps.php'
	);
}

function load_ajaxplorer() {
	$('fileManagerContainer').innerHTML = '<iframe style="width: 100%; height: 100%; border: none;" src="ajaxplorer/"></iframe>';
}

function list_news() {
	new Ajax.Updater(
		$('newsContainer'),
		'get_news.php'
	);

	setTimeout(function() {
		list_news();
	}, 300000);
}

function list_running_apps(apps_) {
	new Ajax.Updater(
		$('runningAppsContainer'),
		'running_apps.php',
		{
			method: 'get',
			parameters: {
				apps: apps_
			}
		}
	);
}

function startExternalApp(app_id_, command_) {
	var rand_ = Math.round(Math.random()*100);

	window_ = popupOpen(rand_);

	setTimeout(function() {
		window_.location.href = 'external_app.php?app_id='+app_id_+'&command='+command_;
	}, 1000);

	return true;
}

function popupOpen(rand_) {
	var my_width = screen.width;
	var my_height = screen.height;
	var new_width = 0;
	var new_height = 0;
	var pos_top = 0;
	var pos_left = 0;

	new_width = my_width;
	new_height = my_height;

	var w = window.open('about:blank', 'Ulteo'+rand_, 'toolbar=no,status=no,top='+pos_top+',left='+pos_left+',width='+new_width+',height='+new_height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

	return w;
}

function suspendApplication(access_id_) {
	new Ajax.Request(
		'application_exit.php',
		{
			method: 'get',
			parameters: {
				access_id: access_id_
			}
		}
	);
}

function resumeApplication(access_id_) {
	var rand_ = Math.round(Math.random()*100);

	window_ = popupOpen(rand_);

	setTimeout(function() {
		window_.location.href = 'resume.php?access_id='+access_id_;
	}, 1000);

	return true;
}

function shareApplication(access_id_) {
	new Ajax.Request(
		'share_app.php',
		{
			method: 'get',
			parameters: {
				access_id: access_id_
			},
			onSuccess: function(transport) {
				showInfo(transport.responseText);
			}
		}
	);
}

Event.observe(window, 'load', function() {
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
	if (!$('lockWrap').visible()) {
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

	$('errorWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideError(); return false"><img src="media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+errormsg;

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

	$('okWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideOk(); return false"><img src="media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+okmsg;

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

	$('infoWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideInfo(); return false"><img src="media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+infomsg;

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
