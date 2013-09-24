uovd.provider.rdp.html5.Mouse = function(rdp_provider, connection) {
	var self = this; /* closure */
	this.rdp_provider = rdp_provider;
	this.connection = connection;

	var client = this.connection.guac_client;
	var canvas = this.connection.guac_canvas;
	var mouse = new Guacamole.TabletMouse(canvas);

	/* Set mouse in connection */
	this.connection.guac_mouse = mouse;

	/* Zoom and gesture data */
	this.zoom = 1.0;
	this.initial_zoom = 1.0;
	this.zoom_center_x = 0;
	this.zoom_center_y = 0;
	this.pan_x = 0;
	this.pan_y = 0;
	this.pan_function = null;

	function updateZoomAndPan() {
		var canvas_width = jQuery(canvas).width();
		var canvas_height = jQuery(canvas).height();

		if (self.zoom > 3.0) self.zoom = 3.0;
		if (self.zoom < 1.0) self.zoom = 1.0;
		if (self.pan_x>0) self.pan_x = 0;
		if (self.pan_y>0) self.pan_y = 0;

		if (parseInt(self.pan_x)+parseInt(canvas_width) < parseInt(canvas_width)/self.zoom)
			self.pan_x = (parseInt(canvas_width)/self.zoom) - parseInt(canvas_width);

		if (parseInt(self.pan_y)+parseInt(canvas_height) < parseInt(canvas_height)/self.zoom)
			self.pan_y = (parseInt(canvas_height)/self.zoom) - parseInt(canvas_height);

		/* Set pan */
		jQuery(canvas).css("left",  self.pan_x);
		jQuery(canvas).css("top", self.pan_y);

		/* Set scale */
		jQuery(canvas).css("zoom", self.zoom);

		/* Allow scroll with two fingers swipe when Zoom == 1 */
		if (self.zoom == 1.0) {
			mouse.onmousepan = null;
		} else {
			mouse.onmousepan = self.pan_function;
		}
	}

	/* Mouse motion and clicks */
	mouse.onmousedown = mouse.onmouseup = mouse.onmousemove = function(mouseState) {
		var offset = jQuery(canvas).offset();
		var newState = new Guacamole.TabletMouse.State();
		newState.x = parseInt(mouseState.x/self.zoom)+(-1*parseInt(offset.left));
		newState.y = parseInt(mouseState.y/self.zoom)+(-1*parseInt(offset.top));
		newState.left = mouseState.left;
		newState.middle = mouseState.middle;
		newState.right = mouseState.right;
		newState.up = mouseState.up;
		newState.down = mouseState.down;
		client.sendMouseState(newState);
	};

	/* Pinch gesture */
	mouse.onmousepinch = function(amount, center_x, center_y, first) {
		if (first) {
			self.initial_zoom = self.zoom;

			/* Compute the session coords from the canvas coords */
			self.zoom_center_x = parseInt(center_x/self.zoom) + (-1*parseInt(self.pan_x));
			self.zoom_center_y = parseInt(center_y/self.zoom) + (-1*parseInt(self.pan_y));
		}
		self.zoom = self.zoom + amount/1000.0;

		/* Compute the "pan" needed to keep the session coords under the screen coords */
		self.pan_x = (parseInt(center_x)/self.zoom) - parseInt(self.zoom_center_x);
		self.pan_y = (parseInt(center_y)/self.zoom) - parseInt(self.zoom_center_y);

		updateZoomAndPan();

		/* Send event */
		var params = {};
		params["center_x"] = center_x;
		params["center_y"] = center_y;
		params["amount"] = amount;
		params["first"] = first;
		self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.gesture.pinching", canvas, params);
	};

	/* Pan gesture */
	mouse.onmousepan = self.pan_function = function(x, y) {
		self.pan_x += parseInt(x)/self.zoom;
		self.pan_y += parseInt(y)/self.zoom;
		updateZoomAndPan();

		/* Send event */
		var params = {};
		params["x"] = x;
		params["y"] = y;
		self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.gesture.panning", canvas, params);
	};

	mouse.ontwofingers = function() {
		/* Send event */
		self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.gesture.twofingers", canvas, {});
	};

	mouse.onthreefingers = function() {
		/* Send event */
		self.rdp_provider.session_management.fireEvent("ovd.rdpProvider.gesture.threefingers", canvas, {});
	};
}
