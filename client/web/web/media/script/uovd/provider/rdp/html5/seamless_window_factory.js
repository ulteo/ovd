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

		/* Mouse */
		this.mouse = new Guacamole.TabletMouse(this.node[0]);

		function sendMouseState(mouseState) {
			self.connection.guac_client.sendMouseState(mouseState);
		}

		this.mouse.onmousemove = function(mouseState) {
			sendMouseState(mouseState);
		};

		this.mouse.onmousedown = this.mouse.onmouseup = function(mouseState) {
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

			sendMouseState(mouseState);
		}

		/* Pinch gesture */
		this.mouse.onmousepinch = function(amount, center_x, center_y, first) {
			/* Send event */
			var params = {};
			params["center_x"] = center_x;
			params["center_y"] = center_y;
			params["amount"] = amount;
			params["first"] = first;
			self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.gesture.pinching", self.node[0], params);
		};

		/* Pan gesture */
		this.mouse.onmousepan = function(x, y) {
			/* Send event */
			var params = {};
			params["x"] = x;
			params["y"] = y;
			self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.gesture.panning", self.node[0], params);
		};

		this.mouse.ontwofingers = function() {
			/* Send event */
			self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.gesture.twofingers", self.node[0], {});
		};

		this.mouse.onthreefingers = function() {
			/* Send event */
			self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.gesture.threefingers", self.node[0], {});
		};

		/* Client-side events (move, resize) */
		this.node.on("mousedown.window_client_events", function(e) {

			function showOverlay(x, y, w, h) {
				var overlay = jQuery(document.createElement("div"));
				overlay.addClass("uovd_seamless_overlay_window");
				overlay.css("left", x-3);
				overlay.css("top", y-3);
				overlay.width(w);
				overlay.height(h);

				jQuery("#w_"+self.id).parent().append(overlay);
				return overlay;
			}

			function move() {
				var lastX = e.pageX;
				var lastY = e.pageY;

				var x = self.x;
				var y = self.y;
				var w = self.w;
				var h = self.h;

				var overlay = null;

				var drag = function(e) {
					var dx = parseInt(e.pageX) - parseInt(lastX);
					var dy = parseInt(e.pageY) - parseInt(lastY);
					x = parseInt(x) + parseInt(dx);
					y = parseInt(y) + parseInt(dy);
					lastX = e.pageX;
					lastY = e.pageY;

					/* Show the wireframe window */
					if(! overlay) {
						overlay = showOverlay(x, y, w, h);

						/* Set cursor */
						jQuery("body").addClass("uovd_cursor_move");
					}

					overlay.css("left", x);
					overlay.css("top", y);
				};

				var drop = function(e) {
					jQuery(document).off("mousemove.wireframe_overlay");
					jQuery(document).off("mouseup.wireframe_overlay");
					self.node.off("mouseup.long_click_check");

					/* hide the wireframe window */
					if(overlay) {
						overlay.remove();
					}

					/* Reset cursor */
					jQuery("body").removeClass("uovd_cursor_move");

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

				jQuery(document).on("mousemove.wireframe_overlay", drag);
				jQuery(document).on("mouseup.wireframe_overlay", drop);
				self.node.on("mouseup.long_click_check", drop);
			}

			function resize(action) {
				var lastX = e.pageX;
				var lastY = e.pageY;

				var x = self.x;
				var y = self.y;
				var w = self.w;
				var h = self.h;

				var overlay = null;

				var drag = function(e) {
					var dx = parseInt(e.pageX) - parseInt(lastX);
					var dy = parseInt(e.pageY) - parseInt(lastY);
					lastX = e.pageX;
					lastY = e.pageY;

					/* Show the wireframe window */
					if(! overlay) {
						overlay = showOverlay(x, y, w, h);

						/* Set cursor */
						jQuery("body").addClass("uovd_cursor_resize_"+action);
					}

					var offset = overlay.offset();
					var overlay_x = offset.left;
					var overlay_y = offset.top;
					var overlay_w = overlay.width();
					var overlay_h = overlay.height();

					if(overlay_x >= x+w) { overlay_x = x+w; }
					if(overlay_y >= y+h) { overlay_y = y+h;	}

					switch(action) {
						case "top":
							overlay.css("top", overlay_y + dy);
							overlay.height(overlay_h - dy);
							break;

						case "bottom":
							overlay.height(overlay_h + dy);
							break;

						case "left":
							overlay.css("left", overlay_x + dx);
							overlay.width(overlay_w - dx);
							break;

						case "right":
							overlay.width(overlay_w + dx);
							break;

						case "topleft":
							overlay.css("top", overlay_y + dy);
							overlay.height(overlay_h - dy);
							overlay.css("left", overlay_x + dx);
							overlay.width(overlay_w - dx);
							break;

						case "topright":
							overlay.css("top", overlay_y + dy);
							overlay.height(overlay_h - dy);
							overlay.width(overlay_w + dx);
							break;

						case "bottomleft":
							overlay.height(overlay_h + dy);
							overlay.css("left", overlay_x + dx);
							overlay.width(overlay_w - dx);
							break;

						case "bottomright":
							overlay.height(overlay_h + dy);
							overlay.width(overlay_w + dx);
							break;
					}
				};

				var drop = function(e) {
					var offset = overlay.offset();
					var overlay_x = offset.left;
					var overlay_y = offset.top;
					var overlay_w = overlay.width();
					var overlay_h = overlay.height();

					jQuery(document).off("mousemove.wireframe_overlay");
					jQuery(document).off("mouseup.wireframe_overlay");
					self.node.off("mouseup.long_click_check");

					/* hide the wireframe window */
					if(overlay) {
						overlay.remove();
					}

					/* Reset cursor */
					jQuery("body").removeClass("uovd_cursor_resize_"+action);

					if(overlay_x != self.x || overlay_y != self.y || overlay_w != self.w || overlay_h != self.h) {
						/* Send seamless size notification */
						var parameters = {};
						parameters["id"] = self.id;
						parameters["server_id"] = self.server_id;
						parameters["property"] = "position";
						parameters["value"] = new Array(overlay_x, overlay_y, overlay_w, overlay_h);
						self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.seamless.out.windowPropertyChanged", self, parameters);
					}
				};

				jQuery(document).on("mousemove.wireframe_overlay", drag);
				jQuery(document).on("mouseup.wireframe_overlay", drop);
				self.node.on("mouseup.long_click_check", drop);
			}

			/* Action : move or resize */
			var action = null;

			/* check button (left click only) */
			if(e.which != 1) {
				return;
			}

			/* check coords */
			var localX = e.pageX - self.x;
			var localY = e.pageY - self.y;

			/* TitleBar longclick */
			if(localY > 5 && localY < 25 ) {
				action = move;
			}

			/* Resize - top */
			if(localY >= 0 && localY <= 5 ) {
				action = jQuery.proxy(resize, self, "top");
			}

			/* Resize - bottom */
			if(localY >= (parseInt(self.h) - 5) && localY <= parseInt(self.h)) {
				action = jQuery.proxy(resize, self, "bottom");
			}

			/* Resize - left */
			if(localX >= 0 && localX <= 5) {
				action = jQuery.proxy(resize, self, "left");
			}

			/* Resize - right */
			if(localX >= (parseInt(self.w) - 5) && localX <= parseInt(self.w)) {
				action = jQuery.proxy(resize, self, "right");
			}

			/* Resize - topleft */
			if(localY >= 0 && localY <= 5 && localX >= 0 && localX <= 5) {
				action = jQuery.proxy(resize, self, "topleft");
			}

			/* Resize - topright */
			if(localY >= 0 && localY <= 5 && localX >= (parseInt(self.w) - 5) && localX <= parseInt(self.w)) {
				action = jQuery.proxy(resize, self, "topright");
			}

			/* Resize - bottomleft */
			if(localY >= (parseInt(self.h) - 5) && localY <= parseInt(self.h) && localX >= 0 && localX <= 5) {
				action = jQuery.proxy(resize, self, "bottomleft");
			}

			/* Resize - bottomright */
			if(localY >= (parseInt(self.h) - 5) && localY <= parseInt(self.h) && localX >= (parseInt(self.w) - 5) && localX <= parseInt(self.w)) {
				action = jQuery.proxy(resize, self, "bottomright");
			}

			/* No pattern : return */
			if(action == null) {
				return
			}

			/* check long click */
			var longclick = true;
			var release_longclick = function() {
				self.node.off("mouseup.long_click_check");
				longclick = false;
			};

			var check_longclick = function() {
				if(longclick == true) {
					self.node.off("mouseup.long_click_check");
					action();
				}
			};

			setTimeout(check_longclick, 100);
			self.node.on("mouseup.long_click_check", release_longclick);

		});


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
		this.connection.keyboard.attach(this.connection);
	}

	this.SeamlessWindow.prototype.blur = function() {
		this.focused = false;
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
		var dx = -1*Math.min(this.x, 0);
		var dy = -1*Math.min(this.y, 0);

		if( this.visible && x0 < x_max && y0 < y_max ) {
			try {
				window_context.drawImage(this.main_canvas[0],x0,y0,w,h,dx,dy,w,h);
			} catch(e) {};
		}
	}

	this.SeamlessWindow.prototype.destroy = function() {
		/* Remove node */
		this.hide();
		this.node.off("mousedown.window_client_events");
		this.node.remove();

		/* Unregister handlers */
		this.mouse.onmousemove = this.mouse.onmousedown = this.mouse.onmouseup = function(mouseState) {};
		this.mouse = null;
	}
}

uovd.provider.rdp.html5.SeamlessWindowFactory.prototype.create = function(params) {
	return new (this.SeamlessWindow)(params);
}
