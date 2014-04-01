News = function(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.news_interval = null;
	this.ul_node = null;
	this.handler = jQuery.proxy(this.handleEvents, this);

	/* Do NOT remove ovd.session.started in destructor as it is used as a delayed initializer */
	this.session_management.addCallback("ovd.session.started",    this.handler);
};

News.prototype.handleEvents = function(type, source, params) {
	if(type == "ovd.session.started") {
		var session_mode = this.session_management.session.mode;

		if(session_mode == uovd.SESSION_MODE_APPLICATIONS) {
			/* register events listeners */
			this.session_management.addCallback("ovd.session.destroying", this.handler);

			/* Insert news list */
			this.ul_node = jQuery(document.createElement("ul"));
			this.node.append(this.ul_node);

			/* Set polling interval for news.php */
			this._check_news();
			this.news_interval = setInterval(jQuery.proxy(this._check_news, this), 600000); /* Poll every 10min */
		}
	}

	if(type == "ovd.session.destroying" ) { /* Clean context */
		this.end();
	}
};

News.prototype._check_news = function() {
	jQuery.ajax({
		url: "news.php?differentiator="+Math.floor(Math.random()*50000),
		type: "GET",
		contentType: "text/xml",
		success: jQuery.proxy(this._parse_news, this)
	});
};

News.prototype._parse_news = function(xml) {
	var self = this; /* closure */
	jQuery(xml).find("new").each( function() {
		var node = jQuery(this);
		var id = node.attr("id");
		var title = node.attr("title");
		var date = new Date(parseInt(node.attr("timestamp"))*1000);
		var message = node.text();

		if(jQuery("#news_"+id)[0]) {
			/* already listed : returning */
			return;
		}

		var span_title_node = jQuery(document.createElement("span"));
		span_title_node.addClass('title');
		span_title_node.html(title);

		var span_date_node = jQuery(document.createElement("span"));
		span_date_node.addClass('date');
		span_date_node.html(date.toLocaleDateString());

		var li_node = jQuery(document.createElement("li"));
		li_node.on('click', function() {
			showNews(title+" <em>("+date.toLocaleDateString()+")</em>", message);
			showLock();
		});
		li_node.append(span_date_node);
		li_node.append(span_title_node);

		self.ul_node.append(li_node);
	});
};

News.prototype.end = function() {
	if(this.session_management.session.mode == uovd.SESSION_MODE_APPLICATIONS) {
		/* Do NOT remove ovd.session.started as it is used as a delayed initializer */
		this.session_management.removeCallback("ovd.session.destroying", this.handler);

		this.node.empty();

		if(this.news_interval != null) {
			clearInterval(this.news_interval);
			this.news_interval = null;
		}
	}
};

