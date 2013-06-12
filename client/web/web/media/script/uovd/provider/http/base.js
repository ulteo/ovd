/* Base class */

uovd.provider.http.Base = function() { }

uovd.provider.http.Base.prototype.initialize = function() {
	this.session_management = null;
}

uovd.provider.http.Base.prototype.sessionStart = function() {
	var self = this; /* closure */
	this.sessionStart_implementation( function(xml) {
		var error = self.session_management.session.update(xml);
		if(error) {
			self.session_management.fireEvent("ovd.ajaxProvider.sessionStart", self, {"state":"error", "code":error});
		} else {
			self.session_management.fireEvent("ovd.ajaxProvider.sessionStart", self, {"state":"success"});
		}
	});
}

uovd.provider.http.Base.prototype.sessionStatus = function() {
	var self = this; /* closure */
	this.sessionStatus_implementation( function(xml) {
		var error = self.session_management.session.update(xml);
		if(error) {
			self.session_management.fireEvent("ovd.ajaxProvider.sessionStatus", self, {"state":"error", "code":error});
		} else {
			self.session_management.fireEvent("ovd.ajaxProvider.sessionStatus", self, {"state":"success"});
		}
	});
}

uovd.provider.http.Base.prototype.sessionEnd = function() {
	var self = this; /* closure */
	this.sessionEnd_implementation( function(xml) {
		var error = self.session_management.session.update(xml);
		if(error) {
			self.session_management.fireEvent("ovd.ajaxProvider.sessionEnd", self, {"state":"error", "code":error});
		} else {
			self.session_management.fireEvent("ovd.ajaxProvider.sessionEnd", self, {"state":"success"});
		}
	});
}

uovd.provider.http.Base.prototype.sessionSuspend = function() {
	var self = this; /* closure */
	this.sessionSuspend_implementation( function(xml) {
		var error = self.session_management.session.update(xml);
		if(error) {
			self.session_management.fireEvent("ovd.ajaxProvider.sessionSuspend", self, {"state":"error", "code":error});
		} else {
			self.session_management.fireEvent("ovd.ajaxProvider.sessionSuspend", self, {"state":"success"});
		}
	});
}
