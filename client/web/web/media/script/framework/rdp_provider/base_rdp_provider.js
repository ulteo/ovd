/* Base class */

uovd.RdpProvider = function() { }

uovd.RdpProvider.prototype.initialize = function() {
	this.session_management = null;
}

uovd.RdpProvider.prototype.testCapabilities = function() { return true; }
uovd.RdpProvider.prototype.connect = function() {
	if (this.session_management.parameters["session_type"] == "desktop") {
		this.connectDesktop();
	} else if (this.session_management.parameters["session_type"] == "applications") {
		this.connectApplications();
	}
}

uovd.RdpProvider.prototype.disconnect = function() {
	this.disconnect_implementation();
}
