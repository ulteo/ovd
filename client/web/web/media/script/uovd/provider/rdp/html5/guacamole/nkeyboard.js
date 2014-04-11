// Guacamole namespace
var Guacamole = Guacamole || {};

/**
 * Native keyboard
 * 
 * @constructor
 */
Guacamole.NativeKeyboard = function() {
	var self = this; /* closure */
	this.focused = false;
	this.composition = false;
	this.skipNextInput = false;
	this.lastvalue = " ";
	this.node = null;
	this.altKey = false;
	this.input = null;
	this.textarea = null;
	this.field = null;

	this.controlKeySym = {
		8:   0xFF08, // backspace
		9:   0xFF09, // tab
		13:  0xFF0D, // enter
		16:  0xFFE1, // shift
		17:  0xFFE3, // ctrl
		18:  0xFFE9, // alt
		19:  0xFF13, // pause/break
		20:  0xFFE5, // caps lock
		27:  0xFF1B, // escape
		32:  0x0020, // space
		33:  0xFF55, // page up
		34:  0xFF56, // page down
		35:  0xFF57, // end
		36:  0xFF50, // home
		37:  0xFF51, // left arrow
		38:  0xFF52, // up arrow
		39:  0xFF53, // right arrow
		40:  0xFF54, // down arrow
		45:  0xFF63, // insert
		46:  0xFFFF, // delete
		91:  0xFFEB, // left window key (super_l)
		92:  0xFF67, // right window key (menu key?)
		93:  0x0000, // select key
		112: 0xFFBE, // f1
		113: 0xFFBF, // f2
		114: 0xFFC0, // f3
		115: 0xFFC1, // f4
		116: 0xFFC2, // f5
		117: 0xFFC3, // f6
		118: 0xFFC4, // f7
		119: 0xFFC5, // f8
		120: 0xFFC6, // f9
		121: 0xFFC7, // f10
		122: 0xFFC8, // f11
		123: 0xFFC9, // f12
		144: 0xFF7F, // num lock
		145: 0xFF14  // scroll lock
	};

	/* Handlers */
	this.onkeydown = function(k) {};
	this.onkeyup   = function(k) {};
	this.onunicode = function(k) {};

	/* Detect mode */
	this.touchscreen = false;
	if(window.ontouchstart != undefined) {
		this.touchscreen = true;
	}

	/* Create text entry */
	this.textarea = jQuery(document.createElement('textarea'));
	this.textarea.attr("type", "text");
	this.textarea.attr("autocorrect", "off");
	this.textarea.attr("autocapitalize", "off");

	this.input = jQuery(document.createElement('input'));
	this.input.attr("type", "password");
	this.input.attr("autocorrect", "off");
	this.input.attr("autocapitalize", "off");

	this.node = jQuery(document.createElement('div'));
	this.node.css({
		"position": "fixed",
		"float":    "left",
		"width":    "1px",
		"height":   "1px",
		"overflow": "hidden",
		"z-index":  "-10",
		"display":  "none"
	});
	this.node.append(this.textarea);
	this.node.append(this.input);
	this.field = this.textarea;

	function reset() {
		self.lastvalue = " ";
		self.input.val(" ");
		self.textarea.val(" ");
	}

	/* Events handlers */
	function handleKeysym(e) {
		if(self.composition) {return;};

		if(self.controlKeySym[e.which]) {
			var keysym = self.controlKeySym[e.which];
			var location = e.originalEvent.location || e.originalEvent.keyLocation || 0;
			e.preventDefault();
			e.stopPropagation();
			reset();

			if(e.type == "keydown") {
				if (e.keyCode == 17 && self.altKey) {
					self.altKey = false;
					self.onkeyup(0xFFE9);
				}
				else if (e.keyCode == 18 && e.ctrlKey)
					self.onkeyup(0xFFE3);
				else if (!(e.keyCode == 18 && location == 2)) {
					if (e.keyCode == 18) {
						self.altKey = true;
					}
					self.onkeydown(keysym);
				}
			} else {
				if (e.keyCode == 17 && self.altKey)
					self.onkeydown(0xFFE9);
				else if (e.keyCode == 18 && e.ctrlKey)
					self.onkeydown(0xFFE3);
				else if (!(e.keyCode == 18 && location == 2)) {
					if (e.keyCode == 18) {
						self.altKey = false;
					}
					self.onkeyup(keysym);
				}
			}
		} else if((self.altKey || e.ctrlKey || e.metaKey) && !(e.altKey && e.ctrlKey)) {
			var keysym = parseInt(e.which)+0x20;
			e.preventDefault();
			e.stopPropagation();

			if(e.type == "keydown") {
				self.onkeydown(keysym);
			} else {
				self.onkeyup(keysym);
			}
		}
	}

	function handleUnicode(e) {
		var i;
		var currentvalue = self.field.val();

		if(self.composition) {return;};

		if(self.skipNextInput) {
			self.skipNextInput = false;
			currentvalue = " ";
			reset();
			return;
		}

		for(i=0;i<currentvalue.length;i++) {
			var k = currentvalue.charCodeAt(i);
			var c = self.lastvalue.charCodeAt(i);
			if(k != c) {
				if(self.lastvalue.length > i) {
					for(j=0; j<self.lastvalue.length - i ; j++) {
						self.onkeydown(65288);
						self.onkeyup(65288);
					}
					self.lastvalue = self.lastvalue.substr(0, i);
				}
				self.onunicode(k);
			}
		}
		if(self.lastvalue.length > i) {
			for(j=0; j<self.lastvalue.length - i ; j++) {
				self.onkeydown(65288);
				self.onkeyup(65288);
			}
		}
		if(currentvalue.length == 0) {
			reset();
		}
		self.lastvalue = currentvalue;
	}

	function handleFocus(e) {
		if(e.type == "focus") {
			self.focused = true;
			reset();
		} else {
			self.focused = false;
			reset();
		}
  }

	function handleComposition(e) {
		if(e.type == "compositionstart" || e.type == "compositionupdate") {
			self.composition = true;
			self.oncomposeupdate(e.originalEvent.data);
		} else {
			self.oncomposeupdate(e.originalEvent.data);
			self.oncomposeend();
			self.composition = false;
			self.skipNextInput = true;
		}
	}

	/* Bind events */
	this.input.on("keydown keyup", handleKeysym);
	this.input.on("input", handleUnicode);
	this.input.on("compositionstart compositionupdate compositionend", handleComposition);
	this.input.on("focus blur", handleFocus);

	this.textarea.on("keydown keyup", handleKeysym);
	this.textarea.on("input", handleUnicode);
	this.textarea.on("compositionstart compositionupdate compositionend", handleComposition);
	this.textarea.on("focus blur", handleFocus);
}



/* -------------- Interface ---------------*/



Guacamole.NativeKeyboard.prototype.getNode = function() {
	return this.node[0];
};

Guacamole.NativeKeyboard.prototype.enable = function() {
	this.node.show();
	this.field.focus();
};

Guacamole.NativeKeyboard.prototype.disable = function() {
	this.node.hide();
	this.field.blur();
};

Guacamole.NativeKeyboard.prototype.active = function() {
	return this.focused;
};

Guacamole.NativeKeyboard.prototype.toggle = function() {
	if(this.active()) {
		this.disable();
	} else {
		this.enable();
	}
};

Guacamole.NativeKeyboard.prototype.setIme = function(value) {
	if(value) {
		if(this.field == this.textarea) { return; } /* No change */
		else                            { this.field = this.textarea; }
		this.field.focus();
	} else {
		if(this.field == this.input) { return; } /* No change */
		else                         { this.field = this.input; }
	}

	this.enable();
};

Guacamole.NativeKeyboard.prototype.setPosition = function(x, y) {
	this.field.css("position", "fixed");
	this.field.css("left", x);
	this.field.css("top", y);
};
