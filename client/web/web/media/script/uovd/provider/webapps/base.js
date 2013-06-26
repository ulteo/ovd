/* Base class */

uovd.provider.webapps.Base = function() {};

uovd.provider.webapps.Base.prototype.initialize = function() {
	this.session_management = null;
};

uovd.provider.webapps.Base.prototype.connect = function() {
	if (this.session_management.parameters["mode"] == uovd.SESSION_MODE_DESKTOP) {
		this.connectDesktop();
	} else if (this.session_management.parameters["mode"] == uovd.SESSION_MODE_APPLICATIONS) {
		this.connectApplications();
	}
};

uovd.provider.webapps.Base.prototype.disconnect = function() {
	this.disconnect_implementation();
};
