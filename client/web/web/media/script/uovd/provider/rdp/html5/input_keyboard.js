uovd.provider.rdp.html5.Keyboard = function(rdp_provider, connection) {
	this.rdp_provider = rdp_provider;
	this.guac_keyboard = null;
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.end = function() {}; /* destructor */

	this.attach(connection);

	this.rdp_provider.session_management.addCallback("ovd.session.destroying", this.handler);

	switch(rdp_provider.session_management.parameters.rdp_input_method) {
		case "unicode" :
			this.setUnicode();
			break;

		case "unicode_local_ime" :
			this.setUnicodeLocalIME();
			break;

		case "scancode" :
		default :
			this.setScancode();
			break;
	}
}

uovd.provider.rdp.html5.Keyboard.prototype.attach = function(connection) {
	this.connection = connection;
}

uovd.provider.rdp.html5.Keyboard.prototype.setUnicode = function() {
	var self = this; /* closure */

	/* Keyboard instance */
	this.guac_keyboard = new Guacamole.NativeKeyboard();

	/* Insert the hidden textfield with the canvas */
	jQuery(this.connection.guac_display).append(this.guac_keyboard.getNode());

	/* Keydown */
	this.guac_keyboard.onkeydown = function (keysym) {
		self.connection.guac_client.sendKeyEvent(1, keysym);
	};

	/* Keyup */
	this.guac_keyboard.onkeyup = function (keysym) {
		self.connection.guac_client.sendKeyEvent(0, keysym);
	};

	/* Unicode input */
	this.guac_keyboard.onunicode = function (keysym) {
		self.connection.guac_client.sendKeyEvent(2, keysym);
	};

	/* Enable on keyevent */
	function auto_activate(e) {
		if( ! self.guac_keyboard.active()) {
			self.guac_keyboard.enable();
		}
	}
	jQuery("body").on("keydown", auto_activate);

	/* Toggle on touchscreen "threefingers" gesture */
	function touchscreen_toggle() {
		self.guac_keyboard.toggle();
	}
	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.gesture.threefingers", touchscreen_toggle);

	/* Set destructor */
	this.end = function() {
		jQuery(self.guac_keyboard.getNode()).remove();
		jQuery("body").off("keydown", auto_activate);
		self.rdp_provider.session_management.removeCallback("ovd.rdpProvider.gesture.threefingers", touchscreen_toggle);
	};
}

uovd.provider.rdp.html5.Keyboard.prototype.setScancode = function() {
	var self = this; /* closure */

	/* Keyboard instance */
	this.guac_keyboard = new Guacamole.Keyboard(document);

	/* Keydown */
	this.guac_keyboard.onkeydown = function (keysym) {
		self.connection.guac_client.sendKeyEvent(1, keysym);
	};

	/* Keyup */
	this.guac_keyboard.onkeyup = function (keysym) {
		self.connection.guac_client.sendKeyEvent(0, keysym);
	};

	/* No destructor */
}

uovd.provider.rdp.html5.Keyboard.prototype.setUnicodeLocalIME = function() {
	/* Set unicode */
	this.setUnicode();

	/* Install instruction hook for imestate order*/
	this.connection.guac_tunnel.addInstructionHandler("imestate", jQuery.proxy(this.handleOrders, this));

	/* Set destructor */
	var self = this; /* closure */
	var super_destructor = this.end;

	this.end = function() {
		super_destructor();

		/* Remove instruction hook */
		self.connection.guac_tunnel.removeInstructionHandler("imestate");
	};
}

uovd.provider.rdp.html5.Keyboard.prototype.handleOrders = function(opcode, parameters) {
	if(opcode == "imestate") {
		var state = parseInt(parameters[1]);
		this.guac_keyboard.setIme(state);
	}
}

uovd.provider.rdp.html5.Keyboard.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.destroying") {
		this.end();
		this.rdp_provider.session_management.removeCallback("ovd.session.destroying", this.handler);
	}
}
