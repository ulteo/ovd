/* WebApps server */

uovd.server.WebApps = function(session, xml) {
	var self = this; /* closure */
	this.initialize(session, xml);

	this.base_url = xml.attr("base-url");
	this.webapps_url = xml.attr("webapps-url");

	xml.find("application").each( function() {
		self.applications.push(new uovd.Application(self, jQuery(this)));
	});

	/* Override setStatus function to emulate "connected" state */
	this.super_setStatus = jQuery.proxy(this.setStatus, this);
	this.setStatus = function(status) {
		if(status == uovd.SERVER_STATUS_READY) {
			self.super_setStatus(uovd.SERVER_STATUS_CONNECTED);
		}

		self.super_setStatus(status);
	}
};

uovd.server.WebApps.prototype = new uovd.server.Base();
