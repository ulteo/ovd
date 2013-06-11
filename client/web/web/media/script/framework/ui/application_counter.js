uovd.ApplicationCounter = function(session_management) {
	this.session_management = session_management;
	this.count = 0;

	/* register events listeners */
	this.handler = this.handleEvents.bind(this);
	this.session_management.addCallback("ovd.rdpProvider.applicationProvider.statusChanged", this.handler);
	this.session_management.addCallback("ovd.ajaxProvider.sessionEnd",                       this.handler);
	this.session_management.addCallback("ovd.ajaxProvider.sessionSuspend",                   this.handler);
}

uovd.ApplicationCounter.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.applicationProvider.statusChanged") {
		var from = params['from'];
		var to = params['to'];
		var application = params['application'];

		if(to == "started") { this.count++; }
		if(to == "stopped") { this.count--; }
	}

	if(type == "ovd.ajaxProvider.sessionEnd" || type == "ovd.ajaxProvider.sessionSuspend" ) { /* Clean context */
		this.end();
	}
}

uovd.ApplicationCounter.prototype.get = function() {
	return this.count;
}

uovd.ApplicationCounter.prototype.end = function() {
	this.count = 0;
}
