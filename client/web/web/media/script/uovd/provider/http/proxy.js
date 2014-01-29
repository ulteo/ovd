/* ProxyHttpProvider */

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
	var sessionmanager = parameters["sessionmanager"];

  jQuery.ajax({
		url: this.proxy_url,
		type: "POST",
		dataType: "xml",
		headers: {
			"x-ovd-sessionmanager" : sessionmanager,
			"x-ovd-service" : "start"
		},
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

uovd.provider.http.Proxy.prototype.sessionStatus_implementation = function(callback) {
	var sessionmanager = this.session_management.parameters["sessionmanager"];
  jQuery.ajax({
		url: this.proxy_url,
		type: "GET",
		dataType: "xml",
		headers: {
			"x-ovd-sessionmanager" : sessionmanager,
			"x-ovd-service" : "session_status"
		},
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			callback(null);
		}
	});
}

uovd.provider.http.Proxy.prototype.sessionEnd_implementation = function(callback) {
	var parameters = this.session_management.parameters;
	var sessionmanager = parameters["sessionmanager"];

  jQuery.ajax({
		url: this.proxy_url,
		type: "POST",
		dataType: "xml",
		headers: {
			"x-ovd-sessionmanager" : sessionmanager,
			"x-ovd-service" : "logout"
		},
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

uovd.provider.http.Proxy.prototype.sessionSuspend_implementation = function(callback) {
	var parameters = this.session_management.parameters;
	var sessionmanager = parameters["sessionmanager"];

  jQuery.ajax({
		url: this.proxy_url,
		type: "POST",
		dataType: "xml",
		headers: {
			"x-ovd-sessionmanager" : sessionmanager,
			"x-ovd-service" : "logout"
		},
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
