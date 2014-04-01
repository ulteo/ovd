/* DirectHttpProvider */

/* Contact directly the session manager.
   Works only with the ssl gateway because of the 
   XmlHttpRequest "same origin" restriction.
*/

uovd.provider.http.Direct = function() {
	this.initialize();
}
uovd.provider.http.Direct.prototype = new uovd.provider.http.Base();

uovd.provider.http.Direct.prototype.sessionStart_implementation = function(callback) {
	var parameters = this.session_management.parameters;

  jQuery.ajax({
		url: "/ovd/client/start",
		type: "POST",
		dataType: "xml",
		contentType: "text/xml",
		data: this.build_sessionStart(parameters, "txt"),
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			callback(null);
		}
	});
}

uovd.provider.http.Direct.prototype.sessionStatus_implementation = function(callback) {
  jQuery.ajax({
		url: "/ovd/client/session_status",
		type: "GET",
		dataType: "xml",
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			callback(null);
		}
	});
}

uovd.provider.http.Direct.prototype.sessionEnd_implementation = function(callback) {
	var parameters = this.session_management.parameters;

  jQuery.ajax({
		url: "/ovd/client/logout",
		type: "POST",
		dataType: "xml",
		contentType: "text/xml",
		data: this.build_sessionEnd(parameters, "txt"),
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			callback(null);
		}
	});
}

uovd.provider.http.Direct.prototype.sessionSuspend_implementation = function(callback) {
	var parameters = this.session_management.parameters;

  jQuery.ajax({
		url: "/ovd/client/logout",
		type: "POST",
		dataType: "xml",
		contentType: "text/xml",
		data: this.build_sessionSuspend(parameters, "txt"),
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			callback(null);
		}
	});
}

