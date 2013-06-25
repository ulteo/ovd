/* Jsonp WebApp Provider */

uovd.provider.webapps.Jsonp = function() {
	this.initialize();
}

uovd.provider.webapps.Jsonp.prototype = new uovd.provider.webapps.Base();

uovd.provider.webapps.Jsonp.prototype.connectDesktop = function() {
	/* Nothing to do in desktop mode */
}

uovd.provider.webapps.Jsonp.prototype.connectApplications = function() {
	var self = this; /* closure */
	var servers = this.session_management.session.servers;

	/* Add the servers status callback */
	window.webappServerStatus = function(id, status) {
		var server = self.session_management.session.servers[id];
		if(server.type == uovd.SERVER_TYPE_WEBAPPS) {
			self.session_management.session.servers[id].setStatus(status);
		}
	}

	/* Load servers */
	for(var i=0 ; i<servers.length ; ++i) {
		var server = servers[i];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) { continue; }

		var url = server.webapps_url + '/connect?id=' + i + '&user=' + server.login + '&pass=' + server.password;

		jQuery.ajax({
			url : url,
			dataType : 'jsonp',
		});
	}

	new uovd.provider.applications.Web(this);
}

uovd.provider.webapps.Jsonp.prototype.disconnect_implementation = function() {
	var servers = this.session_management.session.servers;

	for(var i=0 ; i<servers.length ; ++i) {
		var server = servers[i];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) { continue; }

		var url = server.webapps_url + '/disconnect?id=' + i + '&user=' + server.login + '&pass=' + server.password;

		jQuery.ajax({
			url : url,
			dataType : 'jsonp',
		});

		window.webappServerStatus(i, uovd.SERVER_STATUS_DISCONNECTED);
	}
}
