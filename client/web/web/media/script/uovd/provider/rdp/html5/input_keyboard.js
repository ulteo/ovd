uovd.provider.rdp.html5.Keyboard = function(rdp_provider, connection) {
	this.rdp_provider = rdp_provider;
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
	var keyboard = new Guacamole.NativeKeyboard();
	this.connection.guac_keyboard = keyboard;

	/* Insert the hidden textfield with the canvas */
	jQuery(this.connection.guac_display).append(keyboard.getNode());

	/* Keydown */
	keyboard.onkeydown = function (keysym) {
		self.connection.guac_client.sendKeyEvent(1, keysym);
	};

	/* Keyup */
	keyboard.onkeyup = function (keysym) {
		self.connection.guac_client.sendKeyEvent(0, keysym);
	};

	/* Unicode input */
	keyboard.onunicode = function (keysym) {
		self.connection.guac_client.sendKeyEvent(2, keysym);
	};

	/* Enable on keyevent */
	function auto_activate(e) {
		if( ! keyboard.active()) {
			keyboard.enable();
		}
	}
	jQuery("body").on("keydown", auto_activate);

	/* Toggle on touchscreen "threefingers" gesture */
	function touchscreen_toggle() {
		keyboard.toggle();
	}
	this.rdp_provider.session_management.addCallback("ovd.rdpProvider.gesture.threefingers", touchscreen_toggle);

	/* Set destructor */
	this.end = function() {
		jQuery(keyboard.getNode()).remove();
		jQuery("body").off("keydown", auto_activate);
		self.rdp_provider.session_management.removeCallback("ovd.rdpProvider.gesture.threefingers", touchscreen_toggle);
	};
}

uovd.provider.rdp.html5.Keyboard.prototype.setScancode = function() {
	var self = this; /* closure */
	/* Keyboard instance */
	var keyboard = new Guacamole.Keyboard(document);
	this.connection.guac_keyboard = keyboard;

	/* Keydown */
	keyboard.onkeydown = function (keysym) {
		self.connection.guac_client.sendKeyEvent(1, keysym);
	};

	/* Keyup */
	keyboard.onkeyup = function (keysym) {
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
		var keyboard = this.connection.guac_keyboard;
		keyboard.setIme(state);
	}
}

uovd.provider.rdp.html5.Keyboard.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.destroying") {
		this.end();
		this.rdp_provider.session_management.removeCallback("ovd.session.destroying", this.handler);
	}
}
