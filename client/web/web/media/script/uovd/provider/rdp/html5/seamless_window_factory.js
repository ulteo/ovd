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
		this.node.click(function() {
			/* Give focus on click : simulate a seamless "focus" event */
			params["rdp_provider"] = self.rdp_provider;
			params["server_id"] = self.server_id;
			params["connection"] = self.connection;
			params["main_canvas"] = self.main_canvas;
			params["id"] = self.id;
      params["property"] = "focus";
      params["value"] = true;
      self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.in.windowPropertyChanged", this, params);
		});

		this.mouse = new Guacamole.Mouse(this.node[0]);
		this.mouse.onmousemove = this.mouse.onmousedown = this.mouse.onmouseup = function(mouseState) {
			var x = parseInt(mouseState.x)+parseInt(self.x);
			var y = parseInt(mouseState.y)+parseInt(self.y);
			var newState = new Guacamole.Mouse.State(x, y, mouseState.left, mouseState.middle, mouseState.right, mouseState.up, mouseState.down);
			self.connection.guac_client.sendMouseState(newState);
		}

		this.keyboard = new Guacamole.Keyboard(document);
		this.keyboard.onkeydown = function (keysym) {
			if(self.focus) {
				self.connection.guac_client.sendKeyEvent(1, keysym);
			}
		}
		this.keyboard.onkeyup = function (keysym) {
			if(self.focus) {
				self.connection.guac_client.sendKeyEvent(0, keysym);
			}
		}
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
	}

	this.SeamlessWindow.prototype.blur = function() {
		this.focused = false;
	}

	this.SeamlessWindow.prototype.isFocused = function() {
		return this.focused;
	}

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
		this.node = jQuery(document.createElement("canvas"));

		/* Unregister handlers */
		this.mouse.onmousemove = this.mouse.onmousedown = this.mouse.onmouseup = function(mouseState) {};
		this.keyboard.onkeydown = this.keyboard.onkeyup = function (keysym) {};
		this.keyboard = this.mouse = null;
	}
}

uovd.provider.rdp.html5.SeamlessWindowFactory.prototype.create = function(params) {
	return new (this.SeamlessWindow)(params);
}
