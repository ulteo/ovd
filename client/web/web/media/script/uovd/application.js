
uovd.Application = function(server, xml) {
	var self = this; /* closure */
	this.server = server;
	this.xml = xml[0];
	this.id = xml.attr("id");
	this.name = xml.attr("name");
	this.mime = new Array();

	xml.find("mime").each( function() {
		self.mime.push(jQuery(this).attr("type"))
	});
}
