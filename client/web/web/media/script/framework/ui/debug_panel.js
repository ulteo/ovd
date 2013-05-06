function DebugPanel(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.colors = {
		"normal"  : "black",
		"error"   : "red",
		"warning" : "orange",
		"debug"   : "grey" };

	/* register events listeners */
	var self = this; /* closure */
	this.session_management.addCallback("ovd.log", function(type, source, params) {
		self.handleEvents(type, source, params);
	});                                         

	/* Listen events to log */
	session_management.addCallback("ovd.ajaxProvider.sessionStart", function(type, source, params) {
		if(params["code"]) {
			self.handleEvents("ovd.log", source, {"message":"Session start "+type+" : "+params["state"]+" "+params["code"], "level":"error"});
		} else {
			self.handleEvents("ovd.log", source, {"message":"Session start "+type+" : "+params["state"]});
		}
	});

	session_management.addCallback("ovd.ajaxProvider.sessionEnd", function(type, source, params) {
		if(params["code"]) {
			self.handleEvents("ovd.log", source, {"message":"Session stop "+type+" : "+params["state"]+" "+params["code"], "level":"error"});
		} else {
			self.handleEvents("ovd.log", source, {"message":"Session stop "+type+" : "+params["state"]});
		}
	});

	session_management.addCallback("ovd.session.statusChanged", function(type, source, params) {
		self.handleEvents("ovd.log", source, {"message":"Session status "+type+" : "+params["from"]+" --> "+params["to"]});
  });

	session_management.addCallback("ovd.session.server.statusChanged", function(type, source, params) {
		self.handleEvents("ovd.log", source, {"message":"Server status "+type+" : "+params["from"]+" --> "+params["to"]});
  });
}

DebugPanel.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.log") {
		var message = params["message"] || "In "+((new RegExp( "function \(.*(.*) \)", "g" ).exec((source.constructor+""))[1]) || "unknown");
		var level   = params["level"] || "normal";
		this.node.append(jQuery(document.createElement("div")).css("color", this.colors[level]).text(message));
	}
}
