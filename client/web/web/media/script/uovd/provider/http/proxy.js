/* ProxyAjaxProvider */

/* Provides all OVD services through an unique webService
   specified in the constructor (sample webservice is proxy.php)
*/

uovd.provider.http.Proxy = function(proxy_url) {
	this.initialize();
	this.proxy_url = proxy_url;
}
uovd.provider.http.Proxy.prototype = new uovd.provider.http.Base();

uovd.provider.http.Proxy.prototype.sessionStart_implementation = function(callback) {
	var parameters = this.session_management.parameters;
	var session_manager = parameters["session_manager"];

  jQuery.ajax({
		url: this.proxy_url,
		type: "POST",
		dataType: "xml",
		headers: {
			"X-Ovd-SessionManager" : session_manager,
			"X-Ovd-Service" : "start"
		},
		contentType: "text/xml",
		data: this.build_sessionStart(parameters, "txt"),
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

uovd.provider.http.Proxy.prototype.sessionStatus_implementation = function(callback) {
	var session_manager = this.session_management.parameters["session_manager"];
  jQuery.ajax({
		url: this.proxy_url,
		type: "GET",
		dataType: "xml",
		headers: {
			"X-Ovd-SessionManager" : session_manager,
			"X-Ovd-Service" : "session_status"
		},
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

uovd.provider.http.Proxy.prototype.sessionEnd_implementation = function(callback) {
	var parameters = this.session_management.parameters;
	var session_manager = parameters["session_manager"];

  jQuery.ajax({
		url: this.proxy_url,
		type: "POST",
		dataType: "xml",
		headers: {
			"X-Ovd-SessionManager" : session_manager,
			"X-Ovd-Service" : "logout"
		},
		contentType: "text/xml",
		data: this.build_sessionEnd(parameters, "txt"),
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

uovd.provider.http.Proxy.prototype.sessionSuspend_implementation = function(callback) {
	var parameters = this.session_management.parameters;
	var session_manager = parameters["session_manager"];

  jQuery.ajax({
		url: this.proxy_url,
		type: "POST",
		dataType: "xml",
		headers: {
			"X-Ovd-SessionManager" : session_manager,
			"X-Ovd-Service" : "logout"
		},
		contentType: "text/xml",
		data: this.build_sessionSuspend(parameters, "txt"),
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}
