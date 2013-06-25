
/* ApplicationInstance class */
uovd.provider.applications.ApplicationInstance = function(applicationsProvider, id, instance) {
	this.applicationsProvider = applicationsProvider;
	this.id = id;
	this.instance = instance;
	this.status = "unknown";

	this.create = (new Date()).getTime();
	this.start = 0;
	this.end = 0;
}
