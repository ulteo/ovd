function Html5SeamlessHandler(rdp_provider) {
	this.rdp_provider = rdp_provider;
	this.connections = this.rdp_provider.connections;
	this.refreshTimer = null;
	this.windowIdList = new Array();

	/* Install instruction hook */
	var self = this; /* closure */
	for(var i=0 ; i<this.connections.length ; ++i) {
		(function(server_id) {
			self.connections[server_id].guac_tunnel.addInstructionHandler("seamrdp", self.handleOrders.bind(self, server_id));
		})(i);
	}

	/* Refresh windows with a timeout */
	var self = this; /* closure */
	this.refreshTimer = setInterval( function() {
		for (var i=0 ; i<self.windowIdList.length ; ++i) {
			self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowUpdate", self, {"id":self.windowIdList[i]});
		}
	}, 100);
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

		if(seamless[0] == "HELLO") {
		/* Format :
			 seamless[2] = flags (0x0001 = reconnect)
		*/
			if(seamless[2] == 0) {
				this.rdp_provider.session_management.fireEvent("ovd.log", this, {"message":"Begin seamless session"});
			} else {
				this.rdp_provider.session_management.fireEvent("ovd.log", this, {"message":"Oppening existing session"});
				guac_tunnel.sendMessage("seamrdp", "SYNC,1;\n");
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
			this.windowIdList.push(seamless[2]);

			/* Create the window content node */
			var content = document.createElement("CANVAS");
			var params = {};
			params["id"] = seamless[2];
			params["content"] = content;
			params["update"] = function(win, params) {
				var win_content = win.getContent();
				win_content.width = win.w;
				win_content.height = win.h;

				var ctx = win_content.getContext("2d");
				try {
					/* !!!! Bound check !!!! */
					ctx.drawImage(main_canvas,win.x,win.y,win.w,win.h,0,0,win.w,win.h);
				} catch(e) {}
			}
				
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowCreate", this, params);

			/* bind events */
			var self = this; /* closure */
			var mouse = new Guacamole.Mouse(content);
			mouse.onmousemove = mouse.onmousedown = mouse.onmouseup = function(mouseState) {
				var x = parseInt(mouseState.x)+parseInt(content.seamless_window.x);
				var y = parseInt(mouseState.y)+parseInt(content.seamless_window.y);
				var newState = new Guacamole.Mouse.State(x, y, mouseState.left, mouseState.middle, mouseState.right, mouseState.up, mouseState.down);
				guac_client.sendMouseState(newState);
			};

		} else if(seamless[0] == "DESTROY") {
			/* seamless[2] = Window id
				 seamless[3] = flags
			*/
			var params = {};
			params["id"] = seamless[2];
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowDestroy", this, params);
			var new_windowIdList = new Array();
			for(var i = 0 ; i<this.windowIdList.length ; ++i) {
				if(this.windowIdList[i] != seamless[2]) {
					new_windowIdList.push(this.windowIdList[i]);
				}
			}
			this.windowIdList = new_windowIdList;
		} else if(seamless[0] == "DESTROYGRP") {
			/* seamless[2] = groupID
			   seamless[3] = flags
			*/
		} else if(seamless[0] == "TITLE") {
			/* seamless[2] = Window id
			   seamless[3] = Title
			*/
			var params = {};
			params["id"] = seamless[2];
			params["title"] = seamless[3];
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);
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
			var params = {};
			params["id"] = seamless[2];
			params["position"] = new Array(seamless[3], seamless[4]);
			params["size"] = new Array(seamless[5], seamless[6]);
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);
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
			var params = {};
			params["id"] = seamless[2];
			params["visible"] = (parseInt(seamless[3]) == 0 || parseInt(seamless[3]) == 2 || parseInt(seamless[3]) == 3);
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);

			if(parseInt(seamless[3]) == 2 || parseInt(seamless[3]) == 3) {
				var params = {};
				params["id"] = seamless[2];
				params["position"] = new Array(0, 0);
				params["size"] = new Array(main_canvas.width, main_canvas.height);
				this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);
			}
		} else if(seamless[0] == "FOCUS") {
			/* seamless[2] = Window id
			   seamless[3] = Action (Unused by server)
			*/
			var params = {};
			params["id"] = seamless[2];
			params["focus"] = true;
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);
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
