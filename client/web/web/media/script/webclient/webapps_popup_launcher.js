WebAppsPopupLauncher = function(session_management) {
	this.session_management = session_management;
	this.webapps_handlers = new Array();

	/* polling interval */
	this.polling = setInterval(jQuery.proxy(this.monitorWindowStates, this), 2000);

	/* register events listeners */
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.session_management.addCallback("ovd.applicationsProvider.web.start", this.handler);
	this.session_management.addCallback("ovd.session.destroying",             this.handler);
};

WebAppsPopupLauncher.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.applicationsProvider.web.start") {
		var handler = params;

		/* Here, we can redefine handler.onstart/onstop to specify how to handle the webapp url */
		handler.onstart = function(url) {
			handler.url = url;
			handler.window = window.open(url, '_blank');
		};

		handler.onstop = function(url) {
			handler.window.close();
		};

		this.webapps_handlers.push(handler);
		handler.start();
	}

	if(type == "ovd.session.destroying" ) { /* Clean context */
		this.end();
	}
};

WebAppsPopupLauncher.prototype.monitorWindowStates = function() {
	var to_remove = new Array();

	for(var i=0 ; i<this.webapps_handlers.length ; ++i) {
		var handler = this.webapps_handlers[i];

		if(handler.window != undefined && handler.window.closed == true) {
			handler.stop();
			to_remove.push(i);
		}
	}

	if(to_remove.length > 0) {
		for(var i=0 ; i<to_remove.length ; ++i) {
			this.webapps_handlers.splice(to_remove[i], 1);
		}
	}
};

WebAppsPopupLauncher.prototype.end = function() {
	for(var i=0 ; i<this.webapps_handlers.length ; ++i) {
		this.webapps_handlers[i].stop();
	}

	this.webapps_handlers = new Array();
	clearInterval(this.polling);
};
