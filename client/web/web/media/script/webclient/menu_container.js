MenuContainer = function(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.body = null;
	this.bottom = null;
	this.content = {};

	/* Notifications */
	this.blink_interval = null;

	/* register events listeners */
	this.handler = jQuery.proxy(this.handleEvents, this);
	this.session_management.addCallback("ovd.rdpProvider.menu",          this.handler);
	this.session_management.addCallback("ovd.rdpProvider.menu.notify",   this.handler);
	this.session_management.addCallback("ovd.session.started",           this.handler);
	this.session_management.addCallback("ovd.session.destroying",        this.handler);
}

MenuContainer.prototype.show = function() {
	this.node.animate({"top": "0"});
	this.bottom.html("&#x25B2"); /* Unicode "/\" arrow */
};

MenuContainer.prototype.hide = function() {
	this.node.animate({"top": "-33%"});
	this.bottom.html("&#x25BC"); /* Unicode " \/" arrow */
};

MenuContainer.prototype.handleEvents = function(type, source, params) {
	var self = this; /* closure */

	if(type == "ovd.session.started" ) {
		/* Add inner components */
		this.body =   jQuery(document.createElement('div')).attr('id', 'menuContainer_main');
		this.bottom = jQuery(document.createElement('div')).attr('id', 'menuContainer_bottom');
		this.bottom.html("&#x25BC"); /* Unicode " \/" arrow */
		this.node.append(this.body, this.bottom);

		/* Bind the show/hide button */
		this.bottom.on("click", function() {
			var offset_top = self.node.offset().top;
			if(offset_top != 0) { self.show(); }
			else                { self.hide(); }
			self.blinkStop();
		});

		/* Fill the content */
		for(name in this.content) {
			var entry = this.content[name];
			this.body.append(entry);
			this.node.show();
		}

		this.started = true;
	}

	if(type == "ovd.rdpProvider.menu") {
		var type = params["type"];
		var node = params["node"];

		/* Create a new entry */
		var entry = jQuery(document.createElement('div')).addClass("menuContainer_entry");
		var title = jQuery(document.createElement('h3')).html(type);
		var control = jQuery(document.createElement('div')).addClass("menuContainer_control").append(node);
		entry.append(title, control);

		/* If not started : put it in the queue
		   Else : put it in the queue AND show it
		*/

		this.content[type] = entry;

		if(this.started) {
			this.body.append(entry);
			this.node.show();
		}
	}

	if(type == "ovd.rdpProvider.menu.notify") {
		var message = params["message"];
		var duration = params["duration"];
		var interval = params["interval"];

		if(message != undefined) {
			this.bottom.html("&#x25BC "+message+" &#x25BC");
		}

		this.blinkStart(duration, interval);
	}

	if(type == "ovd.session.destroying" ) { /* Clean context */
		this.end();
	}
}

MenuContainer.prototype.blinkStart = function(duration, interval) {
	var self = this; /* closure */
	var interval_ms = interval || 750;
	var duration_ms = duration || 10000;

	/* Clear interval */
	if(this.blink_interval != null) {
		clearInterval(this.blink_interval);
		this.blink_interval = null;
	}

	/* Set interval */
	this.blink_interval = setInterval( function() {
		self.bottom.toggleClass("glow");

		/* Check stop */
		duration_ms -= interval_ms;

		if(duration_ms <= 0) {
				self.blinkStop();
		}
	}, interval_ms);
}

MenuContainer.prototype.blinkStop = function() {
	/* Clear interval */
	if(this.blink_interval != null) {
		clearInterval(this.blink_interval);
		this.blink_interval = null;
	}

	/* Reset state */
	this.bottom.removeClass("glow");
}

MenuContainer.prototype.end = function() {
	this.hide();
	this.blinkStop();
	this.node.empty();
	this.content = {};
	this.started = false;
}
