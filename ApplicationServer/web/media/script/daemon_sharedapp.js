var SharedApp = Class.create(Daemon, {
	initialize: function(applet_version_, applet_main_class_, printing_applet_version_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, printing_applet_version_, debug_]);
	},

	loop: function() {
		this.push_log('[sharedapp] loop()', 'debug');

		this.check_status();

		if (this.session_state == 2 && this.application_state == 2 && $('splashContainer').visible() && ! $('appletContainer').visible()) {
			if (! this.started) {
				this.applet_width = parseInt(this.my_width);
				this.applet_height = parseInt(this.my_height);

				this.do_started();
			}

			this.started = true;
		} else if ((this.old_session_state == 2 && this.session_state != 2) || this.session_state == 3 || this.session_state == 4 || this.session_state == 9 || (this.old_application_state == 2 && this.application_state != 2) || this.application_state == 3 || this.application_state == 4 || this.application_state == 9) {
			this.do_ended();

			return;
		}

		setTimeout(this.loop.bind(this), 2000);
	},

	parse_check_status: function(transport) {
		var xml = transport.responseXML;

		var buffer = xml.getElementsByTagName('session');

		if (buffer.length != 1) {
			this.push_log('[session] bad xml format 1', 'error');
			return;
		}

		var sessionNode = buffer[0];

		this.old_session_state = this.session_state;

		try { // IE does not have hasAttribute in DOM API...
			this.session_state = sessionNode.getAttribute('status');
		} catch(e) {
			this.push_log('[session] bad xml format 2', 'error');
			return;
		}

		if (this.session_state != this.old_session_state)
			this.push_log('[session] Change status from '+this.old_session_state+' to '+this.session_state, 'info');

		if (this.session_state != 2)
			this.push_log('[session] Status: '+this.session_state, 'warning');
		else
			this.push_log('[session] Status: '+this.session_state, 'debug');

		var buffer = xml.getElementsByTagName('application');

		if (buffer.length != 1) {
			this.push_log('[application] bad xml format 1', 'error');
			return;
		}

		var applicationNode = buffer[0];

		this.old_application_state = this.application_state;

		try { // IE does not have hasAttribute in DOM API...
			this.application_state = applicationNode.getAttribute('status');
		} catch(e) {
			this.push_log('[application] bad xml format 2', 'error');
			return;
		}

		if (this.application_state != this.old_application_state)
			this.push_log('[application] Change status from '+this.old_application_state+' to '+this.application_state, 'info');
		if (this.application_state != 2)
			this.push_log('[application] Status: '+this.application_state, 'warning');
		else
			this.push_log('[application] Status: '+this.application_state, 'debug');

		var printNode = sessionNode.getElementsByTagName('print');
		if (printNode.length > 0) {
			printNode = printNode[0];

			var path = printNode.getAttribute('path');
			var timestamp = printNode.getAttribute('time');
			this.do_print(path, timestamp);
		}
	}
});
