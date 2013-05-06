/* DirectAjaxProvider */

/* Contact directly the session manager.
   Works only with the ssl gateway because of the 
   XmlHttpRequest "same origin" restriction.
*/

function DirectAjaxProvider() {
	AjaxProvider();
}
DirectAjaxProvider.prototype = new AjaxProvider();

DirectAjaxProvider.prototype.sessionStart_implementation = function(callback) {
	var mode = this.session_management.parameters["session_type"];
	var language = "en-us";
	var timezone = "Europe/Amsterdam";
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

DirectAjaxProvider.prototype.sessionStatus_implementation = function(callback) {
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

DirectAjaxProvider.prototype.sessionEnd_implementation = function(callback) {
	var mode = this.session_management.parameters["session_type"];
  jQuery.ajax({
		url: "/ovd/client/logout.php",
		type: "POST",
		dataType: "xml",
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

