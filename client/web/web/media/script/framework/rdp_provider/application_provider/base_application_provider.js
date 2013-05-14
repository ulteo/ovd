/* Base class */

function ApplicationProvider(rdp_provider) { }

ApplicationProvider.prototype.initialize = function(rdp_provider) {
	this.rdp_provider = rdp_provider;
	this.token = 0;

	/* register events listeners */
	var self = this; /* closure */
	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.applicationProvider.*", function(type, source, params) {
			self.handleEvents(type, source, params);
	});
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
