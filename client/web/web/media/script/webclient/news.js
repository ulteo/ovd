News = function(session_management, node) {
	this.node = jQuery(node);
	this.session_management = session_management;
	this.news_interval = null;
	this.tbody_node = null;
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

			/* Insert table */
			this.tbody_node = jQuery(document.createElement("tbody"));
			var table_node = jQuery(document.createElement("table"));
			table_node.css({'width': '100%', 'margin-left': 'auto', 'margin-right': 'auto'});
			table_node.prop('border', '0');
			table_node.prop('cellspacing', '0');
			table_node.prop('cellpadding', '0');
			table_node.append(this.tbody_node);

			this.node.append(table_node);

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
		span_title_node.css('font-weight', 'bold');
		span_title_node.html('● '+title);

		var a_node = jQuery(document.createElement("a"));
		a_node.on('click', function() {
			showNews(title+" <em>("+date.toLocaleDateString()+")</em>", message);
			showLock();
		});
		a_node.prop('href', 'javascript:;');
		a_node.append(span_title_node);

		var span_date_node = jQuery(document.createElement("span"));
		span_date_node.css({'font-size': '1.1em', 'color': 'black'});
		span_date_node.html('<em>'+date.toLocaleDateString()+'</em>');

		var td_lien = jQuery(document.createElement("td"));
		td_lien.css('text-align', 'left');
		td_lien.append(a_node);

		var td_date = jQuery(document.createElement("td"));
		td_date.css('text-align', 'left');
		td_date.append(span_date_node);

		var tr_node = jQuery(document.createElement("tr"));
		tr_node.prop("id", "news_"+id);
		tr_node.append(td_lien);
		tr_node.append(td_date);

		self.tbody_node.append(tr_node);
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

