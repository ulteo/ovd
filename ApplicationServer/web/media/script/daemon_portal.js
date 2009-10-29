/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

var Portal = Class.create(Daemon, {
	i18n: new Array(),

	applications: new Array(),
	running_applications: new Array(),

	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, printing_applet_version_, debug_]);

		$('portalContainer').style.height = parseInt(this.my_height)-154+'px';
		$('appsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('runningAppsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('fileManagerContainer').style.height = parseInt(this.my_height)-154+'px';

		this.applet_width = 1;
		this.applet_height = 1;
	},

	check_status: function() {
		this.push_log('[portal] check_status()', 'debug');

		new Ajax.Request(
			'../whatsup.php',
			{
				method: 'get',
				asynchronous: false,
				parameters: {
					differentiator: Math.floor(Math.random()*50000)
				},
				onSuccess: this.parse_check_status.bind(this)
			}
		);
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

		this.list_running_apps(applicationsNode);

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

				if (this.nb_share != nb_share_active) {
					this.push_log('[session] Watching desktop: '+nb_share_active+' users', 'info');
					this.nb_share = nb_share_active;
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
		new Ajax.Request(
			'../apps.php',
			{
				method: 'get',
				onSuccess: this.parse_list_apps.bind(this)
			}
		);
	},

	parse_list_apps: function(transport) {
		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('applications');

		if (buffer.length != 1) {
			this.push_log('[applications] bad xml format 1', 'error');
			return;
		}

		var applicationNodes = xml.getElementsByTagName('application');

		for (var i=0; i<applicationNodes.length; i++) {
			try { // IE does not have hasAttribute in DOM API...
				var application = new Application(applicationNodes[i].getAttribute('id'), applicationNodes[i].getAttribute('name'));
				this.applications.push(application);
			} catch(e) {
				this.push_log('[applications] bad xml format 2', 'error');
				return;
			}
		}

		this.applications.sort(this.compareApplication);

		this.generate_html_list_apps();
	},

	generate_html_list_apps: function() {
		var table = document.createElement('table');

		for (var i=0; i<this.applications.length; i++) {
			var application = this.applications[i];

			var tr = document.createElement('tr');

			var td_icon = document.createElement('td');
			var td_icon_link = document.createElement('a');
			td_icon_link.setAttribute('href', 'javascript:;');
			td_icon_link.setAttribute('onclick', 'return startExternalApp(\''+application.id+'\');');
			var icon = document.createElement('img');
			icon.setAttribute('src', application.getIconURL());
			td_icon_link.appendChild(icon);
			td_icon.appendChild(td_icon_link);
			tr.appendChild(td_icon);

			var td_app = document.createElement('td');
			var td_app_link = document.createElement('a');
			td_app_link.setAttribute('href', 'javascript:;');
			td_app_link.setAttribute('onclick', 'return startExternalApp(\''+application.id+'\');');
			td_app_link.innerHTML = application.name;
			td_app.appendChild(td_app_link);
			tr.appendChild(td_app);

			table.appendChild(tr);
		}

		$('appsContainer').appendChild(table);
		$('appsContainer').innerHTML = $('appsContainer').innerHTML; // IE DOM API... appendChild... LOL
	},

	list_running_apps: function(applicationsNode_) {
		this.running_applications = new Array();

		var runningApplicationsNodes = applicationsNode_.getElementsByTagName('running');

		for (var i = 0; i < runningApplicationsNodes.length; i++) {
			var app_id = runningApplicationsNodes[i].getAttribute('app_id');
			var pid = runningApplicationsNodes[i].getAttribute('job');
			var app_status = runningApplicationsNodes[i].getAttribute('status');

			var app_object = this.get_application_from_id(app_id);
			if (app_object == null)
				continue;

			var instance = new Running_Application(app_object.id, app_object.name, pid, app_status);

			this.running_applications.push(instance);
		}

		this.running_applications.sort(this.compareApplication);

		this.generate_html_list_running_apps();
	},

	generate_html_list_running_apps: function() {
		var table = document.createElement('table');

		for (var i=0; i<this.running_applications.length; i++) {
			var instance = this.running_applications[i];

			var tr = document.createElement('tr');

			var td_icon = document.createElement('td');
			var icon = document.createElement('img');
			icon.setAttribute('src', instance.getIconURL());
			td_icon.appendChild(icon);
			tr.appendChild(td_icon);

			var td_app = document.createElement('td');
			var td_app_div = document.createElement('div');
			td_app_div.setAttribute('style', 'font-weight: bold;'); // IE DOM API... setAttribute... LOL
			td_app_div.innerHTML = '<strong>'+instance.name+'</strong>';
			td_app.appendChild(td_app_div);

			if (instance.status == 2) {
				if (this.shareable == true) {
					var node = document.createElement('a');
					node.setAttribute('href', 'javascript:;');
					node.setAttribute('onclick', 'return shareApplication(\''+instance.pid+'\');');
					node.innerHTML = this.i18n['share'];
					td_app.appendChild(node);
				}

				if (this.persistent == true) {
					var node = document.createElement('a');
					node.setAttribute('href', 'javascript:;');
					node.setAttribute('onclick', 'return suspendApplication(\''+instance.pid+'\');');
					node.innerHTML = this.i18n['suspend'];
					td_app.appendChild(node);
				}
			}

			if (instance.status == 10) {
				var node = document.createElement('a');
				node.setAttribute('href', 'javascript:;');
				node.setAttribute('onclick', 'return resumeApplication(\''+instance.pid+'\');');
				node.innerHTML = this.i18n['resume'];
				td_app.appendChild(node);
			}

			var real_childNodes = new Array();
			for (var j=0; j<td_app.childNodes.length; j++)
				real_childNodes.push(td_app.childNodes[j]);

			if (real_childNodes.length > 2) {
				var node = document.createElement('span');
				node.innerHTML = ' - ';

				for (var j=2; j<real_childNodes.length; j++)
					td_app.insertBefore(node.cloneNode(true), real_childNodes[j]);
			}

			tr.appendChild(td_app);

			table.appendChild(tr);
		}

		$('runningAppsContainer').innerHTML = '';

		$('runningAppsContainer').appendChild(table);
		$('runningAppsContainer').innerHTML = $('runningAppsContainer').innerHTML; // IE DOM API... appendChild... LOL
	},

	get_application_from_id: function(app_id_) {
		for (var i=0; i<this.applications.length; i++) {
			if (this.applications[i].id == app_id_)
				return this.applications[i];
		}

		return null;
	},

	compareApplication: function(a, b) {
		if (a.name < b.name)
			return -1;
		if (a.name > b.name)
			return 1;

		return 0;
	},

	load_explorer: function() {
		$('fileManagerContainer').innerHTML = '<iframe style="width: 100%; height: 100%; border: none;" src="ajaxplorer/"></iframe>';
	}
});

function startExternalApp(app_id_) {
	var date = new Date();
	var rand_ = Math.round(Math.random()*100)+date.getTime();

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
	var date = new Date();
	var rand_ = Math.round(Math.random()*100)+date.getTime();

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
