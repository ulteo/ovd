function switchSettings() {
	if ($('advanced_settings').visible()) {
		$('advanced_settings_status').innerHTML = '<img src="media/image/show.png" width="9" height="9" alt="" title="" />';
		$('advanced_settings').hide();
	} else {
		$('advanced_settings_status').innerHTML = '<img src="media/image/hide.png" width="9" height="9" alt="" title="" />';
		$('advanced_settings').show();
	}
}
