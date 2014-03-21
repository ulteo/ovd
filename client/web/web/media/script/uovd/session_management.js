
uovd.SessionManagement = function(params, rdp_provider, http_provider, webapps_provider) {
	this.parameters = params;
	this.status_check = null;
	this.status_check_interval_long  = 10*60*1000; // 10min
	this.status_check_interval_short = 3*1000;     // 3sec
	this.status_check_interval_now   = 100;        // 100ms
	this.status_check_interval = this.status_check_interval_short;

	this.session = null;
	this.rdp_provider = rdp_provider;
	this.http_provider = http_provider;
	this.webapps_provider = webapps_provider;

	/* set parent refs */
	if(this.rdp_provider) {
		this.rdp_provider.session_management = this;
	}

	if(this.http_provider) {
		this.http_provider.session_management = this;
	}

	if(this.webapps_provider) {
		this.webapps_provider.session_management = this;
	}

	/* ---------------- Initial callbacks ------------------ */

	var self = this; /* closure */
	this.callbacks = {
		"ovd.httpProvider.sessionStart" : new Array(function(type, source, params) {
			var state = params["state"];

			if(state == uovd.SUCCESS) {
				self.session.starting(type);
			}
		}),
		"ovd.httpProvider.sessionEnd" : new Array(function(type, source, params) {
			var state = params["state"];

			if(state == uovd.SUCCESS) {
				self.session.destroying(type);
			}
		}),
		"ovd.httpProvider.sessionSuspend" : new Array(function(type, source, params) {
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

				/* Connect to webapp servers */
				self.webapps_provider.connect();
			}
			if(to == uovd.SESSION_STATUS_LOGGED) {
				self.session.started(type);
			}
			if(to == uovd.SESSION_STATUS_DISCONNECTED) {
				self.session.destroyed(type);
			}
			if(to == uovd.SESSION_STATUS_UNKNOWN ) {
				self.session.destroyed(type);
			}
		}),
		"ovd.session.server.statusChanged" : new Array(function(type, source, params) {
			var from = params["from"];
			var to = params["to"];

			if(to == uovd.SERVER_STATUS_FAILED) {
				/* Failed to connect */
				self.http_provider.sessionEnd();
			}

			if(to == uovd.SERVER_STATUS_DISCONNECTED) {
				self.session.destroying(type);
			}
		}),
		"ovd.httpProvider.sessionStatus" : new Array(function(type, source, params) {
			if(self.session.phase == uovd.SESSION_PHASE_DESTROYED ) { return; }
			if(self.session.phase == uovd.SESSION_PHASE_STARTED )   { self.status_check_interval = self.status_check_interval_long }
			else                                                    { self.status_check_interval = self.status_check_interval_short; }

			clearTimeout(self.status_check);
			self.status_check = setTimeout(jQuery.proxy(self.http_provider.sessionStatus, self.http_provider), self.status_check_interval);
		}),
		"ovd.session.starting" : new Array(function(type, source, params) {
			/* Start status checking */
			clearTimeout(self.status_check);
			self.status_check = setTimeout(jQuery.proxy(self.http_provider.sessionStatus, self.http_provider), self.status_check_interval_short);
		}),
		"ovd.session.destroying" : new Array(function(type, source, params) {
			/* Trigger status check now !*/
			clearTimeout(self.status_check);
			self.status_check = setTimeout(jQuery.proxy(self.http_provider.sessionStatus, self.http_provider), self.status_check_interval_now);

			/* Disconnect the client */
			self.rdp_provider.disconnect();
			self.webapps_provider.disconnect();
		}),
		"ovd.session.destroyed" : new Array(function(type, source, params) {
			/* Clear status_check interval */
			clearTimeout(self.status_check);
		}),
		"ovd.rdpProvider.crash" : new Array(function(type, source, params) {
			self.http_provider.sessionEnd();
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

uovd.SessionManagement.prototype.setHttpProvider = function(http_provider) {
	this.http_provider = http_provider;

	if(this.http_provider) {
		this.http_provider.session_management = this;
	}
}

uovd.SessionManagement.prototype.setWebAppsProvider = function(webapps_provider) {
	this.webapps_provider = webapps_provider;

	if(this.webapps_provider) {
		this.webapps_provider.session_management = this;
	}
}

uovd.SessionManagement.prototype.start = function() {
	this.session = new uovd.Session(this);
	this.http_provider.sessionStart();
}

uovd.SessionManagement.prototype.stop = function() {
	this.http_provider.sessionEnd();
}

uovd.SessionManagement.prototype.suspend = function() {
	this.http_provider.sessionSuspend();
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
			/* !!! */
			/*console.log("Error in SessionManagement callback system ("+type+"): "+e);*/
		}
	}
}
