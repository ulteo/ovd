/* Java application provider */

uovd.JavaApplicationProvider = function(rdp_provider) {
	this.initialize(rdp_provider);

	var self = this; /* closure */
	window.applicationStatus = function(app_id, instance, status) {
		self.handleOrders(app_id, instance, status);
	}
}

uovd.JavaApplicationProvider.prototype = new uovd.ApplicationProvider();

uovd.JavaApplicationProvider.prototype.applicationStart_implementation = function (application_id, token) { 
	var server_id = this.getServerByAppId(application_id);
	this.applications[token] = new uovd.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		this.rdp_provider.main_applet[0].startApplication(token, application_id, server_id);
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
	} else {
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
}

uovd.JavaApplicationProvider.prototype.applicationStartWithArgs_implementation = function(application_id, args, token) { 
	this.applicationStart_implementation(application_id, token); /* stub */
}

uovd.JavaApplicationProvider.prototype.applicationStop_implementation = function(application_id, token) { 
}

uovd.JavaApplicationProvider.prototype.handleOrders = function(app_id, instance, status) {
	if(status == "started") {
		var application = null;
		if(this.applications[instance]) {
			application = this.applications[instance];
		} else {
			/* Application created from session recovery */
			application = new uovd.ApplicationInstance(this, app_id, instance);
			application.create = 0;
		}

		application.status = "started";
		application.start  = (new Date()).getTime();

		this.applications[instance] = application;
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":application, "from":"unknown", "to":"started"});
	} else if(status == "stopped") {
		var application = this.applications[instance];
		application.status = "stopped";
		application.end = (new Date()).getTime();

		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":application, "from":"started", "to":"stopped"});
	} else if(status == "error") {
		var application = this.applications[instance];
		application.status = "aborted";
		application.end = (new Date()).getTime();

		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":application, "from":"unknown", "to":"aborted"});
	} else {
		console.log("Unknown application status : "+id+" "+token+" "+status);
	}
}
