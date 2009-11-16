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
	applications: new Hash(),
	applicationsPanel: null,
	running_applications: new Hash(),
	runningApplicationsPanel: null,

	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, printing_applet_version_, debug_]);

		$('portalContainer').style.height = parseInt(this.my_height)-154+'px';
		$('appsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('runningAppsContainer').style.height = parseInt(this.my_height)-154+'px';
		$('fileManagerContainer').style.height = parseInt(this.my_height)-154+'px';

		this.applicationsPanel = new ApplicationsPanel($('appsContainer'));
		this.runningApplicationsPanel = new ApplicationsPanel($('runningAppsContainer'));

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
		this.access_id = 'portal';

		Daemon.prototype.start.apply(this);

		this.display_news();
		this.list_apps();
		this.load_explorer();
	},

	do_ended: function() {
		Daemon.prototype.do_ended.apply(this);

		if ($('endMessage')) {
			if (this.error_message != '')
				$('endMessage').innerHTML = '<span class="msg_error">'+this.i18n['session_end_unexpected']+'</span>';
			else
				$('endMessage').innerHTML = this.i18n['session_end_ok'];
		}

		if ($('mainWrap')) {
			$('mainWrap').innerHTML = '';
			$('mainWrap').hide();
		}
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
				this.applications.set(application.id, application);
				this.applicationsPanel.add(application);
			} catch(e) {
				this.push_log('[applications] bad xml format 2', 'error');
				return;
			}
		}
	},

	list_running_apps: function(applicationsNode_) {
		var runningApplicationsNodes = applicationsNode_.getElementsByTagName('running');

		var apps_in_xml = new Array();
		for (var i = 0; i < runningApplicationsNodes.length; i++) {
			var pid = runningApplicationsNodes[i].getAttribute('job');
			var app_status = parseInt(runningApplicationsNodes[i].getAttribute('status'));

			if (typeof this.running_applications.get(pid) == 'undefined') {
				var app_id = runningApplicationsNodes[i].getAttribute('app_id');

				var app_object = this.applications.get(app_id);
				if (typeof app_object == 'undefined')
					continue;

				var instance = new Running_Application(app_object.id, app_object.name, pid, app_status, this.getContext());
				this.running_applications.set(instance.pid, instance);
				this.runningApplicationsPanel.add(instance);
			} else {
				var instance = this.running_applications.get(pid);
				instance.update(app_status);
			}

			apps_in_xml.push(pid);
		}

		var runnings = this.running_applications.keys();
		if (runnings.length > apps_in_xml.length) {
			for (var i=0; i<runnings.length; i++) {
				if (apps_in_xml.indexOf(runnings[i]) == -1)
					this.runningApplicationsPanel.del(this.running_applications.get(runnings[i]));
			}
		}
	},

	load_explorer: function() {
		$('fileManagerContainer').innerHTML = '<iframe style="width: 100%; height: 100%; border: none;" src="ajaxplorer/"></iframe>';
	}
});

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
