/* Base class */

function ApplicationProvider(rdp_provider) { }

ApplicationProvider.prototype.initialize = function(rdp_provider) {
	this.rdp_provider = rdp_provider;
	this.token = 0;
	this.applications = {} /* token -> ApplicationInstance */

	/* register events listeners */
	this.handler = this.handleEvents.bind(this);
	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.applicationProvider.applicationStart",         this.handler);
	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.applicationProvider.applicationStartWithArgs", this.handler);
	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.applicationProvider.applicationStop",          this.handler);
	this.rdp_provider.session_management.addCallback("ovd.ajaxProvider.sessionEnd",                                  this.handler);
	this.rdp_provider.session_management.addCallback("ovd.ajaxProvider.sessionSuspend",                              this.handler);
}

ApplicationProvider.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.applicationProvider.applicationStart") {
		var id = params["id"];
		this.applicationStart(id);
	}
	if(type == "ovd.rdpProvider.applicationProvider.applicationStartWithArgs") {
		var id = params["id"];
		var args = params["args"];
		this.applicationStartWithArgs(id, args);
	}
	if(type == "ovd.rdpProvider.applicationProvider.applicationStop") {
		var id = params["id"];
		var args = params["token"];
		this.applicationStop(id, token);
	}

	if(type == "ovd.ajaxProvider.sessionEnd" || type == "ovd.ajaxProvider.sessionSuspend" ) { /* Clean context */
		this.end();
	}
}

ApplicationProvider.prototype.applicationStart = function(application_id) { 
	this.applicationStart_implementation(application_id, this.token);
	return this.token++;
}

ApplicationProvider.prototype.applicationStartWithArgs = function(application_id, args) { 
	this.applicationStartWithArgs_implementation(application_id, args, this.token);
	return this.token++;
}

ApplicationProvider.prototype.applicationStop = function(application_id, token) { 
	this.applicationStop_implementation(application_id, token);
}

ApplicationProvider.prototype.end = function() {
	this.rdp_provider.session_management.removeCallback("ovd.rdpProvider.applicationProvider.applicationStart",         this.handler);
	this.rdp_provider.session_management.removeCallback("ovd.rdpProvider.applicationProvider.applicationStartWithArgs", this.handler);
	this.rdp_provider.session_management.removeCallback("ovd.rdpProvider.applicationProvider.applicationStop",          this.handler);
	this.rdp_provider.session_management.removeCallback("ovd.ajaxProvider.sessionEnd",                                  this.handler);
	this.rdp_provider.session_management.removeCallback("ovd.ajaxProvider.sessionSuspend",                              this.handler);
}

/* ApplicationInstance class */
function ApplicationInstance(applicationProvider, id, instance) {
	this.applicationProvider = applicationProvider;
	this.id = id;
	this.instance = instance;
	this.status = "unknown";

	this.create = (new Date()).getTime();
	this.start = 0;
	this.end = 0;
}
