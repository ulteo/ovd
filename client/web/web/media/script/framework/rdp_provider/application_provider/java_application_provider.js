/* Java application provider */

function JavaApplicationProvider(rdp_provider) {
	this.initialize(rdp_provider);
}

JavaApplicationProvider.prototype = new ApplicationProvider();

JavaApplicationProvider.prototype.applicationStart_implementation = function (application_id, token) { 
	this.rdp_provider.applet[0].startApplication(token, application_id, 0 /* server id */);
}

JavaApplicationProvider.prototype.applicationStartWithArgs_implementation = function(application_id, args, token) { 
}

JavaApplicationProvider.prototype.applicationStop_implementation = function(application_id, token) { 
}
