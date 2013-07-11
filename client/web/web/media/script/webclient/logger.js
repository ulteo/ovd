Logger = function(session_management, node) {
	this.session_management = session_management;
	this.mode = "no_debug";
	this.node = jQuery(node);
	this.panel = null;
	this.content = null;

	/* register events listeners */
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.session_management.addCallback("ovd.*", this.handler);
};

Logger.prototype.handleEvents = function(type, source, params) {
	var self = this; /* closure */

	if(type == 'ovd.session.starting') {
		if(this.session_management.parameters.debug == undefined) {
			/* default setting : print nothing */
			this.mode = "no_debug";
		}

		if(this.session_management.parameters.debug == false) {
			/* silent debug mode : print in background */
			this.mode = "background";
		}

		if(this.session_management.parameters.debug == true) {
			/* debug mode : display logs  */
			this.mode = "debug";
		}

		if(this.panel != null) {
			this.panel.remove();
			this.panel = null;
			this.content = null;
		}

		if(this.mode == "debug") {
			/* Build floating panel */
			this.panel = jQuery(document.createElement("div"));
			this.panel.css({
				'position'    : 'absolute',
				'border'      : 'solid black 1px',
				'overflow'    : 'hidden'
			});
			this.panel.prop("x", 0);
			this.panel.prop("y", 0);

			/* Control bar */
			var controlbar = jQuery(document.createElement("div"));
			controlbar.width(parseInt(window.innerWidth)/3 - 20);
			controlbar.height("10px");
			controlbar.css({
				'padding'     : '10px',
				"text-align": "right"
			});

			var minimize = jQuery(document.createElement("a"));
			minimize.css({
				'padding'     : '3px 5px 3px 5px ',
				'color'       : 'black',
				'font-size'   : '1em',
				'border'      : 'solid black 1px'
			});
			minimize.html("_");
			minimize.click(function() {
				self.content.slideToggle();
				if(minimize.html() == "_") { minimize.html("□"); }
				else { minimize.html("_"); }
			});

			var close = jQuery(document.createElement("a"));
			close.css({
				'padding'     : '3px 5px 3px 5px ',
				'color'       : 'black',
				'font-size'   : '1em',
				'border'      : 'solid black 1px'
			});
			close.html("X");
			close.click(function() {
				self.panel.hide();
			});

			this.content = jQuery(document.createElement("div"));
			this.content.width(parseInt(window.innerWidth)/3 - 20);
			this.content.height(parseInt(window.innerHeight)/2 -50);
			this.content.css({
				'padding'     : '10px',
				'color'       : 'black',
				'font-size'   : '1.2em',
				'overflow-x'  : 'hidden',
				'overflow-y'  : 'scroll'
			});

			controlbar.append(minimize, close);
			this.panel.append(controlbar, this.content);

			/* drag-n-drop */
			controlbar.mousedown(function(e) {
				var lastX = e.pageX;
				var lastY = e.pageY;

				var drag = function(e) {
					var dx = parseInt(e.pageX) - parseInt(lastX);
					var dy = parseInt(e.pageY) - parseInt(lastY);
					var nx = parseInt(self.panel.prop('x')) + parseInt(dx);
					var ny = parseInt(self.panel.prop('y')) + parseInt(dy);
					lastX = e.pageX;
					lastY = e.pageY;

					self.panel.css({'top':ny, 'left':nx});
					self.panel.prop('x', nx);
					self.panel.prop('y', ny);
				};

				var drop = function(e) {
					self.panel.off("mousemove");
					self.panel.off("onmouseup");
					self.panel.off("mouseout");
				};

				self.panel.mousemove(drag);
				self.panel.mouseup(drop);
				self.panel.mouseout(drop);
			});

			this.node.append(this.panel);
		}
	}

	/* For every event */
	var params_str = "";
	for(var name in params) {
		params_str += '\t' + name + ' : ' + params[name] + '\n';
	}

	this.message(type+ '\n' + params_str);
};

Logger.prototype.message = function(message) {
	if(this.mode == "no_debug") { return; }

	if((this.mode == "debug" || this.mode == "background") && window["console"]) {
		console.log(message);
	}

	if(this.mode == "debug" && this.content != null) {
		this.content.append(jQuery("<div>"+message.replace("\n","<br/>").replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;")+"</div><br/>"));
	}
};
