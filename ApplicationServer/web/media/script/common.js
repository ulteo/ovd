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

function logout() {
	new Ajax.Request(
		'../exit.php',
		{
			method: 'get'
		}
	);
}

Event.observe(window, 'load', function() {
	Effect.Center($('splashContainer'));
	Effect.Center($('endContainer'));
	$('endContainer').style.top = parseInt($('endContainer').style.top)-50+'px';

	$('appletContainer').hide();
	$('splashContainer').show();
});
