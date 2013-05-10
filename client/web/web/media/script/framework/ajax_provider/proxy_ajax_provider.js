/* ProxyAjaxProvider */

/* Provides all OVD services through an unique webService
   specified in the constructor (sample webservice is proxy.php)
*/

function ProxyAjaxProvider(proxy_url) {
	AjaxProvider();
	this.proxy_url = proxy_url;
}
ProxyAjaxProvider.prototype = new AjaxProvider();

ProxyAjaxProvider.prototype.sessionStart_implementation = function(callback) {
	var mode = this.session_management.parameters["session_type"];
	var language = "en-us";
	var timezone = "Europe/Amsterdam";
	var login = this.session_management.parameters["username"];
	var password = this.session_management.parameters["password"];

  jQuery.ajax({
		url: this.proxy_url,
		type: "POST",
		dataType: "xml",
		headers: {
			"X-Ovd-Service" : "start"
		},
		contentType: "text/xml",
		data: ""+
		"<session mode='"+mode+"' language='"+language+"' timezone='"+timezone+"'>"+
			"<user login='"+login+"' password='"+password+"'/>"+
		"</session>",
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

ProxyAjaxProvider.prototype.sessionStatus_implementation = function(callback) {
  jQuery.ajax({
		url: this.proxy_url,
		type: "GET",
		dataType: "xml",
		headers: {
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

ProxyAjaxProvider.prototype.sessionEnd_implementation = function(callback) {
	var mode = this.session_management.parameters["session_type"];
  jQuery.ajax({
		url: this.proxy_url,
		type: "POST",
		dataType: "xml",
		headers: {
			"X-Ovd-Service" : "logout"
		},
		contentType: "text/xml",
		data: ""+
		"<logout mode='"+mode+"'/>",
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

