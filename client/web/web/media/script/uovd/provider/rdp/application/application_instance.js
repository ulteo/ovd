
/* ApplicationInstance class */
uovd.provider.rdp.application.ApplicationInstance = function(applicationProvider, id, instance) {
	this.applicationProvider = applicationProvider;
	this.id = id;
	this.instance = instance;
	this.status = "unknown";

	this.create = (new Date()).getTime();
	this.start = 0;
	this.end = 0;
}
