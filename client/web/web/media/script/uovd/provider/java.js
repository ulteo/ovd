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
	this.main_applet = null;

	/* Applet callbacks */
	this.applet_registered        = function() {};
	this.applet_sessionReady      = function() {};
	this.applet_sessionError      = function() {};
	this.applet_serverStatus      = function() {};
	this.applet_applicationStatus = function() {};

	/* Redefine initialize for JavaProvider */
	this.initialize = function(onsuccess, onfailure) {
		var name = "ulteoapplet";
		var codebase = "applet/";
		var archive = "ulteo-applet.jar";
		var cache_archive = "ulteo-applet.jar";
		var cache_archive_ex = "ulteo-applet.jar;preload"

		/* Create and configure the applet */
		this.main_applet = jQuery(document.createElement("applet"));
		this.main_applet.attr("id", "ulteoapplet");
		this.main_applet.attr("width", 1);
		this.main_applet.attr("height", 1);
		this.main_applet.attr("name", name);
		this.main_applet.attr("codebase", codebase);
		this.main_applet.attr("archive", archive);
		this.main_applet.attr("cache_archive", cache_archive);
		this.main_applet.attr("cache_archive_ex", cache_archive_ex);
		this.main_applet.attr("mayscript", "true");
		this.main_applet.attr("code", "org.ulteo.ovd.applet.WebClient");

		this.main_applet.append(jQuery(document.createElement("param")).attr("name", "name").attr("value", name));
		this.main_applet.append(jQuery(document.createElement("param")).attr("name", "codebase").attr("value", codebase));
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
				try{ onsuccess(); } catch(e) {}
			}
			self.main_applet[0].register(self);
		};

		error_timeout = setTimeout(function() {
			clearTimeout(retry_timeout);
			this.main_applet.remove();
			this.main_applet = null;
			try { onfailure(); } catch(e) {}
		}, 60000);

		retry_timeout = setTimeout(function() {
			activation();
		}, 100);
	};
}

/* Multiple inheritance */
for(var i in uovd.provider.http.Base.prototype) { uovd.provider.Java.prototype[i] = uovd.provider.http.Base.prototype[i]; }
for(var i in uovd.provider.rdp.Base.prototype)  { uovd.provider.Java.prototype[i] = uovd.provider.rdp.Base.prototype[i];  }

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
	var session_manager = this.session_management.parameters["session_manager"];
	var mode = this.session_management.parameters["session_type"];
	var language = this.session_management.parameters["language"];
	var timezone = this.session_management.parameters["timezone"];
	var login = this.session_management.parameters["username"];
	var password = this.session_management.parameters["password"];

	var service_url = "https://"+session_manager+"/ovd/client/start.php";
	var data = ""+
		"<session mode='"+mode+"' language='"+language+"' timezone='"+timezone+"'>"+
			"<user login='"+login+"' password='"+password+"'/>"+
		"</session>";

	var self = this; /* closure */
	var onfailure = function() {
		/* !!! Error  */
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
	var session_manager = this.session_management.parameters["session_manager"];

	var service_url = "https://"+session_manager+"/ovd/client/session_status.php";
	var data = "";

	var self = this; /* closure */
	var onfailure = function() {
		/* !!! Error  */
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
	var session_manager = this.session_management.parameters["session_manager"];

	var service_url = "https://"+session_manager+"/ovd/client/logout.php";
	var data = ""+
		"<logout mode='logout'/>";

	var self = this; /* closure */
	var onfailure = function() {
		/* !!! Error  */
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
	var session_manager = this.session_management.parameters["session_manager"];

	var service_url = "https://"+session_manager+"/ovd/client/logout.php";
	var data = ""+
		"<logout mode='suspend'/>";

	var self = this; /* closure */
	var onfailure = function() {
		/* !!! Error  */
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
	var server = this.session_management.session.servers[0];
	var settings = this.session_management.session.settings;
	var parameters = this.session_management.parameters;
	var onfailure = function() {
		/* !!! Error  */
	};
	var onsuccess = function() {
		var settings = new Array();
		settings.push("sessionmanager");
		settings.push(parameters["session_manager"]+":443");
		settings.push("keymap");
		settings.push(parameters["keymap"]);
		settings.push("rdp_input_method");
		settings.push(""+parameters["rdp_input_method"]);
		settings.push("fullscreen");
		settings.push(""+parameters["fullscreen"]);
		settings.push("local_integration");
		settings.push(""+parameters["local_integration"]);
		settings.push("container");
		settings.push("Desktop_0");

		/* Add the servers status callback */
		self.applet_serverStatus = function(id, status) {
			self.session_management.session.servers[id].setStatus(status);
		};

		/* Generate a desktop applet */
		var codebase = "applet/";
		var archive = "ulteo-applet.jar";
		var cache_archive = "ulteo-applet.jar";
		var cache_archive_ex = "ulteo-applet.jar;preload"
		var name = "Desktop_0";

		var desktop_applet = jQuery(document.createElement("applet"));
		desktop_applet.attr("id", name);
		desktop_applet.attr("name", name);
		desktop_applet.attr("width", parameters["width"]);
		desktop_applet.attr("height", parameters["height"]);
		desktop_applet.attr("codebase", codebase);
		desktop_applet.attr("archive", archive);
		desktop_applet.attr("cache_archive", cache_archive);
		desktop_applet.attr("cache_archive_ex", cache_archive_ex);
		desktop_applet.attr("code", "org.ulteo.ovd.applet.DesktopContainer");

		desktop_applet.append(jQuery(document.createElement("param")).attr("name", "id").attr("value", name));
		desktop_applet.append(jQuery(document.createElement("param")).attr("name", "name").attr("value", name));
		desktop_applet.append(jQuery(document.createElement("param")).attr("name", "codebase").attr("value", codebase));
		desktop_applet.append(jQuery(document.createElement("param")).attr("name", "archive").attr("value", archive));
		desktop_applet.append(jQuery(document.createElement("param")).attr("name", "cache_archive").attr("value", cache_archive));
		desktop_applet.append(jQuery(document.createElement("param")).attr("name", "cache_archive_ex").attr("value", cache_archive_ex));
		desktop_applet.append(jQuery(document.createElement("param")).attr("code", "org.ulteo.ovd.applet.DesktopContainer"));

		/* Notify main panel insertion */
		self.session_management.fireEvent("ovd.rdpProvider.desktopPanel", self, {"name":name, "node":desktop_applet[0]});

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

			/* Applet startSession handler */
			self.applet_sessionReady = function() {
				if(! self.main_applet[0].serverConnect(0, server.fqdn, server.port, server.login, server.password)) {
					/* !!! Error */
				}
			};

			/* Start the session */
			if(! self.main_applet[0].startSession(parameters["session_type"], settings)) {
				/* !!! Error */
			}
		};

		error_timeout = setTimeout(function() {
			clearTimeout(retry_timeout);
			/* !!! Error  */
		}, 60000);

		retry_timeout = setTimeout(function() {
			activation();
		}, 100);

	};

	if(this.main_applet == null) {
		this.initialize(onsuccess, onfailure);
	} else {
		onsuccess();
	}
};

uovd.provider.Java.prototype.connectApplications = function() {
	var self = this; /* closure */
	var server = this.session_management.session.servers[0];
	var settings = this.session_management.session.settings;
	var parameters = this.session_management.parameters;
	var onfailure = function() {
		/* !!! Error  */
	};
	var onsuccess = function() {
		var settings = new Array();
		settings.push("sessionmanager");
		settings.push(parameters["session_manager"]+":443");
		settings.push("keymap");
		settings.push(parameters["keymap"]);
		settings.push("rdp_input_method");
		settings.push(""+parameters["rdp_input_method"]);
		settings.push("fullscreen");
		settings.push(""+parameters["fullscreen"]);
		settings.push("local_integration");
		settings.push(""+parameters["local_integration"]);
		settings.push("container");
		settings.push("Desktop_0");

		/* set application_provider */
		var application_provider = new uovd.provider.rdp.application.Java(self);

		/* Add the servers status callback */
		self.applet_serverStatus = function(id, status) {
			self.session_management.session.servers[id].setStatus(status);
		};

		/* Add the application status callback */
		self.applet_applicationStatus = function(id, instance, status) {
			application_provider.handleOrders(id, instance, status);
		};

		/* Applet startSession handler */
		self.applet_sessionReady = function() {
			/* Connect to all servers and gather application list */
			for(var i=0 ; i<self.session_management.session.servers.length ; ++i) {
				var server = self.session_management.session.servers[i];
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

				if (server.token != null) {
					if(! self.main_applet[0].serverConnect(i, server.fqdn, server.port, server.token, server.login, server.password)) {
						/* !!! Error  */
					};
				} else {
					if(! self.main_applet[0].serverConnect(i, server.fqdn, server.port, server.login, server.password)) {
						/* !!! Error  */
					};
				}
			}
		};

		/* Start the session */
		if(! self.main_applet[0].startSession(parameters["session_type"], settings)) {;
			/* !!! Error  */
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
