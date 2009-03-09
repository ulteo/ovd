function clicMenu(oDiv) {
	if ($(oDiv).visible() != 1)
		_openMenuItem(oDiv);
	else
		_closeMenuItem(oDiv);
}

function _openMenuItem(oDiv) {
	$('appletContainer').style.visibility = 'hidden';
	$(oDiv).show();
}

function _closeMenuItem(oDiv) {
	$(oDiv).hide();
	$('appletContainer').style.visibility = 'visible';
}

Event.observe(window, 'load', function() {
	Effect.Center($('splashContainer'));
	Effect.Center($('endContainer'));

	$('appletContainer').hide();
	$('splashContainer').show();
});
