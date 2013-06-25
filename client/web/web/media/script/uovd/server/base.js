/* Base class */

uovd.server.Base = function(session, xml) {};

uovd.server.Base.prototype.initialize = function(session, xml) {
	this.session = session;
	this.xml = xml[0];
	this.type = xml.attr("type");
	this.login = xml.attr("login");
	this.password = xml.attr("password");
	this.applications = new Array();
	this.status = uovd.SERVER_STATUS_UNKNOWN;
};
