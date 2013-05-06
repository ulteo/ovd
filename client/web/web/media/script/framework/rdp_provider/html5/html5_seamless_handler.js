function Html5SeamlessHandler(rdp_provider) {
	this.rdp_provider = rdp_provider;
	this.tunnel = this.rdp_provider.guac_tunnel;
	this.defaultHandler = this.tunnel.oninstruction;
	this.refreshTimer = null;

	this.windowIdList = new Array();

	/* Install instruction hook */
	var self = this; /* closure */
	this.tunnel.oninstruction = function(opcode, parameters) {
			self.handleOrders(opcode, parameters);
			self.defaultHandler(opcode, parameters);
	};

	/* Refresh windows with a timeout */
	var self = this; /* closure */
	this.refreshTimer = setInterval( function() {
		for (var i=0 ; i<self.windowIdList.length ; ++i) {
			self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowUpdate", self, {"id":self.windowIdList[i]});
		}
	}, 100);
}

Html5SeamlessHandler.prototype.handleOrders = function(opcode, parameters) {
	var display = this.rdp_provider.guac_client.getDisplay();
	var client_node = this.rdp_provider.client_node;
	var main_canvas = display.firstChild.firstChild.firstChild;

	if(opcode == "seamrdp") {
		/* Format :
			 parameters[0] = Instruction
			 parameters[1] = Sequence number
			 parameters[..]= Instruction dependant

			 See java/src/net/propero/rdp/rdp5/seamless/SeamlessChannel.java
		 */

		if(parameters[0] == "HELLO") {
		/* Format :
			 parameters[2] = flags (0x0001 = reconnect)
		*/
			if(parameters[2] == 0) {
				this.rdp_provider.session_management.fireEvent("ovd.log", this, {"message":"Begin seamless session"});
			} else {
				this.rdp_provider.session_management.fireEvent("ovd.log", this, {"message":"Oppening existing session"});
				this.tunnel.sendMessage("seamrdp", "SYNC,1;\n");
			}
		} else if(parameters[0] == "CREATE") {
			/* parameters[2] = Window id
			   parameters[3] = group
				 parameters[4] = parent id
				 parameters[5] = flags
					 0x01 = Modal
					 0x02 = TopMost
					 0x04 = Popup
					 0x08 = FixedSize
					 0x10 = ToolTip
			*/
			this.windowIdList.push(parameters[2]);

			/* Create the window content node */
			var content = document.createElement("CANVAS");
			var params = new Array();
			params["id"] = parameters[2];
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
				self.rdp_provider.guac_client.sendMouseState(newState);
			};

		} else if(parameters[0] == "DESTROY") {
			/* parameters[2] = Window id
				 parameters[3] = flags
			*/
			var params = new Array();
			params["id"] = parameters[2];
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowDestroy", this, params);
			var new_windowIdList = new Array();
			for(var i = 0 ; i<this.windowIdList.length ; ++i) {
				if(this.windowIdList[i] != parameters[2]) {
					new_windowIdList.push(this.windowIdList[i]);
				}
			}
			this.windowIdList = new_windowIdList;
		} else if(parameters[0] == "DESTROYGRP") {
			/* parameters[2] = groupID
			   parameters[3] = flags
			*/
		} else if(parameters[0] == "TITLE") {
			/* parameters[2] = Window id
			   parameters[3] = Title
			*/
			var params = new Array();
			params["id"] = parameters[2];
			params["title"] = parameters[3];
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);
		} else if(parameters[0] == "SETICON") {
			/* parameters[2] = Window id
			   parameters[3] = Chunk seq number
				 parameters[4] = Format
				 parameters[5] = Width
				 parameters[6] = Height
				 parameters[7] = Data
			*/
		} else if(parameters[0] == "POSITION") {
			/* parameters[2] = Window id
			   parameters[3] = Position x
				 parameters[4] = Position y
				 parameters[5] = Width
				 parameters[6] = Height
				 parameters[7] = Flags
			*/
			var params = new Array();
			params["id"] = parameters[2];
			params["position"] = new Array(parameters[3], parameters[4]);
			params["size"] = new Array(parameters[5], parameters[6]);
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);
		} else if(parameters[0] == "STATE") {
			/* parameters[2] = Window id
			   parameters[3] = state
					 -1 = Not yet mapped
					  0 = Normal
					  1 = Iconify
					  2 = Maximized (both state)
					  3 = Full screen
				 parameters[4] = Flags
			*/
			var params = new Array();
			params["id"] = parameters[2];
			params["visible"] = (parseInt(parameters[3]) == 0 || parseInt(parameters[3]) == 2 || parseInt(parameters[3]) == 3);
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);

			if(parseInt(parameters[3]) == 2 || parseInt(parameters[3]) == 3) {
				var params = new Array();
				params["id"] = parameters[2];
				params["position"] = new Array(0, 0);
				params["size"] = new Array(main_canvas.width, main_canvas.height);
				this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);
			}
		} else if(parameters[0] == "FOCUS") {
			/* parameters[2] = Window id
			   parameters[3] = Action (Unused by server)
			*/
			var params = new Array();
			params["id"] = parameters[2];
			params["focus"] = true;
			this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.windowProperties", this, params);
		} else if(parameters[0] == "ACK") {
			/* parameters[2] = id of ACKed message
			*/
		} else if(parameters[0] == "SYNCBEGIN") {
			/* parameters[2] = flags
			*/
		} else if(parameters[0] == "SYNCEND") {
			/* parameters[2] = flags
			*/
		} else if(parameters[0] == "DEBUG") {
			/* parameters[2] = message
			*/
		}
	} 
}
