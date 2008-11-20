/*function clicMenu(oDiv)
{
	if (oDiv.isactive != 1)
	{
		_openMenuItem(oDiv);
	}
	else
	{
		_closeMenuItem(oDiv);
	}
}
function _openMenuItem(oDiv)
{
	$('applet').style.visibility="hidden";

	oDiv.isactive = 1;
	oDiv.className = "menuitem menuitem_active";
	setTimeout(function(){if (oDiv.isactive == 1){oDiv.className = "menuitem menuitem_active menuitem_ready"; }}, 400);

	//close others
	var aoMenuItems = document.getElementsByClassName("menuitem");
	for (var i=0; i<aoMenuItems.length; i++)
	{
		if (aoMenuItems[i] != oDiv) _closeMenuItem(aoMenuItems[i], 1);
	}
}
function _closeMenuItem(oDiv, dontshowapplet)
{
	oDiv.isactive = 0;
	oDiv.className = "menuitem";
	//setTimeout(function(){ if(oDiv.isActive != 1){ oDiv.className = "menuitem"; }}, 1000);
	if (!dontshowapplet) $('applet').style.visibility="visible";
}*/

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
