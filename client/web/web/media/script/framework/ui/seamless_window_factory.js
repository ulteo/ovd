function SeamlessWindowFactory() {

	/* Construct a new SeamlessWindow instance
			Mandatory :
				params["id"] : window id
				params["content"] : content node
  */
	this.SeamlessWindow = function(params) {
		this.id = params["id"];
		this.x = 0;
		this.y = 0;
		this.w = 0;
		this.h = 0;
		this.visible = true;
		this.update_callback = function(win, params){};
		this.content = jQuery(params["content"]).prop("seamless_window", this);

		this.node = jQuery(document.createElement("div"));
		this.node.prop("id", "w_"+this.id);
		this.node.css("position", "absolute");
		this.node.css("left", this.x+"px");
		this.node.css("top", this.y+"px");
		this.node.css("width", this.w+"px");
		this.node.css("height", this.h+"px");
		this.node.css("display", (this.visible == true)?"block":"none");
		this.node.append(this.content);

		this.properties(params);
	}

	/* Destroy a SeamlessWindow instance
			params is not used in this implementation
	*/
	this.SeamlessWindow.prototype.destroy = function(params) {
		this.node.css("display","none");
		this.node.empty();
		this.content = null;
	}

	/* Construct a new SeamlessWindow instance
			Optional :
				params["position"] : window position
				params["size"] : window size
				params["visible"] : window visibility
				params["update"] : set the update callback
  */
	this.SeamlessWindow.prototype.properties = function(params) {
		if(params["position"] != undefined) {
			this.x = params["position"][0];
			this.y = params["position"][1];
		}
		if(params["size"] != undefined) {
			this.w = params["size"][0];
			this.h = params["size"][1];
		}
		if(params["visible"] != undefined) {
			this.visible = params["visible"];
		}

		this.node.css("position", "absolute");
		this.node.css("left", this.x+"px");
		this.node.css("top", this.y+"px");
		this.node.css("width", this.w+"px");
		this.node.css("height", this.h+"px");
		this.node.css("display", (this.visible == true)?"block":"none");

		if(params["update"]) {
			this.update_callback = params["update"];
		}
	}

	/* Trigger an update */
	this.SeamlessWindow.prototype.update = function(params) {
		this.update_callback(this, params);
	}

	this.SeamlessWindow.prototype.getNode = function() {
		return this.node[0];
	}

	this.SeamlessWindow.prototype.getContent = function() {
		return this.content[0];
	}
}

SeamlessWindowFactory.prototype.create = function(params) {
	return new (this.SeamlessWindow)(params);
}
