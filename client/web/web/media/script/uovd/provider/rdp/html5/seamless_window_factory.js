uovd.provider.rdp.html5.SeamlessWindowFactory = function() {

	this.SeamlessWindow = function(params) {
		this.id = params["id"];
		this.group = params["group"];
		this.parent = params["parent"];
		this.attributes = params["attributes"];
		this.rdp_provider = params["rdp_provider"];
		this.server_id = params["server_id"];
		this.connection = params["connection"];
		this.main_canvas = jQuery(params["main_canvas"]);

		this.x = 0;
		this.y = 0;
		this.w = 0;
		this.h = 0;

		this.title = "";
		this.icon = "";
		this.visible = false;
		this.focused = false;

		var self = this; /* closure */

		this.node = jQuery(document.createElement("canvas"));
		this.node.css("position", "absolute");
		this.node.prop("id", "w_"+this.id);

		/* Focus on click */
		this.node.click(function() {
			if(! self.focused) {
				/* Simulate a focus change event to send the canvas on the top */
				params["rdp_provider"] = self.rdp_provider;
				params["server_id"] = self.server_id;
				params["connection"] = self.connection;
				params["main_canvas"] = self.main_canvas;
				params["id"] = self.id;
				params["property"] = "focus";
				params["value"] = true;
				self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", self, params);
			}
		});

		/* Move window */
		this.node.mousedown(function(e) {
			var begin_drag_n_drop = function() {
				var lastX = e.pageX;
				var lastY = e.pageY;

				var x = self.x;
				var y = self.y;
				var w = self.w;
				var h = self.h;

				var overlay = jQuery(document.createElement("canvas"));
				overlay.css("position", "absolute");
				overlay.prop("width", w);
				overlay.prop("height", h);
				overlay.css("left", x);
				overlay.css("top", y);
				var context = overlay[0].getContext("2d");
				context.lineWidth="4";
				context.strokeStyle="black";
				context.rect(0,0,w,h);
				context.stroke();

				jQuery("#w_"+self.id).after(overlay);

				var drag = function(e) {
					var dx = parseInt(e.pageX) - parseInt(lastX);
					var dy = parseInt(e.pageY) - parseInt(lastY);
					x = parseInt(x) + parseInt(dx);
					y = parseInt(y) + parseInt(dy);
					lastX = e.pageX;
					lastY = e.pageY;

					overlay.css("left", x);
					overlay.css("top", y);
				};

				var drop = function(e) {
					overlay.off("mousemove");
					overlay.off("onmouseup");
					overlay.off("mouseout");
					overlay.remove();

					if(x != self.x || y != self.y) {
						/* Send seamless move notification */
						var parameters = {};
						parameters["id"] = self.id;
						parameters["server_id"] = self.server_id;
						parameters["property"] = "position";
						parameters["value"] = new Array(x, y, w, h);
						self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.out.windowPropertyChanged", self, parameters);
					}
				};

				overlay.mousemove(drag);
				overlay.mouseup(drop);
				overlay.mouseout(drop);
			}

			/* check coords */
			var localY = e.pageY - self.y;
			if(localY < 5 || localY > 25 ) { return; }

			/* check long click */
			var longclick = true;
			var release_longclick = function() {
				self.node.off("onmouseup");
				longclick = false;
			};

			var check_longclick = function() {
				if(longclick == true) {
					self.node.off("onmouseup");
					begin_drag_n_drop();
				}
			};

			setTimeout( check_longclick, 300);
			self.node.mouseup(release_longclick);

		});

		this.mouse = new Guacamole.Mouse(this.node[0]);
		this.mouse.onmousemove = this.mouse.onmousedown = this.mouse.onmouseup = function(mouseState) {
			var x = parseInt(mouseState.x)+parseInt(self.x);
			var y = parseInt(mouseState.y)+parseInt(self.y);
			var newState = new Guacamole.Mouse.State(x, y, mouseState.left, mouseState.middle, mouseState.right, mouseState.up, mouseState.down);
			self.connection.guac_client.sendMouseState(newState);
		}

		/* /!\ The keyboard is not handled per window but per connection /!\ */
	}

	this.SeamlessWindow.prototype.getNode = function() {
		return this.node[0];
	}

	this.SeamlessWindow.prototype.getId = function() {
		return this.id;
	}

	this.SeamlessWindow.prototype.getServerId = function() {
		return this.server_id;
	}

	this.SeamlessWindow.prototype.getGroup = function() {
		return this.group;
	}

	this.SeamlessWindow.prototype.getParent = function() {
		return this.parent;
	}

	this.SeamlessWindow.prototype.getAttributes = function() {
		return (new Array()).concat(this.attributes);
	}

	this.SeamlessWindow.prototype.setPosition = function(x, y) {
		this.x = x;
		this.y = y;

		this.node.css("left", x);
		this.node.css("top", y);
	}

	this.SeamlessWindow.prototype.getPosition = function() {
		return new Array(this.x, this.y);
	}

	this.SeamlessWindow.prototype.setSize = function(w, h) {
		this.w = w;
		this.h = h;

		this.node.prop("width", w);  /* /!\ Don't use this.node.width(w) */
		this.node.prop("height", h); /* /!\ Don't use this.node.height(h) */
	}

	this.SeamlessWindow.prototype.getSize = function() {
		return new Array(this.w, this.h);
	}

	this.SeamlessWindow.prototype.setTitle = function(title) {
		this.title = ""+title;
	}

	this.SeamlessWindow.prototype.getTitle = function() {
		return ""+this.title;
	}

	this.SeamlessWindow.prototype.setIcon = function(icon) {
		this.icon = icon;
	}

	this.SeamlessWindow.prototype.getIcon = function() {
		return this.icon;
	}

	this.SeamlessWindow.prototype.show = function() {
		this.node.show();
		this.visible = true;
	}

	this.SeamlessWindow.prototype.hide = function() {
		this.node.hide();
		this.visible = false;
	}

	this.SeamlessWindow.prototype.isVisible = function() {
		return this.visible;
	}

	this.SeamlessWindow.prototype.focus = function() {
		this.focused = true;

		/* Bind keyboard events */
		var self = this; /* closure */
		this.connection.guac_keyboard.onkeydown = function (keysym) { self.connection.guac_client.sendKeyEvent(1, keysym); };
		this.connection.guac_keyboard.onkeyup =   function (keysym) { self.connection.guac_client.sendKeyEvent(0, keysym); };
	}

	this.SeamlessWindow.prototype.blur = function() {
		this.focused = false;

		/* UnBind keyboard events */
		this.connection.guac_keyboard.onkeydown = function (keysym) {};
		this.connection.guac_keyboard.onkeyup =   function (keysym) {};
	}

	this.SeamlessWindow.prototype.isFocused = function() {
		return this.focused;
	}

	this.SeamlessWindow.prototype.maximize = function() {
		var xmax = this.main_canvas.prop("width");
		var ymax = this.main_canvas.prop("height");

		this.setPosition(0, 0);
		this.setSize(xmax, ymax);
	};

	this.SeamlessWindow.prototype.restore = function() {
	};

	this.SeamlessWindow.prototype.update = function() {
		var window_canvas = this.node[0];
		var window_context = window_canvas.getContext("2d");
		var main_canvas = this.main_canvas[0];
		var main_context = main_canvas.getContext("2d");

		var x_max = this.main_canvas.width();
		var y_max = this.main_canvas.height();

		var x0 = Math.max(this.x, 0);
		var y0 = Math.max(this.y, 0);
		var x1 = Math.min(x0+this.w, x_max);
		var y1 = Math.min(y0+this.h, y_max);

		var w = x1-x0;
		var h = y1-y0;

		if( this.visible && x0 < x_max && y0 < y_max ) {
			try {
				window_context.drawImage(this.main_canvas[0],x0,y0,w,h,0,0,w,h);
			} catch(e) {};
		}
	}

	this.SeamlessWindow.prototype.destroy = function() {
		/* Remove node */
		this.hide();
		this.node.off("click");
		this.node.remove();

		/* Unregister handlers */
		this.mouse.onmousemove = this.mouse.onmousedown = this.mouse.onmouseup = function(mouseState) {};
		this.mouse = null;
	}
}

uovd.provider.rdp.html5.SeamlessWindowFactory.prototype.create = function(params) {
	return new (this.SeamlessWindow)(params);
}
