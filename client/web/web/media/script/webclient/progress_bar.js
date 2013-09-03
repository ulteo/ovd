ProgressBar = function(session_management, node) {
	this.session_management = session_management;
	this.node = jQuery(node);

	/* register events listeners */
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.session_management.addCallback("ovd.session.starting",             this.handler);
	this.session_management.addCallback("ovd.session.statusChanged",        this.handler);
	this.session_management.addCallback("ovd.session.server.statusChanged", this.handler);
	this.session_management.addCallback("ovd.session.destroying",           this.handler);
}

ProgressBar.prototype.handleEvents = function(type, source, params) {
	var self = this; /* closure */
	function step(n, dur) {
		self.node.animate({width: n+'%'}, (dur == undefined) ? 400 : dur);
	}

	if(type == 'ovd.session.starting') {
		initSplashConnection();
		step(20);
		return;
	}

	if(type == 'ovd.session.statusChanged' && params['to'] == uovd.SESSION_STATUS_INITED) {
		step(45);
		return;
	}

	if(type == 'ovd.session.statusChanged' && params['to'] == uovd.SESSION_STATUS_READY) {
		step(90);
		return;
	}

	if(type == 'ovd.session.server.statusChanged' && params['to'] == uovd.SERVER_STATUS_CONNECTED) {
		step(100);
		return;
	}

	if(type == 'ovd.session.statusChanged' && params['to'] == uovd.SESSION_STATUS_LOGGED) {
		step(100);
		return;
	}

	if(type == 'ovd.session.statusChanged' && params['to'] == uovd.SESSION_STATUS_DESTROYING) {
		step(0, 10000);
		return;
	}

	if(type == 'ovd.session.destroying') {
		initSplashDisconnection();
		step(75);
	}
}
