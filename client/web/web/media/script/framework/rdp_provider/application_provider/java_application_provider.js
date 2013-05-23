/* Java application provider */

function JavaApplicationProvider(rdp_provider) {
	this.initialize(rdp_provider);

	var self = this; /* closure */
	window.applicationStatus = function(app_id, instance, status) {
		self.handleOrders(app_id, instance, status);
	}
}

JavaApplicationProvider.prototype = new ApplicationProvider();

JavaApplicationProvider.prototype.applicationStart_implementation = function (application_id, token) { 
	this.rdp_provider.applet[0].startApplication(token, application_id, 0 /* server id */);
	this.applications[token] = new ApplicationInstance(this, application_id, token);
	this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
}

JavaApplicationProvider.prototype.applicationStartWithArgs_implementation = function(application_id, args, token) { 
	this.applicationStart_implementation(application_id, token); /* stub */
}

JavaApplicationProvider.prototype.applicationStop_implementation = function(application_id, token) { 
}

JavaApplicationProvider.prototype.handleOrders = function(app_id, instance, status) {
	if(status == "started") {
		var application = null;
		if(this.applications[instance]) {
			application = this.applications[instance];
		} else {
			/* Application created from session recovery */
			application = new ApplicationInstance(this, app_id, instance);
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
