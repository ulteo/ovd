/* Html5 Applications provider */

uovd.provider.applications.Html5 = function(rdp_provider) {
	this.initialize(rdp_provider.session_management);
	this.rdp_provider = rdp_provider;
	this.connections = this.rdp_provider.connections;

	/* Constants */
	this.ORDER_INIT = 0x00;
	this.ORDER_STARTED = 0x02;
	this.ORDER_STOPPED = 0x03;
	this.ORDER_CANT_START = 0x03;
	this.ORDER_KNOWN_DRIVES = 0x20;
	this.ORDER_START = 0x01;
	this.ORDER_START_WITH_ARGS = 0x07;
	this.FILE_SHAREDFOLDER = 0x01;
	this.FILE_HTTP = 0x10;

	/* Install instruction hook */
	var self = this; /* closure */
	for(var i=0 ; i<this.connections.length ; ++i) {
		(function(server_id) {
			 self.connections[server_id].guac_tunnel.addInstructionHandler("ovdapp", jQuery.proxy(self.handleOrders, self, server_id));
		 })(i);
	}

	/* Override destructor */
	var end_super = jQuery.proxy(this.end, this);
	this.end = function() {
		end_super();

		/* Remove instruction hook */
		var self = this; /* closure */
		for(var i=0 ; i<this.connections.length ; ++i) {
			(function(server_id) {
				self.connections[server_id].guac_tunnel.removeInstructionHandler("ovdapp");
			})(i);
		}
	};
}
uovd.provider.applications.Html5.prototype = new uovd.provider.applications.Base();

uovd.provider.applications.Html5.prototype.applicationStart_implementation = function (application_id, token) { 
	var server_id = this.getServerByAppId(application_id);
	var stream = new DataStream();
	stream.write_Byte(this.ORDER_START);
	stream.write_UInt32LE(parseInt(token));
	stream.write_UInt32LE(parseInt(application_id));
	this.applications[token] = new uovd.provider.applications.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		var server = this.session_management.session.servers[server_id];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) {
			var ovdapp_message = stream.toBase64();
			this.connections[server_id].guac_tunnel.sendMessage("ovdapp", ovdapp_message);
			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
		}
	} else {
		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
}

uovd.provider.applications.Html5.prototype.applicationStartWithArgs_implementation = function(application_id, args, token) { 
	var server_id = this.getServerByAppId(application_id);
	var file_type = args["type"];
	var file_path = args["path"];
	var file_share = args["share"];

	var stream = new DataStream();
	stream.write_Byte(this.ORDER_START_WITH_ARGS);
	stream.write_UInt32LE(parseInt(token));
	stream.write_UInt32LE(parseInt(application_id));

	switch(file_type) {
		case "sharedfolder" : dir_type = this.FILE_SHAREDFOLDER; break;
		case "http"         : dir_type = this.FILE_HTTP        ; break;
		default             : dir_type = this.FILE_SHAREDFOLDER;
	}

	stream.write_Byte(dir_type);

	var stream_share = new DataStream(); /* Only to compute encoded size of the UTF-16 string */
	stream_share.write_UTF16LE(file_share);
	var share_len = stream_share.get_size();
	stream.write_UInt32LE(share_len);
	stream.write_UTF16LE(file_share);

	var stream_path = new DataStream(); /* Only to compute encoded size of the UTF-16 string */
	stream_path.write_UTF16LE(file_path);
	var path_len = stream_path.get_size();
	stream.write_UInt32LE(path_len);
	stream.write_UTF16LE(file_path);

	this.applications[token] = new uovd.provider.applications.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		var server = this.session_management.session.servers[server_id];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) {
			var ovdapp_message = stream.toBase64();
			this.connections[server_id].guac_tunnel.sendMessage("ovdapp", ovdapp_message);
			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
		}
	} else {
		this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
}

uovd.provider.applications.Html5.prototype.applicationStop_implementation = function(application_id, token) { 
}

uovd.provider.applications.Html5.prototype.handleOrders = function(server_id, opcode, parameters) {
	if(opcode == "ovdapp") {
		/* Format :
		parameters[0] = binary encoded
			uint8 : opcode
		*/

		var connection = this.connections[server_id];
		var guac_client   = connection.guac_client;
		var guac_canvas   = connection.guac_canvas
		var guac_tunnel   = connection.guac_tunnel
		var guac_mouse    = connection.guac_mouse
		var guac_keyboard = connection.guac_keyboard

		var stream = DataStream.fromBase64(parameters[0]);
		var opcode = stream.read_Byte();

		if(opcode == this.ORDER_INIT) {
			/* channel connected */
			this.rdp_provider.serverStatus(server_id, uovd.SERVER_STATUS_READY);
		} else if (opcode == this.ORDER_STARTED) {
			/* Format :
					uint32 = app id
					uint32 = instance
			*/

			var app_id   = stream.read_UInt32LE();
			var instance = stream.read_UInt32LE();

			var application = null;
			if(this.applications[instance]) {
				application = this.applications[instance];
			} else {
				/* Application created from session recovery */
				application = new uovd.provider.applications.ApplicationInstance(this, app_id, instance);
				application.create = 0;
			}

			application.status = "started";
			application.start  = (new Date()).getTime();

			this.applications[instance] = application;
			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":application, "from":"unknown", "to":"started"});
		} else if (opcode == this.ORDER_STOPPED) {
			/* Format :
					uint32 = instance
			*/
			var instance = stream.read_UInt32LE();
			var application = this.applications[instance];

			application.status = "stopped";
			application.end = (new Date()).getTime();

			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":application, "from":"started", "to":"stopped"});
		} else if (opcode == this.ORDER_CANT_START) {
			/* Format :
					uint32 = instance
			*/
			var instance = stream.read_UInt32LE();
			var application = this.applications[instance];

			application.status = "aborted";
			application.end = (new Date()).getTime();

			this.session_management.fireEvent("ovd.applicationsProvider.statusChanged", this, {"application":application, "from":"unknown", "to":"aborted"});
		} else if (opcode == this.ORDER_KNOWN_DRIVES) {
			/* ??? */
		} else {
		}
	}
}
