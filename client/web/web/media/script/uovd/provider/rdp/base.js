/* Base class */

uovd.provider.rdp.Base = function() { }

uovd.provider.rdp.Base.prototype.initialize = function() {
	this.session_management = null;
}

uovd.provider.rdp.Base.prototype.testCapabilities = function(onsuccess, onfailure) {};

uovd.provider.rdp.Base.prototype.connect = function() {
	if (this.session_management.parameters["session_type"] == uovd.SESSION_MODE_DESKTOP) {
		this.connectDesktop();
	} else if (this.session_management.parameters["session_type"] == uovd.SESSION_MODE_APPLICATIONS) {
		this.connectApplications();
	}
}

uovd.provider.rdp.Base.prototype.disconnect = function() {
	this.disconnect_implementation();
}
