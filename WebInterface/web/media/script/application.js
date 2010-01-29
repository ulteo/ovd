/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com>
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

var Application = Class.create({
	id: 0,
	name: '',
	node: null,

	initialize: function(id_, name_) {
		this.id = id_;
		this.name = name_;
	},

	initNode: function() {
		var tr = new Element('tr');

		var td_icon = new Element('td');
		var td_icon_link = new Element('a');
		td_icon_link.observe('click', this.onClick.bind(this));
		td_icon_link.setAttribute('href', 'javascript:;');

		var icon = new Element('img');
		icon.setAttribute('src', this.getIconURL());
		td_icon_link.appendChild(icon);
		td_icon.appendChild(td_icon_link);
		tr.appendChild(td_icon);

		var td_app = new Element('td');
		var td_app_link = new Element('a');
		td_app_link.observe('click', this.onClick.bind(this));
		td_app_link.setAttribute('href', 'javascript:;');
		td_app_link.innerHTML = this.name;
		td_app.appendChild(td_app_link);
		tr.appendChild(td_app);

		return tr;
	},

	getNode: function() {
		if (this.node == null)
			this.node = this.initNode();

		return this.node;
	},

 	onClick: function(event) {
		this.launch();
		event.stop();
	},

	launch: function() {
		var popup = this.popupOpen();

		var app_id = this.id;
		setTimeout(function() {
			popup.location.href = 'external_app.php?app_id='+app_id;
		}, 1000);
	},

	popupOpen: function() {
		var date = new Date();
		var rand = Math.round(Math.random()*100)+date.getTime();

		var w = window.open('about:blank', 'Ulteo'+rand, 'toolbar=no,status=no,top=0,left=0,width='+screen.width+',height='+screen.height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

		return w;
	},

	getIconURL: function() {
		return 'icon.php?id='+this.id;
	}
});

var Running_Application = Class.create(Application, {
	pid: '',
	status: -1,
	context: null,

	app_span: null,

	initialize: function(id_, name_, pid_, status_, context_) {
		Application.prototype.initialize.apply(this, [id_, name_]);

		this.pid = pid_;
		this.status = status_;
		this.context = context_;
	},

	update: function(status_) {
		if (status_ != this.status) {
			this.status = status_;
			this.repaintNode();
		}
	},

	initNode: function() {
		var tr = new Element('tr');

		var td_icon = new Element('td');
		var icon = new Element('img');
		icon.setAttribute('src', this.getIconURL());
		td_icon.appendChild(icon);
		tr.appendChild(td_icon);

		var td_app = new Element('td');
		var td_app_div = new Element('div');
		td_app_div.setAttribute('style', 'font-weight: bold;');
		td_app_div.innerHTML = '<strong>'+this.name+'</strong>';
		td_app.appendChild(td_app_div);

		this.app_span = new Element('span');
		td_app.appendChild(this.app_span);

		tr.appendChild(td_app);

		this.repaintNode();

		return tr;
	},

	repaintNode: function() {
		this.app_span.innerHTML = '';

		if (this.status == 2) {
			if (this.context.shareable == true) {
				var node = new Element('a');
				node.observe('click', this.onClickShare.bind(this));
				node.setAttribute('href', 'javascript:;');
				node.innerHTML = this.context.translate('share');
				this.app_span.appendChild(node);
			}

			if (this.context.persistent == true) {
				var node = new Element('a');
				node.observe('click', this.onClickSuspend.bind(this));
				node.setAttribute('href', 'javascript:;');
				node.innerHTML = this.context.translate('suspend');
				this.app_span.appendChild(node);
			}
		}

		if (this.status == 10) {
			var node = new Element('a');
			node.observe('click', this.onClickResume.bind(this));
			node.setAttribute('href', 'javascript:;');
			node.innerHTML = this.context.translate('resume');
			this.app_span.appendChild(node);
		}

		var separator_node = new Element('span');
		separator_node.innerHTML = '&nbsp;-&nbsp;';

		for (var j=1; j<this.app_span.childNodes.length; j+=2)
			this.app_span.insertBefore(separator_node.cloneNode(true), this.app_span.childNodes[j]);

		return true;
	},

	onClickShare: function(event) {
		this.share();
		event.stop();
	},

	onClickSuspend: function(event) {
		this.suspend();
		event.stop();
	},

	onClickResume: function(event) {
		this.resume();
		event.stop();
	},

	share: function() {
		$('infoWrap').style.width = '30%';
		var node = new Element('div');
		var table = new Element('table');
		table.setAttribute('style', 'width: 100%; margin-left: auto; margin-right: auto;');
		var tbody = new Element('tbody');
		var tr = new Element('tr');
		var td = new Element('td');
		td.setAttribute('style', 'text-align: center;');
		var h2 = new Element('h2');
		h2.setAttribute('style', 'text-align: center;');
		h2.innerHTML = this.context.translate('application_sharing');
		td.appendChild(h2);
		var invite_form = new Element('form');
		//invite_form.observe('submit', doInvite('portal'));
		invite_form.setAttribute('action', 'javascript:;');
		invite_form.setAttribute('method', 'post');
		invite_form.setAttribute('onsubmit', 'doInvite(\'portal\'); return false;');
		var input_pid = new Element('input');
		input_pid.setAttribute('type', 'hidden');
		input_pid.setAttribute('id', 'invite_access_id');
		input_pid.setAttribute('name', 'access_id');
		input_pid.setAttribute('value', this.pid);
		invite_form.appendChild(input_pid);
		var para = new Element('p');
		para.innerHTML = this.context.translate('email_address')+':&nbsp;';
		var input_email = new Element('input');
		input_email.setAttribute('type', 'text');
		input_email.setAttribute('id', 'invite_email');
		input_email.setAttribute('name', 'email');
		para.appendChild(input_email);
		para.innerHTML += '&nbsp;';
		var input_mode = new Element('input');
		input_mode.setAttribute('class', 'input_checkbox');
		input_mode.setAttribute('type', 'checkbox');
		input_mode.setAttribute('id', 'invite_mode');
		input_mode.setAttribute('name', 'mode');
		para.appendChild(input_mode);
		para.innerHTML += '&nbsp;'+this.context.translate('active_mode');
		invite_form.appendChild(para);
		var para2 = new Element('p');
		para2.setAttribute('style', 'text-align: center;');
		var input_submit = new Element('input');
		input_submit.setAttribute('type', 'submit');
		input_submit.setAttribute('id', 'invite_submit');
		input_submit.setAttribute('value', this.context.translate('invite'));
		para2.appendChild(input_submit);
		invite_form.appendChild(para2);
		td.appendChild(invite_form);
		tr.appendChild(td);
		tbody.appendChild(tr);
		table.appendChild(tbody);
		node.appendChild(table);

		showInfo(node.innerHTML);
	},

	suspend: function() {
		new Ajax.Request(
			'application_exit.php',
			{
				method: 'get',
				parameters: {
					access_id: this.pid
				}
			}
		);
	},

	resume: function() {
		var popup = this.popupOpen();

		var app_pid = this.pid;
		setTimeout(function() {
			popup.location.href = 'resume.php?access_id='+app_pid;
		}, 1000);

		return true;
	}
});

var Context = Class.create({
	i18n: new Array(),
	shareable: false,
	persistent: false,

	initialize: function(i18n_, shareable_, persistent_) {
		this.i18n = i18n_,
		this.shareable = shareable_;
		this.persistent = persistent_;
	},

 	translate: function(str_) {
		var ret = this.i18n[str_];
		if (typeof ret == 'undefined')
			return str_;

		return ret;
	}
});

var ApplicationsPanel = Class.create({
	node: null,
	applications: null,

	initialize: function(node_) {
		var table = new Element('table');
		var tbody = new Element('tbody');
		table.appendChild(tbody);
		node_.appendChild(table);

		this.applications = new Array();
		this.node = tbody;
 	},

	compare: function(a, b) {
		if (a.name < b.name)
			return -1;
		if (a.name > b.name)
			return 1;

		return 0;
	},

	add: function(app_) {
		this.applications.push(app_);
		this.applications.sort(this.compare);

		for (var i = 0; i < this.applications.length; i++) {
			var app = this.applications[i];
			if (app_ != app)
				continue;

			if (i+1 == this.applications.length)
				this.node.appendChild(app.getNode());
			else {
				var nextApp = this.applications[i+1];
				this.node.insertBefore(app.getNode(), nextApp.getNode());
			}
		}
	},

	del: function(app_) {
		this.applications = this.applications.without(app_);
		try {
			this.node.removeChild(app_.getNode());
		} catch(e) {}
	}
});
