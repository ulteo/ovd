/* Base class */

uovd.provider.applications.Base = function(session_management) { }

uovd.provider.applications.Base.prototype.initialize = function(session_management) {
	this.session_management = session_management;
	this.token = 0;
	this.applications = {} /* token -> ApplicationInstance */

	/* register events listeners */
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.session_management.addCallback("ovd.applicationsProvider.applicationStart",         this.handler);
	this.session_management.addCallback("ovd.applicationsProvider.applicationStartWithArgs", this.handler);
	this.session_management.addCallback("ovd.applicationsProvider.applicationStop",          this.handler);
	this.session_management.addCallback("ovd.session.destroying",                           this.handler);
}

uovd.provider.applications.Base.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.applicationsProvider.applicationStart") {
		var id = params["id"];
		this.applicationStart(id);
	}
	if(type == "ovd.applicationsProvider.applicationStartWithArgs") {
		var id = params["id"];
		var args = params["args"];
		this.applicationStartWithArgs(id, args);
	}
	if(type == "ovd.applicationsProvider.applicationStop") {
		var id = params["id"];
		var args = params["token"];
		this.applicationStop(id, token);
	}

	if(type == "ovd.session.destroying") { /* Clean context */
		this.end();
	}
}

uovd.provider.applications.Base.prototype.applicationStart = function(application_id) { 
	this.applicationStart_implementation(application_id, this.token);
	return this.token++;
}

uovd.provider.applications.Base.prototype.applicationStartWithArgs = function(application_id, args) { 
	this.applicationStartWithArgs_implementation(application_id, args, this.token);
	return this.token++;
}

uovd.provider.applications.Base.prototype.applicationStop = function(application_id, token) { 
	this.applicationStop_implementation(application_id, token);
}

uovd.provider.applications.Base.prototype.getServerByAppId = function(application_id) {
	var session = this.session_management.session;
	var servers = session.servers;

	for(var i = 0 ; i<servers.length ; ++i) {
		var applications = servers[i].applications;

		for(var j = 0 ; j<applications.length ; ++j) {
			if(applications[j].id == application_id) {
				return i;
			}
		}
	}

	return -1;
}

uovd.provider.applications.Base.prototype.end = function() {
	this.session_management.removeCallback("ovd.applicationsProvider.applicationStart",         this.handler);
	this.session_management.removeCallback("ovd.applicationsProvider.applicationStartWithArgs", this.handler);
	this.session_management.removeCallback("ovd.applicationsProvider.applicationStop",          this.handler);
	this.session_management.removeCallback("ovd.session.destroying",                           this.handler);
}
