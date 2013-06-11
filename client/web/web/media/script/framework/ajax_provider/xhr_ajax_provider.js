/* XhrAjaxProvider */

/* Standard AjaxProvider */

function XhrAjaxProvider() {
	this.initialize();
}
XhrAjaxProvider.prototype = new uovd.AjaxProvider();

XhrAjaxProvider.prototype.sessionStart_implementation = function(callback) {
	jQuery.ajax({
		url: "login.php",
		type: "POST",
		dataType : "xml",
		data: {
			requested_port: 443,
			sessionmanager_host: this.session_management.parameters["session_manager"],
			login: this.session_management.parameters["username"],
			password: this.session_management.parameters["password"],
			mode: this.session_management.parameters["session_type"],
			language: this.session_management.parameters["language"],
			keymap: "fr",
			timezone: this.session_management.parameters["timezone"],
			desktop_fullscreen: 0,
			debug: 0,
		},
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

XhrAjaxProvider.prototype.sessionStatus_implementation = function(callback) {
	jQuery.ajax({
		url: "session_status.php",
		type: "GET",
		dataType : "xml",
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

XhrAjaxProvider.prototype.sessionEnd_implementation = function(callback) {
	jQuery.ajax({
		url: "logout.php",
		type: "GET",
		dataType : "xml",
		data : {
			mode: "logout"
		},
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}

XhrAjaxProvider.prototype.sessionSuspend_implementation = function(callback) {
	jQuery.ajax({
		url: "logout.php",
		type: "GET",
		dataType : "xml",
		data : {
			mode: "suspend"
		},
		success: function(xml) {
			callback(xml);
		},
		error: function( xhr, status ) {
			console.log("Error : "+status);
		}
	});
}
