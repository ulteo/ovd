/* Web applications provider */

uovd.provider.applications.Web = function(webapp_provider) {
	this.initialize(webapp_provider.session_management);
};

uovd.provider.applications.Web.prototype = new uovd.provider.applications.Base();

uovd.provider.applications.Web.prototype.applicationStart_implementation = function (application_id, token) { 
	var server_id = this.getServerByAppId(application_id);
	this.applications[token] = new uovd.provider.applications.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		var server = this.session_management.session.servers[server_id];

		if(server.type == uovd.SERVER_TYPE_WEBAPPS) {
			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
			var url = server.webapps_url + '/webapps/open?id=' + application_id + '&user=' + server.login + '&pass=' + server.password;
			this.session_management.fireEvent("ovd.applicationsProvider.web.start", this, this.build_startWebAppInstance(this.session_management, url, token));
		}
	} else {
		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
};

uovd.provider.applications.Web.prototype.applicationStartWithArgs_implementation = function(application_id, args, token) { 
	var server_id = this.getServerByAppId(application_id);
	var file_type = args["type"];
	var file_path = args["path"];
	var file_share = args["share"];
	this.applications[token] = new uovd.provider.applications.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		var server = this.session_management.session.server[server_id];

		if(server.type == uovd.SERVER_TYPE_WEBAPPS) {
			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
			var url = server.webapps_url + '/webapps/open?id=' + application_id + '&user=' + server.login + '&pass=' + server.password;
			this.session_management.fireEvent("ovd.applicationsProvider.web.start", this, this.build_startWebAppInstance(this.session_management, url, token));
		}
	} else {
		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
};

uovd.provider.applications.Web.prototype.applicationStop_implementation = function(application_id, token) { 
};

uovd.provider.applications.Web.prototype.build_startWebAppInstance = function(session_management, url, token) {
	var self = this; /* closure */
	var instance = {};
	instance.start = jQuery.proxy(function(session_management, url, token) {
		session_management.fireEvent("ovd.applicationsProvider.statusChanged", self, {"application":self.applications[token], "from":"unknown", "to":"started"});
		instance.onstart(url);
	}, this, session_management, url, token);
	instance.stop = jQuery.proxy(function(session_management, url, token) {
		session_management.fireEvent("ovd.applicationsProvider.statusChanged", self, {"application":self.applications[token], "from":"started", "to":"stopped"});
		instance.onstop(url);
	}, this, session_management, url, token);

	/* Default behaviour : open a new window */
	instance.onstart = function(url) {
		this.app_window = window.open(url, '_blank');

		/* Monitor for close event */
		this.interval = setInterval( function() {
			if(instance.app_window.closed) {
				instance.stop();
			}
		}, 2000);
	};

	instance.onstop = function(url) {
		instance.app_window.close();
		clearInterval(instance.interval);
	};

	return instance;
};
