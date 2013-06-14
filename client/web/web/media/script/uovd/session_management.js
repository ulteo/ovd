
uovd.SessionManagement = function(params, rdp_provider, ajax_provider) {
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

			if(state == uovd.SUCCESS) {
				self.session.starting(type);
			}
		}),
		"ovd.ajaxProvider.sessionEnd" : new Array(function(type, source, params) {
			var state = params["state"];

			if(state == uovd.SUCCESS) {
				self.session.destroying(type);
			}
		}),
		"ovd.ajaxProvider.sessionSuspend" : new Array(function(type, source, params) {
			var state = params["state"];

			if(state == uovd.SUCCESS) {
				self.session.destroying(type);
			}
		}),
		"ovd.session.statusChanged" : new Array(function(type, source, params) {
			var from = params["from"];
			var to = params["to"];

			if(to == uovd.SESSION_STATUS_READY) {
				/* Connect to rdp servers */
				self.rdp_provider.connect();
			}
			if(to == uovd.SESSION_STATUS_LOGGED) {
				self.session.started(type);
			}
			if(to == uovd.SESSION_STATUS_DISCONNECTED) {
				/* Disconnect the client */
				self.rdp_provider.disconnect();

				self.session.destroyed(type);
			}
			if(to == uovd.SESSION_STATUS_UNKNOWN ) {
				self.session.destroyed(type);
			}
		}),
		"ovd.session.server.statusChanged" : new Array(function(type, source, params) {
			var from = params["from"];
			var to = params["to"];

			if(to == uovd.SERVER_STATUS_DISCONNECTED) {
				self.session.destroying(type);
			}
		}),
		"ovd.session.starting" : new Array(function(type, source, params) {
			/* Set the polling interval to 3 sec */
			clearInterval(self.status_check);
			self.status_check = setInterval(self.ajax_provider.sessionStatus.bind(self.ajax_provider), 3000);
		}),
		"ovd.session.started" : new Array(function(type, source, params) {
			/* Lower the polling interval to 30 sec */
			clearInterval(self.status_check);
			self.status_check = setInterval(self.ajax_provider.sessionStatus.bind(self.ajax_provider), 30000);
		}),
		"ovd.session.destroying" : new Array(function(type, source, params) {
			/* Set the polling interval to 3 sec */
			clearInterval(self.status_check);
			self.status_check = setInterval(self.ajax_provider.sessionStatus.bind(self.ajax_provider), 3000);
		}),
		"ovd.session.destroyed" : new Array(function(type, source, params) {
			/* Clear status_check interval */
			clearInterval(self.status_check);
		})
	};
}

uovd.SessionManagement.prototype.setParameters = function(params) {
	this.parameters = params;
}

uovd.SessionManagement.prototype.setRdpProvider = function(rdp_provider) {
	this.rdp_provider = rdp_provider;

	if(this.rdp_provider) {
		this.rdp_provider.session_management = this;
	}
}

uovd.SessionManagement.prototype.setAjaxProvider = function(ajax_provider) {
	this.ajax_provider = ajax_provider;

	if(this.ajax_provider) {
		this.ajax_provider.session_management = this;
	}
}

uovd.SessionManagement.prototype.start = function() {
	this.session = new uovd.Session(this);
	this.ajax_provider.sessionStart();
}

uovd.SessionManagement.prototype.stop = function() {
	this.ajax_provider.sessionEnd();
}

uovd.SessionManagement.prototype.suspend = function() {
	this.ajax_provider.sessionSuspend();
}

uovd.SessionManagement.prototype.addCallback = function(type, func) {
	if(! this.callbacks[type]) {
		this.callbacks[type] = new Array();
	}

	this.callbacks[type].push(func);
}

uovd.SessionManagement.prototype.removeCallback = function(type, func) {
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

uovd.SessionManagement.prototype.fireEvent = function(type, source, params) {
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
			console.log("Error in SessionManagement callback system ("+type+"): "+e);
		}
	}
}
