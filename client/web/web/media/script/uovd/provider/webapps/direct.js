/* DirectAjaxProvider */

/* Contact directly the webapps.
   Works only with the ssl gateway because of the 
   XmlHttpRequest "same origin" restriction.
*/

uovd.provider.webapps.Direct = function() {
	this.initialize();
};

uovd.provider.webapps.Direct.prototype = new uovd.provider.webapps.Base();

uovd.provider.webapps.Direct.prototype.connectDesktop = function() {
	/* Nothing to do in desktop mode */
};

uovd.provider.webapps.Direct.prototype.connectApplications = function() {
	var servers = this.session_management.session.servers;
	for(var i=0 ; i<servers.length ; ++i) {
		var server = servers[i];
		
		if(server.type != uovd.SERVER_TYPE_WEBAPPS) { continue; }
		
		var url = '/webapps/connect';
		
		jQuery.ajax({
			url : url,
			dataType: "xml",
			headers: {
				"x-ovd-service" : "connect",
				"x-ovd-param" : 'id=' + i + '&user=' + server.login + '&pass=' + server.password,
				"x-ovd-webappsserver" : server.webapps_url
			},
			success: jQuery.proxy(this.webappServerStatus, this)
		});
	}
	
	new uovd.provider.applications.Web(this);
}

uovd.provider.webapps.Direct.prototype.disconnect_implementation = function() {
	var servers = this.session_management.session.servers;
	
	for(var i=0 ; i<servers.length ; ++i) {
		var server = servers[i];

		if(server.type != uovd.SERVER_TYPE_WEBAPPS) { continue; }

		var url = '/webapps/disconnect';

		jQuery.ajax({
			url : url,
			dataType: "xml",
			headers: {
				"x-ovd-service" : "disconnect",
				"x-ovd-param" : 'id=' + i + '&user=' + server.login + '&pass=' + server.password,
				"x-ovd-webappsserver" : server.webapps_url
			},
			success: jQuery.proxy(this.webappServerStatus, this)
		});
	}
}

uovd.provider.webapps.Direct.prototype.webappServerStatus = function(xml) {
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
}

