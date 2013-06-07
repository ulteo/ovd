function Html5SeamlessHandler(rdp_provider) {
	this.rdp_provider = rdp_provider;
	this.connections = this.rdp_provider.connections;
	this.handler = this.handleEvents.bind(this);
	this.message_id = 0;

	/* Install instruction hook */
	var self = this; /* closure */
	for(var i=0 ; i<this.connections.length ; ++i) {
		(function(server_id) {
			self.connections[server_id].guac_tunnel.addInstructionHandler("seamrdp", self.handleOrders.bind(self, server_id));
		})(i);
	}

	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.seamless.out.*",   this.handler);
	this.rdp_provider.session_management.addCallback("ovd.session.server.statusChanged", this.handler);
}

Html5SeamlessHandler.prototype.handleOrders = function(server_id, opcode, parameters) {
	if(opcode == "seamrdp") {
		/* Format :
			 parameters[0] = string (CSV encoded)
			 See java/src/net/propero/rdp/rdp5/seamless/SeamlessChannel.java

			 Spliting it into "seamless" array
		 */

		var seamless = parameters[0].split(",");

		var connection = this.connections[server_id];
		var guac_client   = connection.guac_client;
		var guac_display  = connection.guac_display
		var guac_canvas   = connection.guac_canvas
		var guac_tunnel   = connection.guac_tunnel
		var guac_mouse    = connection.guac_mouse
		var guac_keyboard = connection.guac_keyboard

		var display = guac_client.getDisplay();
		var main_canvas = display.firstChild.firstChild.firstChild;

		/* Context params for windows */
		var params = {};
		params["rdp_provider"] = this.rdp_provider;
		params["server_id"] = server_id;
		params["connection"] = connection;
		params["main_canvas"] = main_canvas;

		if(seamless[0] == "HELLO") {
			/* seamless[2] = flags (0x0001 = reconnect)
			*/
			if(seamless[2] == 1) {
				/* Begin session recovery */
				guac_tunnel.sendMessage("seamrdp", "SYNC,"+ (this.message_id++) +";\n");
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

Html5SeamlessHandler.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.rdpProvider.seamless.out.windowDestroy") {
		var id = parseInt(params["id"]).toString(16);
		var server_id = params["server_id"];
		var connection = this.connections[server_id];
		var guac_tunnel = connection.guac_tunnel
		guac_tunnel.sendMessage("seamrdp", "DESTROY,"+ (this.message_id++) +","+id+",;\n");
	} else if(type == "ovd.rdpProvider.seamless.out.windowPropertyChanged") {
		var id = parseInt(params["id"]).toString(16);
		var server_id = params["server_id"];
		var property = params["property"];
		var value = params["value"];
		var connection = this.connections[server_id];
		var guac_tunnel = connection.guac_tunnel

		switch(property) {
			case "position" :
				var size = source.getSize();
				var w = size[0];
				var h = size[1];
				var x = value[0];
				var y = value[1];
				guac_tunnel.sendMessage("seamrdp", "POSITION,"+ (this.message_id++) +","+id+","+x+","+y+","+w+","+h+",;\n");
				break

			case "size" :
				var position = source.getPosition();
				var w = value[0];
				var h = value[1];
				var x = position[0];
				var y = position[1];
				guac_tunnel.sendMessage("seamrdp", "POSITION,"+ (this.message_id++) +","+id+","+x+","+y+","+w+","+h+",;\n");
				break

			case "state" :
				var state = 0;
				if(value == "Notmapped")  state = -1;
				if(value == "Normal")     state =  0;
				if(value == "Iconify")    state =  1;
				if(value == "Maximized")  state =  2;
				if(value == "Fullscreen") state =  3;
				guac_tunnel.sendMessage("seamrdp", "STATE,"+ (this.message_id++) +","+id+","+state+",;\n");
				break

			case "focus" :
				guac_tunnel.sendMessage("seamrdp", "FOCUS,"+ (this.message_id++) +","+id+","+value+",;\n");
				break
		}
	}
}
