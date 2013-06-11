function showLogin() {
  jQuery('#loginBox').show();
  new Effect.Move(jQuery('#loginBox')[0], { x: 0, y: 1000 });

  if (debug) {
    Logger.del_instance();
    debug = false;
  }
}

function hideLogin() {
  new Effect.Move(jQuery('#loginBox')[0], { x: 0, y: -1000 });
  setTimeout(function() {
    jQuery('#loginBox').hide();
  }, 1000);
}

function disableLogin() {
  jQuery('#submitButton').hide();
  jQuery('#submitLoader').show();
}

function enableLogin() {
  jQuery('#submitButton').show();
  jQuery('#submitLoader').hide();
}

function showNews(title_, content_) {
	hideNews();
	hideInfo();
	hideError();
	hideOk();
	showLock();

	jQuery('#newsWrap_title').html(title_);
	refresh_body_size();
	var reg = new RegExp("\n", "g");
	jQuery('#newsWrap_content').html('<div style="width: 100%; height: '+parseInt(my_height*(75/100))+'px; overflow: auto;">'+content_.replace(reg, '<br />')+'</div>');

	new Effect.Center(jQuery('#newsWrap')[0]);
	new Effect.Appear(jQuery('#newsWrap')[0]);
}

function hideNews() {
	jQuery('#newsWrap').hide();

	hideLock();

	jQuery('#newsWrap_title').html('');
	jQuery('#newsWrap_content').html('');
	jQuery('#newsWrap').width('750px');
	jQuery('#newsWrap').height('');
}

function showIFrame(url_) {
	showLock();

	jQuery('#iframeContainer').prop('src', url_);

	new Effect.Appear(jQuery('#iframeWrap')[0]);
}

function hideIFrame() {
	jQuery('#iframeWrap').hide();

	jQuery('#iframeContainer').prop('src', 'about:blank');

	hideLock();
}

function showMainContainer() {
	jQuery('#sessionContainer').show();
}

function hideMainContainer() {
	jQuery('#sessionContainer').hide();
}

function pullMainContainer(mode) {
	new Effect.Move(jQuery('#sessionContainer')[0], { x: 0, y: my_height });
}

function pushMainContainer(mode) {
	new Effect.Move(jQuery('#sessionContainer')[0], { x: 0, y: -my_height, mode: 'absolute' });
}

function showInternalError() {
	try {
		showError(i18n.get('internal_error'));
	} catch(e) {}

	enableLogin();
}

function generateEnd() {
	$('endContent').innerHTML = '';

	var buf = jQuery(document.createElement('span'))
	buf.css({'font-size' : '1.1em', 'font-weight' : 'bold', 'color' : '#686868'});

	var end_message = jQuery(document.createElement('span'))
	end_message.prop('id', 'endMessage');

	buf.append(end_message);

	/* if(error) {
			[...]
			buf.appendChild(error_message_node);
		}
	*/

	if(jQuery('#loginBox')) {
		var close_container = jQuery(document.createElement('div'));
		close_container.css('margin-top', '10px');

		var close_text = jQuery(document.createElement('span'));
		close_text.html(i18n.get('start_another_session'));

		close_container.append(close_text);
		buf.append(close_container);
	}

	jQuery('#endContent').append(buf);

	if(jQuery('#endMessage')) {
		/*
		if(error)
			jQuery('#endMessage').innerHTML = '<span class="msg_error">'+i18n.get('session_end_unexpected')+'</span>';
		else
		*/
			jQuery('#endMessage').html(i18n.get('session_end_ok'));
	}
}

function configureUI(mode) {
	var session_settings = session_management.session.settings;
	if(mode == "applications") {
		/* Configure page layout */
		(function() {
			/* Set page size */
			var header_height = jQuery('#applicationsHeaderWrap').height();
			var height = parseInt(my_height)-parseInt(header_height);
			jQuery('#appsContainer').height(height-30);
			jQuery('#fileManagerContainer').height(height-30);

			/* Hide desktops */
			/* do not use .hide() or applet wil not load */
			jQuery('#desktopContainer').width(1).height(1).css("overflow", "hidden");

			/* Show applications mode components */
			jQuery("#applicationsHeaderWrap").show();
			jQuery("#applicationsContainer").show();
			jQuery("#windowsContainer").show();
		})();

		/* Suport suspend ? */
		(function() {
			if(session_settings["persistent"]) {
				jQuery('#suspend_button').show();
			}
		})();
	} else {
		/* Configure page layout */
		(function() {
			/* Show desktop */
			jQuery('#desktopContainer').width(1).height(1).css("overflow", "visible");

			/* Hide applications mode components */
			jQuery("#applicationsHeaderWrap").hide();
			jQuery("#applicationsContainer").hide();
			jQuery("#windowsContainer").hide();
		})();
	}
}
