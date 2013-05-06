function SeamlessDraggingBar(node) {
	this.node = jQuery(node);
	this.content = jQuery(document.createElement("div"));
	this.header = jQuery(document.createElement("div"));
	this.content.append(this.header);
	this.content.append(this.node);

	this.lastX = 0;
	this.lastY = 0;

	this.header.css("background-color", "grey");
	this.header.css("height", "40px");
	this.header.css("width", "400px");
	this.header.css("float", "left");
	this.header.css("position", "absolute");
	this.header.css("opacity", "0.5");

	var self = this; /* closure */
	var startingDragAndDrop = function(e) {
		self.lastX = e.pageX;
		self.lastY = e.pageY;

		self.header.mousemove(drag);
		self.header.mouseup(drop);
		self.header.mouseout(drop);
	}

	var drag = function(e) {
		var dx = parseInt(e.pageX) - parseInt(self.lastX);
		var dy = parseInt(e.pageY) - parseInt(self.lastY);
		var nx = parseInt(self.content[0].seamless_window.x) + parseInt(dx); /* seamless_window as a div attribure :/ must fix it */
		var ny = parseInt(self.content[0].seamless_window.y) + parseInt(dy);
		self.lastX = e.pageX;
		self.lastY = e.pageY;

		self.content[0].seamless_window.properties({"position" : [nx, ny]});
	}

	var drop = function(e) {
		self.header.off("mousemove");
		self.header.off("onmouseup");
		self.header.off("mouseout");
	}
		
	this.header.mousedown(startingDragAndDrop);
}

SeamlessDraggingBar.prototype.getNode = function() {
	return this.content[0];
}
