/* Java applications provider */

uovd.provider.applications.Java = function(rdp_provider) {
	this.initialize(rdp_provider.session_management);
	this.rdp_provider = rdp_provider;

	var self = this; /* closure */
	window.applicationStatus = function(app_id, instance, status) {
		self.handleOrders(app_id, instance, status);
	}
}

uovd.provider.applications.Java.prototype = new uovd.provider.applications.Base();

uovd.provider.applications.Java.prototype.applicationStart_implementation = function (application_id, token) { 
	var server_id = this.getServerByAppId(application_id);
	this.applications[token] = new uovd.provider.applications.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		var server = this.session_management.session.servers[server_id];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) {
			this.rdp_provider.main_applet[0].startApplication(token, application_id, server_id);
			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
		}
	} else {
		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
}

uovd.provider.applications.Java.prototype.applicationStartWithArgs_implementation = function(application_id, args, token) { 
	var server_id = this.getServerByAppId(application_id);
	var file_type = args["type"];
	var file_path = args["path"];
	var file_share = args["share"];
	this.applications[token] = new uovd.provider.applications.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		var server = this.session_management.session.servers[server_id];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) {
			this.rdp_provider.main_applet[0].startApplicationWithFile(token, application_id, server_id, file_type, file_path, file_share);
			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
		}
	} else {
		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
}

uovd.provider.applications.Java.prototype.applicationStop_implementation = function(application_id, token) { 
}

uovd.provider.applications.Java.prototype.handleOrders = function(app_id, instance, status) {
	if(status == "started") {
		var application = null;
		if(this.applications[instance]) {
			application = this.applications[instance];
		} else {
			/* Application created from session recovery */
			application = new uovd.provider.applications.ApplicationInstance(this, app_id, instance);
			application.create = 0;
		}

		application.status = "started";
		application.start  = (new Date()).getTime();

		this.applications[instance] = application;
		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":application, "from":"unknown", "to":"started"});
	} else if(status == "stopped") {
		var application = this.applications[instance];
		application.status = "stopped";
		application.end = (new Date()).getTime();

		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":application, "from":"started", "to":"stopped"});
	} else if(status == "error") {
		var application = this.applications[instance];
		application.status = "aborted";
		application.end = (new Date()).getTime();

		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":application, "from":"unknown", "to":"aborted"});
	} else {
		/* !!! */
		/*console.log("Unknown application status : "+id+" "+token+" "+status);*/
	}
}
