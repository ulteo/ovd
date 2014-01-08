uovd.provider.rdp.html5.Keyboard = function(rdp_provider, connection) {
	this.rdp_provider = rdp_provider;
	this.guac_keyboard = null;
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.focus = function() {};
	this.blur = function() {};
	this.toggle = function() {};
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

	/* Actions */
	this.focus =  function() { if(!self.guac_keyboard.active()) self.guac_keyboard.enable(); };
	this.blur =   function() { if(self.guac_keyboard.active())  self.guac_keyboard.disable(); };
	this.toggle = function() { if(self.guac_keyboard.active()) { self.blur(); } else { self.focus(); } };

	/* UI element */
	this.ui = jQuery(document.createElement("div"));
	this.ui.css({
		"box-sizing": "border-box",
		"width":"100%",
		"height":"100%",
		"background": "#FFF",
		"overflow": "auto"
	});

	var input0 = jQuery(document.createElement("input"));
	input0.attr("type", "radio");
	input0.attr("value", "0");
	input0.attr("name", "keyboard_focus_mode");
	input0.attr("id", "keyboard_focus_mode_0");
	input0.prop("checked", "checked"); /* Default */
	var label0 = jQuery(document.createElement("label"));
	label0.attr("for", "keyboard_focus_mode_0");
	label0.html("Mouse click");
	this.ui.append(input0, label0, jQuery(document.createElement("br")));

	var input1 = jQuery(document.createElement("input"));
	input1.attr("type", "radio");
	input1.attr("value", "1");
	input1.attr("name", "keyboard_focus_mode");
	input1.attr("id", "keyboard_focus_mode_1");
	var label1 = jQuery(document.createElement("label"));
	label1.attr("for", "keyboard_focus_mode_1");
	label1.html("Two fingers gesture");
	this.ui.append(input1, label1, jQuery(document.createElement("br")));

	/* Change event */
	this.ui.find('input[type=radio][name=keyboard_focus_mode]').change(function() {
		if(this.value == '0') {
			/* Disable gesture mode */
			self.rdp_provider.session_management.removeCallback("ovd.rdpProvider.gesture.twofingers", self.toggle);
			/* Enable click mode */
			jQuery(self.connection.guac_display).on("click.keyboard_autostart", self.focus);
		} else {
			/* Disable click mode */
			jQuery(self.connection.guac_display).off("click.keyboard_autostart");
			/* Enable gesture mode */
			self.rdp_provider.session_management.addCallback("ovd.rdpProvider.gesture.twofingers", self.toggle);
		}
	});

	this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.menu", this, {"node":this.ui, "type":"Keyboard"});

	/* Activation of default mode */
	jQuery(self.connection.guac_display).on("click.keyboard_autostart", self.focus);

	/* Set destructor */
	this.end = function() {
		self.rdp_provider.session_management.removeCallback("ovd.rdpProvider.gesture.twofingers", self.toggle);
		jQuery(self.connection.guac_display).off("click.keyboard_autostart");
		jQuery(self.guac_keyboard.getNode()).remove();
	};
}

uovd.provider.rdp.html5.Keyboard.prototype.setScancode = function() {
	var self = this; /* closure */

	/* Keyboard instance */
	this.guac_keyboard = new Guacamole.Keyboard(this.connection.guac_display);

	/* Ensure the element can takes focus (Hack !) */
	jQuery(this.connection.guac_display).attr("tabindex", "0");

	/* Keydown */
	this.guac_keyboard.onkeydown = function (keysym) {
		self.connection.guac_client.sendKeyEvent(1, keysym);
	};

	/* Keyup */
	this.guac_keyboard.onkeyup = function (keysym) {
		self.connection.guac_client.sendKeyEvent(0, keysym);
	};

	this.focus = function() { if (!self.guac_keyboard.active()) jQuery(self.connection.guac_display).focus(); };
	this.blur = function() { if (self.guac_keyboard.active()) jQuery(self.connection.guac_display).blur(); };
	jQuery(this.connection.guac_display).on("click.keyboard_autostart", this.focus);

	/* Set destructor */
	this.end = function() {
		jQuery(self.connection.guac_display).off("click.keyboard_autostart");
		jQuery(self.guac_keyboard.getNode()).remove();
	};

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
