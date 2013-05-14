function SessionManagement(params, rdp_provider, ajax_provider) {
	this.parameters = params;
	this.callbacks = {};
	this.status_check = null;

	this.session = new Session(this);
	this.rdp_provider = rdp_provider;
	this.ajax_provider = ajax_provider;

	/* set parent refs */
	if(this.rdp_provider) {
		this.rdp_provider.session_management = this;
	}

	if(this.ajax_provider) {
		this.ajax_provider.session_management = this;
	}
}

SessionManagement.prototype.setParameters = function(params) {
	this.parameters = params;
}

SessionManagement.prototype.setRdpProvider = function(rdp_provider) {
	this.rdp_provider = rdp_provider;

	if(this.rdp_provider) {
		this.rdp_provider.session_management = this;
	}
}

SessionManagement.prototype.setAjaxProvider = function(ajax_provider) {
	this.ajax_provider = ajax_provider;

	if(this.ajax_provider) {
		this.ajax_provider.session_management = this;
	}
}

SessionManagement.prototype.start = function() {
	var self = this; /* closure */

	/* ---------------- Internal callbacks ------------------ */

	/* Check the SessionStart status */	
	this.addCallback("ovd.ajaxProvider.sessionStart", function(type, source, params) {
		var state = params["state"];

		if(state = "success") {
			self.status_check = setInterval( function() {
				self.ajax_provider.sessionStatus();
			}, 3000);
		}
	});

	/* Handle the session status evolutions */
	this.addCallback("ovd.session.statusChanged", function(type, source, params) {
		var from = params["from"];
		var to = params["to"];

		if(to == "ready") {
			/* Connect to rdp servers */
			self.rdp_provider.connect();
		}
		if(to == "logged") {
			/* Lower the polling interval to 30 sec */
			clearInterval(self.status_check);
			self.status_check = setInterval( function() {
				self.ajax_provider.sessionStatus();
			}, 30000);
		}
		if(to == "unknown") {
			/* Call SessionManagement.stop for a clean stop */
			self.stop();
			/* Clear status_check interval */
			clearInterval(self.status_check);
		}
	});

	/* Handle server status evolutions */
	this.addCallback("ovd.session.server.statusChanged", function(type, source, params) {
		var from = params["from"];
		var to = params["to"];

		if(to == "disconnected") {
			/* Set the polling interval to 3 sec */
			clearInterval(self.status_check);
			self.status_check = setInterval( function() {
				self.ajax_provider.sessionStatus();
			}, 3000);
		}
	});

	/* ----------------   Start session   ------------------ */

	this.ajax_provider.sessionStart();
}

SessionManagement.prototype.stop = function() {
	this.ajax_provider.sessionEnd();
}

SessionManagement.prototype.addCallback = function(type, func) {
	if(! this.callbacks[type]) {
		this.callbacks[type] = new Array();
	}

	this.callbacks[type].push(func);
}

SessionManagement.prototype.removeCallback = function(type, func) {
	if(this.callbacks[type]) {
			var idx = this.callbacks[type].indexOf(func);
			if(idx != -1) {
				this.callbacks[type].splice(idx, 1);
			}
	}
}

SessionManagement.prototype.fireEvent = function(type, source, params) {
	var path_elements = type.split(".");
	var patterns = [];
	for(var i=0 ; i<path_elements.length ; ++i) {
		var pattern = "";
		for(var j=0 ; j<i ; ++j) {
			pattern +=path_elements[j]+".";
		}
		pattern +="*";
		patterns.push(pattern);
	}

	patterns.push(type);

	for(var i=0 ; i<patterns.length ; ++i) {
		if(this.callbacks[patterns[i]]) {
			for(var j = 0 ; j<this.callbacks[patterns[i]].length ; ++j) {
				this.callbacks[patterns[i]][j](type, source, params);
			}
		}
	}
}
