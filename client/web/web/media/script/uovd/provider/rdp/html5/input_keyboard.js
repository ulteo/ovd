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

	var sm_use_local_ime = this.rdp_provider.session_management.session.settings.use_local_ime || 0;
	var force_unicode_local_ime = parseInt(sm_use_local_ime) ? 1 : 0;
	var rdp_input_method = force_unicode_local_ime ? "unicode_local_ime" : rdp_provider.session_management.parameters.rdp_input_method;

	switch(rdp_input_method) {
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
	var server_id = this.attachedTo();

	if(this.ime_settings) {
		/* Restore IME server params */
		var params = this.ime_settings[server_id];
		this.guac_keyboard.setPosition(params["position"]["x"], params["position"]["y"]);
		this.guac_keyboard.setIme(params["state"]);
	}
}

uovd.provider.rdp.html5.Keyboard.prototype.attachedTo = function() {
	for(var i=0 ; i<this.rdp_provider.connections.length ; ++i) {
		if(this.rdp_provider.connections[i] == this.connection) { return i; }
	}

	return undefined;
}

uovd.provider.rdp.html5.Keyboard.prototype.setUnicode = function() {
	var self = this; /* closure */

	/* Keyboard instance */
	this.guac_keyboard = new Guacamole.NativeKeyboard();

	/* Insert the hidden textfield with the canvas */
	jQuery(this.connection.guac_display).prepend(this.guac_keyboard.getNode());

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

	/* Ukbrdr */
	this.guac_keyboard.oncomposeupdate = function(text) {
		var preedit = new DataStream();
		preedit.write_UInt16LE(4);
		preedit.write_UInt16LE(0);

		var size = new DataStream();
		size.write_UTF16LE(text+"\0");
		preedit.write_UInt32LE(size.get_size());
		preedit.write_UTF16LE(text+"\0");

		self.connection.guac_tunnel.sendMessage("ukbrdr",preedit.toBase64());
	}

	this.guac_keyboard.oncomposeend = function() {
		var preedit = new DataStream();
		preedit.write_UInt16LE(5);
		preedit.write_UInt16LE(0);
		preedit.write_UInt32LE(0);

		self.connection.guac_tunnel.sendMessage("ukbrdr",preedit.toBase64());
	}


	/* Install instruction hook */
	for(var i=0 ; i<self.rdp_provider.connections.length ; ++i) {
		(function(server_id) {
			self.rdp_provider.connections[server_id].guac_tunnel.addInstructionHandler("ukbrdr", function(opcode, params) {
				var stream = DataStream.fromBase64(params[0]);
				var message = stream.read_UInt16LE();
				var flags = stream.read_UInt16LE();
				var size = stream.read_UInt32LE();

				switch(message) {
					case 1: // UKB_CARET_POS
						var x = stream.read_UInt32LE();
						var y = stream.read_UInt32LE();

						var offset = jQuery(self.connection.guac_display).offset();
						var px = offset.left + x;
						var py = offset.top + y;
						self.guac_keyboard.setPosition(px, py);
						break;
				}
			});
		})(i);
	}

	/* Actions */
	this.focus =  function() { if(!self.guac_keyboard.active()) self.guac_keyboard.enable(); };
	this.blur =   function() { if(self.guac_keyboard.active())  self.guac_keyboard.disable(); };
	this.toggle = function() { if(self.guac_keyboard.active()) { self.blur(); } else { self.focus(); } };
	this.lastmode = null;

	/* Focus mode */
	var update_mode = function(type, source, params) {
		var mode = params["mode"];

		if(self.lastmode == mode) {
			return;
		}

		switch(mode) {
			case "click":
				self.lastmode = "click";
				self.rdp_provider.session_management.removeCallback("ovd.rdpProvider.gesture.twofingers", self.toggle);
				jQuery(self.connection.guac_display).on("click.keyboard_autostart", self.focus);
				break;

			case "gesture":
				self.lastmode = "gesture";
				jQuery(self.connection.guac_display).off("click.keyboard_autostart");
				self.rdp_provider.session_management.addCallback("ovd.rdpProvider.gesture.twofingers", self.toggle);
				break;

			default:
				self.lastmode = null;
				self.rdp_provider.session_management.removeCallback("ovd.rdpProvider.gesture.twofingers", self.toggle);
				jQuery(self.connection.guac_display).off("click.keyboard_autostart");
		}
	};

	this.rdp_provider.session_management.addCallback("ovd.wm.keyboard.focusMode", update_mode);

	/* UI element */
	this.ui = jQuery(document.createElement("div"));
	this.ui.css({
		"box-sizing": "border-box",
		"width":"100%",
		"height":"100%",
		"background": "#FFF",
		"overflow": "auto"
	});

	var modes = [ {"name":"Mouse click", "id":"click"}, {"name":"Two fingers gesture", "id":"gesture"} ];
	for(var i=0 ; i<modes.length ; ++i) {
		var id = modes[i]["id"];
		var name = modes[i]["name"];

		var input = jQuery(document.createElement("input"));
		input.attr("type", "radio");
		input.attr("value", id);
		input.attr("name", "keyboard_focus_mode");
		input.attr("id", "keyboard_focus_mode_"+id);
		var label = jQuery(document.createElement("label"));
		label.attr("for", "keyboard_focus_mode_"+id);
		label.html(name);
		this.ui.append(input, label, jQuery(document.createElement("br")));
	}

	this.ui.find('input[type=radio][name=keyboard_focus_mode]').change(function() {
		self.rdp_provider.session_management.fireEvent("ovd.wm.keyboard.focusMode", self, {"mode":this.value});
	});

	this.rdp_provider.session_management.fireEvent("ovd.rdpProvider.menu", this, {"node":this.ui, "type":"Keyboard"});

	var update_ui = function(type, source, params) {
		self.ui.find("#keyboard_focus_mode_"+params["mode"]).attr("checked", "checked");
	};

	this.rdp_provider.session_management.addCallback("ovd.wm.keyboard.focusMode", update_ui);

	/* Activation of default mode */
	if("ontouchstart" in window) {
		this.rdp_provider.session_management.fireEvent("ovd.wm.keyboard.focusMode", this, {"mode":"gesture"});
	} else {
		this.rdp_provider.session_management.fireEvent("ovd.wm.keyboard.focusMode", this, {"mode":"click"});
	}

	/* Set destructor */
	this.end = function() {
		self.connection.guac_tunnel.removeInstructionHandler("ukbrdr");
		self.rdp_provider.session_management.removeCallback("ovd.wm.keyboard.focusMode", update_mode);
		self.rdp_provider.session_management.removeCallback("ovd.wm.keyboard.focusMode", update_ui);
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
	this.toggle = function() { if(self.guac_keyboard.active()) { self.blur(); } else { self.focus(); } };
	jQuery(this.connection.guac_display).on("click.keyboard_autostart", this.focus);

	/* Set destructor */
	this.end = function() {
		jQuery(self.connection.guac_display).off("click.keyboard_autostart");
		jQuery(self.guac_keyboard.getNode()).remove();
	};

}

uovd.provider.rdp.html5.Keyboard.prototype.setUnicodeLocalIME = function() {
	var self = this; /* closure */

	/* Set unicode */
	this.setUnicode();

	/* Keep track of IME settings by connection */
	this.ime_settings = [];
	for(var i=0 ; i<self.rdp_provider.connections.length ; ++i) {
		this.ime_settings[i] = {
			"position":{"x":0, "y":0},
			"state": true
		}
	}

	/* Install instruction hook */
	for(var i=0 ; i<self.rdp_provider.connections.length ; ++i) {
		(function(server_id) {
			var sid = server_id;

			self.rdp_provider.connections[server_id].guac_tunnel.addInstructionHandler("ukbrdr", function(opcode, params) {
				var stream = DataStream.fromBase64(params[0]);
				var message = stream.read_UInt16LE();
				var flags = stream.read_UInt16LE();
				var size = stream.read_UInt32LE();
				var server_attached = self.attachedTo();

				switch(message) {
					case 0: // UKB_INIT
						self.guac_keyboard.setUkbrdr(true);
						break;

					case 1: // UKB_CARET_POS
						var x = stream.read_UInt32LE();
						var y = stream.read_UInt32LE();

						var offset = jQuery(self.connection.guac_display).offset();
						var px = offset.left + x;
						var py = offset.top + y;
						var last_x = self.ime_settings[sid]["position"]["x"];
						var last_y = self.ime_settings[sid]["position"]["y"];

						if(last_x != px || last_y != py) {
							self.ime_settings[sid]["position"]["x"] = px;
							self.ime_settings[sid]["position"]["y"] = py;

							if(sid == server_attached) {
								self.guac_keyboard.setPosition(px, py);
							}
						}
						break;

					case 2: // UKB_IME_STATUS
						var state = stream.read_Byte();
						var last_state = self.ime_settings[sid]["state"];

						if(last_state != state) {
							self.ime_settings[sid]["state"] = state;

							if(sid == server_attached) {
								self.guac_keyboard.setIme(state);
							}
						}
						break;
				}
			});
		})(i);
	}
}

uovd.provider.rdp.html5.Keyboard.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.destroying") {
		this.end();
		this.rdp_provider.session_management.removeCallback("ovd.session.destroying", this.handler);
	}
}
