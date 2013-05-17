function DebugPanel(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.colors = {
		"normal"  : "black",
		"error"   : "red",
		"warning" : "orange",
		"debug"   : "grey" };

	var self = this; /* closure */
	this.callbacks = {
		"ovd.ajaxProvider.sessionStart" : function(type, source, params) {
			if(params["code"]) self.handleEvents("ovd.log", source, {"message":"Session start "+type+" : "+params["state"]+" "+params["code"], "level":"error"});
			else               self.handleEvents("ovd.log", source, {"message":"Session start "+type+" : "+params["state"]});
		},

		"ovd.ajaxProvider.sessionEnd" : function(type, source, params) {
			if(params["code"]) self.handleEvents("ovd.log", source, {"message":"Session stop "+type+" : "+params["state"]+" "+params["code"], "level":"error"});
			else               self.handleEvents("ovd.log", source, {"message":"Session stop "+type+" : "+params["state"]});
		},

		"ovd.session.statusChanged" : function(type, source, params) {
			self.handleEvents("ovd.log", source, {"message":"Session status "+type+" : "+params["from"]+" --> "+params["to"]});
		},

		"ovd.session.server.statusChanged" : function(type, source, params) {
			self.handleEvents("ovd.log", source, {"message":"Server status "+type+" : "+params["from"]+" --> "+params["to"]});
		}
	}

	/* register events listeners */
	this.handler = this.handleEvents.bind(this);
	this.session_management.addCallback("ovd.log", this.handler);
	this.session_management.addCallback("ovd.ajaxProvider.sessionEnd",  this.handler);

	/* Listen events to log */
	for(var type in this.callbacks) {
		this.session_management.addCallback(type, this.callbacks[type]);
	}
}

DebugPanel.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.log") {
		var message = params["message"] || "In "+((new RegExp( "function \(.*(.*) \)", "g" ).exec((source.constructor+""))[1]) || "unknown");
		var level   = params["level"] || "normal";
		this.node.append(jQuery(document.createElement("div")).css("color", this.colors[level]).text(message));
	}

	if(type == "ovd.ajaxProvider.sessionEnd") { /* Clean context */
		this.end();
	}
}

DebugPanel.prototype.end = function() {
	this.node.empty();
	this.session_management.removeCallback("ovd.log", this.handler);
	this.session_management.removeCallback("ovd.ajaxProvider.sessionEnd",  this.handler);

	for(var type in this.callbacks) {
		this.session_management.removeCallback(type, callbacks[type]);
	}
}
