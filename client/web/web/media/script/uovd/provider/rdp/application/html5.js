/* Html5 Application provider */

uovd.provider.rdp.application.Html5 = function(rdp_provider) {
	this.initialize(rdp_provider);
	this.connections = this.rdp_provider.connections;

	/* Install instruction hook */
	var self = this; /* closure */
	for(var i=0 ; i<this.connections.length ; ++i) {
		(function(server_id) {
			 self.connections[server_id].guac_tunnel.addInstructionHandler("ovdapp", self.handleOrders.bind(self, server_id));
		 })(i);
	}
}
uovd.provider.rdp.application.Html5.prototype = new uovd.provider.rdp.application.Base();

uovd.provider.rdp.application.Html5.prototype.applicationStart_implementation = function (application_id, token) { 
	var server_id = this.getServerByAppId(application_id);
	var opcode    = "01";                          /* uint8     */
	var appToken  = this.write(token, 4);          /* uint32 Le */
	var appId     = this.write(application_id, 4); /* uint32 Le */
	this.applications[token] = new uovd.provider.rdp.application.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		this.connections[server_id].guac_tunnel.sendMessage("ovdapp", opcode+""+appToken+""+appId+";");
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
	} else {
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
}

uovd.provider.rdp.application.Html5.prototype.applicationStartWithArgs_implementation = function(application_id, args, token) { 
	var server_id = this.getServerByAppId(application_id);
	var file_type = args["type"];
	var file_path = args["path"];
	var file_share = args["share"];

	var opcode    = "07";                           /* uint8     */
	var appToken  = this.write(token, 4);           /* uint32 Le */
	var appId     = this.write(application_id, 4);  /* uint32 Le */
	var dir_type  = "";                             /* uint8     */
	var share_len = "";                             /* uint32 Le */
	var share     = "";                             /* UTF-16 Le */
	var path_len  = "";                             /* uint32 Le */
	var path      = "";                             /* UTF-16 Le */

	switch(file_type) {
		case "sharedfolder" : dir_type = "01"; break;
		case "http"         : dir_type = "10"; break;
		default             : dir_type = "01";
	}

	share = this.writeString(file_share);
	share_len = this.write(share.length/2, 4);

	path = this.writeString(file_path);
	path_len = this.write(path.length/2, 4);

	this.applications[token] = new uovd.provider.rdp.application.ApplicationInstance(this, application_id, token);

	if(server_id != -1) {
		this.connections[server_id].guac_tunnel.sendMessage("ovdapp", opcode+""+appToken+""+appId+""+dir_type+""+share_len+""+share+""+path_len+""+path+";");
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"unknown"});
	} else {
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":this.applications[token], "from":"", "to":"aborted"});
	}
}

uovd.provider.rdp.application.Html5.prototype.applicationStop_implementation = function(application_id, token) { 
}

uovd.provider.rdp.application.Html5.prototype.handleOrders = function(server_id, opcode, parameters) {
	if(opcode == "ovdapp") {
		/* Format :
		parameters[0] = binary encoded
			uint8 : opcode
		*/

		var connection = this.connections[server_id];
		var guac_client   = connection.guac_client;
		var guac_display  = connection.guac_display
		var guac_canvas   = connection.guac_canvas
		var guac_tunnel   = connection.guac_tunnel
		var guac_mouse    = connection.guac_mouse
		var guac_keyboard = connection.guac_keyboard

		var opcode = parameters[0].slice(0,2);
		var bin =    parameters[0].slice(2);
		if(opcode == "00") {         /* ORDER_INIT */
			/* channel connected */
		} else if (opcode == "02") { /* ORDER_STARTED */
			/* Format :
					uint32 = app id
					uint32 = instance
			*/
			var app_id   = this.read(bin.slice(0,8),4); bin = bin.slice(8);
			var instance = this.read(bin.slice(0,8),4); bin = bin.slice(8);

			var application = null;
			if(this.applications[instance]) {
				application = this.applications[instance];
			} else {
				/* Application created from session recovery */
				application = new uovd.ApplicationInstance(this, app_id, instance);
				application.create = 0;
			}

			application.status = "started";
			application.start  = (new Date()).getTime();

			this.applications[instance] = application;
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":application, "from":"unknown", "to":"started"});
		} else if (opcode == "03") { /* ORDER_STOPPED */
			/* Format :
					uint32 = instance
			*/
			var instance = this.read(bin.slice(0,8),4); bin = bin.slice(8);
			var application = this.applications[instance];

			application.status = "stopped";
			application.end = (new Date()).getTime();

			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":application, "from":"started", "to":"stopped"});
		} else if (opcode == "06") { /* ORDER_CANT_START */
			/* Format :
					uint32 = instance
			*/
			var instance = this.read(bin.slice(0,8),4); bin = bin.slice(8);
			var application = this.applications[instance];

			application.status = "aborted";
			application.end = (new Date()).getTime();

			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.applicationProvider.statusChanged", this, {"application":application, "from":"unknown", "to":"aborted"});
		} else if (opcode == "20") { /* ORDER_KNOWN_DRIVES */
			console.log("ORDER_KNOWN_DRIVES : "+bin);
			/* ??? */
		} else {
			console.log("ORDER_??? ("+opcode+") : "+bin);
		}
	}
}

uovd.provider.rdp.application.Html5.prototype.write = function(num, bytes) {
	var buffer = new Array();
	var output = "";
	var input = "";

	/* convert to hex */
	input = ""+parseInt(num).toString(16);

	/* Prepend '0' if input.length is not even */
	if((input.length % 2) != 0) {
		input = "0" + input;
	}

	/* Push hex digits by pair */
	for(var i=0 ; i<input.length; i+=2) {
		buffer.push(input.charAt(i)+""+input.charAt(i+1));
	}

	/* Pad the aray with "00" up to 'bytes' arg */
	var remains = parseInt(bytes) - buffer.length;
	for(var i=0 ; i<remains ; ++i) {
		buffer.push("00");
	}

	/* output as a string */
	for(var i=0 ; i<buffer.length ; ++i) {
		output = output+buffer[i];
	}

	return output;
}

uovd.provider.rdp.application.Html5.prototype.read = function(str, bytes) {
	var buffer = new Array();
	var output = "0x";
	var num = 0;

	/* Push hex digits by pair */
	for(var i=0 ; i<(parseInt(bytes)*2); i+=2) {
		buffer.push(str.charAt(i)+""+str.charAt(i+1));
	}

	buffer.reverse();

	for(var i=0 ; i<bytes ; ++i) {
		output += buffer[i];
	}

	try{
		num = parseInt(output);
	} catch (e) {
		console.error("Error : conversion ("+str+" to "+output+")");
		return 0;
	}

	return num;
}

uovd.provider.rdp.application.Html5.prototype.writeString = function(str) {
	var buffer = "";
	for(var i=0 ; i<str.length ; ++i) {
		buffer+= this.write(str.charCodeAt(i), 2);
	}

	return buffer;
}
