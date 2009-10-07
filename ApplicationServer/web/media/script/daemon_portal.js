var Portal = Class.create(Daemon, {
	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, printing_applet_version_, debug_]);

		$('portalContainer').style.height = parseInt(this.my_height)-154+'px';
		$('appsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('runningAppsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('fileManagerContainer').style.height = parseInt(this.my_height)-154+'px';

		this.applet_width = 1;
		this.applet_height = 1;
	},

	parse_check_status: function(transport) {
		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('session');

		if (buffer.length != 1) {
			this.push_log('[session] bad xml format 1', 'error');
			return;
		}

		var sessionNode = buffer[0];

		this.old_session_state = this.session_state;

		try { // IE does not have hasAttribute in DOM API...
			this.session_state = sessionNode.getAttribute('status');
		} catch(e) {
			this.push_log('[session] bad xml format 2', 'error');
			return;
		}

		if (this.session_state != this.old_session_state)
			this.push_log('[session] Change status from '+this.old_session_state+' to '+this.session_state, 'info');

		if (this.session_state != 2)
			this.push_log('[session] Status: '+this.session_state, 'warning');
		else
			this.push_log('[session] Status: '+this.session_state, 'debug');

		var buffer = xml.getElementsByTagName('applications');

		if (buffer.length != 1) {
			this.push_log('[applications] bad xml format', 'error');
			return;
		}

		var applicationsNode = buffer[0];

		var runningApplicationsNodes = applicationsNode.getElementsByTagName('running');

		var apps;
		for (var i = 0; i < runningApplicationsNodes.length; i++) {
			var app_id = runningApplicationsNodes[i].getAttribute('app_id');
			var access_id = runningApplicationsNodes[i].getAttribute('job');
			var app_status = runningApplicationsNodes[i].getAttribute('status');

			apps = apps+','+app_id+'-'+access_id+'-'+app_status;
		}

		this.list_running_apps(apps);

		var printNode = sessionNode.getElementsByTagName('print');
		if (printNode.length > 0) {
			printNode = printNode[0];

			var path = printNode.getAttribute('path');
			var timestamp = printNode.getAttribute('time');
			this.do_print(path, timestamp);
		}

		var sharingNode = sessionNode.getElementsByTagName('sharing');
		if (sharingNode.length > 0) {
			sharingNode = sharingNode[0];

			try { // IE does not have hasAttribute in DOM API...
				var nb = sharingNode.getAttribute('count');
			} catch(e) {
				this.push_log('[session] bad xml format 3', 'error');
				return;
			}

			if (nb > 0) {
				var shareNodes = sharingNode.getElementsByTagName('share');

				var html = '<div style="margin-left: 0px; margin-right: 0px; text-align: left"><ul>';

				var nb_share_active = 0;
				for (var i = 0; i < shareNodes.length; i++) {
					var buf = shareNodes[i];

					var email = buf.getAttribute('email');
					var mode = buf.getAttribute('mode');
					var alive = buf.getAttribute('alive');
					if (alive == 1)
					nb_share_active += 1;
					var joined = buf.getAttribute('joined');

					html += '<li>';

					html += '<span style="';
					if (alive != 1 && joined != 1)
					html += 'color: orange;';
					if (alive == 1 && joined == 1)
					html += 'color: green;';
					if (alive != 1 && joined == 1)
					html += 'color: blue; text-decoration: line-through;';
					html += '">'+email+'</span>';

					html += ' ('+mode+')</li>';
				}

				html += '</ul></div>';

				$('menuShareContent').innerHTML = html;

				if (nb_share != nb_share_active) {
					this.push_log('[session] Watching desktop: '+nb_share_active+' users', 'info');
					nb_share = nb_share_active;
				}

				if (nb_share_active != 0) {
					var buf_html = '<img style="margin-left: 5px;" src="../media/image/watch_icon.png" width="16" height="16" alt="" title="" /> <span style="font-size: 0.8em;">Currently watching your desktop: '+nb_share_active+' user';
					if (nb_share_active > 1)
					buf_html += 's';
					buf_html += '</span>';
					$('menuShareWarning').innerHTML = buf_html;
				} else
					$('menuShareWarning').innerHTML = '';
			}
		}
	},

	start: function() {
		Daemon.prototype.start.apply(this);

		this.display_news();
		this.list_apps();
		this.load_explorer();
	},

	do_ended: function() {
		Daemon.prototype.do_ended.apply(this);

		$('mainWrap').hide();
	},

	display_news: function() {
		new Ajax.Updater(
			$('newsContainer'),
			'get_news.php'
		);

		setTimeout(this.display_news.bind(this), 300000);
	},

	list_apps: function() {
		new Ajax.Updater(
			$('appsContainer'),
			'apps.php'
		);
	},

	list_running_apps: function(apps_) {
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
	},

	load_explorer: function() {
		$('fileManagerContainer').innerHTML = '<iframe style="width: 100%; height: 100%; border: none;" src="ajaxplorer/"></iframe>';
	}
});

function startExternalApp(app_id_) {
	var rand_ = Math.round(Math.random()*100);

	window_ = popupOpen(rand_);

	setTimeout(function() {
		window_.location.href = 'external_app.php?app_id='+app_id_;
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

	$('errorWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideError(); return false"><img src="../media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+errormsg;

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

	$('okWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideOk(); return false"><img src="../media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+okmsg;

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

	$('infoWrap').innerHTML = '<div style="width: 16px; height: 16px; float: right"><a href="javascript:;" onclick="hideInfo(); return false"><img src="../media/image/close.png" width="16" height="16" alt="fermer" title="Fermer" /></a></div>'+infomsg;

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
