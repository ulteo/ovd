function list_apps() {
	new Ajax.Updater(
		$('appsContainer'),
		'apps.php'
	);
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
