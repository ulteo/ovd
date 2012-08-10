/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author Omar AKHAM <oakham@ulteo.com> 2011
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

var Portal = Class.create(Applications, {
	local_integration: false,
	applicationsPanel: null,
	news: null, // Hash

	initialize: function(debug_) {
		Applications.prototype.initialize.apply(this, [debug_]);
		this.news = new Hash();
		$('applicationsAppletContainer').innerHTML = '';

		var remove_height = 114;
		if (this.debug)
			remove_height = 115;
		$('applicationsContainer').style.height = parseInt(this.my_height)-remove_height+'px';
		$('appsContainer').style.height = parseInt(this.my_height)-remove_height+'px';
		$('fileManagerContainer').style.height = parseInt(this.my_height)-remove_height+'px';

		this.applicationsPanel = new ApplicationsPanel($('appsContainer'));
		
		try {
			this.local_integration = local_integration;
		} catch(e) {}
	},

	parseSessionSettings: function(setting_nodes) {
		Applications.prototype.parseSessionSettings.apply(this, [setting_nodes]);

		if ($('suspend_button')) {
			if (this.persistent) {
				$('suspend_button').show();
			}
			else {
				$('suspend_button').hide();
			}
		}
	},

	do_started: function() {
		Logger.debug('[applications_int] do_started()');

		this.load_explorer();
		this.display_news();

		Applications.prototype.do_started.apply(this, []);
	},
	
	on_application_add: function(application_) {
		var app_item = new ApplicationItem(application_);
		
		this.servers.each(function(pair) {
			var server = pair.value;
			server.add_status_changed_callback(app_item.on_server_status_change.bind(app_item));
		});
		
		app_item.add_on_click_callback(this.on_application_item_clicked.bind(this));
		
		this.applicationsPanel.add(app_item);
	},
	
	on_running_app_started: function(instance_) {
		var app_id = instance_.id;
		var running = 0;
		
		if ($('running_'+app_id)) {
			if ($('running_'+app_id).innerHTML != '' && typeof parseInt($('running_'+app_id).innerHTML) == 'number')
				running += parseInt($('running_'+app_id).innerHTML);
		}
		running += 1;
		this.nb_running_applications += 1;

		$('running_'+app_id).innerHTML = running;
	
	},
	
	on_running_app_stopped: function(instance_) {
		var app_id = instance_.id;
		var running = 0;
		
		if ($('running_'+app_id)) {
			if ($('running_'+app_id).innerHTML != '' && typeof parseInt($('running_'+app_id).innerHTML) == 'number')
				running = parseInt($('running_'+app_id).innerHTML);
		}
		running -= 1;
		this.nb_running_applications -= 1;

		if (running > 0)
			$('running_'+app_id).innerHTML = running;
		else
			$('running_'+app_id).innerHTML = '';
	},
	
	on_application_item_clicked: function(application_item_) {
		this.launch_application(application_item_.application);
	},

	load_explorer: function() {
		if (! this.explorer)
			return;

		$('fileManagerContainer').innerHTML = '<iframe style="width: 100%; height: 100%; border: none;" src="ajaxplorer/"></iframe>';
	},

	display_news: function() {
		new Ajax.Request(
			'news.php',
			{
				method: 'get',
				onSuccess: this.parse_display_news.bind(this)
			}
		);

		setTimeout(this.display_news.bind(this), 300000);
	},

	parse_display_news: function(transport) {
		Logger.debug('[applications] parse_display_news(transport@display_news())');

		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('news');

		if (buffer.length != 1) {
			Logger.error('[applications] parse_display_news(transport@display_news()) - Invalid XML (No "news" node)');
			return;
		}

		var table_node = new Element('table');
		table_node.setAttribute('style', 'width: 100%; margin-left: auto; margin-right: auto;');
		table_node.setAttribute('border', '0');
		table_node.setAttribute('cellspacing', '0');
		table_node.setAttribute('cellpadding', '0');
		
		var tbody_node = new Element('tbody');
		table_node.appendChild(tbody_node);
		
		var new_nodes = xml.getElementsByTagName('new');
		for (var i=0; i<new_nodes.length; i++) {
			this.news.set(''+new_nodes[i].getAttribute('id'), new_nodes[i]);

			var date = new Date();
			date.setTime(new_nodes[i].getAttribute('timestamp')*1000);
			
			var myself = this;
			var news_id = new_nodes[i].getAttribute('id');
			
			var a_node = new Element('a');
			a_node.observe('click', function(event) {
				myself.show_new(news_id);
				event.stop();
			});
			a_node.setAttribute('href', 'javascript:;');
			a_node.innerHTML = new_nodes[i].getAttribute('title');

			var strong_node = new Element('strong');
			strong_node.appendChild(a_node);
			
			var span_node = new Element('span');
			span_node.setAttribute('style', 'font-size: 1.1em; color: black;');
			span_node.innerHTML = '<em>'+date.toLocaleString()+'</em> - ';
			span_node.appendChild(strong_node);
			
			var td_node = new Element('td');
			td_node.setAttribute('style', 'text-align: left;');
			td_node.appendChild(span_node);
			
			var tr_node = new Element('tr');
			tr_node.appendChild(td_node);
			
			tbody_node.appendChild(tr_node);
		}
		
		$('newsContainer').innerHTML = '';
		$('newsContainer').appendChild(table_node);
	},

	show_new: function(i_) {
		var new_ = this.news.get(''+i_);
		var title = new_.getAttribute('title');
		var content = new_.firstChild.nodeValue;

		showNews(title, content);
	}
});
