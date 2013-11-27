/* JavaProvider */

uovd.provider.Java = function(node) {
	/* Call parent inilializers */
	uovd.provider.http.Base.prototype.initialize.apply(this);
	uovd.provider.rdp.Base.prototype.initialize.apply(this);

	/* Ajax provider initializer */
	this.request_cache = {};
	this.request_cache_index = 0;

	/* ---------- Init Applet ---------- */
	this.node = jQuery((node || "body"));
	this.applet_codebase = null;
	this.main_applet = null;
	this.initialisation_state = 0; // 0=none | 1=started | 2=ok | 3=error

	/* Applet callbacks */
	this.applet_registered        = function() {};
	this.applet_sessionReady      = function() {};
	this.applet_sessionError      = function() {};
	this.applet_serverStatus      = function() {};
	this.applet_applicationStatus = function() {};

	/* Redefine initialize for JavaProvider */
	this.initialize = function(onsuccess, onfailure) {
		/* Handle state */
		if(this.initialisation_state == 2) { onsuccess(); return; }
		if(this.initialisation_state == 3) { onfailure(); return; }
		if(this.initialisation_state == 1) {
			/* Another initialization is in progress : delaying */
			setTimeout(jQuery.proxy(this.initialize, this, onsuccess, onfailure), 1000);
			return;
		}

		/* First initialization */
		this.initialisation_state = 1;

		var name = "ulteoapplet";
		var archive = "ulteo-applet.jar";
		var cache_archive = "ulteo-applet.jar";
		var cache_archive_ex = "ulteo-applet.jar;preload"

		/* Create and configure the applet */
		this.main_applet = jQuery(document.createElement("applet"));
		this.main_applet.attr("id", "ulteoapplet");
		this.main_applet.attr("width", 1);
		this.main_applet.attr("height", 1);
		this.main_applet.attr("name", name);
		if (this.applet_codebase != null) {
			this.main_applet.attr("codebase", this.applet_codebase);
		}
		this.main_applet.attr("archive", archive);
		this.main_applet.attr("cache_archive", cache_archive);
		this.main_applet.attr("cache_archive_ex", cache_archive_ex);
		this.main_applet.attr("mayscript", "true");
		this.main_applet.attr("code", "org.ulteo.ovd.applet.WebClient");

		this.main_applet.append(jQuery(document.createElement("param")).attr("name", "name").attr("value", name));
		if (this.applet_codebase != null) {
			this.main_applet.append(jQuery(document.createElement("param")).attr("name", "codebase").attr("value", this.applet_codebase));
		}
		this.main_applet.append(jQuery(document.createElement("param")).attr("name", "archive").attr("value", archive));
		this.main_applet.append(jQuery(document.createElement("param")).attr("name", "cache_archive").attr("value", cache_archive));
		this.main_applet.append(jQuery(document.createElement("param")).attr("name", "cache_archive_ex").attr("value", cache_archive_ex));
		this.main_applet.append(jQuery(document.createElement("param")).attr("code", "org.ulteo.ovd.applet.WebClient"));

		/* minimize applet size */
		/* css "display: none" doesn't works with applets */
		this.main_applet.width("1").height("1");

		/* Insert it into the specified node */
		this.node.append(this.main_applet);

		/* Try to activate java applet */
		var self = this; /* closure */
		var error_timeout = null;
		var retry_timeout = null;

		var activation = function() {
			try {
				self.main_applet[0].isActive();
			} catch(e) {
				retry_timeout = setTimeout(activation, 1000);
				return;
			}

			clearTimeout(error_timeout);
			self.applet_registered = function() {
				self.initialisation_state = 2;
				onsuccess();
			}
			self.main_applet[0].register(self);
		};

		error_timeout = setTimeout(function() {
			clearTimeout(retry_timeout);
			self.main_applet.remove();
			self.main_applet = null;
			self.initialisation_state = 3;
			onfailure();
		}, 60000);

		retry_timeout = setTimeout(function() {
			activation();
		}, 100);
	};
}

/* Multiple inheritance */
for(var i in uovd.provider.http.Base.prototype) { uovd.provider.Java.prototype[i] = uovd.provider.http.Base.prototype[i]; }
for(var i in uovd.provider.rdp.Base.prototype)  { uovd.provider.Java.prototype[i] = uovd.provider.rdp.Base.prototype[i];  }


uovd.provider.Java.prototype.set_applet_codebase = function(applet_codebase_) {
	this.applet_codebase = applet_codebase_;
	return this;
}

/* --------------- Ajax provider part --------------- */

uovd.provider.Java.prototype.applet_ajaxResponse = function(req_id, http_code, contentType, data) {
	if(! this.request_cache[req_id]) { return; }

	var callback = this.request_cache[req_id];
	delete this.request_cache[req_id];

	if(http_code != 200) { return; }

	/* parse XML */
	var xml = null;
	if (window.ActiveXObject){
		xml = new ActiveXObject('Microsoft.XMLDOM');
		xml.async='false';
		xml.loadXML(data);
	} else {
		var parser = new DOMParser();
		xml = parser.parseFromString(data,'text/xml');
	}

	callback(xml);
}

uovd.provider.Java.prototype.sessionStart_implementation = function(callback) {
	var parameters = this.session_management.parameters;
	var sessionmanager = parameters["sessionmanager"];

	var service_url = "https://"+sessionmanager+"/ovd/client/start.php";
	var data = this.build_sessionStart(parameters, "txt");

	var self = this; /* closure */
	var onfailure = function() {
		callback(null);
	};
	var onsuccess = function() {
		var index = self.request_cache_index++;
		self.request_cache[index] = callback;
		self.main_applet[0].ajaxRequest(service_url, "post", "text/xml", data, index);
	}

	if(this.main_applet == null) {
		this.initialize(onsuccess, onfailure);
	} else {
		onsuccess();
	}
}

uovd.provider.Java.prototype.sessionStatus_implementation = function(callback) {
	var sessionmanager = this.session_management.parameters["sessionmanager"];

	var service_url = "https://"+sessionmanager+"/ovd/client/session_status.php";
	var data = "";

	var self = this; /* closure */
	var onfailure = function() {
		callback(null);
	};
	var onsuccess = function() {
		var index = self.request_cache_index++;
		self.request_cache[index] = callback;
		self.main_applet[0].ajaxRequest(service_url, "get", "text/xml", data, index);
	}

	if(this.main_applet == null) {
		this.initialize(onsuccess, onfailure);
	} else {
		onsuccess();
	}
}

uovd.provider.Java.prototype.sessionEnd_implementation = function(callback) {
	var parameters = this.session_management.parameters;
	var sessionmanager = parameters["sessionmanager"];

	var service_url = "https://"+sessionmanager+"/ovd/client/logout.php";
	var data = this.build_sessionEnd(parameters, "txt");

	var self = this; /* closure */
	var onfailure = function() {
		callback(null);
	};
	var onsuccess = function() {
		var index = self.request_cache_index++;
		self.request_cache[index] = callback;
		self.main_applet[0].ajaxRequest(service_url, "post", "text/xml", data, index);
	}

	if(this.main_applet == null) {
		this.initialize(onsuccess, onfailure);
	} else {
		onsuccess();
	}
}

uovd.provider.Java.prototype.sessionSuspend_implementation = function(callback) {
	var parameters = this.session_management.parameters;
	var sessionmanager = parameters["sessionmanager"];

	var service_url = "https://"+sessionmanager+"/ovd/client/logout.php";
	var data = this.build_sessionSuspend(parameters, "txt");

	var self = this; /* closure */
	var onfailure = function() {
		callback(null);
	};
	var onsuccess = function() {
		var index = self.request_cache_index++;
		self.request_cache[index] = callback;
		self.main_applet[0].ajaxRequest(service_url, "post", "text/xml", data, index);
	}

	if(this.main_applet == null) {
		this.initialize(onsuccess, onfailure);
	} else {
		onsuccess();
	}
}

/* --------------- Rdp provider part --------------- */

uovd.provider.Java.prototype.connectDesktop = function() {
	var self = this; /* closure */

	/* Get the first RDP server */
	var server = null;
	for(var i=0 ; i<this.session_management.session.servers.length ; ++i) {
		var srv = this.session_management.session.servers[i];
		if(srv.type == uovd.SERVER_TYPE_LINUX || srv.type == uovd.SERVER_TYPE_WINDOWS) {
			server = srv;
			break;
		}
	}

	if(server == null) {
		self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"No RDP server"});
		return;
	}

	var parameters = this.session_management.parameters;
	var session_settings = this.session_management.session.settings;
	var onfailure = function() {
		self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"Can't initialize applet"});
	};
	var onsuccess = function() {
		var settings = new Array();
		settings.push("sessionmanager");
		settings.push(parameters["sessionmanager"] + (parameters["sessionmanager"].indexOf(":") == -1 ? ":443" : ""));
		settings.push("keymap");
		settings.push(parameters["keymap"]);
		settings.push("rdp_input_method");
		settings.push(""+parameters["rdp_input_method"]);
		settings.push("fullscreen");
		settings.push(""+parameters["fullscreen"]);
		settings.push("local_integration");
		settings.push(""+parameters["local_integration"]);
		settings.push("wc_url");
		settings.push(""+parameters["wc_url"]);
		settings.push("container");
		settings.push("Desktop_0");

		for(i in session_settings) {
			settings.push(i);
			settings.push(session_settings[i]);
		}

		/* Add the servers status callback */
		self.applet_serverStatus = function(id, status) {
			var server = self.session_management.session.servers[id];

			if(server.type == uovd.SERVER_TYPE_LINUX || server.type == uovd.SERVER_TYPE_WINDOWS) {
				server.setStatus(status);
			}
		};

		if(parameters["fullscreen"] == true) {
			self.connectDesktop_fullscreen(server, settings);
		} else {
			self.connectDesktop_embeeded(server, settings);
		}

		/* Applet startSession handler */
		self.applet_sessionReady = function() {
			var success = true;
			if(server.token) {
				var fqdn = window.location.hostname;
				var port = window.location.port !=  '' ? window.location.port : 443;
				success = self.main_applet[0].serverConnect(0, fqdn, port, server.token, server.login, server.password);
			} else {
				success = self.main_applet[0].serverConnect(0, server.fqdn, server.port, server.login, server.password);
			}

			if(! success) {
				self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"serverConnect failed"});
			}
		};

		/* Handle startSession error */
		self.applet_sessionError = function(code, message) {
			self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:message});
		};
	};

	if(this.main_applet == null) {
		this.initialize(onsuccess, onfailure);
	} else {
		onsuccess();
	}
};

uovd.provider.Java.prototype.connectDesktop_embeeded = function(server, settings) {
	var self = this; /* closure */
	var parameters = this.session_management.parameters;

	/* Generate a desktop applet */
	var archive = "ulteo-applet.jar";
	var cache_archive = "ulteo-applet.jar";
	var cache_archive_ex = "ulteo-applet.jar;preload"
	var name = "Desktop_0";

	var desktop_applet = jQuery(document.createElement("applet"));
	desktop_applet.attr("id", name);
	desktop_applet.attr("name", name);
	desktop_applet.attr("width", parameters["width"]);
	desktop_applet.attr("height", parameters["height"]);
	if (self.applet_codebase != null) {
		desktop_applet.attr("codebase", self.applet_codebase);
	}
	desktop_applet.attr("archive", archive);
	desktop_applet.attr("cache_archive", cache_archive);
	desktop_applet.attr("cache_archive_ex", cache_archive_ex);
	desktop_applet.attr("code", "org.ulteo.ovd.applet.DesktopContainer");

	desktop_applet.append(jQuery(document.createElement("param")).attr("name", "id").attr("value", name));
	desktop_applet.append(jQuery(document.createElement("param")).attr("name", "name").attr("value", name));
	if (self.applet_codebase != null) {
		desktop_applet.append(jQuery(document.createElement("param")).attr("name", "codebase").attr("value", self.applet_codebase));
	}
	desktop_applet.append(jQuery(document.createElement("param")).attr("name", "archive").attr("value", archive));
	desktop_applet.append(jQuery(document.createElement("param")).attr("name", "cache_archive").attr("value", cache_archive));
	desktop_applet.append(jQuery(document.createElement("param")).attr("name", "cache_archive_ex").attr("value", cache_archive_ex));
	desktop_applet.append(jQuery(document.createElement("param")).attr("code", "org.ulteo.ovd.applet.DesktopContainer"));

	/* Notify main panel insertion */
	self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"type":"Desktop", "node":desktop_applet[0]});

	/* Wait for desktop applet */
	var error_timeout = null;
	var retry_timeout = null;

	var activation = function() {
		try {
			desktop_applet[0].isActive();
		} catch(e) {
			retry_timeout = setTimeout(activation, 1000);
			return;
		}

		clearTimeout(error_timeout);

		/* Start the session */
		if(! self.main_applet[0].startSession(self.session_management.session.mode, settings)) {
			self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"startSession failed"});
		}
	};

	error_timeout = setTimeout(function() {
		clearTimeout(retry_timeout);
		self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"Can't initialize applet"});
	}, 60000);

	retry_timeout = setTimeout(function() {
		activation();
	}, 100);
};

uovd.provider.Java.prototype.connectDesktop_fullscreen = function(server, settings) {
	var self = this; /* closure */
	var parameters = this.session_management.parameters;

	self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"type":"Fullscreen", "node":null});

	/* Add the fullscreen request function */
	self.request_fullscreen = function() {
		self.main_applet[0].switchBackFullscreenWindow();
	};

	/* Start the session */
	if(! this.main_applet[0].startSession(self.session_management.session.mode, settings)) {
		self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"startSession failed"});
	}
}

uovd.provider.Java.prototype.connectApplications = function() {
	var self = this; /* closure */
	var parameters = this.session_management.parameters;
	var session_settings = this.session_management.session.settings;
	var onfailure = function() {
		self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"Can't initialize applet"});
	};
	var onsuccess = function() {
		var settings = new Array();
		settings.push("sessionmanager");
		settings.push(parameters["sessionmanager"] + (parameters["sessionmanager"].indexOf(":") == -1 ? ":443" : ""));
		settings.push("keymap");
		settings.push(parameters["keymap"]);
		settings.push("rdp_input_method");
		settings.push(""+parameters["rdp_input_method"]);
		settings.push("fullscreen");
		settings.push(""+parameters["fullscreen"]);
		settings.push("local_integration");
		settings.push(""+parameters["local_integration"]);
		settings.push("wc_url");
		settings.push(""+parameters["wc_url"]);
		settings.push("container");
		settings.push("Desktop_0");

		for(i in session_settings) {
			settings.push(i);
			settings.push(session_settings[i]);
		}

		/* set applications_provider */
		var applications_provider = new uovd.provider.applications.Java(self);

		/* Add the servers status callback */
		self.applet_serverStatus = function(id, status) {
			var server = self.session_management.session.servers[id];

			if(server.type == uovd.SERVER_TYPE_LINUX || server.type == uovd.SERVER_TYPE_WINDOWS) {
				server.setStatus(status);
			}
		};

		/* Add the application status callback */
		self.applet_applicationStatus = function(id, instance, status) {
			applications_provider.handleOrders(id, instance, status);
		};

		/* Applet startSession handler */
		self.applet_sessionReady = function() {
			/* Connect to all servers and gather application list */
			for(var i=0 ; i<self.session_management.session.servers.length ; ++i) {
				var server = self.session_management.session.servers[i];

				if(server.type != uovd.SERVER_TYPE_LINUX && server.type != uovd.SERVER_TYPE_WINDOWS) {
					/* Not an RDP server : skip it */
					continue;
				}

				var serialized;
				try {
					// XMLSerializer exists in current Mozilla browsers
					serializer = new XMLSerializer();
					serialized = serializer.serializeToString(server.xml);
				} catch (e) {
					// Internet Explorer has a different approach to serializing XML
					serialized = server.xml.xml;
				}

				self.main_applet[0].serverPrepare(i, serialized);

				var success = true;
				if(server.token) {
					var fqdn = window.location.hostname;
					var port = window.location.port !=  '' ? window.location.port : 443;
					success = self.main_applet[0].serverConnect(i, fqdn, port, server.token, server.login, server.password);
				} else {
				  success = self.main_applet[0].serverConnect(i, server.fqdn, server.port, server.login, server.password);
				}

				if(! success) {
					self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"serverConnect failed"});
					return;
				}
			}
		};

		/* Handle startSession error */
		self.applet_sessionError = function(code, message) {
			self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:message});
		};

		/* Start the session */
		if(! self.main_applet[0].startSession(self.session_management.session.mode, settings)) {;
			self.session_management.fireEvent("ovd.rdpProvider.crash", self, {message:"startSession failed"});
		}
	};

	if(this.main_applet == null) {
		this.initialize(onsuccess, onfailure);
	} else {
		onsuccess();
	}
};

uovd.provider.Java.prototype.disconnect_implementation = function() {
	this.main_applet[0].endSession();
};

/* --------------- Utils --------------- */

uovd.provider.Java.prototype.testCapabilities = function(onsuccess, onfailure) {
	this.initialize(onsuccess, onfailure);
};

uovd.provider.Java.prototype.getUserLogin = function(callback) {
	var self = this; /* closure */
	var onfailure = function() {
		callback("");
	};
	var onsuccess = function() {
		callback(self.main_applet[0].getUserLogin());
	}

	this.initialize(onsuccess, onfailure);
}

uovd.provider.Java.prototype.getDetectedKeyboardLayout = function(callback) {
	var self = this; /* closure */
	var onfailure = function() {
		callback("");
	};
	var onsuccess = function() {
		callback(self.main_applet[0].getDetectedKeyboardLayout());
	}

	this.initialize(onsuccess, onfailure);
}
