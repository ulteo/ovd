/* Base class */

function AjaxProvider() {
	this.session_management = null;
}

AjaxProvider.prototype.sessionStart = function() {
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

AjaxProvider.prototype.sessionStatus = function() {
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

AjaxProvider.prototype.sessionEnd = function() {
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
