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
	this.lastvalue = " ";
	this.node = null;

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
	var input = jQuery(document.createElement('input'));
	input.attr("type", "text");
	input.attr("autocorrect", "off");
	input.attr("autocapitalize", "off");
	input.css({
		"width": "1px",
		"height": "1px",
		"font-size": "1%"
	});

	this.node = jQuery(document.createElement('div'));
	this.node.css({
		"position": "relative",
		"float":    "left",
		"width":    "1px",
		"height":   "1px",
		"overflow": "hidden",
		"z-index":  "-10",
		"display":  "none"
	});
	this.node.append(input);

	/* Events handlers */
	function handleKeysym(e) {
		if(self.controlKeySym[e.keyCode]) {
			var keysym = self.controlKeySym[e.keyCode];
			e.preventDefault();
			e.stopPropagation();
			e.target.value = self.lastvalue = " ";

			if(e.type == "keydown") {
				self.onkeydown(keysym);
			} else {
				self.onkeyup(keysym);
			}
		} else if(e.altKey || e.ctrlKey || e.metaKey) {
			var keysym = parseInt(e.keyCode)+0x20;
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

		for(i=0;i<e.target.value.length;i++) {
			var k = e.target.value.charCodeAt(i);
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
		if(e.target.value.length == 0) {
			e.target.value = " ";
		}
		self.lastvalue = e.target.value;
	}

	function focusOut(e) {
		self.focused = false;
		self.node.hide();
  }

	function focusIn(e) {
		self.focused = true;
		self.node.show();
		e.target.value = self.lastvalue = " ";
	}

	/* Bind events */
	input[0].addEventListener("input", handleUnicode, true);
	input[0].addEventListener("keydown", handleKeysym, true);
	input[0].addEventListener("keyup", handleKeysym, true);
	input[0].addEventListener("blur", focusOut, true);
	input[0].addEventListener("focus", focusIn, true);
}



/* -------------- Interface ---------------*/



Guacamole.NativeKeyboard.prototype.getNode = function() {
	return this.node[0];
};

Guacamole.NativeKeyboard.prototype.enable = function() {
	var input = jQuery(this.node).find("input");
	this.node.show();
	input.focus();
};

Guacamole.NativeKeyboard.prototype.disable = function() {
	var input = jQuery(this.node).find("input");
	input.blur()
	this.node.hide();
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
	var input = jQuery(this.node).find("input");
	if(value) {
		input.attr("type", "text");
	} else {
		input.attr("type", "password");
	}

	/* Close the keyboard to refresh the layout */
	this.disable();
};
