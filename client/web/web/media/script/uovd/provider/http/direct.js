/* DirectAjaxProvider */

/* Contact directly the session manager.
   Works only with the ssl gateway because of the 
   XmlHttpRequest "same origin" restriction.
*/

uovd.provider.http.Direct = function() {
	this.initialize();
}
uovd.provider.http.Direct.prototype = new uovd.provider.http.Base();

uovd.provider.http.Direct.prototype.sessionStart_implementation = function(callback) {
	var mode = this.session_management.parameters["session_type"];
	var language = this.session_management.parameters["language"];
	var timezone = this.session_management.parameters["timezone"];
	var login = this.session_management.parameters["username"];
	var password = this.session_management.parameters["password"];

  jQuery.ajax({
		url: "/ovd/client/start.php",
		type: "POST",
		dataType: "xml",
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

uovd.provider.http.Direct.prototype.sessionStatus_implementation = function(callback) {
  jQuery.ajax({
		url: "/ovd/client/session_status.php",
		type: "GET",
		dataType: "xml",
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

uovd.provider.http.Direct.prototype.sessionEnd_implementation = function(callback) {
  jQuery.ajax({
		url: "/ovd/client/logout.php",
		type: "POST",
		dataType: "xml",
		contentType: "text/xml",
		data: ""+
		"<logout mode='logout'/>",
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

uovd.provider.http.Direct.prototype.sessionSuspend_implementation = function(callback) {
  jQuery.ajax({
		url: "/ovd/client/logout.php",
		type: "POST",
		dataType: "xml",
		contentType: "text/xml",
		data: ""+
		"<logout mode='suspend'/>",
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

