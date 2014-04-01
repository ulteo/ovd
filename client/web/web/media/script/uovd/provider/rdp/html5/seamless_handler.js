uovd.provider.rdp.html5.SeamlessHandler = function(rdp_provider) {
	var self = this; /* closure */
	this.rdp_provider = rdp_provider;
	this.connections = this.rdp_provider.connections;
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.message_id = {};
	this.confirmation_popup = {};

	/* Install instruction hook */
	for(var i=0 ; i<this.connections.length ; ++i) {
		(function(server_id) {
			self.connections[server_id].guac_tunnel.addInstructionHandler("seamrdp", jQuery.proxy(self.handleOrders, self, server_id));
			self.connections[server_id].guac_client.oncursor = jQuery.proxy(self.handleOrders, self, server_id, "cursor");
		})(i);
	}

	/* Check for confirmation popup */
	for(var i=0 ; i<this.connections.length ; ++i) {
		(function(server_id) {
			setTimeout( function() {
				var rdp_provider = self.rdp_provider;
				var session_management = rdp_provider.session_management;
				var connection = self.connections[server_id];

				if(session_management.session.servers[server_id].status != uovd.SERVER_STATUS_READY) {
					/* Context params for windows */
					var params = {};
					params["rdp_provider"] = rdp_provider;
					params["server_id"] = server_id;
					params["connection"] = connection;
					params["main_canvas"] = connection.guac_canvas;

					/* New window */
					self.confirmation_popup[server_id] = "confirm_server_"+server_id;
					params["id"] = self.confirmation_popup[server_id];
					params["group"] = "";
					params["parent"] = "";
					params["attributes"] = new Array("Topmost", "Fixedsize");
					session_management.fireEvent("ovd.rdpProvider.seamless.in.windowCreate", self, params);
					delete params.group;
					delete params.params;
					delete params.attributes;

					/* Position */
					params["property"] = "position";
					params["value"] = [0, 0];
					session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", self, params);

					/* Size */
					params["property"] = "size";
					params["value"] = [session_management.parameters.width, session_management.parameters.height];
					session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", self, params);

					/* State */
					params["property"] = "state";
					params["value"] = "Maximized"
					session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", self, params);

					/* Focus */
					params["property"] = "focus";
					params["value"] = true;
					session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", self, params);
				}
			}, 5 * 1000);
		})(i);
	}

	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.seamless.out.*" ,  this.handler);
	this.rdp_provider.session_management.addCallback("ovd.session.server.statusChanged", this.handler);
	this.rdp_provider.session_management.addCallback("ovd.session.destroying",           this.handler);
}

uovd.provider.rdp.html5.SeamlessHandler.prototype.handleOrders = function(server_id, opcode, parameters) {
	if(opcode == "cursor") {
		var params = {};
		params["server_id"] = server_id;
		params["url"] = parameters["url"];
		params["x"] = parameters["x"];
		params["y"] = parameters["y"];
		this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.cursor", this, params);
	}

	if(opcode == "seamrdp") {
		/* Format :
			 parameters[0] = string (CSV encoded)
			 See java/src/net/propero/rdp/rdp5/seamless/SeamlessChannel.java

			 Spliting it into "seamless" array
		 */

		var seamless =  base64_decode(parameters[0]).split(",");

		var connection = this.connections[server_id];
		var guac_client   = connection.guac_client;
		var guac_canvas   = connection.guac_canvas
		var guac_tunnel   = connection.guac_tunnel

		/* Context params for windows */
		var params = {};
		params["rdp_provider"] = this.rdp_provider;
		params["server_id"] = server_id;
		params["connection"] = connection;
		params["main_canvas"] = guac_canvas;

		if(seamless[0] == "HELLO") {
			/* seamless[2] = flags (0x0001 = reconnect)
			*/

			/* Initialize message ID */
			this.message_id[server_id] = 0;

			if(seamless[2] == 1) {
				/* Begin session recovery */
				var seamless_message = "SYNC,"+(this.message_id[server_id]++)+",\n";
				guac_tunnel.sendMessage("seamrdp", base64_encode(seamless_message));
			}
		} else if(seamless[0] == "CREATE") {
			/* seamless[2] = Window id
				 seamless[3] = group
				 seamless[4] = parent id
				 seamless[5] = flags
					 0x01 = Modal
					 0x02 = TopMost
					 0x04 = Popup
					 0x08 = FixedSize
					 0x10 = ToolTip
			*/
			params["id"] = parseInt(seamless[2]);
			params["group"] = parseInt(seamless[3]);
			params["parent"] = parseInt(seamless[4]);
			params["attributes"] = new Array();

			var flags = parseInt(seamless[5]);
			if(flags & 0x01) { params["attributes"].push("Modal"); }
			if(flags & 0x02) { params["attributes"].push("Topmost"); }
			if(flags & 0x04) { params["attributes"].push("Popup"); }
			if(flags & 0x08) { params["attributes"].push("Fixedsize"); }
			if(flags & 0x10) { params["attributes"].push("Tooltip"); }

			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowCreate", this, params);
		} else if(seamless[0] == "DESTROY") {
			/* seamless[2] = Window id
				 seamless[3] = flags
			*/
			params["id"] = parseInt(seamless[2]);
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowDestroy", this, params);
		} else if(seamless[0] == "DESTROYGRP") {
			/* seamless[2] = groupID
				 seamless[3] = flags
			*/
			params["id"] = parseInt(seamless[2]);
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.groupDestroy", this, params);
		} else if(seamless[0] == "TITLE") {
			/* seamless[2] = Window id
				 seamless[3] = Title
			*/
			params["id"] = parseInt(seamless[2]);
			params["property"] = "title";
			params["value"] = seamless[3];
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", this, params);
		} else if(seamless[0] == "SETICON") {
			/* seamless[2] = Window id
				 seamless[3] = Chunk seq number
				 seamless[4] = Format
				 seamless[5] = Width
				 seamless[6] = Height
				 seamless[7] = Data
			*/
		} else if(seamless[0] == "POSITION") {
			/* seamless[2] = Window id
				 seamless[3] = Position x
				 seamless[4] = Position y
				 seamless[5] = Width
				 seamless[6] = Height
				 seamless[7] = Flags
			*/
			params["id"] = parseInt(seamless[2]);
			params["property"] = "position";
			params["value"] = [parseInt(seamless[3]), parseInt(seamless[4])];
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", this, params);

			params["id"] = parseInt(seamless[2]);
			params["property"] = "size";
			params["value"] = [parseInt(seamless[5]), parseInt(seamless[6])];
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", this, params);
		} else if(seamless[0] == "STATE") {
			/* seamless[2] = Window id
				 seamless[3] = state
					 -1 = Not yet mapped
						0 = Normal
						1 = Iconify
						2 = Maximized (both state)
						3 = Full screen
				 seamless[4] = Flags
			*/
			params["id"] = parseInt(seamless[2]);
			params["property"] = "state";

			var state = parseInt(seamless[3]);
			switch(state) {
				case -1 : params["value"] = "Notmapped";  break;
				case  0 : params["value"] = "Normal";     break;
				case  1 : params["value"] = "Iconify";    break;
				case  2 : params["value"] = "Maximized";  break;
				case  3 : params["value"] = "Fullscreen"; break;
				default : params["value"] = "Normal";
			}

			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", this, params);
		} else if(seamless[0] == "FOCUS") {
			/* seamless[2] = Window id
				 seamless[3] = Action (Unused by server)
			*/
			params["id"] = parseInt(seamless[2]);
			params["property"] = "focus";
			params["value"] = true;
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", this, params);
		} else if(seamless[0] == "ACK") {
			/* seamless[2] = id of ACKed message
			*/
		} else if(seamless[0] == "SYNCBEGIN") {
			/* seamless[2] = flags
			*/
		} else if(seamless[0] == "SYNCEND") {
			/* seamless[2] = flags
			*/
		} else if(seamless[0] == "DEBUG") {
			/* seamless[2] = message
			*/
		}
	}
}

uovd.provider.rdp.html5.SeamlessHandler.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.seamless.out.windowDestroy") {
		var id = parseInt(params["id"]).toString(16);
		var server_id = params["server_id"];
		var connection = this.connections[server_id];
		var guac_tunnel = connection.guac_tunnel
		var seamless_message = "DESTROY,"+(this.message_id[server_id]++)+","+id+"\n";
		guac_tunnel.sendMessage("seamrdp", base64_encode(seamless_message));
	} else if(type == "ovd.rdpProvider.seamless.out.windowPropertyChanged") {
		var id = "0x"+parseInt(params["id"]).toString(16);
		var server_id = params["server_id"];
		var property = params["property"];
		var value = params["value"];
		var connection = this.connections[server_id];
		var guac_tunnel = connection.guac_tunnel

		switch(property) {
			case "position" :
				var x = value[0];
				var y = value[1];
				var w = value[2];
				var h = value[3];
				var seamless_message = "POSITION,"+(this.message_id[server_id]++)+","+id+","+x+","+y+","+w+","+h+",0x0\n";
				guac_tunnel.sendMessage("seamrdp", base64_encode(seamless_message));
				break

			case "state" :
				var state = "0x0";
				if(value == "Notmapped")  state = "-1";
				if(value == "Normal")     state = "0x0";
				if(value == "Iconify")    state = "0x1";
				if(value == "Maximized")  state = "0x2";
				if(value == "Fullscreen") state = "0x3";
				var seamless_message = "STATE,"+(this.message_id[server_id]++)+","+id+","+state+",0x0\n";
				guac_tunnel.sendMessage("seamrdp", base64_encode(seamless_message));
				break

			case "focus" :
				value = value ? "0x0" : "0x1";
				var seamless_message = "FOCUS,"+(this.message_id[server_id]++)+","+id+","+value+"\n";
				guac_tunnel.sendMessage("seamrdp", base64_encode(seamless_message));
				break
		}
	} else if(type == "ovd.session.server.statusChanged") {
		var self = this; /* closure */
		var rdp_provider = this.rdp_provider;
		var session_management = rdp_provider.session_management;
		var server_id = session_management.session.servers.indexOf(source);
		var connection = this.connections[server_id];
		var from = params["from"];
		var to = params["to"];

		if(to == uovd.SERVER_STATUS_READY) {
			if(this.confirmation_popup[server_id]) {
				/* Context params for windows */
				var params = {};
				params["rdp_provider"] = rdp_provider;
				params["server_id"] = server_id;
				params["connection"] = connection;
				params["main_canvas"] = connection.guac_canvas;

				/* Destroy */
				params["id"] = this.confirmation_popup[server_id];
				session_management.fireEvent("ovd.rdpProvider.seamless.in.windowDestroy", this, params);

				delete this.confirmation_popup[server_id];
			}
		}
	} else if(type == "ovd.session.destroying") {
		/* Remove instruction hook */
		var self = this; /* closure */
		for(var i=0 ; i<this.connections.length ; ++i) {
			(function(server_id) {
				self.connections[server_id].guac_tunnel.removeInstructionHandler("seamrdp");
				self.connections[server_id].guac_client.oncursor = null;
			})(i);
		}

		this.rdp_provider.session_management.removeCallback("ovd.rdpProvider.seamless.out.*",   this.handler);
		this.rdp_provider.session_management.removeCallback("ovd.session.server.statusChanged", this.handler);
		this.rdp_provider.session_management.removeCallback("ovd.session.destroying",           this.handler);
	}
}
