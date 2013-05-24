/* Base class */

function RdpProvider() { }

RdpProvider.prototype.initialize = function() {
	this.session_management = null;
}

RdpProvider.prototype.testCapabilities = function() { return true; }
RdpProvider.prototype.connect = function() {
	if (this.session_management.parameters["session_type"] == "desktop") {
		this.connectDesktop();
	} else if (this.session_management.parameters["session_type"] == "applications") {
		this.connectApplications();
	}
}

RdpProvider.prototype.disconnect = function() {
	this.disconnect_implementation();
}
