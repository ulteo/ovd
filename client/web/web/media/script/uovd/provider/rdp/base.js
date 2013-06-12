/* Base class */

uovd.provider.rdp.Base = function() { }

uovd.provider.rdp.Base.prototype.initialize = function() {
	this.session_management = null;
}

uovd.provider.rdp.Base.prototype.testCapabilities = function() { return true; }
uovd.provider.rdp.Base.prototype.connect = function() {
	if (this.session_management.parameters["session_type"] == "desktop") {
		this.connectDesktop();
	} else if (this.session_management.parameters["session_type"] == "applications") {
		this.connectApplications();
	}
}

uovd.provider.rdp.Base.prototype.disconnect = function() {
	this.disconnect_implementation();
}
