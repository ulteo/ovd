function SessionManagement(params, rdp_provider, ajax_provider) {
	this.parameters = params;
	this.status_check = null;

	this.session = null;
	this.rdp_provider = rdp_provider;
	this.ajax_provider = ajax_provider;

	/* set parent refs */
	if(this.rdp_provider) {
		this.rdp_provider.session_management = this;
	}

	if(this.ajax_provider) {
		this.ajax_provider.session_management = this;
	}

	/* ---------------- Initial callbacks ------------------ */

	var self = this; /* closure */
	this.callbacks = {
		"ovd.ajaxProvider.sessionStart" : new Array(function(type, source, params) {
			var state = params["state"];

			if(state == "success") {
				self.status_check = setInterval( function() {
					self.ajax_provider.sessionStatus();
				}, 3000);
			}
		}),
		"ovd.ajaxProvider.sessionEnd" : new Array(function(type, source, params) {
			var state = params["state"];

			if(state == "success" && self.status_check) {
				/* Set the polling interval to 3 sec */
				clearInterval(self.status_check);
				self.status_check = setInterval( function() {
					self.ajax_provider.sessionStatus();
				}, 3000);
			}
		}),
		"ovd.ajaxProvider.sessionSuspend" : new Array(function(type, source, params) {
			var state = params["state"];

			if(state == "success" && self.status_check) {
				/* Set the polling interval to 3 sec */
				clearInterval(self.status_check);
				self.status_check = setInterval( function() {
					self.ajax_provider.sessionStatus();
				}, 3000);
			}
		}),
		"ovd.session.statusChanged" : new Array(function(type, source, params) {
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
			if(to == "disconnected") {
				/* Clear status_check interval */
				clearInterval(self.status_check);
				self.status_check = 0;
			}
			if(to == "unknown") {
				/* Call SessionManagement.stop for a clean stop */
				self.stop();
				/* Clear status_check interval */
				clearInterval(self.status_check);
				self.status_check = 0;
			}
		}),
		"ovd.session.server.statusChanged" : new Array(function(type, source, params) {
			var from = params["from"];
			var to = params["to"];

			if(to == "disconnected" && self.status_check) {
				/* Set the polling interval to 3 sec */
				clearInterval(self.status_check);
				self.status_check = setInterval( function() {
					self.ajax_provider.sessionStatus();
				}, 3000);
			}
		})
	};
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
	this.session = new Session(this);
	this.ajax_provider.sessionStart();
}

SessionManagement.prototype.stop = function() {
	this.ajax_provider.sessionEnd();
}

SessionManagement.prototype.suspend = function() {
	this.ajax_provider.sessionSuspend();
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
				if(this.callbacks[type].length == 0) {
					delete this.callbacks[type];
				}
			}
	}
}

SessionManagement.prototype.fireEvent = function(type, source, params) {
	var path_elements = type.split(".");
	var patterns = [];
	var callbacks = [];
	for(var i=0 ; i<path_elements.length ; ++i) {
		var pattern = "";
		for(var j=0 ; j<i ; ++j) {
			pattern +=path_elements[j]+".";
		}
		pattern +="*";
		patterns.push(pattern);
	}

	patterns.push(type);

	/* Copy callbacks before calling */
	for(var i=0 ; i<patterns.length ; ++i) {
		if(this.callbacks[patterns[i]]) {
			for(var j = 0 ; j<this.callbacks[patterns[i]].length ; ++j) {
				callbacks.push(this.callbacks[patterns[i]][j]);
			}
		}
	}

	/* Call */
	for(var i=0 ; i<callbacks.length ; ++i) {
		try {
			callbacks[i](type, source, params);
		} catch(e) {
			console.log("Error in SessionManagement callback system : "+e);
		}
	}
}
