ApplicationCounter = function(session_management) {
	this.session_management = session_management;
	this.count = 0;

	/* register events listeners */
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.session_management.addCallback("ovd.applicationsProvider.statusChanged", this.handler);
	this.session_management.addCallback("ovd.session.destroying",                 this.handler);
}

ApplicationCounter.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.applicationsProvider.statusChanged") {
		var from = params['from'];
		var to = params['to'];
		var application = params['application'];

		if(to == "started") { this.count++; }
		if(to == "stopped") { this.count--; }
	}

	if(type == "ovd.session.destroying" ) { /* Clean context */
		this.end();
	}
}

ApplicationCounter.prototype.get = function() {
	return this.count;
}

ApplicationCounter.prototype.end = function() {
	this.count = 0;
}
