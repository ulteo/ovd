/* Proxy WebApp Provider */

uovd.provider.webapps.Proxy = function(proxy_url) {
	this.initialize();
	this.proxy_url = proxy_url;
};

uovd.provider.webapps.Proxy.prototype = new uovd.provider.webapps.Base();

uovd.provider.webapps.Proxy.prototype.connectDesktop = function() {
	/* Nothing to do in desktop mode */
};

uovd.provider.webapps.Proxy.prototype.connectApplications = function() {
	var servers = this.session_management.session.servers;

	for(var i=0 ; i<servers.length ; ++i) {
		var server = servers[i];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) { continue; }

		var url = this.proxy_url + '?id=' + i + '&user=' + server.login + '&pass=' + server.password;
		console.log(url);

		jQuery.ajax({
			url : url,
			dataType: "xml",
			headers: {
				"x-ovd-service" : "connect",
				"x-ovd-webappsserver" : server.webapps_url
			},
			success: jQuery.proxy(this.webappServerStatus, this)
		});
	}

	new uovd.provider.applications.Web(this);
};

uovd.provider.webapps.Proxy.prototype.disconnect_implementation = function() {
	var servers = this.session_management.session.servers;

	for(var i=0 ; i<servers.length ; ++i) {
		var server = servers[i];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) { continue; }

		var url = this.proxy_url + '?id=' + i + '&user=' + server.login + '&pass=' + server.password;
		console.log(url);

		jQuery.ajax({
			url : url,
			dataType: "xml",
			headers: {
				"x-ovd-service" : "disconnect",
				"x-ovd-webappsserver" : server.webapps_url
			},
			success: jQuery.proxy(this.webappServerStatus, this)
		});
	}
};

uovd.provider.webapps.Proxy.prototype.webappServerStatus = function(xml) {
	console.log(xml);
	var xml_root = jQuery(xml).find(":root");

	if(xml_root.prop("nodeName") != "webapp_server_status") {
		return;
	}

	var server_id     = xml_root.attr("server");
	var server_status = xml_root.attr("status");

	if(server_id == undefined || server_status == undefined) {
		return;
	}

	var server = this.session_management.session.servers[server_id];

	if(server == undefined) {
		return;
	}

	if(server.type != uovd.SERVER_TYPE_WEBAPPS) {
		return;
	}

	server.setStatus(server_status);
};
